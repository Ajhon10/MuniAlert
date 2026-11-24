<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountOnboarding;
use App\Services\SecurityEvents;
use App\Services\SecuritySessions;
use App\Support\PasswordInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAccountController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
        app(SecuritySessions::class)->requireRecent($request);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return User::where('role', 'admin')->orderBy('id')->paginate(20)->through(fn ($user) => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'is_active' => $user->is_active, 'mfa_enabled' => $user->mfa_enabled,
            'identity_verified' => app(AccountOnboarding::class)->state($user)['identity_verified'],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate(['name' => 'required|string|min:2|max:80',
            'email' => 'required|string|email|max:255|unique:users', 'password' => ['required', ...PasswordInput::rules(true)]]);
        DB::transaction(function () use ($data, $request) {
            $user = new User($data);
            $user->role = 'admin';
            $user->mfa_enrollment_until = now()->addMinutes(15);
            $user->save();
            app(SecurityEvents::class)->record('administrator_created', $user, $request);
            DB::table('admin_account_actions')->insert(['actor_id' => $request->user()->id, 'target_id' => $user->id, 'action' => 'created', 'created_at' => now()]);
        });

        return response()->json(['message' => 'Administrator created. Complete MFA enrollment within 15 minutes, then verify email and accept the policies.'], 201);
    }

    public function enrollment(Request $request, int $administrator)
    {
        $this->authorizeAdmin($request);
        DB::transaction(function () use ($request, $administrator) {
            $user = User::where('role', 'admin')->whereKey($administrator)->lockForUpdate()->firstOrFail();
            abort_unless($user->is_active && ! $user->mfa_enabled, 409, 'Only an active administrator awaiting MFA setup can receive a new enrollment window.');
            $user->forceFill(['mfa_enrollment_until' => now()->addMinutes(15)])->save();
            app(SecurityEvents::class)->record('administrator_enrollment_authorized', $user, $request);
            DB::table('admin_account_actions')->insert(['actor_id' => $request->user()->id, 'target_id' => $user->id, 'action' => 'enrollment_authorized', 'created_at' => now()]);
        });

        return ['message' => 'A new 15-minute MFA enrollment window is available.'];
    }

    public function destroy(Request $request, int $administrator)
    {
        $this->authorizeAdmin($request);
        DB::transaction(function () use ($request, $administrator) {
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = $admins->firstWhere('id', $administrator);
            abort_unless($user, 404);
            abort_if($user->id === $request->user()->id, 409, 'You cannot delete the administrator account you are using. Sign in as the replacement administrator first.');
            abort_if($user->is_active && $admins->where('is_active', true)->count() < 2, 409, 'The last active administrator cannot be deleted.');
            app(AccountOnboarding::class)->invalidateIdentity($user);
            app(SecuritySessions::class)->invalidate($user);
            DB::table('device_tokens')->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill(['is_active' => false, 'mfa_enrollment_until' => null])->save();
            app(SecurityEvents::class)->record('administrator_deleted', $user, $request);
            DB::table('admin_account_actions')->insert(['actor_id' => $request->user()->id, 'target_id' => $user->id, 'action' => 'deleted', 'created_at' => now()]);
            // Preserve foreign-key references in municipal history/audit records.
            $user->delete();
        });

        return ['message' => 'Administrator deleted and sign-in access revoked. Municipal history and the removal audit record are retained.'];
    }
}
