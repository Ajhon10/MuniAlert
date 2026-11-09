<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Process\Process;
use Tests\Fixtures\RecordScheduledPush;
use Tests\TestCase;

class ScheduledPushWorkerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['hazards.fcm_project_id' => 'synthetic-project', 'hazards.fcm_credentials' => 'synthetic-config']);
    }

    public function test_each_invocation_drains_a_bounded_batch_and_releases_its_lock(): void
    {
        for ($index = 0; $index < 4; $index++) {
            Queue::connection('report-push')->push(new RecordScheduledPush);
        }

        $this->artisan('hazard:work-push')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertSame(3, DB::table('municipal_settings')->where('key', 'like', 'synthetic-worker-%')->count());
        $this->artisan('hazard:work-push')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(4, DB::table('municipal_settings')->where('key', 'like', 'synthetic-worker-%')->count());
    }

    public function test_an_overlapping_invocation_retains_all_queued_work(): void
    {
        Queue::connection('report-push')->push(new RecordScheduledPush);
        $lock = Cache::store('database')->lock('bantay:scheduled-push-worker', 180);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('hazard:work-push')->expectsOutput('Another scheduled notification batch is still running.')->assertSuccessful();
            $this->assertDatabaseCount('jobs', 1);
            $this->assertSame(0, DB::table('municipal_settings')->where('key', 'like', 'synthetic-worker-%')->count());
        } finally {
            $lock->release();
        }
    }

    public function test_expired_lock_can_recover_after_a_killed_cron_process(): void
    {
        Queue::connection('report-push')->push(new RecordScheduledPush);
        Cache::store('database')->lock('bantay:scheduled-push-worker', 1)->get();
        $this->travel(2)->seconds();
        $this->artisan('hazard:work-push')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(1, DB::table('municipal_settings')->where('key', 'like', 'synthetic-worker-%')->count());
    }

    public function test_missing_push_configuration_does_not_discard_pending_jobs(): void
    {
        config(['hazards.fcm_project_id' => null, 'hazards.fcm_credentials' => null]);
        Queue::connection('report-push')->push(new RecordScheduledPush);
        $this->artisan('hazard:work-push')->expectsOutput('Push is not configured; queued notifications were retained.')->assertSuccessful();
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_scheduler_enables_push_only_in_scheduled_hosting_mode(): void
    {
        foreach (['false', 'true'] as $enabled) {
            // Each scheduler boots once per real cron process, with its own env.
            $process = new Process([PHP_BINARY, 'artisan', 'schedule:list', '--no-ansi'], base_path(), [
                'APP_ENV' => 'testing', 'HAZARD_SCHEDULED_PUSH' => $enabled,
                'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'CACHE_STORE' => 'array',
            ]);
            $process->setTimeout(15);
            $process->run();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $this->assertSame($enabled === 'true', str_contains($process->getOutput(), 'hazard:work-push'));
        }
    }
}
