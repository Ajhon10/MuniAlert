<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AccountMfa
{
    public function __construct(private Google2FA $totp, private SecuritySessions $sessions, private SecurityEvents $events) {}

    public function fingerprint(User $user): string
    {
        return hash('sha256', $user->password.'|'.$user->email.'|'.$user->role.'|'.$user->security_version);
    }

    public function login(Request $request, User $user, ?bool $native = null): array
    {
        abort_unless($user->is_active, 422, 'Email or password is incorrect.');
        $native ??= $request->is('api/auth/*');
        if ($user->role !== 'admin' && ! $user->mfa_enabled) {
            return $this->sessions->issue($request, $user, false, $native);
        }
        abort_if(! $user->mfa_enabled && ! $this->enrollmentAuthorized($user), 422, 'Administrator setup needs operator approval. Ask municipal support to authorize a new enrollment window.');

        return $this->challenge($request, $user, $user->mfa_enabled ? 'login' : 'enroll', $native);
    }

    public function challenge(Request $request, User $user, string $purpose, bool $native): array
    {
        $secret = $purpose === 'enroll' ? $this->totp->generateSecretKey(32) : null;
        $proof = Str::random(64);
        $id = (string) Str::uuid();
        if (! $native && ! $request->session()->has('mfa_binding')) {
            $request->session()->put('mfa_binding', Str::random(64));
        }
        DB::table('security_challenges')->insert([
            'id' => $id, 'user_id' => $user->id, 'purpose' => $purpose,
            'token_hash' => hash('sha256', $proof), 'security_version' => $user->security_version,
            'fingerprint' => $this->fingerprint($user), 'role' => $user->role,
            'platform' => $native ? 'native' : 'web',
            'binding' => $native ? null : hash('sha256', $request->session()->get('mfa_binding')),
            'payload' => $secret ? Crypt::encryptString($secret) : null,
            'expires_at' => now()->addMinutes(config('security.challenge_minutes')), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->events->record('mfa_challenge_created', $user, $request);

        return ['mfa_required' => true, 'enrollment_required' => $purpose === 'enroll', 'challenge_token' => $id.'.'.$proof,
            'expires_in' => config('security.challenge_minutes') * 60,
            ...($secret ? ['secret' => $secret, 'otpauth_uri' => $this->totp->getQRCodeUrl('MuniAlert', $user->email, $secret)] : [])];
    }

    private function enrollmentAuthorized(User $user): bool
    {
        return $user->mfa_enrollment_until !== null && Carbon::parse($user->mfa_enrollment_until)->isFuture();
    }

    /** Called while holding the user row lock, across every challenge/device. */
    private function factorBlocked(User $user): bool
    {
        if ($user->mfa_locked_until !== null) {
            if (Carbon::parse($user->mfa_locked_until)->isFuture()) {
                return true;
            }
            $user->forceFill(['mfa_failures' => 0, 'mfa_locked_until' => null])->save();
        }

        return false;
    }

    private function failedFactor(User $user): void
    {
        $failures = min(5, $user->mfa_failures + 1);
        $user->forceFill(['mfa_failures' => $failures, 'mfa_locked_until' => $failures >= 5 ? now()->addMinutes(5) : null])->save();
        if ($failures >= 5) {
            $this->events->record('mfa_temporarily_locked', $user);
        }
    }

    /** The caller holds the user's row lock. Persist a monotonically increasing step. */
    public function verify(User $user, string $code, bool $recovery = true): bool
    {
        if ($this->factorBlocked($user)) {
            return false;
        }
        if (preg_match('/^\d{6}$/D', $code) && $user->mfa_enabled) {
            $step = $this->totp->verifyKeyNewer($user->mfa_secret, $code, $user->mfa_last_step ?? 0, 1, intdiv(now()->timestamp, 30));
            if ($step !== false) {
                $user->forceFill(['mfa_last_step' => $step, 'mfa_failures' => 0, 'mfa_locked_until' => null])->save();

                return true;
            }
        }
        if ($recovery && preg_match('/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/D', $code)) {
            $codes = $user->mfa_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (hash_equals($hash, hash('sha256', $code))) {
                    unset($codes[$index]);
                    $user->forceFill(['mfa_recovery_codes' => array_values($codes), 'mfa_failures' => 0, 'mfa_locked_until' => null])->save();
                    $this->events->record('mfa_recovery_used', $user);

                    return true;
                }
            }
        }

        $this->failedFactor($user);

        return false;
    }

    public function recoveryCodes(User $user): array
    {
        $codes = array_map(fn () => Str::random(10).'-'.Str::random(10), range(1, 10));
        $user->forceFill(['mfa_recovery_codes' => array_map(fn ($code) => hash('sha256', $code), $codes)])->save();

        return $codes;
    }

    public function complete(Request $request): array
    {
        $data = $request->validate(['challenge_token' => ['required', 'string', 'regex:/^[a-f0-9-]{36}\.[A-Za-z0-9]{64}$/D'], 'code' => 'required|string|max:32']);
        [$id, $proof] = explode('.', $data['challenge_token']);
        $found = DB::table('security_challenges')->where('id', $id)->first();
        $result = $found ? DB::transaction(function () use ($request, $data, $id, $proof, $found) {
            // Always lock user first, then challenge, including competing attempts.
            $user = User::whereKey($found->user_id)->lockForUpdate()->first();
            $challenge = DB::table('security_challenges')->where('id', $id)->lockForUpdate()->first();
            $native = $request->is('api/auth/*');
            if (! $user || ! $challenge || ! $user->is_active || $challenge->consumed
                || ! in_array($challenge->purpose, ['login', 'enroll'], true) || $challenge->attempts >= 5
                || Carbon::parse($challenge->expires_at)->lte(now()) || ! hash_equals($challenge->token_hash, hash('sha256', $proof))
                || $challenge->platform !== ($native ? 'native' : 'web') || (int) $challenge->security_version !== $user->security_version
                || ! hash_equals($challenge->fingerprint, $this->fingerprint($user))
                || (! $native && ! hash_equals($challenge->binding, hash('sha256', (string) $request->session()->get('mfa_binding'))))) {
                return null;
            }
            if ($this->factorBlocked($user)) {
                return null;
            }
            $valid = false;
            if ($challenge->purpose === 'enroll' && ! $user->mfa_enabled && $this->enrollmentAuthorized($user) && preg_match('/^\d{6}$/D', $data['code'])) {
                $secret = Crypt::decryptString($challenge->payload);
                $step = $this->totp->verifyKeyNewer($secret, $data['code'], 0, 1, intdiv(now()->timestamp, 30));
                if ($step !== false) {
                    $user->forceFill(['mfa_secret' => $secret, 'mfa_confirmed_at' => now(), 'mfa_enrollment_until' => null, 'mfa_last_step' => $step, 'mfa_failures' => 0, 'mfa_locked_until' => null])->save();
                    $this->sessions->invalidate($user);
                    $codes = $this->recoveryCodes($user);
                    $valid = true;
                    $this->events->record('mfa_enrolled', $user, $request);
                }
            } elseif ($challenge->purpose === 'login') {
                $valid = $this->verify($user, $data['code']);
            }
            DB::table('security_challenges')->where('id', $id)->update(['attempts' => $challenge->attempts + 1,
                'consumed' => $valid || $challenge->attempts >= 4, 'payload' => $valid || $challenge->attempts >= 4 ? null : $challenge->payload, 'updated_at' => now()]);
            if (! $valid) {
                if ($challenge->purpose === 'enroll') {
                    $this->failedFactor($user);
                }
                $this->events->record('mfa_failed', $user, $request);

                return null;
            }

            return [...$this->sessions->issue($request, $user, true, $native), ...(isset($codes) ? ['recovery_codes' => $codes] : [])];
        }) : null;
        if (! $result) {
            throw ValidationException::withMessages(['code' => 'Verification failed or expired. Use a fresh code or start sign-in again. After repeated failures, wait five minutes.']);
        }

        return $result;
    }
}
