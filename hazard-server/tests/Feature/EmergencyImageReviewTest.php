<?php

namespace Tests\Feature;

use App\Jobs\ScreenReportImage;
use App\Models\HazardReport;
use App\Models\User;
use App\Services\AdminReportAlerts;
use App\Services\LocalImageDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmergencyImageReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('evidence');
        config(['hazards.photo_disk' => 'evidence', 'image_screening.enabled' => false]);
    }

    private function submit(User $owner, bool $urgent = true): HazardReport
    {
        $this->authenticateUser($owner);
        $response = $this->postJson('/api/reports', ['category_id' => 'fire', 'title' => 'PRIVATE emergency title', 'description' => 'PRIVATE original eyewitness description.', 'address' => 'PRIVATE exact address', 'barangay' => 'Anambongan', 'selected_latitude' => 15.864, 'selected_longitude' => 120.399, 'location_source' => 'manual', 'location_confirmed' => true, 'severity' => 'high', 'assistance_needed' => $urgent, 'photos' => [UploadedFile::fake()->image('private-personal-name.png', 50, 50)]])->assertCreated()->assertJsonPath('data.assistance_needed', $urgent);
        $response->assertJsonMissingPath('data.photos.0.authenticity');

        return HazardReport::findOrFail($response->json('data.id'));
    }

    private function factCheck(): array
    {
        return ['confirmed_happened' => true, 'method' => 'responder_confirmation', 'verification_note' => 'The municipal responder independently confirmed this synthetic incident.', 'verified_latitude' => 15.864, 'verified_longitude' => 120.399];
    }

    public function test_submission_alerts_only_active_admins_and_single_read_is_owned_role_scoped_and_requires_no_step_up(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $inactive = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $member = User::factory()->create();
        $report = $this->submit($member);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $inactive->id]);
        $this->getJson('/api/notifications')->assertJsonCount(0);
        $this->authenticateUser($admin);
        $inbox = $this->getJson('/api/notifications')->assertOk()->assertJsonCount(1)->assertJsonPath('0.data.assistance_needed', true)->assertJsonPath('0.data.category_id', 'fire')->assertDontSee('PRIVATE')->assertDontSee($member->email);
        $notice = $inbox->json('0.id');
        DB::table('personal_access_tokens')->where('tokenable_id', $admin->id)->update(['reauthenticated_at' => now()->subHour()]);
        $this->patchJson('/api/notifications/'.$notice.'/read')->assertOk();
        $this->getJson('/api/notifications')->assertJsonPath('0.read_at', fn ($value) => $value !== null);
        $this->authenticateUser($otherAdmin);
        $this->patchJson('/api/notifications/'.$notice.'/read')->assertNotFound();
        $this->getJson('/api/notifications')->assertJsonPath('0.read_at', null);
        $this->authenticateUser($member);
        $this->patchJson('/api/notifications/'.$notice.'/read')->assertNotFound();
        $this->getJson('/api/reports')->assertJsonCount(0, 'data');
        $this->assertFalse($report->fresh()->isCommunityVisible());
    }

    public function test_new_incident_images_need_explicit_admin_review_and_private_analysis_does_not_reach_reporters(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->submit($owner);
        $photo = $report->photos()->firstOrFail();
        $this->assertTrue($photo->authenticity_review_required);
        $this->assertSame('not_assessed', $photo->ai_status);
        $url = '/api/admin/reports/'.$report->id.'/photos/'.$photo->id.'/review';
        $review = ['assessment_version' => 0, 'verdict' => 'accepted_evidence', 'review_note' => 'PRIVATE: photo checked against an independent municipal responder record.', 'confirmed_reviewed' => true];
        $this->postJson($url, $review)->assertForbidden();
        $this->authenticateUser($admin);
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', $this->factCheck())->assertUnprocessable();
        $this->postJson($url, [...$review, 'confirmed_reviewed' => false])->assertUnprocessable();
        $this->postJson($url, [...$review, 'assessment_version' => 1])->assertStatus(409);
        $this->postJson($url, [...$review, 'verdict' => 'suspected_ai'])->assertOk();
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', $this->factCheck())->assertUnprocessable();
        $this->postJson($url, $review)->assertOk();
        $this->getJson('/api/reports/'.$report->id)->assertJsonPath('data.photos.0.authenticity.review.verdict', 'accepted_evidence');
        $this->postJson('/api/admin/reports/'.$report->id.'/verify', $this->factCheck())->assertOk();
        $this->assertDatabaseHas('audit_logs', ['hazard_report_id' => $report->id, 'action' => 'image_reviewed']);
        $this->authenticateUser($owner);
        $this->getJson('/api/reports/'.$report->id)->assertJsonMissingPath('data.photos.0.authenticity')->assertDontSee('PRIVATE: photo checked');
        $this->getJson('/api/reports')->assertJsonMissingPath('data.0.assistance_needed')->assertJsonPath('data.0.photos', []);
    }

    public function test_local_detector_is_loopback_only_and_rejects_redirects_malformed_scores_and_raw_error_leaks(): void
    {
        $detector = app(LocalImageDetector::class);
        Http::preventStrayRequests();
        config(['image_screening.enabled' => true]);
        foreach (['https://detector.example.com/classify', 'http://localhost:8099/classify', 'file:///private/image', 'http://127.0.0.1:8099/classify?token=secret', 'http://user:secret@127.0.0.1/classify'] as $endpoint) {
            config(['image_screening.endpoint' => $endpoint]);
            $this->assertFalse($detector->configured());
        }
        config(['image_screening.endpoint' => 'http://127.0.0.1:8099/classify']);
        $this->assertTrue($detector->configured());
        Http::fake(['127.0.0.1:8099/*' => Http::response(['ai_probability' => .92, 'model' => 'synthetic-model@test'])]);
        $this->assertSame('possible_ai', $detector->assess('synthetic-private-image')['ai_status']);
        foreach ([Http::response('', 302, ['Location' => 'https://public.example.com']), Http::response(['ai_probability' => '0.9', 'model' => 'bad']), Http::response(['ai_probability' => 2, 'model' => 'bad']), Http::response('PRIVATE raw error', 500)] as $response) {
            Http::swap(new Factory);
            Http::preventStrayRequests();
            Http::fake(['127.0.0.1:8099/*' => $response]);
            try {
                $detector->assess('PRIVATE image');
                $this->fail('Invalid detector output was accepted.');
            } catch (\RuntimeException $error) {
                $this->assertSame('Local image assessment failed.', $error->getMessage());
                $this->assertNull($error->getPrevious());
            }
        }
    }

    public function test_scheduled_image_job_has_id_only_transactional_payload_and_a_stale_result_cannot_overwrite_manual_review(): void
    {
        config(['image_screening.enabled' => true, 'image_screening.endpoint' => 'http://127.0.0.1:8099/classify']);
        $owner = User::factory()->create();
        $report = $this->submit($owner, false);
        $photo = $report->photos()->firstOrFail();
        $this->assertSame('queued', $photo->ai_status);
        $payload = DB::table('jobs')->where('queue', 'image-screening')->first()->payload;
        $this->assertStringNotContainsString('PRIVATE', $payload);
        $this->assertStringNotContainsString($owner->email, $payload);
        Http::fake(function () use ($photo) {
            $photo->forceFill(['ai_status' => 'not_assessed', 'assessment_version' => 1, 'reviewed_assessment_version' => 1, 'review_verdict' => 'accepted_evidence', 'reviewed_at' => now(), 'review_note' => 'Independent manual review completed while the detector was still busy.'])->save();

            return Http::response(['ai_probability' => .95, 'model' => 'synthetic-late-model']);
        });
        (new ScreenReportImage($photo->id))->handle(app(LocalImageDetector::class));
        $this->assertNull($photo->fresh()->ai_probability);
        $this->assertSame('accepted_evidence', $photo->fresh()->review_verdict);
        $this->assertSame(1, $photo->fresh()->assessment_version);
    }

    public function test_detector_failure_is_inconclusive_not_genuine_and_pending_manual_review_cancels_the_job(): void
    {
        config(['image_screening.enabled' => true, 'image_screening.endpoint' => 'http://127.0.0.1:8099/classify']);
        $owner = User::factory()->create();
        $report = $this->submit($owner);
        $photo = $report->photos()->firstOrFail();
        Http::fake(['127.0.0.1:8099/*' => Http::response('', 503)]);
        (new ScreenReportImage($photo->id))->handle(app(LocalImageDetector::class));
        $this->assertSame('unavailable', $photo->fresh()->ai_status);
        $this->assertNull($photo->fresh()->ai_probability);
        $photo->refresh()->forceFill(['ai_status' => 'queued'])->save();
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $this->postJson('/api/admin/reports/'.$report->id.'/photos/'.$photo->id.'/review', ['assessment_version' => 1, 'verdict' => 'inconclusive', 'review_note' => 'Further independent checking is needed before accepting this image.', 'confirmed_reviewed' => true])->assertOk();
        $this->assertSame('not_assessed', $photo->fresh()->ai_status);
        $this->assertSame(2, $photo->fresh()->reviewed_assessment_version);
        Http::preventStrayRequests();
        (new ScreenReportImage($photo->id))->handle(app(LocalImageDetector::class));
        $this->assertNull($photo->fresh()->ai_probability);
    }

    public function test_directory_preserves_optional_service_area_and_coordinates_and_rejects_incomplete_positions(): void
    {
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        $settings = ['map' => ['latitude' => 15.864, 'longitude' => 120.399, 'zoom' => 13], 'hotlines' => [['name' => 'Synthetic rescue agency', 'number' => '0000000000', 'service' => 'rescue', 'area' => 'Anambongan', 'latitude' => 15.865, 'longitude' => 120.4]]];
        $this->putJson('/api/admin/settings', $settings)->assertOk()->assertJsonPath('hotlines.0.area', 'Anambongan');
        $this->getJson('/api/settings')->assertJsonPath('hotlines.0.latitude', 15.865);
        unset($settings['hotlines'][0]['longitude']);
        $this->putJson('/api/admin/settings', $settings)->assertUnprocessable();
        $settings['hotlines'][0]['longitude'] = 200;
        $this->putJson('/api/admin/settings', $settings)->assertUnprocessable();
    }

    public function test_advisory_scores_are_saved_without_automatically_accepting_or_rejecting_the_report(): void
    {
        config(['image_screening.enabled' => true, 'image_screening.endpoint' => 'http://127.0.0.1:8099/classify']);
        $report = $this->submit(User::factory()->create());
        $photo = $report->photos()->firstOrFail();
        foreach ([[.91, 'possible_ai'], [.08, 'low_signal'], [.55, 'inconclusive']] as [$score, $expected]) {
            $photo->refresh()->forceFill(['ai_status' => 'queued'])->save();
            Http::swap(new Factory);
            Http::fake(['127.0.0.1:8099/*' => Http::response(['ai_probability' => $score, 'model' => 'synthetic-detector@revision1'])]);
            (new ScreenReportImage($photo->id))->handle(app(LocalImageDetector::class));
            $this->assertSame($expected, $photo->fresh()->ai_status);
            $this->assertEquals($score, $photo->fresh()->ai_probability);
            $this->assertNull($photo->fresh()->reviewed_at);
            $this->assertNull($report->fresh()->fact_checked_at);
            $this->assertSame('submitted', $report->fresh()->status);
        }
    }

    public function test_failed_submission_rolls_back_admin_alerts_and_image_jobs_and_removes_prepared_evidence(): void
    {
        config(['image_screening.enabled' => true, 'image_screening.endpoint' => 'http://127.0.0.1:8099/classify']);
        User::factory()->create(['role' => 'admin']);
        $this->mock(AdminReportAlerts::class)->shouldReceive('submitted')->once()->andThrow(new \RuntimeException('Synthetic failure before transaction commit.'));
        $this->authenticateUser(User::factory()->create());
        $this->postJson('/api/reports', ['category_id' => 'fire', 'title' => 'Synthetic rollback report', 'description' => 'Synthetic transaction rollback verification.', 'address' => 'Test Road', 'barangay' => 'Anambongan', 'selected_latitude' => 15.864, 'selected_longitude' => 120.399, 'location_source' => 'manual', 'location_confirmed' => true, 'severity' => 'high', 'photos' => [UploadedFile::fake()->image('test.png', 50, 50)]])->assertStatus(500);
        $this->assertDatabaseCount('hazard_reports', 0);
        $this->assertDatabaseCount('hazard_photos', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame([], Storage::disk('evidence')->allFiles());
    }
}
