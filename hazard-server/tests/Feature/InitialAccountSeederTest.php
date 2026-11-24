<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_has_no_shared_demo_logins_and_missing_password_creates_no_account(): void
    {
        app(DatabaseSeeder::class)->run();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('categories', 10);
    }

    public function test_configured_starter_admin_is_created_without_email_policy_or_identity_bypass(): void
    {
        config(['initial_accounts.password' => 'synthetic-private-password']);
        app(DatabaseSeeder::class)->run();
        $user = User::sole();
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('synthetic-private-password', $user->password));
        $this->assertNull($user->local_email_verified_at);
        $this->assertFalse($user->mfa_enabled);
        $this->assertDatabaseCount('policy_acceptances', 0);
        $this->assertDatabaseCount('identity_verifications', 0);
        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'synthetic-private-password'])
            ->assertOk()->assertJsonPath('enrollment_required', true)->assertJsonMissingPath('token');
    }

    public function test_production_seed_accepts_private_initial_credentials_and_is_idempotent(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['initial_accounts.name' => 'Municipal Operator', 'initial_accounts.email' => 'operator@municipality.gov.ph', 'initial_accounts.password' => 'synthetic-private-password']);
        app(DatabaseSeeder::class)->run();
        $before = User::sole()->getRawOriginal();
        config(['initial_accounts.password' => 'changed-private-password']);
        app(DatabaseSeeder::class)->run();
        $this->assertSame($before, User::sole()->getRawOriginal());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_production_sample_email_and_invalid_password_are_rejected_without_partial_writes(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['initial_accounts.password' => 'synthetic-private-password']);
        try {
            app(DatabaseSeeder::class)->run();
            $this->fail('Production requires a reachable initial email.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('configuration is invalid', $error->getMessage());
            $this->assertStringNotContainsString('synthetic-private-password', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_existing_citizen_is_never_promoted_by_seeding(): void
    {
        $user = User::factory()->create(['email' => 'admin@munialert.test']);
        config(['initial_accounts.password' => 'synthetic-private-password']);
        try {
            app(DatabaseSeeder::class)->run();
            $this->fail('An existing account cannot be taken over.');
        } catch (\RuntimeException) {
            $this->assertSame('citizen', $user->fresh()->role);
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_reseeding_does_not_recreate_a_deleted_starter_administrator(): void
    {
        config(['initial_accounts.password' => 'synthetic-private-password']);
        app(DatabaseSeeder::class)->run();
        $user = User::sole();
        $user->delete();
        app(DatabaseSeeder::class)->run();
        $this->assertCount(0, User::all());
        $this->assertCount(1, User::withTrashed()->get());
    }
}
