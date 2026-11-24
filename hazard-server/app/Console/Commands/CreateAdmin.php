<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'hazard:admin {email?}';

    protected $description = 'Retired: initial accounts are created by deployment seeders';

    public function handle(): int
    {
        $this->error('Use db:seed for the initial administrator, then Settings > Administrator accounts to manage administrators.');

        return self::FAILURE;
    }
}
