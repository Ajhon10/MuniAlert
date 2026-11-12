<?php

namespace Tests\Feature;

use App\Services\SecurityMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_detects_failures_queue_and_stale_cron_without_private_records(): void
    {
        Storage::fake('quarantine');
        $this->assertFalse(app(SecurityMonitor::class)->inspect()['healthy']);
        Cache::store('database')->put('security.scheduler.heartbeat', now()->timestamp, 600);
        $this->assertTrue(app(SecurityMonitor::class)->inspect()['healthy']);
        config(['security.failure_alert_threshold' => 5]);
        for ($i = 0; $i < 5; $i++) {
            DB::table('security_events')->insert(['event' => 'mfa_failed', 'created_at' => now(), 'source_hash' => 'private-source-marker']);
        }
        DB::table('jobs')->insert(['queue' => 'security-mail', 'payload' => 'private-queue-marker', 'attempts' => 0, 'available_at' => now()->timestamp, 'created_at' => now()->subMinutes(16)->timestamp]);
        $result = app(SecurityMonitor::class)->inspect();
        $this->assertFalse($result['healthy']);
        $this->assertSame(5, $result['counts']['recent_authentication_failures']);
        $this->assertSame(1, $result['counts']['old_queue_jobs']);
        $this->assertStringNotContainsString('private-source-marker', json_encode($result));
        $this->assertStringNotContainsString('private-queue-marker', json_encode($result));
        $this->artisan('hazard:security-monitor')->assertFailed();
        $this->travel(16)->minutes();
        $this->assertFalse(app(SecurityMonitor::class)->inspect()['healthy']);
    }
}
