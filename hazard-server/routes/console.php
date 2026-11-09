<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

// Invoke Artisan in this PHP process: shared hosting may disable proc_open.
Schedule::call(fn () => Artisan::call('sanctum:prune-expired', ['--hours' => 24]))
    ->name('sanctum:prune-expired')->daily();
Schedule::call(fn () => DB::table('device_tokens')->where('updated_at', '<', now()->subDays(90))->delete())->daily();
Schedule::call(fn () => DB::table('social_auth_attempts')->where('expires_at', '<', now()->subDay())->delete())->name('social-auth-cleanup')->daily();

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
