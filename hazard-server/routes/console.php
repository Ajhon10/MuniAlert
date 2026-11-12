<?php

use App\Services\SecurityMonitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

// Invoke Artisan in this PHP process: shared hosting may disable proc_open.
Schedule::call(function () {
    Cache::store('database')->put('security.scheduler.heartbeat', now()->timestamp, 600);
    if (config('security.monitor_enabled')) {
        $result = app(SecurityMonitor::class)->inspect();
        if (! $result['healthy']) {
            logger()->warning('Security operational monitor requires attention.', $result);
        }
    }
})->name('security-operational-monitor')->everyMinute();
Schedule::call(fn () => Artisan::call('sanctum:prune-expired', ['--hours' => 24]))
    ->name('sanctum:prune-expired')->daily();
Schedule::call(fn () => DB::table('device_tokens')->where('updated_at', '<', now()->subDays(90))->delete())->daily();
Schedule::call(fn () => DB::table('social_auth_attempts')->where('expires_at', '<', now()->subDay())->delete())->name('social-auth-cleanup')->daily();

Schedule::call(function () {
    $ids = DB::table('security_challenges')->where('expires_at', '<', now()->subDay())->limit(1000)->pluck('id');
    DB::table('security_challenges')->whereIn('id', $ids)->delete();
    $events = DB::table('security_events')->where('created_at', '<', now()->subDays(config('security.event_retention_days')))->limit(1000)->pluck('id');
    DB::table('security_events')->whereIn('id', $events)->delete();
})->name('security-records-cleanup')->daily();

Schedule::call(function () {
    $disk = Storage::disk('quarantine');
    foreach (array_slice($disk->allFiles(), 0, 1000) as $file) {
        if ($disk->lastModified($file) < now()->subDay()->timestamp) {
            $disk->delete($file);
        }
    }
})->name('security-quarantine-cleanup')->daily();

if (config('image_screening.enabled')) {
    Schedule::call(function () {
        if (Artisan::call('hazard:work-image-screening') !== 0) {
            throw new RuntimeException('Image screening batch failed.');
        }
    })->name('hazard:work-image-screening')->everyMinute();
}

if (config('mail.enabled')) {
    Schedule::call(function () {
        if (Artisan::call('hazard:work-security-mail') !== 0) {
            throw new RuntimeException('Security mail batch failed.');
        }
    })->name('hazard:work-security-mail')->everyMinute();
}

if (config('hazards.scheduled_push')) {
    Schedule::call(function () {
        $exit = Artisan::call('hazard:work-push');
        echo Artisan::output();
        if ($exit !== 0) {
            throw new RuntimeException('Scheduled notification batch failed.');
        }
    })->name('hazard:work-push')->everyMinute();
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
