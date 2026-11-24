<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_retired_provider_routes_cannot_create_link_or_authenticate_accounts(): void
    {
        foreach (['google', 'facebook'] as $provider) {
            $this->postJson('/api/auth/social/'.$provider.'/attempts', [])->assertNotFound();
            $this->get('/auth/social/'.$provider.'/redirect')->assertNotFound();
            $this->get('/auth/social/'.$provider.'/callback?code=retired')->assertNotFound();
        }
        $this->postJson('/api/auth/social/exchange', [])->assertNotFound();
        $this->getJson('/api/auth/providers')->assertNotFound();
        $this->getJson('/api/auth/social/accounts')->assertNotFound();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('social_accounts', 0);
    }
}
