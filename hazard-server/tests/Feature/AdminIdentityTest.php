<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountOnboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity');
        config(['onboarding.contact' => 'privacy@example.test', 'security.recovery_enabled' => true, 'security.frontend_url' => 'https://municipal.example.test', 'mail.enabled' => true]);
    }

    private function pendingAdmin(): array
    {
        $user = User::factory()->create(['role' => 'admin', 'local_email_verified_at' => now()]);
        $this->authenticateUser($user, false);
        $this->postJson('/api/account/policies', ['accepted' => true, 'version' => config('onboarding.policy_version')])->assertOk();
        $id = $this->postJson('/api/account/identity', ['image' => UploadedFile::fake()->image('synthetic-admin.jpg', 100, 100), 'document_type' => 'passport', 'consent' => true, 'version' => config('onboarding.policy_version')])->assertOk()->assertJsonPath('account_verified', false)->json('identity_id');

        return [$user, $id];
    }

    public function test_admin_without_optional_id_can_administer_but_cannot_self_approve(): void
    {
        [$admin, $id] = $this->pendingAdmin();
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('role', 'admin')->assertJsonPath('account_verified', false)->assertJsonPath('account_ready', true);
        $this->getJson('/api/profile')->assertOk();
        foreach (['/api/admin/reports', '/api/admin/stats', '/api/admin/analytics?from=2026-11-01&to=2026-11-24&group=day', '/api/admin/identities', '/api/notifications'] as $path) {
            $this->getJson($path)->assertOk();
        }
        $this->putJson('/api/admin/settings', [])->assertUnprocessable();
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => true])->assertForbidden();
        $this->getJson('/api/admin/identities/'.$id.'/image')->assertOk();
        $this->getJson('/api/account/verification')->assertOk()->assertJsonPath('identity_status', 'pending');
        $this->postJson('/api/auth/logout')->assertOk();
    }

    public function test_another_admin_can_approve_optional_badge_and_withdrawal_keeps_access(): void
    {
        [$admin, $id] = $this->pendingAdmin();
        $reviewer = User::factory()->verifiedReporter()->create(['role' => 'admin']);
        $this->authenticateUser($reviewer);
        $this->get('/api/admin/identities/'.$id.'/image')->assertOk();
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => true])->assertOk();
        $this->assertDatabaseHas('identity_verifications', ['id' => $id, 'reviewed_by' => $reviewer->id, 'review_method' => 'administrator', 'path' => null]);
        $this->authenticateUser($admin->fresh(), false);
        $this->getJson('/api/account/verification')->assertJsonPath('can_administer', true)->assertJsonPath('can_report', false);
        $this->getJson('/api/admin/identities')->assertOk();
        $this->deleteJson('/api/account/identity')->assertOk()->assertJsonPath('can_administer', true)->assertJsonPath('identity_verified', false);
        $this->getJson('/api/admin/identities')->assertOk();
    }

    public function test_operator_bootstrap_requires_pending_admin_consent_email_and_independent_attestation(): void
    {
        [$admin, $id] = $this->pendingAdmin();
        $question = 'Are you the designated municipal operator, and have you independently checked this administrator in person against their original Philippine government photo ID and matching account name?';
        $this->artisan('hazard:review-admin-identity', ['email' => $admin->email])->expectsConfirmation($question, 'no')->assertFailed();
        $this->assertDatabaseHas('identity_verifications', ['id' => $id, 'status' => 'pending']);
        $this->artisan('hazard:review-admin-identity', ['email' => $admin->email])->expectsConfirmation($question, 'yes')->assertSuccessful();
        $this->assertDatabaseHas('identity_verifications', ['id' => $id, 'status' => 'approved', 'review_method' => 'operator_in_person', 'reviewed_by' => null, 'path' => null]);
        $this->assertDatabaseHas('security_events', ['event' => 'operator_admin_identity_approved', 'user_id' => $admin->id]);
        $this->assertCount(0, Storage::disk('identity')->allFiles());
        $this->assertTrue(app(AccountOnboarding::class)->state($admin)['can_administer']);
        $this->artisan('hazard:review-admin-identity', ['email' => $admin->email])->assertFailed();
    }

    public function test_operator_cannot_silently_approve_a_citizen_or_an_admin_with_changed_identity(): void
    {
        $citizen = User::factory()->create();
        $this->artisan('hazard:review-admin-identity', ['email' => $citizen->email])->assertFailed();
        [$admin, $id] = $this->pendingAdmin();
        $admin->forceFill(['local_email_verified_at' => null])->save();
        $question = 'Are you the designated municipal operator, and have you independently checked this administrator in person against their original Philippine government photo ID and matching account name?';
        $this->artisan('hazard:review-admin-identity', ['email' => $admin->email])->expectsConfirmation($question, 'yes')->assertFailed();
        $this->assertDatabaseHas('identity_verifications', ['id' => $id, 'status' => 'pending']);
    }

    public function test_name_change_removes_badge_and_policy_change_revokes_admin_access(): void
    {
        $admin = User::factory()->verifiedReporter()->create(['role' => 'admin']);
        $this->authenticateUser($admin, false);
        $this->getJson('/api/admin/identities')->assertOk();
        $this->patchJson('/api/profile', ['name' => 'Changed administrator name', 'email' => $admin->email])->assertOk()->assertJsonPath('user.account_verified', false);
        $this->getJson('/api/admin/identities')->assertOk();
        $this->getJson('/api/account/verification')->assertOk()->assertJsonPath('identity_status', 'name_changed');
        config(['onboarding.policy_version' => 'changed-policy']);
        $this->getJson('/api/admin/identities')->assertForbidden()->assertJsonPath('account_verification_required', true);
    }
}
