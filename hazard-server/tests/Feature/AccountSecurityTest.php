<?php

namespace Tests\Feature;

use App\Jobs\SendSecurityMail;
use App\Models\User;
use App\Services\AccountMfa;
use App\Services\AccountRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'isolated-security-password';

    private const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    private function admin(bool $enrolled = false): User
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => self::PASSWORD]);
        $user->forceFill(['mfa_enrollment_until' => now()->addMinutes(15)])->save();
        if ($enrolled) {
            $user->forceFill(['mfa_secret' => self::SECRET, 'mfa_confirmed_at' => now()])->save();
        }

        return $user;
    }

    private function login(User $user): array
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertOk()->json();
    }

    private function code(string $secret = self::SECRET): string
    {
        return (new Google2FA)->oathTotp($secret, intdiv(now()->timestamp, 30));
    }

    private function verify(array $challenge, ?string $code = null)
    {
        return $this->postJson('/api/auth/mfa/verify', ['challenge_token' => $challenge['challenge_token'], 'code' => $code ?? $this->code()]);
    }

    private function tokenRequest(string $token, string $url = '/api/auth/me')
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson($url);
    }

    public function test_administrator_enrolls_before_any_credential_is_issued_and_secret_is_encrypted(): void
    {
        $admin = $this->admin();
        $pending = $this->login($admin);
        $this->assertTrue($pending['enrollment_required']);
        $this->assertArrayNotHasKey('token', $pending);
        $this->assertArrayNotHasKey('user', $pending);
        $this->assertSame(0, $admin->tokens()->count());
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $result = $this->verify($pending, $this->code($pending['secret']))->assertOk();
        $this->assertCount(10, $result->json('recovery_codes'));
        $this->assertNotSame($pending['secret'], DB::table('users')->where('id', $admin->id)->value('mfa_secret'));
        $result->assertJsonMissingPath('user.mfa_secret')->assertJsonMissingPath('user.mfa_recovery_codes')->assertJsonMissingPath('user.security_version');
        $this->tokenRequest($result->json('token'))->assertOk();
        $this->verify($pending, $this->code($pending['secret']))->assertUnprocessable();
    }

    public function test_totp_and_recovery_codes_are_single_use_across_challenges(): void
    {
        $admin = $this->admin(true);
        $codes = app(AccountMfa::class)->recoveryCodes($admin);
        $a = $this->login($admin);
        $b = $this->login($admin);
        $this->verify($a)->assertOk();
        $this->verify($b)->assertUnprocessable();
        $this->verify($b, $codes[0])->assertOk();
        $this->verify($this->login($admin), $codes[0])->assertUnprocessable();
        $this->assertCount(9, $admin->fresh()->mfa_recovery_codes);
    }

    public function test_attempt_limit_expiry_password_change_and_competing_enrollment_invalidate_challenges(): void
    {
        $admin = $this->admin();
        $a = $this->login($admin);
        $b = $this->login($admin);
        $this->verify($a, $this->code($a['secret']))->assertOk();
        $this->verify($b, $this->code($b['secret']))->assertUnprocessable();
        $pending = $this->login($admin);
        for ($i = 0; $i < 5; $i++) {
            $this->verify($pending, 'not-a-code')->assertUnprocessable();
        }
        $this->travel(31)->seconds();
        $this->verify($pending)->assertUnprocessable();
        $pending = $this->login($admin);
        $this->travel(6)->minutes();
        $this->verify($pending)->assertUnprocessable();
        $pending = $this->login($admin);
        $admin->forceFill(['password' => 'another-security-password'])->save();
        $this->verify($pending)->assertUnprocessable();
    }

    public function test_old_administrator_tokens_suspension_and_role_promotion_cannot_bypass_mfa(): void
    {
        $admin = $this->admin(true);
        $old = $admin->createToken('mobile')->plainTextToken;
        $this->tokenRequest($old)->assertUnauthorized();
        $token = $this->verify($this->login($admin))->assertOk()->json('token');
        $admin->is_active = false;
        $admin->save();
        $this->tokenRequest($token)->assertUnauthorized();
        $this->loginFailure($admin);
        $citizen = User::factory()->create();
        $plain = $citizen->createToken('mobile')->plainTextToken;
        $citizen->role = 'admin';
        $citizen->save();
        $this->tokenRequest($plain)->assertUnauthorized();
    }

    private function loginFailure(User $user): void
    {
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertUnprocessable();
    }

    public function test_recent_verification_is_device_bound_and_requires_a_fresh_factor(): void
    {
        $this->seed();
        $admin = $this->admin(true);
        $token = $this->verify($this->login($admin))->assertOk()->json('token');
        $this->tokenRequest($token, '/api/security/status')->assertOk();
        $this->travel(6)->minutes();
        $this->withToken($token)->postJson('/api/security/recovery-codes')->assertStatus(428);
        $this->withToken($token)->postJson('/api/security/reauthenticate', ['password' => 'incorrect', 'code' => $this->code()])->assertUnprocessable();
        $this->withToken($token)->postJson('/api/security/reauthenticate', ['password' => self::PASSWORD, 'code' => $this->code()])->assertOk();
        $this->withToken($token)->postJson('/api/security/recovery-codes')->assertOk()->assertJsonCount(10, 'recovery_codes');
        // Changing recovery material invalidates other credentials and step-up.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/security/recovery-codes')->assertStatus(428);
    }

    public function test_recovery_is_generic_queued_and_revokes_credentials_without_removing_mfa(): void
    {
        config(['security.recovery_enabled' => true, 'security.frontend_url' => 'https://municipal.example.test']);
        Mail::fake();
        $admin = $this->admin(true);
        $admin->forceFill(['local_email_verified_at' => now()])->save();
        $old = $admin->createToken('mobile')->plainTextToken;
        $known = $this->postJson('/api/auth/recovery', ['email' => $admin->email])->assertOk()->json();
        $unknown = $this->postJson('/api/auth/recovery', ['email' => 'missing@example.test'])->assertOk()->json();
        $this->assertSame($known, $unknown);
        $this->assertSame(1, DB::table('jobs')->where('queue', 'security-mail')->count());
        $challenge = DB::table('security_challenges')->where('purpose', 'reset')->first();
        $proof = Crypt::decryptString($challenge->payload);
        $this->assertStringNotContainsString($proof, DB::table('jobs')->first()->payload);
        $job = new SendSecurityMail($challenge->id);
        $job->handle(app(AccountRecovery::class), app(AccountMfa::class));
        Mail::assertSentCount(1);
        $values = ['purpose' => 'reset', 'token' => $challenge->id.'.'.$proof, 'password' => 'new-security-password', 'password_confirmation' => 'new-security-password'];
        $this->postJson('/api/auth/recovery/consume', $values)->assertOk();
        $this->postJson('/api/auth/recovery/consume', $values)->assertUnprocessable();
        $this->tokenRequest($old)->assertUnauthorized();
        $this->assertTrue($admin->fresh()->mfa_enabled);
        $this->assertTrue(Hash::check('new-security-password', $admin->fresh()->password));
    }

    public function test_provider_email_alone_cannot_enable_local_recovery_and_disabled_mail_never_queues(): void
    {
        config(['security.recovery_enabled' => true, 'security.frontend_url' => 'https://municipal.example.test']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->postJson('/api/auth/recovery', ['email' => $user->email])->assertOk();
        $this->assertSame(0, DB::table('jobs')->count());
        config(['security.recovery_enabled' => false]);
        $user->forceFill(['local_email_verified_at' => now()])->save();
        $this->postJson('/api/auth/recovery', ['email' => $user->email])->assertOk();
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_security_events_never_contain_credentials_email_or_incident_payloads(): void
    {
        $admin = $this->admin();
        $challenge = $this->login($admin);
        $serialized = json_encode(DB::table('security_events')->get());
        $this->assertStringNotContainsString(self::PASSWORD, $serialized);
        $this->assertStringNotContainsString($admin->email, $serialized);
        $this->assertStringNotContainsString($challenge['secret'], $serialized);
        $this->assertStringNotContainsString($challenge['challenge_token'], $serialized);
    }

    public function test_password_alone_cannot_bootstrap_an_unapproved_administrator_factor(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['mfa_enrollment_until' => null])->save();
        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => self::PASSWORD])->assertUnprocessable();
        $this->assertDatabaseCount('security_challenges', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_web_challenge_cannot_be_used_from_another_session_or_native_endpoint(): void
    {
        $admin = $this->admin(true);
        $pending = $this->postJson('/auth/login', ['email' => $admin->email, 'password' => self::PASSWORD])->assertOk()->json();
        $this->verify($pending)->assertUnprocessable();
        $this->withSession(['mfa_binding' => 'another-browser'])->postJson('/auth/mfa/verify', ['challenge_token' => $pending['challenge_token'], 'code' => $this->code()])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertGuest('web');
    }

    public function test_factor_lockout_survives_new_challenges_and_other_request_sources(): void
    {
        $admin = $this->admin(true);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($attempt + 1)]);
            $this->verify($this->login($admin), 'not-a-code')->assertUnprocessable();
        }
        $this->assertSame(5, $admin->fresh()->mfa_failures);
        $this->assertNotNull($admin->fresh()->mfa_locked_until);
        $this->verify($this->login($admin))->assertUnprocessable();
        $this->assertSame(0, $admin->tokens()->count());
        $this->travel(6)->minutes();
        $this->verify($this->login($admin))->assertOk();
        $this->assertSame(0, $admin->fresh()->mfa_failures);
        $this->assertNull($admin->fresh()->mfa_locked_until);
    }

    public function test_recovery_requires_a_valid_trusted_origin(): void
    {
        config(['security.recovery_enabled' => true]);
        foreach (['https:', 'https://*.example.test', 'https://user:secret@example.test', 'http://external.example.test', 'https://example.test/untrusted', 'https://example.test?redirect=1'] as $origin) {
            config(['security.frontend_url' => $origin]);
            $this->assertFalse(app(AccountRecovery::class)->available());
        }
        config(['security.frontend_url' => 'https://municipal.example.test']);
        $this->assertTrue(app(AccountRecovery::class)->available());
    }

    public function test_operator_actions_preserve_the_last_admin_and_revoke_credentials(): void
    {
        $admin = $this->admin(true);
        $this->artisan('hazard:account-security', ['email' => $admin->email, '--suspend' => true])
            ->expectsConfirmation('Has the municipality approved this account change and, for MFA recovery, independently verified the owner?', 'yes')->assertFailed();
        $this->assertTrue($admin->fresh()->is_active);
        $this->admin(true);
        $old = $admin->createToken('mobile')->plainTextToken;
        $this->artisan('hazard:account-security', ['email' => $admin->email, '--suspend' => true])
            ->expectsConfirmation('Has the municipality approved this account change and, for MFA recovery, independently verified the owner?', 'yes')->assertSuccessful();
        $this->assertFalse($admin->fresh()->is_active);
        $this->tokenRequest($old)->assertUnauthorized();
    }
}
