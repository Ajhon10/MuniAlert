<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    public function test_retired_creation_command_redirects_to_seeder_without_connecting_to_database(): void
    {
        DB::shouldReceive('connection')->never();
        // Laravel 12.73 inspects existing connections during test teardown;
        // this read must not open a connection or weaken the assertion above.
        DB::shouldReceive('getConnections')->andReturn([]);

        $this->artisan('hazard:admin', [
            'email' => '[admin@example.test](mailto:admin@example.test)',
        ])
            ->expectsOutput('Use db:seed for the initial administrator, then Settings > Administrator accounts to manage administrators.')
            ->assertFailed();
    }
}
