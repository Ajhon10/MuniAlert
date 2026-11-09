<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WorkPush extends Command
{
    protected $signature = 'hazard:work-push';

    protected $description = 'Drain a bounded notification batch for scheduled Web/Cloud hosting';

    public function handle(): int
    {
        if (! config('hazards.fcm_project_id') || ! config('hazards.fcm_credentials')) {
            $this->info('Push is not configured; queued notifications were retained.');

            return self::SUCCESS;
        }

        // Use a shared database lock, including when hPanel invokes this command
        // directly. A killed cron process releases its claim through expiry.
        $lock = Cache::store('database')->lock('bantay:scheduled-push-worker', 180);
        if (! $lock->get()) {
            $this->info('Another scheduled notification batch is still running.');

            return self::SUCCESS;
        }

        try {
            return $this->call('queue:work', [
                'connection' => 'report-push',
                '--queue' => 'default',
                '--stop-when-empty' => true,
                '--max-jobs' => 3,
                '--max-time' => 30,
                '--sleep' => 1,
                '--tries' => 3,
                '--timeout' => 25,
                '--memory' => 128,
            ]);
        } finally {
            $lock->release();
        }
    }
}
