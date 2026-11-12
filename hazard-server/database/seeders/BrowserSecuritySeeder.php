<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Independent synthetic browser scenarios only; never the maintainer database. */
class BrowserSecuritySeeder extends Seeder
{
    public function run(): void
    {
        $driver = config('database.default');
        $database = config('database.connections.'.$driver.'.database');
        $sqlite = str_replace('\\', '/', dirname(base_path()).'/.cache/e2e.sqlite');
        if (! app()->environment('testing') || ! (($driver === 'sqlite' && str_replace('\\', '/', $database) === $sqlite)
            || ($driver === 'mysql' && $database === 'municipal_hazards_e2e'))) {
            throw new \RuntimeException('Browser security fixtures require the isolated E2E database.');
        }
        $secret = env('E2E_ADMIN_MFA_SECRET');
        if (! is_string($secret) || ! preg_match('/^[A-Z2-7]{32}$/D', $secret)) {
            throw new \RuntimeException('Missing synthetic browser MFA configuration.');
        }
        $user = User::where('email', 'admin@example.test')->firstOrFail();
        $user->forceFill(['mfa_secret' => $secret, 'mfa_confirmed_at' => now(), 'mfa_last_step' => null])->save();
        $code = env('E2E_ADMIN_RECOVERY_CODE');
        if (! is_string($code) || ! preg_match('/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/D', $code)) {
            throw new \RuntimeException('Missing synthetic recovery fixture.');
        }
        $user->forceFill(['mfa_recovery_codes' => [hash('sha256', $code)]])->save();
    }
}
