<?php

namespace App\Console\Commands;

use App\Services\AccountMailDelivery;
use App\Support\SecurityLock;
use Illuminate\Console\Command;

class WorkSecurityMail extends Command
{
    protected $signature = 'hazard:work-security-mail';

    protected $description = 'Run a bounded private security-mail batch without a persistent daemon';

    public function handle(AccountMailDelivery $delivery): int
    {
        if (! $delivery->available()) {
            $this->info('Security mail is not configured; queued work was retained.');

            return self::SUCCESS;
        }
        $lock = SecurityLock::make('bantay:security-mail-worker', 180);
        if (! $lock->get()) {
            $this->info('Another security-mail batch is running.');

            return self::SUCCESS;
        }
        try {
            return $this->call('queue:work', ['connection' => 'security-mail', '--queue' => 'security-mail', '--stop-when-empty' => true,
                '--max-jobs' => 3, '--max-time' => 30, '--sleep' => 1, '--tries' => 3, '--timeout' => 25, '--memory' => 128]);
        } finally {
            $lock->release();
        }
    }
}
