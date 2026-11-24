<?php

namespace Tests\Feature;

use App\Jobs\SendSecurityMail;
use App\Mail\AccountSecurityLink;
use App\Models\HazardReport;
use App\Models\User;
use App\Services\AccountMfa;
use App\Services\AccountOnboarding;
use App\Services\AccountRecovery;
use App\Services\UploadScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReporterVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('identity');
        config(['onboarding.contact' => 'privacy@example.test', 'security.recovery_enabled' => true,
            'security.frontend_url' => 'https://municipal.example.test', 'mail.enabled' => true]);
    }

    private function registration(array $extra = []): array
    {
        return array_replace(['name' => 'Synthetic Citizen', 'email' => 'new@example.test', 'password' => 'synthetic-long-password',
            'accepted' => true, 'policy_version' => config('onboarding.policy_version')], $extra);
    }

    private function applicant(): User
    {
        $user = User::factory()->create(['local_email_verified_at' => now()]);
        app(AccountOnboarding::class)->accept($user);
        $this->authenticateUser($user);

        return $user;
    }

    private function upload(array $extra = []): array
    {
        return array_replace(['image' => UploadedFile::fake()->image('private-id.jpg', 300, 180), 'document_type' => 'philsys',
            'consent' => true, 'version' => config('onboarding.policy_version')], $extra);
    }

    public function test_registration_requires_current_policy_acceptance_and_does_not_trust_verification_fields(): void
    {
        $this->postJson('/api/auth/register', $this->registration(['accepted' => false]))->assertUnprocessable()->assertJsonValidationErrors('accepted');
        $this->postJson('/api/auth/register', $this->registration(['policy_version' => 'old']))->assertUnprocessable()->assertJsonValidationErrors('policy_version');
        $this->assertDatabaseCount('users', 0);
        $response = $this->postJson('/api/auth/register', $this->registration(['role' => 'admin', 'local_email_verified_at' => now(), 'identity_status' => 'approved']))->assertCreated()->assertJsonPath('user.role', 'citizen');
        $this->assertNull(User::find($response->json('user.id'))->local_email_verified_at);
        $this->assertDatabaseCount('policy_acceptances', 1);
        $this->assertDatabaseCount('identity_verifications', 0);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertStringNotContainsString('new@example.test', DB::table('jobs')->value('payload'));
        $this->withToken($response->json('token'))->postJson('/api/reports', [])->assertForbidden()->assertJsonPath('verification_required', true);
    }

    public function test_signup_verification_email_is_queued_and_link_is_single_use_expiring_and_account_bound(): void
    {
        Mail::fake();
        $this->postJson('/api/auth/register', $this->registration())->assertCreated();
        $challenge = DB::table('security_challenges')->where('purpose', 'verify')->first();
        $token = $challenge->id.'.'.Crypt::decryptString($challenge->payload);
        (new SendSecurityMail($challenge->id))->handle(app(AccountRecovery::class), app(AccountMfa::class));
        Mail::assertSent(AccountSecurityLink::class, fn ($mail) => $mail->hasTo('new@example.test'));
        $this->postJson('/api/auth/recovery/consume', ['token' => $token, 'purpose' => 'verify'])->assertOk();
        $this->assertNotNull(User::first()->local_email_verified_at);
        $this->postJson('/api/auth/recovery/consume', ['token' => $token, 'purpose' => 'verify'])->assertUnprocessable();
        $this->travel(31)->minutes();
        app(AccountRecovery::class)->request('new@example.test', 'verify');
        $challenge = DB::table('security_challenges')->where('consumed', false)->first();
        $token = $challenge->id.'.'.Crypt::decryptString($challenge->payload);
        User::first()->forceFill(['email' => 'changed@example.test', 'local_email_verified_at' => null])->save();
        $this->postJson('/api/auth/recovery/consume', ['token' => $token, 'purpose' => 'verify'])->assertUnprocessable();
        $this->assertNull(User::first()->local_email_verified_at);
        User::first()->forceFill(['email' => 'new@example.test'])->save();
        $this->travel(31)->minutes();
        $this->postJson('/api/auth/recovery/consume', ['token' => $token, 'purpose' => 'verify'])->assertUnprocessable();
    }

    public function test_unconfigured_mail_never_claims_verification_or_allows_reporting(): void
    {
        config(['mail.enabled' => false]);
        $response = $this->postJson('/api/auth/register', $this->registration())->assertCreated();
        $this->assertDatabaseCount('jobs', 0);
        $this->withToken($response->json('token'))->getJson('/api/account/verification')->assertOk()->assertJsonPath('mail_available', false)->assertJsonPath('can_report', false);
        $this->postJson('/api/reports', [])->assertForbidden();
        $this->seed();
        $report = HazardReport::create(['reference_number' => 'HZ-SYNTHETIC-GUARD', 'user_id' => User::first()->id, 'category_id' => 'fire', 'department_id' => 'fire', 'title' => 'Synthetic report', 'description' => 'Synthetic confirmation guard report', 'address' => 'Test road', 'barangay' => 'Anambongan', 'selected_latitude' => 15.864, 'selected_longitude' => 120.399, 'location_source' => 'manual']);
        $this->postJson('/api/reports/'.$report->id.'/confirm')->assertForbidden();
    }

    public function test_verified_email_and_current_policies_allow_reporting_without_any_id_or_privacy_contact(): void
    {
        $this->seed();
        Storage::fake('evidence');
        config(['hazards.photo_disk' => 'evidence', 'onboarding.contact' => null]);
        $user = $this->applicant();
        $this->getJson('/api/account/verification')->assertOk()->assertJsonPath('can_report', true)
            ->assertJsonPath('identity_verified', false)->assertJsonPath('identity_available', false);
        $this->postJson('/api/reports', ['category_id' => 'tree', 'title' => 'Synthetic optional identity report',
            'description' => 'A synthetic tree report submitted without any identity document.', 'address' => 'Synthetic Street', 'barangay' => 'Anambongan',
            'selected_latitude' => 15.864, 'selected_longitude' => 120.399, 'location_source' => 'manual', 'location_confirmed' => 1, 'severity' => 'moderate',
            'photos' => [UploadedFile::fake()->image('synthetic-hazard.jpg', 200, 120)]])->assertCreated();
        $this->assertDatabaseCount('identity_verifications', 0);
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('user.identity_verified', false);
    }

    public function test_one_id_requires_verified_email_current_policies_public_contact_and_explicit_consent(): void
    {
        $user = User::factory()->create();
        $this->authenticateUser($user);
        $this->postJson('/api/account/identity', $this->upload())->assertForbidden();
        $user->forceFill(['local_email_verified_at' => now()])->save();
        $this->postJson('/api/account/identity', $this->upload())->assertForbidden();
        $this->postJson('/api/account/policies', ['accepted' => true, 'version' => config('onboarding.policy_version')])->assertOk();
        config(['onboarding.contact' => null]);
        $this->postJson('/api/account/identity', $this->upload())->assertStatus(503);
        config(['onboarding.contact' => 'privacy@example.test']);
        // Independent invalid-input cases get fresh isolated array-cache buckets.
        Cache::flush();
        $this->postJson('/api/account/identity', $this->upload(['consent' => false]))->assertUnprocessable()->assertJsonValidationErrors('consent');
        $this->postJson('/api/account/identity', $this->upload(['document_type' => 'invented']))->assertUnprocessable();
        $this->postJson('/api/account/identity', $this->upload(['image' => UploadedFile::fake()->create('id.pdf', 10, 'application/pdf')]))->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson('/api/account/identity', $this->upload())->assertTooManyRequests();
        Cache::flush();
        $this->postJson('/api/account/identity', $this->upload(['image' => UploadedFile::fake()->image('large.jpg', 1921, 10)]))->assertUnprocessable();
        $this->postJson('/api/account/identity', $this->upload())->assertOk()->assertJsonPath('identity_status', 'pending')->assertJsonPath('can_report', true)->assertJsonPath('identity_verified', false);
        $this->postJson('/api/account/identity', $this->upload())->assertConflict();
        $this->assertCount(1, Storage::disk('identity')->allFiles());
    }

    public function test_private_encrypted_image_is_admin_only_step_up_protected_and_deleted_on_approval(): void
    {
        $user = $this->applicant();
        $response = $this->postJson('/api/account/identity', $this->upload())->assertOk();
        $id = $response->json('identity_id');
        $row = DB::table('identity_verifications')->first();
        $encrypted = Storage::disk('identity')->get($row->path);
        $this->assertStringNotContainsString('RIFF', $encrypted);
        $this->assertSame('image/webp', getimagesizefromstring(Crypt::decryptString($encrypted))['mime']);
        $this->getJson('/api/admin/identities')->assertForbidden();
        $this->getJson('/api/admin/identities/'.$id.'/image')->assertForbidden();
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => true])->assertForbidden();
        $other = User::factory()->create();
        $this->authenticateUser($other);
        $this->getJson('/api/account/verification')->assertJsonPath('identity_id', null);
        $this->deleteJson('/api/account/identity')->assertOk();
        Storage::disk('identity')->assertExists($row->path);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        DB::table('personal_access_tokens')->where('tokenable_id', $admin->id)->update(['reauthenticated_at' => now()->subHour()]);
        $this->getJson('/api/admin/identities/'.$id.'/image')->assertStatus(428);
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => true])->assertStatus(428);
        $this->authenticateUser($admin->fresh());
        $this->getJson('/api/admin/identities')->assertOk()->assertJsonPath('data.0.applicant_name', $user->name)->assertJsonMissingPath('data.0.path');
        $this->get('/api/admin/identities/'.$id.'/image')->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => false])->assertUnprocessable();
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => true])->assertOk();
        Storage::disk('identity')->assertMissing($row->path);
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => true])->assertConflict();
        $this->getJson('/api/admin/identities/'.$id.'/image')->assertNotFound();
        $this->authenticateUser($user);
        $this->getJson('/api/account/verification')->assertJsonPath('can_report', true)->assertJsonPath('identity_verified', true);
        $this->getJson('/api/profile')->assertJsonPath('user.identity_verified', true);
        $this->postJson('/api/reports', [])->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->patchJson('/api/profile', ['name' => 'Changed Name', 'email' => $user->email])->assertOk();
        $this->getJson('/api/account/verification')->assertJsonPath('identity_status', 'name_changed')->assertJsonPath('can_report', true)->assertJsonPath('identity_verified', false);
        $this->postJson('/api/reports', [])->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $this->patchJson('/api/profile', ['name' => $user->name, 'email' => $user->email])->assertOk();
        $this->getJson('/api/account/verification')->assertJsonPath('identity_status', 'name_changed')->assertJsonPath('can_report', true)->assertJsonPath('identity_verified', false);
    }

    public function test_rejection_withdrawal_and_expiry_remove_images_and_allow_safe_resubmission(): void
    {
        $user = $this->applicant();
        $id = $this->postJson('/api/account/identity', $this->upload())->json('identity_id');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'rejected', 'checked' => true])->assertUnprocessable();
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'rejected', 'checked' => true, 'reason' => 'unreadable'])->assertOk();
        $this->assertCount(0, Storage::disk('identity')->allFiles());
        $this->authenticateUser($user);
        $this->getJson('/api/account/verification')->assertJsonPath('identity_reason', 'unreadable')->assertJsonPath('can_report', true)->assertJsonPath('identity_verified', false);
        $this->postJson('/api/account/identity', $this->upload())->assertOk();
        $this->deleteJson('/api/account/identity')->assertOk()->assertJsonPath('identity_status', 'withdrawn');
        $this->assertCount(0, Storage::disk('identity')->allFiles());
        $id = $this->postJson('/api/account/identity', $this->upload())->assertOk()->json('identity_id');
        $this->travel(8)->days();
        $this->getJson('/api/account/verification')->assertJsonPath('identity_status', 'expired');
        $this->authenticateUser($admin);
        $this->getJson('/api/admin/identities/'.$id.'/image')->assertNotFound();
        $this->postJson('/api/admin/identities/'.$id.'/review', ['decision' => 'approved', 'checked' => true])->assertConflict();
        $this->artisan('hazard:prune-identity')->assertSuccessful();
        $this->assertCount(0, Storage::disk('identity')->allFiles());
        $this->assertDatabaseHas('identity_verifications', ['id' => $id, 'status' => 'expired', 'path' => null]);
    }

    public function test_policy_changes_and_email_changes_block_previously_approved_reporters(): void
    {
        $user = User::factory()->verifiedReporter()->create();
        $this->authenticateUser($user);
        $this->getJson('/api/account/verification')->assertJsonPath('can_report', true);
        config(['onboarding.policy_version' => 'new-version']);
        $this->postJson('/api/reports', [])->assertForbidden();
        $this->postJson('/api/account/policies', ['accepted' => true, 'version' => 'new-version'])->assertOk()->assertJsonPath('can_report', true);
        $this->patchJson('/api/profile', ['name' => $user->name, 'email' => 'replacement@example.test'])->assertOk();
        $this->authenticateUser($user->fresh());
        $this->getJson('/api/account/verification')->assertJsonPath('email_verified', false)->assertJsonPath('can_report', false);
    }

    public function test_sensitive_id_scanner_rejection_does_not_keep_unencrypted_quarantine_samples(): void
    {
        Storage::fake('quarantine');
        $this->applicant();
        config(['security.scan_uploads' => true]);
        $this->mock(UploadScanner::class)->shouldReceive('scan')->once()->andReturn('infected');
        $this->postJson('/api/account/identity', $this->upload())->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertCount(0, Storage::disk('identity')->allFiles());
        $this->assertCount(0, Storage::disk('quarantine')->allFiles());
        $this->assertDatabaseCount('identity_verifications', 0);
    }

    public function test_cleanup_removes_old_orphaned_encrypted_images_but_preserves_recent_files(): void
    {
        Storage::disk('identity')->put('old.enc', Crypt::encryptString('synthetic orphan'));
        Storage::disk('identity')->put('recent.enc', Crypt::encryptString('synthetic recent'));
        touch(Storage::disk('identity')->path('old.enc'), now()->subDays(8)->timestamp);
        $this->artisan('hazard:prune-identity')->assertSuccessful();
        Storage::disk('identity')->assertMissing('old.enc');
        Storage::disk('identity')->assertExists('recent.enc');
    }
}
