<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityConfigurationTest extends TestCase
{
    public function test_local_configuration_fails_without_exposing_secret_values(): void
    {
        config(['app.key' => 'synthetic-sensitive-key', 'database.connections.mysql.password' => 'synthetic-sensitive-database-password']);
        $this->artisan('hazard:security-check')->expectsOutputToContain('FAIL Production environment selected')
            ->doesntExpectOutputToContain('synthetic-sensitive-key')->doesntExpectOutputToContain('synthetic-sensitive-database-password')->assertFailed();
    }

    public function test_production_baseline_passes_only_with_explicit_safe_configuration(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'app.debug' => false, 'app.url' => 'https://hazards.example.test', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'session.secure' => true, 'session.http_only' => true, 'session.same_site' => 'lax',
            'database.default' => 'mysql', 'database.connections.mysql.username' => 'dedicated_application_user',
            'hazards.photo_disk' => 'evidence', 'cors.allowed_origins' => ['https://hazards.example.test', 'https://localhost', 'capacitor://localhost'], 'cors.allowed_origins_patterns' => [],
            'hashing.driver' => 'bcrypt', 'hashing.bcrypt.rounds' => 12,
            'hazards.demo_admin_password' => null, 'hazards.demo_citizen_one_password' => null, 'hazards.demo_citizen_two_password' => null,
        ]);
        $this->artisan('hazard:security-check', ['--json' => true])->expectsOutputToContain('"passed": true')->assertSuccessful();
        config(['cors.allowed_origins' => ['*']]);
        $this->artisan('hazard:security-check')->expectsOutputToContain('FAIL Explicit HTTPS web origins')->assertFailed();
        config(['cors.allowed_origins' => ['https://hazards.example.test'], 'hazards.demo_admin_password' => 'synthetic-demo-password']);
        $this->artisan('hazard:security-check')->expectsOutputToContain('FAIL Demo passwords absent')->assertFailed();
    }
}
