<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AccountOnboarding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Already-reviewed synthetic accounts for incident tests; never a runtime bypass. */
class BrowserReporterSeeder extends Seeder
{
    public function run(): void
    {
        $driver = config('database.default');
        $database = config('database.connections.'.$driver.'.database');
        $sqlite = str_replace('\\', '/', dirname(base_path()).'/.cache/e2e.sqlite');
        if (! app()->environment('testing') || ! (($driver === 'sqlite' && str_replace('\\', '/', $database) === $sqlite)
            || ($driver === 'mysql' && $database === 'municipal_hazards_e2e'))) {
            throw new \RuntimeException('Reporter browser fixtures require the isolated E2E database.');
        }
        $email = env('E2E_VERIFIED_REPORTER_EMAIL');
        $email = is_string($email) ? strtolower(trim($email)) : null;
        $users = $email ? User::where('email', $email)->get()
            : User::whereIn('email', ['admin@munialert.test', 'resident1@munialert.test', 'resident2@munialert.test'])->get();
        if ($email && $users->count() !== 1) {
            throw new \RuntimeException('The synthetic reporter fixture was not found.');
        }
        foreach ($users as $user) {
            if (! Str::endsWith($user->email, ['@example.test', '@munialert.test'])) {
                throw new \RuntimeException('Reporter fixtures require synthetic addresses.');
            }
            $user->forceFill(['local_email_verified_at' => now()])->save();
            app(AccountOnboarding::class)->accept($user);
            DB::table('identity_verifications')->updateOrInsert(['user_id' => $user->id], [
                'id' => (string) Str::uuid(), 'applicant_name' => $user->name, 'document_type' => 'philsys', 'status' => 'approved', 'path' => null,
                'consent_version' => config('onboarding.policy_version'), 'consented_at' => now(), 'expires_at' => now()->addDays(7),
                'reviewed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
