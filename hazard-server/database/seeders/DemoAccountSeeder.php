<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\CsvData;
use App\Support\PasswordInput;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo accounts may only be seeded in local or testing environments.');
        }

        $adminPassword = config('hazards.demo_admin_password');
        $citizenOnePassword = config('hazards.demo_citizen_one_password');
        $citizenTwoPassword = config('hazards.demo_citizen_two_password');
        $passwords = [$adminPassword, $citizenOnePassword, $citizenTwoPassword];
        if (count(array_filter($passwords, fn ($password) => Validator::make(['password' => $password], ['password' => ['required', ...PasswordInput::rules(true)]])->passes())) !== 3
            || count(array_unique($passwords)) !== 3) {
            throw new RuntimeException('Set three different HAZARD_DEMO_*_PASSWORD values: at least 12 characters and at most 72 bytes in .env.');
        }
        $adminEmail = config('hazards.demo_admin_email');
        if (! is_string($adminEmail) || Validator::make(['email' => $adminEmail], ['email' => 'required|string|email|max:255'])->fails() || ! str_ends_with(strtolower(trim($adminEmail)), '.test')) {
            throw new RuntimeException('Set HAZARD_DEMO_ADMIN_EMAIL to a sample .test address, such as admin@example.test. Use the administrator provisioning command for a real account.');
        }
        $adminEmail = strtolower(trim($adminEmail));
        if (in_array($adminEmail, ['citizen.one@example.test', 'citizen.two@example.test'], true)) {
            throw new RuntimeException('The demo administrator and citizens must have different email addresses.');
        }

        $accounts = CsvData::read('sample_accounts.csv');
        if (count($accounts) !== 3 || count(array_unique(array_column($accounts, 'password_config'))) !== 3 || count(array_filter($accounts, fn ($row) => ($row['role'] ?? '') === 'admin')) !== 1) {
            throw new RuntimeException('The sample account CSV must contain one administrator and two citizens with distinct password configuration keys.');
        }
        $allowedKeys = ['demo_admin_password', 'demo_citizen_one_password', 'demo_citizen_two_password'];
        $emails = [];
        foreach ($accounts as &$account) {
            Validator::make($account, ['name' => 'required|string|min:2|max:80', 'email' => 'required|email|max:255', 'role' => 'required|in:admin,citizen', 'password_config' => ['required', Rule::in($allowedKeys)]])->validate();
            $account['email'] = $account['role'] === 'admin' ? $adminEmail : strtolower(trim($account['email']));
            if (! str_ends_with($account['email'], '.test') || in_array($account['email'], $emails, true)) {
                throw new RuntimeException('Sample CSV accounts must have distinct reserved .test addresses.');
            }
            $emails[] = $account['email'];
        }
        unset($account);
        DB::transaction(function () use ($accounts) {
            foreach ($accounts as $account) {
                $email = $account['email'];
                if (User::where('email', $email)->exists()) {
                    $this->command?->warn("Account already exists; kept unchanged: {$email}");

                    continue;
                }
                $user = new User(['name' => $account['name'], 'email' => $email, 'password' => config('hazards.'.$account['password_config'])]);
                $user->role = $account['role'];
                if ($user->role === 'admin') {
                    $user->mfa_enrollment_until = now()->addMinutes(15);
                }
                $user->save();
                $this->command?->info("Created {$user->role}: {$email}");
            }
        });
    }
}
