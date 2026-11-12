<?php

namespace Tests\Feature;

use App\Jobs\SendReportEmail;
use App\Mail\ReportStatusUpdate;
use App\Models\HazardReport;
use App\Models\User;
use App\Services\AccountMailDelivery;
use App\Services\AccountRecovery;
use App\Services\ReportUpdates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountPreferencesMailTest extends TestCase
{
    use RefreshDatabase;

    private function preferences(int $size = 165): array
    {
        return ['textSize' => $size, 'boldText' => true, 'highContrast' => false, 'readingSpace' => true, 'reduceMotion' => true, 'keepMessages' => false];
    }

    public function test_preferences_are_private_bound_to_the_current_account_and_persist_across_sign_in(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->putJson('/api/preferences/reading', [])->assertUnauthorized();
        $this->authenticateUser($owner);
        $this->putJson('/api/preferences/reading', ['account_id' => $other->id, 'preferences' => $this->preferences()])->assertForbidden();
        $this->putJson('/api/preferences/reading', ['account_id' => $owner->id, 'preferences' => $this->preferences()])->assertOk()->assertJsonPath('preferences.textSize', 165);
        $this->getJson('/api/auth/me')->assertJsonPath('reading_preferences.textSize', 165)->assertJsonPath('reading_preferences.boldText', true);
        $this->assertNull($other->fresh()->reading_preferences);
        $this->authenticateUser($other);
        $this->getJson('/api/auth/me')->assertJsonPath('reading_preferences', null);
        // A delayed write from the former account cannot overwrite this one.
        $this->putJson('/api/preferences/reading', ['account_id' => $owner->id, 'preferences' => $this->preferences(200)])->assertForbidden();
        $this->authenticateUser($owner->fresh());
        $this->getJson('/api/auth/me')->assertJsonPath('reading_preferences.textSize', 165);
    }

    public function test_invalid_preferences_are_rejected_without_changes_and_admin_personal_settings_need_no_step_up(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->authenticateUser($admin);
        DB::table('personal_access_tokens')->where('tokenable_id', $admin->id)->update(['reauthenticated_at' => now()->subHour()]);
        foreach ([['textSize' => 166], ['textSize' => 205], ['boldText' => 'not-a-boolean'], ['unexpected' => 'value']] as $invalid) {
            $this->putJson('/api/preferences/reading', ['account_id' => $admin->id, 'preferences' => [...$this->preferences(), ...$invalid]])->assertUnprocessable();
        }
        $this->assertNull($admin->fresh()->reading_preferences);
        $this->putJson('/api/preferences/reading', ['account_id' => $admin->id, 'preferences' => $this->preferences(200)])->assertOk();
        $this->putJson('/api/preferences/email', ['account_id' => $admin->id, 'email_report_updates' => true])->assertOk();
        $this->assertTrue($admin->fresh()->email_report_updates);
        $this->putJson('/api/preferences/email', ['account_id' => $admin->id + 1, 'email_report_updates' => false])->assertForbidden();
    }

    public function test_report_emails_are_opt_in_verified_transactional_id_only_and_rechecked_at_delivery(): void
    {
        $this->seed();
        config(['security.recovery_enabled' => true, 'security.frontend_url' => 'https://municipal.example.test', 'mail.enabled' => true]);
        Mail::fake();
        $owner = User::factory()->create(['email_report_updates' => true, 'local_email_verified_at' => now()]);
        $report = HazardReport::create(['user_id' => $owner->id, 'reference_number' => 'HZ-SYNTHETIC-MAIL', 'category_id' => 'fire', 'department_id' => 'fire', 'title' => 'PRIVATE original title', 'description' => 'PRIVATE original details', 'address' => 'PRIVATE address', 'barangay' => 'Anambongan', 'selected_latitude' => 15.864, 'selected_longitude' => 120.399, 'location_source' => 'manual', 'status' => 'verified']);
        DB::beginTransaction();
        app(ReportUpdates::class)->notify($report, 'PRIVATE municipal response note.');
        $this->assertDatabaseCount('jobs', 1);
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('notifications', 0);
        DB::transaction(fn () => app(ReportUpdates::class)->notify($report, 'PRIVATE municipal response note.'));
        $payload = DB::table('jobs')->first()->payload;
        $this->assertStringNotContainsString('PRIVATE', $payload);
        $this->assertStringNotContainsString($owner->email, $payload);
        $id = DB::table('notifications')->first()->id;
        (new SendReportEmail($id))->handle(app(AccountMailDelivery::class), app(AccountRecovery::class));
        Mail::assertSent(ReportStatusUpdate::class, function ($mail) use ($owner) {
            $this->assertStringNotContainsString('PRIVATE', $mail->render());

            return $mail->hasTo($owner->email) && $mail->reference === 'HZ-SYNTHETIC-MAIL' && $mail->status === 'Verified';
        });
        Mail::fake();
        $owner->forceFill(['email_report_updates' => false])->save();
        (new SendReportEmail($id))->handle(app(AccountMailDelivery::class), app(AccountRecovery::class));
        Mail::assertNothingSent();
        $owner->forceFill(['email_report_updates' => true, 'local_email_verified_at' => null])->save();
        (new SendReportEmail($id))->handle(app(AccountMailDelivery::class), app(AccountRecovery::class));
        Mail::assertNothingSent();
        $owner->forceFill(['local_email_verified_at' => now()])->save();
        $report->delete();
        (new SendReportEmail($id))->handle(app(AccountMailDelivery::class), app(AccountRecovery::class));
        Mail::assertNothingSent();
    }

    public function test_real_mail_readiness_rejects_log_transport_missing_credentials_sample_addresses_and_disabled_delivery(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $delivery = app(AccountMailDelivery::class);
        config(['mail.enabled' => true, 'mail.default' => 'log']);
        $this->assertFalse($delivery->available());
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.gmail.com', 'mail.mailers.smtp.username' => 'sender@gmail.com', 'mail.mailers.smtp.password' => null, 'mail.from.address' => 'sender@gmail.com']);
        $this->assertFalse($delivery->available());
        config(['mail.mailers.smtp.password' => 'synthetic-separate-credential', 'mail.mailers.smtp.require_tls' => true]);
        $this->assertTrue($delivery->available());
        $this->assertFalse($delivery->recipientAllowed('admin@example.test'));
        config(['mail.enabled' => false]);
        $this->assertFalse($delivery->available());
    }
}
