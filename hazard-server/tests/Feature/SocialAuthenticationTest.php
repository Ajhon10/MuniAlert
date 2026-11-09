<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as ProviderUser;
use Mockery;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cors.allowed_origins' => ['http://localhost:5173']]);
        foreach (['google', 'facebook'] as $provider) {
            config(['services.'.$provider => ['client_id' => 'test-client', 'client_secret' => 'test-secret', 'redirect' => 'http://localhost/auth/social/'.$provider.'/callback']]);
        }
    }

    private function attempt(string $provider = 'google', array $values = []): string
    {
        return $this->postJson('/api/auth/social/'.$provider.'/attempts', array_replace(['platform' => 'web', 'mode' => 'login', 'return_origin' => 'http://localhost:5173', 'intent' => 'report'], $values))->assertCreated()->json('attempt');
    }

    private function identity(string $email = 'new@example.test', string $subject = 'provider-subject', bool $verified = true): ProviderUser
    {
        return (new ProviderUser)->setRaw(['email_verified' => $verified])->map(['id' => $subject, 'email' => $email, 'name' => 'New Citizen'])->setToken('provider-token-must-not-be-stored');
    }

    private function fakeProvider(string $provider, ProviderUser $identity): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('setHttpClient')->andReturnSelf();
        $driver->shouldReceive('enablePKCE')->andReturnSelf();
        $driver->shouldReceive('fields')->with(['id', 'name', 'email'])->andReturnSelf();
        $driver->shouldReceive('user')->once()->andReturn($identity);
        Socialite::shouldReceive('driver')->with($provider)->once()->andReturn($driver);
    }

    private function finishCallback(string $attempt, string $provider = 'google')
    {
        DB::table('social_auth_attempts')->where('id', $attempt)->update(['started_at' => now()]);

        return $this->withSession(['social_auth_attempt' => $attempt, 'state' => 'test-state'])->get('/auth/social/'.$provider.'/callback?state=test-state&code=provider-code');
    }

    public function test_configuration_is_server_only_and_unconfigured_providers_fail_closed(): void
    {
        config(['services.google.client_secret' => '', 'services.facebook.redirect' => 'http://insecure.example/callback']);
        $this->getJson('/api/auth/providers')->assertOk()->assertExactJson(['google' => ['enabled' => false], 'facebook' => ['enabled' => false]])->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson('/api/auth/social/google/attempts', [])->assertStatus(503);
        $this->postJson('/api/auth/social/github/attempts', [])->assertNotFound();
    }

    public function test_prepare_rejects_untrusted_origins_and_missing_android_challenge(): void
    {
        foreach (['https://attacker.example', 'http://localhost:5173/redirect', 'http://localhost:5173?next=evil', 'http://user@localhost:5173'] as $origin) {
            $this->postJson('/api/auth/social/google/attempts', ['platform' => 'web', 'mode' => 'login', 'return_origin' => $origin])->assertUnprocessable();
        }
        $this->postJson('/api/auth/social/google/attempts', ['platform' => 'android', 'mode' => 'login'])->assertUnprocessable()->assertJsonValidationErrors('code_challenge');
        $this->assertDatabaseCount('social_auth_attempts', 0);
    }

    public function test_redirect_uses_real_state_and_google_pkce_and_cannot_be_started_twice(): void
    {
        $id = $this->attempt();
        $response = $this->get('/auth/social/google/redirect?attempt='.$id)->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertNotEmpty($query['state']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('test-client', $query['client_id']);
        $this->assertNotEmpty(session('code_verifier'));
        $this->getJson('/auth/social/google/redirect?attempt='.$id)->assertUnprocessable();
    }

    public function test_invalid_state_and_denial_are_handled_without_provider_network_requests(): void
    {
        $id = $this->attempt();
        $this->withSession(['social_auth_attempt' => $id, 'state' => 'expected'])->get('/auth/social/google/callback?state=wrong&code=ignored')->assertRedirectContains('social_error=expired');
        $id = $this->attempt();
        $this->withSession(['social_auth_attempt' => $id, 'state' => 'expected'])->get('/auth/social/google/callback?state=expected&error=access_denied')->assertRedirectContains('social_error=cancelled');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_google_signup_issues_web_cookie_and_persists_only_provider_identity(): void
    {
        $id = $this->attempt();
        $this->fakeProvider('google', $this->identity());
        $response = $this->finishCallback($id)->assertRedirectContains('social_auth=success')->assertRedirectContains('social_intent=report');
        $user = User::sole();
        $this->assertAuthenticatedAs($user, 'web');
        $response->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertSame('citizen', $user->role);
        $this->assertFalse($user->has_password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', ['provider' => 'google', 'provider_id' => 'provider-subject', 'user_id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertStringNotContainsString('provider-token', json_encode(DB::table('social_accounts')->first()));
        Auth::guard('web')->setUser($user->fresh());
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('has_password', false)->assertJsonMissingPath('password_enabled');
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password-123'])->assertUnprocessable();
    }

    public function test_existing_emails_are_not_automatically_linked_or_promoted(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.test', 'role' => 'admin']);
        $id = $this->attempt();
        $this->fakeProvider('google', $this->identity($existing->email));
        $this->finishCallback($id)->assertRedirectContains('social_error=existing_account');
        $this->assertGuest('web');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_social_sign_in_accepts_the_guide_and_preserves_its_return_destination(): void
    {
        $id = $this->attempt(values: ['intent' => 'guide']);
        $this->assertDatabaseHas('social_auth_attempts', ['id' => $id, 'intent' => 'guide']);
        $this->fakeProvider('google', $this->identity());
        $this->finishCallback($id)->assertRedirectContains('social_auth=success')->assertRedirectContains('social_intent=guide');
        $this->assertAuthenticated('web');
        $this->postJson('/api/auth/social/google/attempts', ['platform' => 'web', 'mode' => 'login', 'return_origin' => 'http://localhost:5173', 'intent' => 'untrusted_destination'])->assertUnprocessable();
    }

    public function test_google_requires_verified_email_and_facebook_requires_email_for_signup(): void
    {
        $id = $this->attempt();
        $this->fakeProvider('google', $this->identity(verified: false));
        $this->finishCallback($id)->assertRedirectContains('social_error=email_unverified');
        $id = $this->attempt('facebook');
        $this->fakeProvider('facebook', $this->identity(''));
        $this->finishCallback($id, 'facebook')->assertRedirectContains('social_error=email_required');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_facebook_signup_and_repeat_login_use_stable_identity_without_overwriting_profile(): void
    {
        $id = $this->attempt('facebook');
        $this->fakeProvider('facebook', $this->identity(verified: false));
        $this->finishCallback($id, 'facebook')->assertRedirectContains('social_auth=success');
        $user = User::sole();
        $this->assertNull($user->email_verified_at);
        Auth::guard('web')->logout();

        $id = $this->attempt('facebook');
        $this->fakeProvider('facebook', $this->identity('changed@example.test'));
        $this->finishCallback($id, 'facebook')->assertRedirectContains('social_auth=success');
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('new@example.test', $user->fresh()->email);
    }

    public function test_linking_requires_password_confirmation_and_preserves_the_existing_account(): void
    {
        $user = User::factory()->create(['password' => 'existing-password-123']);
        $values = ['platform' => 'web', 'mode' => 'link', 'return_origin' => 'http://localhost:5173'];
        $this->postJson('/api/auth/social/google/attempts', $values)->assertUnauthorized();
        $this->actingAs($user, 'web')->postJson('/api/auth/social/google/attempts', $values)->assertUnprocessable();
        $id = $this->attempt(values: ['mode' => 'link', 'current_password' => 'existing-password-123']);
        $this->fakeProvider('google', $this->identity('different-provider-email@example.test'));
        $this->finishCallback($id)->assertRedirectContains('social_auth=linked');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id]);
        $this->getJson('/api/auth/social/accounts')->assertExactJson(['providers' => ['google']]);
    }

    public function test_linking_rejects_provider_identity_already_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        DB::table('social_accounts')->insert(['user_id' => $owner->id, 'provider' => 'google', 'provider_id' => 'provider-subject', 'created_at' => now(), 'updated_at' => now()]);
        $other = User::factory()->create(['password' => 'existing-password-123']);
        $this->actingAs($other, 'web');
        $id = $this->attempt(values: ['mode' => 'link', 'current_password' => 'existing-password-123']);
        $this->fakeProvider('google', $this->identity());
        $this->finishCallback($id)->assertRedirectContains('social_error=account_in_use');
        $this->assertDatabaseCount('social_accounts', 1);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $owner->id]);
    }

    public function test_password_change_invalidates_pending_link_proof(): void
    {
        $user = User::factory()->create(['password' => 'existing-password-123']);
        $this->actingAs($user, 'web');
        $id = $this->attempt(values: ['mode' => 'link', 'current_password' => 'existing-password-123']);
        $user->update(['password' => 'changed-password-123']);
        $this->fakeProvider('google', $this->identity());
        $this->finishCallback($id)->assertRedirectContains('social_error=expired');
        $this->assertDatabaseCount('social_accounts', 0);
    }

    private function androidResult(): array
    {
        $verifier = str_repeat('v', 43);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $id = $this->attempt(values: ['platform' => 'android', 'return_origin' => null, 'code_challenge' => $challenge]);
        $this->fakeProvider('google', $this->identity());
        $response = $this->finishCallback($id)->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringStartsWith('ph.municipal.bantay://auth/callback?', $target);
        $this->assertStringNotContainsString('token', $target);
        $this->assertStringNotContainsString('@', $target);
        $this->assertGuest('web');
        parse_str(parse_url($target, PHP_URL_QUERY), $query);

        return ['attempt' => $id, 'code' => $query['code'], 'code_verifier' => $verifier];
    }

    public function test_android_code_requires_verifier_and_can_be_exchanged_only_once(): void
    {
        $data = $this->androidResult();
        $this->postJson('/api/auth/social/exchange', array_replace($data, ['code_verifier' => str_repeat('x', 43)]))->assertUnprocessable();
        $this->assertDatabaseHas('social_auth_attempts', ['id' => $data['attempt'], 'failures' => 1]);
        $response = $this->postJson('/api/auth/social/exchange', $data)->assertOk()->assertJsonPath('intent', 'report')->assertJsonPath('linked', false)->assertJsonPath('user.has_password', false);
        $this->assertNotEmpty($response->json('token'));
        $token = PersonalAccessToken::sole();
        $this->assertEqualsWithDelta(90, now()->diffInDays($token->expires_at), 0.1);
        $this->postJson('/api/auth/social/exchange', $data)->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_android_exchange_is_locked_after_five_failed_proofs(): void
    {
        $data = $this->androidResult();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/social/exchange', array_replace($data, ['code_verifier' => str_repeat('x', 43)]))->assertUnprocessable();
        }
        $this->assertDatabaseHas('social_auth_attempts', ['id' => $data['attempt'], 'failures' => 5]);
        $this->postJson('/api/auth/social/exchange', $data)->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_expired_android_exchange_and_callback_replay_are_rejected(): void
    {
        $data = $this->androidResult();
        $this->travel(91)->seconds();
        $this->postJson('/api/auth/social/exchange', $data)->assertUnprocessable();
        $this->withSession(['social_auth_attempt' => $data['attempt']])->get('/auth/social/google/callback?state=test-state&code=replayed')->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_revoked_native_session_invalidates_pending_link(): void
    {
        $user = User::factory()->create(['password' => 'existing-password-123']);
        $credential = $user->createToken('mobile', ['*'], now()->addDay());
        $id = $this->withToken($credential->plainTextToken)->attempt(values: ['platform' => 'android', 'return_origin' => null, 'mode' => 'link', 'current_password' => 'existing-password-123', 'code_challenge' => str_repeat('c', 43)]);
        $credential->accessToken->delete();
        $this->fakeProvider('google', $this->identity());
        $this->finishCallback($id)->assertRedirectContains('social_error=expired');
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_expired_start_and_denial_with_wrong_state_fail_closed(): void
    {
        $id = $this->attempt();
        $this->travel(11)->minutes();
        $this->getJson('/auth/social/google/redirect?attempt='.$id)->assertUnprocessable();
        $id = $this->attempt();
        $this->withSession(['social_auth_attempt' => $id, 'state' => 'expected'])->get('/auth/social/google/callback?state=wrong&error=access_denied')->assertRedirectContains('social_error=expired');
        $this->assertGuest('web');
    }

    public function test_provider_failure_does_not_expose_remote_exception_details(): void
    {
        $id = $this->attempt();
        $driver = Mockery::mock();
        $driver->shouldReceive('setHttpClient')->andReturnSelf();
        $driver->shouldReceive('enablePKCE')->andReturnSelf();
        $driver->shouldReceive('user')->once()->andThrow(new \RuntimeException('provider-secret-or-token'));
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($driver);
        $response = $this->finishCallback($id)->assertRedirectContains('social_error=provider_failed');
        $this->assertStringNotContainsString('provider-secret', $response->headers->get('Location'));
        $this->assertDatabaseCount('users', 0);
    }
}
