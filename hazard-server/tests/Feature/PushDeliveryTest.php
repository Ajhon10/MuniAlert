<?php

namespace Tests\Feature;

use App\Jobs\SendReportPush;
use App\Models\HazardReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['hazards.fcm_project_id' => 'isolated-project', 'hazards.fcm_credentials' => 'unused-test-file']);
        Cache::put('fcm-access-'.hash('sha256', 'isolated-project'), 'synthetic-access-token', 60);
        Http::preventStrayRequests();
    }

    private function report(User $owner): HazardReport
    {
        return HazardReport::create(['reference_number' => 'HZ-PUSH-TEST', 'user_id' => $owner->id, 'category_id' => 'tree', 'department_id' => 'engineering', 'title' => 'Synthetic test hazard', 'description' => 'Isolated push delivery test.', 'address' => 'Test road', 'barangay' => 'Test barangay', 'selected_latitude' => 14.6, 'selected_longitude' => 121, 'location_source' => 'manual', 'status' => 'submitted']);
    }

    private function device(User $owner, string $name): int
    {
        return DB::table('device_tokens')->insertGetId(['user_id' => $owner->id, 'token' => $name, 'token_hash' => hash('sha256', $name), 'platform' => 'web', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_report_inbox_and_each_device_job_commit_and_rollback_together(): void
    {
        $owner = User::factory()->create();
        $report = $this->report($owner);
        $this->device($owner, 'synthetic-device-one');
        $this->device($owner, 'synthetic-device-two');
        $this->authenticateUser(User::factory()->create(['role' => 'admin']));
        DB::beginTransaction();
        $this->patchJson('/api/reports/'.$report->id, ['status' => 'under_verification', 'severity' => 'high', 'department_id' => 'engineering', 'note' => 'Reviewing synthetic report.'])->assertOk();
        $this->assertDatabaseCount('jobs', 2);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame('under_verification', $report->fresh()->status);
        Http::assertNothingSent();
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertSame('submitted', $report->fresh()->status);

        $this->patchJson('/api/reports/'.$report->id, ['status' => 'under_verification', 'severity' => 'high', 'department_id' => 'engineering', 'note' => 'Committed review update.'])->assertOk();
        $this->assertDatabaseCount('jobs', 2);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_retrying_one_device_does_not_resend_to_successful_devices(): void
    {
        $owner = User::factory()->create();
        $first = $this->device($owner, 'synthetic-success-device');
        $second = $this->device($owner, 'synthetic-retry-device');
        $attempt = 0;
        Http::fake(function ($request) use (&$attempt) {
            if ($request['message']['token'] === 'synthetic-success-device') {
                return Http::response(['name' => 'accepted']);
            }

            return ++$attempt === 1 ? Http::response(['error' => ['status' => 'UNAVAILABLE']], 503) : Http::response(['name' => 'accepted']);
        });
        (new SendReportPush($owner->id, 'synthetic-report', 'HZ-TEST', 'Verified', $first, 'synthetic-notice'))->handle();
        $retry = new SendReportPush($owner->id, 'synthetic-report', 'HZ-TEST', 'Verified', $second, 'synthetic-notice');
        try {
            $retry->handle();
            $this->fail('A provider failure must remain retryable.');
        } catch (RequestException $error) {
            $this->assertSame(503, $error->response->status());
        }
        $retry->handle();
        Http::assertSentCount(3);
        $this->assertCount(1, Http::recorded(fn ($request) => $request['message']['token'] === 'synthetic-success-device'));
        Http::assertSent(fn ($request) => $request['message']['data']['notification_id'] === 'synthetic-notice');
    }

    public function test_unregistered_and_reassigned_devices_are_not_delivered_again(): void
    {
        $owner = User::factory()->create();
        $device = $this->device($owner, 'synthetic-stale-device');
        Http::fake(['*' => Http::response(['error' => ['details' => [['errorCode' => 'UNREGISTERED']]]], 404)]);
        $job = new SendReportPush($owner->id, 'synthetic-report', 'HZ-TEST', 'Verified', $device, 'synthetic-notice');
        $job->handle();
        $this->assertDatabaseMissing('device_tokens', ['id' => $device]);
        $job->handle();
        $other = $this->device(User::factory()->create(), 'synthetic-other-user-device');
        (new SendReportPush($owner->id, 'synthetic-report', 'HZ-TEST', 'Verified', $other, 'synthetic-notice'))->handle();
        Http::assertSentCount(1);
    }
}
