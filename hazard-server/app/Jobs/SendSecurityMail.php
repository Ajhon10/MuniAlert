<?php

namespace App\Jobs;

use App\Mail\AccountSecurityLink;
use App\Models\User;
use App\Services\AccountMfa;
use App\Services\AccountRecovery;
use App\Services\SecurityEvents;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendSecurityMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 25;

    public int $backoff = 60;

    // Queue payloads hold only an opaque row ID. The delivery proof is encrypted
    // in the private challenge table, never serialized into ordinary job logs.
    public function __construct(public string $challengeId) {}

    public function handle(AccountRecovery $recovery, AccountMfa $mfa): void
    {
        $challenge = DB::table('security_challenges')->where('id', $this->challengeId)->first();
        if (! $challenge || $challenge->consumed || ! $challenge->payload || Carbon::parse($challenge->expires_at)->lte(now())) {
            return;
        }
        $user = User::find($challenge->user_id);
        if (! $user || ! $user->is_active || ! hash_equals($challenge->fingerprint, $mfa->fingerprint($user))) {
            return;
        }
        if (! $recovery->available()) {
            throw new \RuntimeException('Security email delivery is not configured.');
        }
        $url = rtrim(config('security.frontend_url'), '/').'/#'.$challenge->purpose.'='.rawurlencode($challenge->id.'.'.Crypt::decryptString($challenge->payload));
        try {
            Mail::to($user->email)->send(new AccountSecurityLink($url, $challenge->purpose));
        } catch (\Throwable) {
            // Provider exceptions may include a token or message body.
            app(SecurityEvents::class)->record('security_mail_failed', $user);
            throw new \RuntimeException('Security email delivery failed; the job may retry.');
        }
        DB::table('security_challenges')->where('id', $challenge->id)->update(['payload' => null]);
    }
}
