<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountOnboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizens_and_stale_admin_sessions_cannot_manage_administrators(): void
    {
        $citizen = User::factory()->create();
        $this->authenticateUser($citizen);
        $this->getJson('/api/admin/accounts')->assertForbidden();
        $this->postJson('/api/admin/accounts', [])->assertForbidden();
        $this->deleteJson('/api/admin/accounts/'.$citizen->id)->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        DB::table('personal_access_tokens')->where('tokenable_id', $admin->id)->update(['reauthenticated_at' => now()->subHour()]);
        $this->getJson('/api/admin/accounts')->assertStatus(428);
        $this->postJson('/api/admin/accounts', [])->assertStatus(428);
    }

    public function test_new_admin_requires_mfa_email_and_policies_but_no_id_and_cannot_take_over_an_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        $data = ['name' => 'Replacement Administrator', 'email' => 'replacement@example.test', 'password' => 'synthetic-new-private-password', 'identity_verified' => true, 'local_email_verified_at' => now()];
        $this->postJson('/api/admin/accounts', $data)->assertCreated()->assertJsonMissingPath('password');
        $new = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame('admin', $new->role);
        $this->assertTrue(Hash::check($data['password'], $new->password));
        $this->assertNull($new->local_email_verified_at);
        $this->assertFalse($new->mfa_enabled);
        $this->assertFalse(app(AccountOnboarding::class)->state($new)['account_ready']);
        $this->assertDatabaseCount('identity_verifications', 1); // Only original synthetic admin fixture.
        $this->postJson('/api/admin/accounts', $data)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->getJson('/api/admin/accounts')->assertOk()->assertJsonMissingPath('data.0.password')->assertJsonMissingPath('data.0.mfa_secret');
        $this->postJson('/api/admin/accounts/'.$new->id.'/enrollment')->assertOk();
    }

    public function test_self_or_last_admin_cannot_be_removed_and_other_admin_removal_revokes_credentials(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        $this->deleteJson('/api/admin/accounts/'.$admin->id)->assertConflict();
        $target = User::factory()->create(['role' => 'admin']);
        $token = $target->createToken('synthetic-old-device')->plainTextToken;
        $this->deleteJson('/api/admin/accounts/'.$target->id)->assertOk();
        $this->assertSoftDeleted('users', ['id' => $target->id, 'is_active' => false]);
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('admin_account_actions', ['actor_id' => $admin->id, 'target_id' => $target->id, 'action' => 'deleted']);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_unverified_email_or_current_policy_still_blocks_admin_access_without_an_id_requirement(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'local_email_verified_at' => now()]);
        $this->authenticateUser($admin, false);
        $this->getJson('/api/admin/accounts')->assertForbidden()->assertJsonPath('account_verification_required', true);
        app(AccountOnboarding::class)->accept($admin);
        $this->getJson('/api/admin/accounts')->assertOk();
        $this->getJson('/api/auth/me')->assertJsonPath('identity_verified', false)->assertJsonPath('account_ready', true);
        $this->assertDatabaseCount('identity_verifications', 0);
    }
}
