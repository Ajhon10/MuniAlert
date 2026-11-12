<?php

namespace App\Console\Commands;

use App\Services\SecurityMonitor;
use Illuminate\Console\Command;

class MonitorSecurity extends Command
{
    protected $signature = 'hazard:security-monitor';

    protected $description = 'Read-only, redacted operational signals for an operator/monitoring collector';

    public function handle(SecurityMonitor $monitor): int
    {
        $result = $monitor->inspect();
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $result['healthy'] ? self::SUCCESS : self::FAILURE;
    }
}
