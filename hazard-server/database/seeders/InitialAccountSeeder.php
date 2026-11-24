<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PasswordInput;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class InitialAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Rerunning deployment seeds must never recreate a removed starter account
        // or change an existing account's credentials, role, consent or MFA.
        if (User::withTrashed()->where('role', 'admin')->exists()) {
            return;
        }
        $name = config('initial_accounts.name');
        $email = strtolower(trim((string) config('initial_accounts.email')));
        $password = config('initial_accounts.password');
        if (! filled($password)) {
            $this->command?->warn('No initial administrator created. Set private INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL and INITIAL_ADMIN_PASSWORD before deployment seeding.');

            return;
        }
        if (Validator::make(compact('name', 'email', 'password'), [
            'name' => 'required|string|min:2|max:80', 'email' => 'required|email|max:255|unique:users',
            'password' => ['required', ...PasswordInput::rules(true)],
        ])->fails() || (app()->environment('production') && (str_ends_with($email, '.test') || str_ends_with($email, '.example')))) {
            throw new RuntimeException('Initial administrator configuration is invalid. Use an unused reachable email and a private password of 12 to 72 bytes; configuration values are never printed.');
        }
        DB::transaction(function () use ($name, $email, $password) {
            $user = new User(compact('name', 'email', 'password'));
            $user->role = 'admin';
            // Provision immediately before the first login; an operator can
            // authorize another bounded window using hazard:account-security.
            $user->mfa_enrollment_until = now()->addMinutes(15);
            $user->save();
            $this->command?->info('Initial administrator created. Sign in, enroll authenticator MFA, verify email and accept the current policies.');
        });
    }
}
