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

    public function test_demo_accounts_have_the_expected_roles_and_passwords(): void
    {
        config()->set('hazards.demo_admin_password', 'admin-test-password-123');
        config()->set('hazards.demo_citizen_one_password', 'citizen-one-test-password-123');
        config()->set('hazards.demo_citizen_two_password', 'citizen-two-test-password-123');

        $this->seed(DemoAccountSeeder::class);

        $admin = User::where('email', 'itsajhon@gmail.com')->firstOrFail();
        $citizen = User::where('email', 'citizen.one@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
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
        $existing = User::factory()->create(['email' => 'itsajhon@gmail.com']);

        $this->seed(DemoAccountSeeder::class);

        $existing->refresh();
        $this->assertSame('citizen', $existing->role);
        $this->assertTrue(Hash::check('password', $existing->password));
        $this->assertDatabaseCount('users', 3);
    }
}
