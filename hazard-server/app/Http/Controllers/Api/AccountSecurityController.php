<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountMailDelivery;
use App\Services\AccountMfa;
use App\Services\AccountRecovery;
use App\Services\SecurityEvents;
use App\Services\SecuritySessions;
use App\Support\PasswordInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountSecurityController extends Controller
{
    public function status(Request $request): array
    {
        $user = $request->user();

        return ['mfa_enabled' => $user->mfa_enabled, 'mfa_required' => $user->role === 'admin',
            'recovery_codes_remaining' => count($user->mfa_recovery_codes ?? []),
            'email_verified' => $user->local_email_verified_at !== null,
            'recovery_available' => app(AccountRecovery::class)->available(),
            'email_eligible' => app(AccountMailDelivery::class)->recipientAllowed($user->email),
            'email_report_updates' => (bool) $user->email_report_updates];
    }

    public function verify(Request $request, AccountMfa $mfa)
    {
        return response()->json($mfa->complete($request));
    }

    public function pending(Request $request): array
    {
        $pending = $request->session()->pull('security_pending');
        abort_unless(is_array($pending), 422, 'Start sign-in again.');

        return $pending;
    }

    public function reauthenticate(Request $request, AccountMfa $mfa, SecuritySessions $sessions): array
    {
        $data = $request->validate(['password' => ['nullable', ...PasswordInput::rules()], 'code' => 'nullable|string|max:32']);
        $valid = DB::transaction(function () use ($request, $data, $mfa, $sessions) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (! $sessions->valid($request, $user) || (! $user->has_password && ! $user->mfa_enabled)
                || ($user->has_password && ! Hash::check($data['password'] ?? '', $user->password))
                || ($user->mfa_enabled && ! $mfa->verify($user, $data['code'] ?? ''))) {
                app(SecurityEvents::class)->record('reauthentication_failed', $user, $request);

                return false;
            }
            $sessions->stampRecent($request);
            app(SecurityEvents::class)->record('reauthentication_succeeded', $user, $request);

            return true;
        });
        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'Verification failed. Check your password and use a fresh authenticator code.']);
        }

        return ['message' => 'Identity verified for five minutes.'];
    }

    public function recoveryCodes(Request $request, AccountMfa $mfa, SecuritySessions $sessions): array
    {
        $sessions->requireRecent($request);

        return DB::transaction(function () use ($request, $mfa, $sessions) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($sessions->valid($request, $user) && $user->mfa_enabled, 403);
            $codes = $mfa->recoveryCodes($user);
            $sessions->invalidate($user, $request);
            app(SecurityEvents::class)->record('mfa_recovery_regenerated', $user, $request);

            return ['recovery_codes' => $codes];
        });
    }
}
