<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AccountOnboarding
{
    public const DOCUMENTS = ['philsys' => 'Philippine National ID / ePhilID', 'passport' => 'Philippine passport', 'drivers_license' => 'Philippine driver’s license', 'umid' => 'UMID', 'prc' => 'PRC professional ID', 'postal' => 'Philippine Postal ID'];

    public function configured(): bool
    {
        return filled(config('onboarding.operator')) && filter_var(config('onboarding.contact'), FILTER_VALIDATE_EMAIL)
            && app(AccountMailDelivery::class)->recipientAllowed(config('onboarding.contact'));
    }

    public function state(User $user): array
    {
        $user->refresh();
        $identity = DB::table('identity_verifications')->where('user_id', $user->id)->first();
        $status = $identity?->status ?? 'not_submitted';
        if ($identity && $identity->applicant_name !== $user->name) {
            $status = 'name_changed';
        } elseif ($status === 'pending' && now()->gte($identity->expires_at)) {
            $status = 'expired';
        }
        $accepted = DB::table('policy_acceptances')->where('user_id', $user->id)->where('version', config('onboarding.policy_version'))->exists();
        $ready = $user->is_active && $user->local_email_verified_at !== null && $accepted;
        $verified = $ready && $status === 'approved';

        return ['email_verified' => $user->local_email_verified_at !== null, 'policies_accepted' => $accepted,
            'policy_version' => config('onboarding.policy_version'), 'identity_status' => $status,
            'identity_reason' => $identity?->reason, 'identity_id' => $identity?->id,
            'identity_available' => $this->configured(), 'mail_available' => app(AccountRecovery::class)->available(),
            'identity_verified' => $verified, 'account_verified' => $verified, 'account_ready' => $ready,
            'can_administer' => $user->role === 'admin' && $ready,
            'can_report' => $user->role === 'citizen' && $ready];
    }

    public function serialize(User $user): array
    {
        $state = $this->state($user);

        return [...$user->toArray(), 'account_verified' => $state['identity_verified'],
            'identity_verified' => $state['identity_verified'], 'account_ready' => $state['account_ready']];
    }

    public function accept(User $user): void
    {
        DB::table('policy_acceptances')->insertOrIgnore(['user_id' => $user->id, 'version' => config('onboarding.policy_version'), 'accepted_at' => now()]);
    }

    public function requireReporter(User $user): void
    {
        abort_unless($this->state($user)['can_report'], response()->json(['message' => 'Before reporting or confirming a hazard, verify your email and accept the current policies in Settings. Government-ID verification is optional.', 'verification_required' => true], 403));
    }

    /** Called under the profile's user-row lock; a previous name cannot restore approval. */
    public function invalidateIdentity(User $user): void
    {
        $row = DB::table('identity_verifications')->where('user_id', $user->id)->lockForUpdate()->first();
        if ($row) {
            if ($row->path) {
                abort_unless(Storage::disk('identity')->delete($row->path), 503, 'Identity storage is unavailable.');
            }
            DB::table('identity_verifications')->where('id', $row->id)->update(['path' => null, 'status' => 'name_changed', 'updated_at' => now()]);
        }
    }
}
