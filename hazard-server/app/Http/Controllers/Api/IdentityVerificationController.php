<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountOnboarding;
use App\Services\IdentityReviews;
use App\Services\PhotoStorage;
use App\Services\SecurityEvents;
use App\Services\SecuritySessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IdentityVerificationController extends Controller
{
    public function status(Request $request, AccountOnboarding $onboarding): array
    {
        return [...$onboarding->state($request->user()), 'document_types' => AccountOnboarding::DOCUMENTS];
    }

    public function accept(Request $request, AccountOnboarding $onboarding): array
    {
        $request->validate(['accepted' => 'required|accepted', 'version' => ['required', Rule::in([config('onboarding.policy_version')])]]);
        $onboarding->accept($request->user());

        return $this->status($request, $onboarding);
    }

    public function store(Request $request, AccountOnboarding $onboarding, PhotoStorage $photos): array
    {
        abort_unless(in_array($request->user()->role, ['citizen', 'admin'], true), 403);
        abort_unless($onboarding->configured(), 503, 'Identity verification is waiting for the operator’s privacy contact configuration.');
        $state = $onboarding->state($request->user());
        abort_unless($state['email_verified'] && $state['policies_accepted'], 403, 'Verify your email and accept the current policies before uploading an ID.');
        $data = $request->validate(['document_type' => ['required', Rule::in(array_keys(AccountOnboarding::DOCUMENTS))],
            'consent' => 'required|accepted', 'version' => ['required', Rule::in([config('onboarding.policy_version')])],
            'image' => 'required|file|mimes:jpeg,png,webp|max:1024|dimensions:max_width=1920,max_height=1920']);
        try {
            $bytes = $photos->prepare($request->file('image'), false);
        } catch (ValidationException $error) {
            throw ValidationException::withMessages(['image' => collect($error->errors())->flatten()->all()]);
        }
        $path = Str::uuid().'.enc';
        $disk = Storage::disk('identity');
        abort_unless($disk->put($path, Crypt::encryptString($bytes), 'private'), 503, 'Identity storage is unavailable.');
        try {
            DB::transaction(function () use ($request, $data, $path) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                abort_unless($user->local_email_verified_at && app(AccountOnboarding::class)->state($user)['policies_accepted'], 403);
                $previous = DB::table('identity_verifications')->where('user_id', $user->id)->lockForUpdate()->first();
                abort_if($previous && in_array($previous->status, ['pending', 'approved']) && $previous->applicant_name === $user->name, 409, 'An identity submission is already pending or approved. Withdraw it before replacing it.');
                if ($previous?->path) {
                    abort_unless(Storage::disk('identity')->delete($previous->path), 503, 'Identity storage is unavailable.');
                }
                DB::table('identity_verifications')->updateOrInsert(['user_id' => $user->id], [
                    'id' => (string) Str::uuid(), 'applicant_name' => $user->name, 'document_type' => $data['document_type'], 'path' => $path,
                    'status' => 'pending', 'consent_version' => $data['version'], 'consented_at' => now(), 'expires_at' => now()->addDays(7),
                    'reviewed_by' => null, 'review_method' => null, 'reviewed_at' => null, 'reason' => null, 'created_at' => now(), 'updated_at' => now(),
                ]);
                app(SecurityEvents::class)->record('identity_submitted', $user, $request);
            }, 3);
        } catch (\Throwable $error) {
            $disk->delete($path);
            throw $error;
        }

        return $this->status($request, $onboarding);
    }

    public function withdraw(Request $request, AccountOnboarding $onboarding): array
    {
        DB::transaction(function () use ($request) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $row = DB::table('identity_verifications')->where('user_id', $request->user()->id)->lockForUpdate()->first();
            if ($row) {
                if ($row->path) {
                    abort_unless(Storage::disk('identity')->delete($row->path), 503, 'Identity storage is unavailable.');
                }
                DB::table('identity_verifications')->where('id', $row->id)->update(['path' => null, 'status' => 'withdrawn', 'updated_at' => now()]);
                app(SecurityEvents::class)->record('identity_withdrawn', $request->user(), $request);
            }
        });

        return $this->status($request, $onboarding);
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        return DB::table('identity_verifications')->select('id', 'applicant_name', 'document_type', 'created_at', 'expires_at')
            ->where('status', 'pending')->where('expires_at', '>', now())->orderBy('created_at')->orderBy('id')->paginate(20);
    }

    public function image(Request $request, string $identity)
    {
        abort_unless($request->user()->role === 'admin', 403);
        app(SecuritySessions::class)->requireRecent($request);
        $row = DB::table('identity_verifications')->where('id', $identity)->where('status', 'pending')->where('expires_at', '>', now())->first();
        abort_unless($row && $row->path && Storage::disk('identity')->exists($row->path), 404);
        app(SecurityEvents::class)->record('identity_image_viewed', $request->user(), $request);

        return response(Crypt::decryptString(Storage::disk('identity')->get($row->path)))
            ->header('Content-Type', 'image/webp')->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
    }

    public function review(Request $request, string $identity): array
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'checked' => 'required|accepted',
            'reason' => ['nullable', 'required_if:decision,rejected', Rule::in(['unreadable', 'name_mismatch', 'unsupported_document', 'cannot_verify'])]]);
        app(IdentityReviews::class)->decide($identity, $data['decision'], $data['reason'] ?? null, $request->user(), $request);

        return ['message' => 'Identity decision saved. The ID image has been deleted.'];
    }
}
