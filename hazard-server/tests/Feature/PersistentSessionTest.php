<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersistentSessionTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'persistent-session-test-password';

    private function citizen(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function me(string $token)
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->getJson('/api/auth/me');
    }

    public function test_web_login_issues_a_persistent_http_only_remember_cookie(): void
    {
        $user = $this->citizen();
        $response = $this->postJson('/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertOk();
        $this->assertSame('bantay_session', config('session.cookie'));
        $response->assertCookie('bantay_session');
        $name = Auth::guard('web')->getRecallerName();
        $response->assertCookie($name);
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === $name);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertGreaterThan(now()->addDays(89)->timestamp, $cookie->getExpiresTime());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_mobile_token_survives_long_absence_and_renews_but_expired_tokens_are_rejected(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = $this->citizen();
        $response = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => self::PASSWORD])->assertOk();
        $plain = $response->json('token');
        $token = $user->tokens()->firstOrFail();
        $this->assertTrue($token->expires_at->equalTo(now()->addDays(90)));
        $this->travel(60)->days();
        $this->me($plain)->assertOk();
        $this->assertTrue($token->fresh()->expires_at->equalTo(now()->addDays(90)));
        $this->travel(91)->days();
        $this->me($plain)->assertUnauthorized();
        $this->travelBack();
    }

    public function test_logout_revokes_only_the_current_mobile_device(): void
    {
        $user = $this->citizen();
        $current = $user->createToken('mobile', ['*'], now()->addDays(90))->plainTextToken;
        $other = $user->createToken('mobile', ['*'], now()->addDays(90))->plainTextToken;
        $this->withToken($current)->postJson('/api/auth/logout')->assertOk();
        $this->me($current)->assertUnauthorized();
        $this->me($other)->assertOk();
    }

    public function test_password_change_revokes_other_tokens_and_remember_credentials_but_keeps_current_device(): void
    {
        $this->seed();
        $user = $this->citizen();
        $user->forceFill(['remember_token' => 'previous-remember-credential'])->save();
        $current = $user->createToken('mobile', ['*'], now()->addDays(90))->plainTextToken;
        $other = $user->createToken('mobile', ['*'], now()->addDays(90))->plainTextToken;
        $this->withToken($current)->patchJson('/api/profile', [
            'name' => $user->name, 'email' => $user->email,
            'current_password' => self::PASSWORD, 'password' => 'new-persistent-session-password',
            'password_confirmation' => 'new-persistent-session-password',
        ])->assertOk();
        $this->assertNotSame('previous-remember-credential', $user->fresh()->remember_token);
        $this->me($other)->assertUnauthorized();
        $this->me($current)->assertOk();
    }
}
