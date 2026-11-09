<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_local_health_identifies_the_workspace_without_exposing_its_path(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        DB::shouldReceive('select')->once()->with('SELECT 1')->andReturn([]);
        $workspace = strtolower(str_replace('\\', '/', dirname(base_path())));
        $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok'])
            ->assertHeader('X-Bantay-Dev', substr(hash('sha256', $workspace), 0, 16));
    }

    public function test_database_failure_has_a_safe_unavailable_response_and_retains_local_identity(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        DB::shouldReceive('select')->once()->with('SELECT 1')->andThrow(new RuntimeException('Synthetic internal database detail'));
        $this->getJson('/api/health')->assertStatus(503)->assertExactJson(['status' => 'unavailable'])->assertHeader('X-Bantay-Dev');
    }

    public function test_production_health_does_not_expose_the_local_workspace_identifier(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        DB::shouldReceive('select')->once()->with('SELECT 1')->andReturn([]);
        $this->getJson('/api/health')->assertOk()->assertHeaderMissing('X-Bantay-Dev');
    }
}
