<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $config = $app->make('config');
        $driver = $config->get('database.default');
        $database = $config->get('database.connections.'.$driver.'.database');
        // Runs before RefreshDatabase traits can migrate/reset a schema.
        if (! $app->environment('testing') || ! (
            ($driver === 'sqlite' && $database === ':memory:')
            || ($driver === 'mysql' && $database === 'municipal_hazards_test')
        )) {
            throw new \RuntimeException('Refusing tests outside the isolated in-memory or municipal_hazards_test database.');
        }

        return $app;
    }
}
