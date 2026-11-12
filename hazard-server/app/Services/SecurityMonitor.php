<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SecurityMonitor
{
    /** Aggregate operational signals only: never attach account/incident data. */
    public function inspect(): array
    {
        try {
            DB::select('SELECT 1');
            $failures = DB::table('security_events')->whereIn('event', ['login_failed', 'mfa_failed', 'reauthentication_failed'])->where('created_at', '>=', now()->subMinutes(15))->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $oldJobs = DB::table('jobs')->whereIn('queue', ['push', 'security-mail'])->where('created_at', '<', now()->subMinutes(15)->timestamp)->count();
            $quarantine = count(Storage::disk('quarantine')->allFiles());
            $heartbeat = Cache::store('database')->get('security.scheduler.heartbeat');
            $checks = [
                ['id' => 'database', 'passed' => true],
                ['id' => 'authentication_failures', 'passed' => $failures < config('security.failure_alert_threshold')],
                ['id' => 'failed_jobs', 'passed' => $failedJobs === 0],
                ['id' => 'old_queue_jobs', 'passed' => $oldJobs === 0],
                ['id' => 'quarantine_capacity', 'passed' => $quarantine < 90],
                ['id' => 'scheduler', 'passed' => is_numeric($heartbeat) && (int) $heartbeat >= now()->subMinutes(3)->timestamp],
            ];

            return ['healthy' => collect($checks)->every(fn ($check) => $check['passed']), 'checks' => $checks,
                'counts' => ['recent_authentication_failures' => $failures, 'failed_jobs' => $failedJobs, 'old_queue_jobs' => $oldJobs, 'quarantine_files' => $quarantine],
                'external_delivery_verified' => false];
        } catch (\Throwable) {
            return ['healthy' => false, 'checks' => [['id' => 'monitor_dependencies', 'passed' => false]], 'external_delivery_verified' => false];
        }
    }
}
