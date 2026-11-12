<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PasswordInput;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

        DB::transaction(function () use ($adminEmail, $adminPassword, $citizenOnePassword, $citizenTwoPassword) {
            foreach ([
                ['Sample Administrator', $adminEmail, 'admin', $adminPassword],
                ['Sample Citizen One', 'citizen.one@example.test', 'citizen', $citizenOnePassword],
                ['Sample Citizen Two', 'citizen.two@example.test', 'citizen', $citizenTwoPassword],
            ] as [$name, $email, $role, $password]) {
                if (User::where('email', $email)->exists()) {
                    $this->command?->warn("Account already exists; kept unchanged: {$email}");

                    continue;
                }

                $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
                $user->role = $role;
                if ($role === 'admin') {
                    $user->mfa_enrollment_until = now()->addMinutes(15);
                }
                $user->save();
                $this->command?->info("Created {$role}: {$email}");
            }
        });
    }
}
