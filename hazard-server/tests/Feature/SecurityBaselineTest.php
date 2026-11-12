<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_callers_cannot_read_private_data_or_change_administration(): void
    {
        foreach (['/api/auth/me', '/api/profile', '/api/reports/mine', '/api/admin/reports', '/api/admin/analytics', '/api/notifications'] as $path) {
            $this->getJson($path)->assertUnauthorized()->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->putJson('/api/admin/settings', [])->assertUnauthorized();
        $this->postJson('/api/reports', [])->assertUnauthorized();
    }

    public function test_registration_ignores_privileged_fields_and_hides_credentials(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Synthetic Citizen', 'email' => 'citizen@example.test', 'password' => 'synthetic-password-123',
            'role' => 'admin', 'email_verified_at' => now()->toISOString(), 'password_enabled' => false,
        ])->assertCreated()->assertJsonPath('user.role', 'citizen')->assertJsonMissingPath('user.password')->assertJsonMissingPath('user.remember_token');
        $this->assertNull(User::findOrFail($response->json('user.id'))->email_verified_at);
    }

    public function test_malformed_email_and_oversized_multibyte_passwords_are_validation_errors(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Synthetic Citizen', 'email' => ['unexpected'], 'password' => 'synthetic-password-123'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        foreach ([str_repeat('a', 73), str_repeat('é', 37), "synthetic-password\0"] as $password) {
            $this->postJson('/api/auth/register', ['name' => 'Synthetic Citizen', 'email' => 'new@example.test', 'password' => $password])
                ->assertUnprocessable()->assertJsonValidationErrors('password');
            $this->postJson('/api/auth/login', ['email' => 'new@example.test', 'password' => $password])
                ->assertUnprocessable()->assertJsonValidationErrors('password');
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_boundary_is_accepted_and_profile_changes_cannot_escalate_roles(): void
    {
        $password = str_repeat('é', 36);
        $response = $this->postJson('/api/auth/register', ['name' => 'Synthetic Citizen', 'email' => 'boundary@example.test', 'password' => $password])->assertCreated();
        $user = User::findOrFail($response->json('user.id'));
        $this->assertTrue(Hash::check($password, $user->password));
        $this->authenticateUser($user);
        $this->patchJson('/api/profile', ['name' => $user->name, 'email' => ['unexpected']])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->patchJson('/api/profile', ['name' => $user->name, 'email' => $user->email, 'password' => str_repeat('x', 73), 'password_confirmation' => str_repeat('x', 73), 'current_password' => $password])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->seed();
        $this->patchJson('/api/profile', ['name' => $user->name, 'email' => $user->email, 'role' => 'admin'])->assertOk();
        $this->assertSame('citizen', $user->fresh()->role);
        $this->putJson('/api/admin/settings', [])->assertForbidden();
        $this->getJson('/api/admin/analytics')->assertForbidden();
    }

    public function test_incorrect_login_has_generic_errors_and_is_throttled(): void
    {
        $user = User::factory()->create(['password' => 'synthetic-password-123']);
        $known = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertUnprocessable();
        $unknown = $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'wrong-password'])->assertUnprocessable();
        $this->assertSame($known->json('errors'), $unknown->json('errors'));
        for ($i = 0; $i < 8; $i++) {
            $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertUnprocessable();
        }
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'synthetic-password-123'])->assertTooManyRequests()->assertHeader('Retry-After');
    }

    public function test_cookie_login_rejects_a_missing_csrf_token_with_real_csrf_middleware(): void
    {
        // Laravel normally skips CSRF in tests; use local mode after isolated DB setup.
        $this->app->detectEnvironment(fn () => 'local');
        $this->postJson('/auth/login', ['email' => 'synthetic@example.test', 'password' => 'synthetic-password-123'])->assertStatus(419);
    }

    public function test_headers_cover_success_errors_and_private_cache_control_without_blocking_phone_features(): void
    {
        $this->seed();
        $this->getJson('/api/catalog')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()')->assertHeaderMissing('Strict-Transport-Security');
        $this->getJson('/api/missing-resource')->assertNotFound()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_production_disables_debug_and_forces_secure_http_only_cookies(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.debug' => true, 'session.secure' => false, 'session.http_only' => false]);
        Route::get('/synthetic-failure', fn () => throw new \RuntimeException('Sensitive synthetic internal detail'));
        $this->getJson('https://localhost/synthetic-failure')->assertStatus(500)->assertDontSee('Sensitive synthetic internal detail')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->assertFalse(config('app.debug'));
        $this->assertTrue(config('session.secure'));
        $this->assertTrue(config('session.http_only'));
        $cookies = $this->get('https://localhost/sanctum/csrf-cookie')->assertNoContent()->headers->getCookies();
        $sessionCookie = collect($cookies)->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($sessionCookie);
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertTrue($sessionCookie->isHttpOnly());
    }

    public function test_cors_does_not_grant_credentials_to_an_unapproved_origin(): void
    {
        $this->withHeader('Origin', 'https://unapproved.example.test')->options('/api/auth/login', [], ['Access-Control-Request-Method' => 'POST'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
