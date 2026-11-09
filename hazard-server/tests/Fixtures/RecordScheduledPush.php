<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordScheduledPush implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        DB::table('municipal_settings')->insert(['key' => 'synthetic-worker-'.Str::uuid(), 'value' => 'true', 'updated_at' => now()]);
    }
}
