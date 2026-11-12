<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Already-authenticated fixture for business tests; MFA flows have separate tests. */
    protected function authenticateUser(User $user): void
    {
        if ($user->role === 'admin' && ! $user->mfa_enabled) {
            $user->forceFill(['mfa_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', 'mfa_confirmed_at' => now()])->save();
        }
        $credential = $user->createToken('mobile', ['*'], now()->addDay());
        $credential->accessToken->forceFill(['security_version' => $user->security_version,
            'mfa_verified_at' => $user->mfa_enabled ? now() : null, 'reauthenticated_at' => now()])->save();
        $this->app['auth']->forgetGuards();
        $this->withToken($credential->plainTextToken);
    }

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
