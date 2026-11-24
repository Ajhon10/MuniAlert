<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdentityReviews
{
    public function decide(string $identity, string $decision, ?string $reason, ?User $reviewer, ?Request $request = null): void
    {
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        $found = DB::table('identity_verifications')->where('id', $identity)->first();
        abort_unless($found, 404);
        DB::transaction(function () use ($identity, $decision, $reason, $reviewer, $request, $found) {
            $users = User::whereIn('id', array_filter([$found->user_id, $reviewer?->id]))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $user = $users->get($found->user_id);
            abort_unless($user && $user->is_active, 409, 'This account is no longer available for review.');
            if ($reviewer) {
                $actor = $users->get($reviewer->id);
                abort_unless($actor && $actor->role === 'admin' && app(AccountOnboarding::class)->state($actor)['can_administer'], 403);
                abort_if($user->id === $reviewer->id, 403, 'You cannot review your own identity.');
            } else {
                // Only the interactive server-operator command calls this branch.
                abort_unless($user->role === 'admin', 403);
            }
            $row = DB::table('identity_verifications')->where('id', $identity)->lockForUpdate()->first();
            abort_unless($row && $row->status === 'pending' && now()->lt($row->expires_at) && $row->applicant_name === $user->name, 409, 'This submission changed or expired. Refresh the review queue.');
            $state = app(AccountOnboarding::class)->state($user);
            abort_unless($state['email_verified'] && $state['policies_accepted'], 409, 'The account must verify its email and accept the current policies first.');
            abort_unless($row->path && Storage::disk('identity')->exists($row->path), 409, 'The ID image is unavailable. Ask the applicant to withdraw and resubmit.');
            abort_unless(Storage::disk('identity')->delete($row->path), 503, 'Identity storage is unavailable.');
            DB::table('identity_verifications')->where('id', $identity)->update(['status' => $decision, 'path' => null,
                'reason' => $decision === 'rejected' ? $reason : null, 'reviewed_by' => $reviewer?->id,
                'review_method' => $reviewer ? 'administrator' : 'operator_in_person', 'reviewed_at' => now(), 'updated_at' => now()]);
            app(SecurityEvents::class)->record($reviewer ? 'identity_'.$decision : 'operator_admin_identity_'.$decision, $user, $request);
        }, 3);
    }
}
