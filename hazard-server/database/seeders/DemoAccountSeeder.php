<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        if (count(array_filter($passwords, fn ($password) => is_string($password) && strlen($password) >= 12 && strlen($password) <= 128)) !== 3
            || count(array_unique($passwords)) !== 3) {
            throw new RuntimeException('Set three different HAZARD_DEMO_*_PASSWORD values of 12–128 characters in .env.');
        }

        foreach ([
            ['Municipal Administrator', 'itsajhon@gmail.com', 'admin', $adminPassword],
            ['Sample Citizen One', 'citizen.one@example.test', 'citizen', $citizenOnePassword],
            ['Sample Citizen Two', 'citizen.two@example.test', 'citizen', $citizenTwoPassword],
        ] as [$name, $email, $role, $password]) {
            if (User::where('email', $email)->exists()) {
                $this->command?->warn("Account already exists; kept unchanged: {$email}");

                continue;
            }

            $user = new User(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
            $user->role = $role;
            $user->save();
            $this->command?->info("Created {$role}: {$email}");
        }
    }
}
