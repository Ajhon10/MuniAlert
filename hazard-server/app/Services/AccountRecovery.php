<?php

namespace App\Services;

use App\Jobs\SendSecurityMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountRecovery
{
    public function __construct(private AccountMfa $mfa, private SecuritySessions $sessions, private SecurityEvents $events) {}

    public function available(): bool
    {
        $origin = config('security.frontend_url');
        $parts = is_string($origin) ? parse_url($origin) : false;

        return config('security.recovery_enabled') && is_string($origin) && filter_var($origin, FILTER_VALIDATE_URL)
            && $parts && ! empty($parts['host']) && ! str_contains($parts['host'], '*') && ! isset($parts['user']) && ! isset($parts['pass'])
            && ! isset($parts['query']) && ! isset($parts['fragment']) && in_array($parts['path'] ?? '', ['', '/'], true)
            && (($parts['scheme'] ?? '') === 'https' || (app()->environment('local', 'testing') && in_array($parts['host'] ?? '', ['localhost', '127.0.0.1'], true)))
            && app(AccountMailDelivery::class)->available();
    }

    public function request(string $email, string $purpose, ?Request $request = null): void
    {
        if (! $this->available()) {
            return;
        }
        // The user lock serializes issuance/reset/email changes and throttles a
        // recipient independently of shared IPs. No unknown-account distinction.
        DB::transaction(function () use ($email, $purpose, $request) {
            $user = User::where('email', strtolower(trim($email)))->lockForUpdate()->first();
            if (! $user || ! $user->is_active || ! app(AccountMailDelivery::class)->recipientAllowed($user->email) || ($purpose === 'reset' && (! $user->has_password || ! $user->local_email_verified_at))) {
                return null;
            }
            if (DB::table('security_challenges')->where('user_id', $user->id)->where('purpose', $purpose)->where('created_at', '>', now()->subMinute())->exists()) {
                return null;
            }
            DB::table('security_challenges')->where('user_id', $user->id)->where('purpose', $purpose)->update(['consumed' => true, 'payload' => null]);
            $proof = Str::random(64);
            $id = (string) Str::uuid();
            DB::table('security_challenges')->insert([
                'id' => $id, 'user_id' => $user->id, 'token_hash' => hash('sha256', $proof), 'purpose' => $purpose,
                'security_version' => $user->security_version, 'fingerprint' => $this->mfa->fingerprint($user),
                'role' => $user->role, 'platform' => 'mail', 'expires_at' => now()->addMinutes(30), 'created_at' => now(), 'updated_at' => now(),
                'payload' => Crypt::encryptString($proof),
            ]);
            $this->events->record($purpose === 'reset' ? 'recovery_requested' : 'email_verification_requested', $user, $request);
            SendSecurityMail::dispatch($id)->onConnection('security-mail')->onQueue('security-mail');

            return null;
        });
    }

    public function consume(string $token, string $purpose, ?string $password, Request $request): void
    {
        if (! $this->available()) {
            throw ValidationException::withMessages(['token' => 'Account recovery is unavailable. Contact approved municipal support.']);
        }
        [$id, $proof] = explode('.', $token);
        $found = DB::table('security_challenges')->where('id', $id)->first();
        $valid = $found ? DB::transaction(function () use ($found, $id, $proof, $purpose, $password, $request) {
            $user = User::whereKey($found->user_id)->lockForUpdate()->first();
            $challenge = DB::table('security_challenges')->where('id', $id)->lockForUpdate()->first();
            if (! $user || ! $user->is_active || ! $challenge || $challenge->purpose !== $purpose || $challenge->platform !== 'mail'
                || $challenge->consumed || Carbon::parse($challenge->expires_at)->lte(now())
                || ! hash_equals($challenge->token_hash, hash('sha256', $proof)) || ! hash_equals($challenge->fingerprint, $this->mfa->fingerprint($user))) {
                return false;
            }
            DB::table('security_challenges')->where('id', $id)->update(['consumed' => true]);
            if ($purpose === 'verify') {
                $user->forceFill(['local_email_verified_at' => now()])->save();
                $this->events->record('email_verified', $user, $request);
            } else {
                if (! $user->has_password || ! $user->local_email_verified_at) {
                    return false;
                }
                $user->password = $password;
                $this->sessions->invalidate($user);
                $this->events->record('password_recovered', $user, $request);
            }

            return true;
        }) : false;
        if (! $valid) {
            throw ValidationException::withMessages(['token' => 'This link is invalid or expired. Request a new link.']);
        }
    }
}
