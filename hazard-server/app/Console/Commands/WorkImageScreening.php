<?php

namespace App\Console\Commands;

use App\Services\LocalImageDetector;
use App\Support\SecurityLock;
use Illuminate\Console\Command;

class WorkImageScreening extends Command
{
    protected $signature = 'hazard:work-image-screening';

    protected $description = 'Run a bounded batch of private, loopback-only image checks';

    public function handle(LocalImageDetector $detector): int
    {
        if (! $detector->configured()) {
            $this->info('Local detector is not configured; no photos were transmitted.');

            return self::SUCCESS;
        }
        $lock = SecurityLock::make('bantay:image-screening-worker', 180);
        if (! $lock->get()) {
            return self::SUCCESS;
        }
        try {
            return $this->call('queue:work', ['connection' => 'image-screening', '--queue' => 'image-screening', '--stop-when-empty' => true, '--max-jobs' => 3, '--max-time' => 30, '--sleep' => 1, '--tries' => 1, '--timeout' => 15, '--memory' => 128]);
        } finally {
            $lock->release();
        }
    }
}
