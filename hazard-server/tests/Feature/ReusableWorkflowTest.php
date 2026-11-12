<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\HazardReport;
use App\Models\User;
use App\Services\AccountMfa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReusableWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_departments_require_admin_and_preserve_category_and_report_history(): void
    {
        $this->seed();
        $citizen = User::factory()->create();
        $this->authenticateUser($citizen);
        $this->postJson('/api/admin/departments', ['id' => 'health', 'name' => 'Health Office'])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->forceFill(['mfa_secret' => 'JBSWY3DPEHPK3PXP', 'mfa_confirmed_at' => now()])->save();
        $this->authenticateUser($admin);
        $this->postJson('/api/admin/departments', ['id' => 'health', 'name' => 'Health Office'])->assertCreated();
        $this->patchJson('/api/admin/departments/mdrrmo', ['name' => 'Disaster Office', 'is_active' => false])->assertUnprocessable()->assertJsonValidationErrors('is_active');
        $this->patchJson('/api/admin/categories/accident/routing', ['department_id' => 'health'])->assertOk();
        $this->assertSame('health', Category::findOrFail('accident')->department_id);
        $this->patchJson('/api/admin/departments/health', ['name' => 'Community Health Office', 'is_active' => true])->assertOk();
        $this->seed();
        $this->assertSame('health', Category::findOrFail('accident')->department_id);
        $this->assertSame('Community Health Office', Department::findOrFail('health')->name);
        $this->patchJson('/api/admin/departments/general', ['name' => 'General Services', 'is_active' => false])->assertUnprocessable();
        $report = HazardReport::create(['reference_number' => 'HZ-ROUTING-HISTORY', 'user_id' => $citizen->id, 'category_id' => 'accident', 'department_id' => 'health', 'title' => 'Synthetic previous assignment', 'description' => 'Synthetic history retained after office archival.', 'address' => 'Synthetic Street', 'barangay' => 'Anambongan', 'selected_latitude' => 15.864, 'selected_longitude' => 120.399, 'location_source' => 'manual']);
        $this->patchJson('/api/admin/categories/accident/routing', ['department_id' => 'mdrrmo'])->assertOk();
        $this->patchJson('/api/admin/departments/health', ['name' => 'Community Health Office', 'is_active' => false])->assertOk();
        $this->assertSame('health', $report->fresh()->department_id);
        $this->assertFalse(Department::findOrFail('health')->is_active);
        $this->patchJson('/api/admin/categories/accident/routing', ['department_id' => 'health'])->assertUnprocessable();
    }

    public function test_one_seed_command_loads_csv_accounts_and_preserves_existing_passwords(): void
    {
        config(['hazards.demo_admin_password' => 'synthetic-admin-password', 'hazards.demo_citizen_one_password' => 'synthetic-citizen-one', 'hazards.demo_citizen_two_password' => 'synthetic-citizen-two']);
        $this->seed();
        $this->assertSame(3, User::count());
        $this->assertSame(10, Category::count());
        $this->assertSame(5, Department::count());
        $this->assertSame(4, DB::table('badges')->count());
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $hash = $admin->password;
        config(['hazards.demo_admin_password' => 'different-admin-password']);
        $this->seed();
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertSame(3, User::count());
    }

    public function test_saved_code_resets_once_revokes_sessions_and_preserves_admin_mfa(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->forceFill(['mfa_secret' => 'JBSWY3DPEHPK3PXP', 'mfa_confirmed_at' => now()])->save();
        $codes = app(AccountMfa::class)->recoveryCodes($user);
        $user->createToken('old');
        $payload = ['email' => $user->email, 'code' => $codes[0], 'password' => 'new-isolated-password', 'password_confirmation' => 'new-isolated-password'];
        $this->postJson('/api/auth/recovery/code', $payload)->assertOk()->assertJsonMissingPath('user');
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertTrue($user->fresh()->mfa_enabled);
        $this->assertSame(0, $user->tokens()->count());
        $this->postJson('/api/auth/recovery/code', $payload)->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertCount(9, $user->fresh()->mfa_recovery_codes);
    }

    public function test_partial_sample_configuration_rolls_back_reference_writes(): void
    {
        config(['hazards.demo_admin_password' => 'synthetic-admin-password', 'hazards.demo_citizen_one_password' => null, 'hazards.demo_citizen_two_password' => null]);
        try {
            $this->seed();
            $this->fail('Partial credential configuration must reject the seed command.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('three different', $error->getMessage());
        }
        $this->assertSame(0, User::count());
        $this->assertSame(0, Department::count());
        $this->assertSame(0, Category::count());
    }

    public function test_recovery_errors_do_not_expose_accounts_and_lockout_applies_to_codes(): void
    {
        $user = User::factory()->create();
        $codes = app(AccountMfa::class)->recoveryCodes($user);
        $payload = ['email' => $user->email, 'code' => 'aaaaaaaaaa-bbbbbbbbbb', 'password' => 'new-isolated-password', 'password_confirmation' => 'new-isolated-password'];
        $unknown = $this->postJson('/api/auth/recovery/code', $payload + [])->assertUnprocessable()->json('errors.code');
        $payload['email'] = 'unknown@example.test';
        $this->assertSame($unknown, $this->postJson('/api/auth/recovery/code', $payload)->assertUnprocessable()->json('errors.code'));
        $payload['email'] = $user->email;
        for ($i = 0; $i < 4; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.1.'.($i + 1)]);
            $this->postJson('/api/auth/recovery/code', $payload)->assertUnprocessable();
        }
        $payload['code'] = $codes[0];
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.1.10']);
        $this->postJson('/api/auth/recovery/code', $payload)->assertUnprocessable();
        $this->assertCount(10, $user->fresh()->mfa_recovery_codes);
        $this->postJson('/api/auth/register', [])->assertUnprocessable()->assertJsonPath('code', 'validation_error')->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_citizen_can_create_saved_codes_after_recent_verification(): void
    {
        $user = User::factory()->create();
        $this->authenticateUser($user);
        $this->postJson('/api/security/recovery-codes')->assertOk()->assertJsonCount(10, 'recovery_codes');
        $this->assertCount(10, $user->fresh()->mfa_recovery_codes);
        $this->assertFalse($user->fresh()->mfa_enabled);
    }
}
