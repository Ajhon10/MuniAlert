<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'hazards.demo_admin_email' => 'admin@example.test',
            'hazards.demo_admin_password' => 'admin-test-password-123',
            'hazards.demo_citizen_one_password' => 'citizen-one-test-password-123',
            'hazards.demo_citizen_two_password' => 'citizen-two-test-password-123',
        ]);
    }

    public function test_production_refuses_demo_accounts_without_changing_users(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        try {
            app(DemoAccountSeeder::class)->run();
            $this->fail('Production must refuse demo accounts.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('local or testing', $error->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_missing_reused_or_oversized_passwords_leave_no_partial_accounts(): void
    {
        foreach ([null, 'short', 'admin-test-password-123', str_repeat('é', 37), "synthetic-password\0"] as $password) {
            config(['hazards.demo_citizen_two_password' => $password]);
            try {
                $this->seed(DemoAccountSeeder::class);
                $this->fail('Invalid demo passwords must be rejected.');
            } catch (\RuntimeException $error) {
                $this->assertStringContainsString('different HAZARD_DEMO', $error->getMessage());
            }
            $this->assertDatabaseCount('users', 0);
        }
    }

    public function test_administrator_email_is_configurable_and_cannot_collide_with_a_citizen(): void
    {
        foreach (['not-an-email', 'citizen.one@example.test', 'personal@example.com'] as $email) {
            config(['hazards.demo_admin_email' => $email]);
            try {
                $this->seed(DemoAccountSeeder::class);
                $this->fail('Invalid/colliding email must be rejected.');
            } catch (\RuntimeException) {
                $this->assertDatabaseCount('users', 0);
            }
        }
        config(['hazards.demo_admin_email' => 'local-admin@example.test']);
        $this->seed(DemoAccountSeeder::class);
        $this->assertSame('admin', User::where('email', 'local-admin@example.test')->firstOrFail()->role);
    }

    public function test_demo_accounts_have_the_expected_roles_and_passwords(): void
    {
        config()->set('hazards.demo_admin_password', 'admin-test-password-123');
        config()->set('hazards.demo_citizen_one_password', 'citizen-one-test-password-123');
        config()->set('hazards.demo_citizen_two_password', 'citizen-two-test-password-123');

        $this->seed(DemoAccountSeeder::class);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $citizen = User::where('email', 'citizen.one@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertSame('Sample Administrator', $admin->name);
        $this->assertSame('citizen', $citizen->role);
        $this->assertTrue(Hash::check('admin-test-password-123', $admin->password));
        $this->assertTrue(Hash::check('citizen-one-test-password-123', $citizen->password));
        $this->assertTrue(Hash::check('citizen-two-test-password-123', User::where('email', 'citizen.two@example.test')->firstOrFail()->password));
        $this->assertDatabaseCount('users', 3);
    }

    public function test_reseeding_keeps_existing_accounts_unchanged(): void
    {
        config()->set('hazards.demo_admin_password', 'admin-test-password-123');
        config()->set('hazards.demo_citizen_one_password', 'citizen-one-test-password-123');
        config()->set('hazards.demo_citizen_two_password', 'citizen-two-test-password-123');
        $existing = User::factory()->create(['email' => 'admin@example.test']);

        $this->seed(DemoAccountSeeder::class);

        $existing->refresh();
        $this->assertSame('citizen', $existing->role);
        $this->assertTrue(Hash::check('password', $existing->password));
        $this->assertDatabaseCount('users', 3);
    }
}
