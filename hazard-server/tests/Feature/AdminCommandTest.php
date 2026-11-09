<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    public function test_markdown_email_is_rejected_without_connecting_to_the_database(): void
    {
        DB::shouldReceive('connection')->never();

        $this->artisan('hazard:admin', [
            'email' => '[admin@example.test](mailto:admin@example.test)',
        ])
            ->expectsOutput('Enter a plain email address, for example: admin@example.test')
            ->assertFailed();
    }
}
