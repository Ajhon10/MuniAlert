<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SecurityEvents;
use App\Services\SecuritySessions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageAccountSecurity extends Command
{
    protected $signature = 'hazard:account-security {email} {--suspend} {--resume} {--role=} {--reset-mfa} {--authorize-mfa}';

    protected $description = 'Operator-only suspension, role changes or approved MFA recovery; revokes existing credentials';

    public function handle(SecuritySessions $sessions, SecurityEvents $events): int
    {
        $role = $this->option('role');
        if (count(array_filter([$this->option('suspend'), $this->option('resume'), $role, $this->option('reset-mfa'), $this->option('authorize-mfa')])) !== 1
            || ($role && ! in_array($role, ['citizen', 'admin'], true)) || ! filter_var($this->argument('email'), FILTER_VALIDATE_EMAIL)) {
            $this->error('Supply one action and a plain email address.');

            return self::FAILURE;
        }
        if (! $this->confirm('Has the municipality approved this account change and, for MFA recovery, independently verified the owner?')) {
            return self::FAILURE;
        }
        $changed = DB::transaction(function () use ($sessions, $events, $role) {
            // Lock all active administrators in stable order to protect the last
            // administrator against competing demotion/suspension operations.
            $admins = User::where('role', 'admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $user = User::where('email', strtolower(trim($this->argument('email'))))->lockForUpdate()->first();
            if (! $user) {
                return false;
            }
            if ($this->option('authorize-mfa') && ($user->role !== 'admin' || $user->mfa_enabled)) {
                return false;
            }
            if ($user->role === 'admin' && $user->is_active && $admins->count() <= 1 && ($this->option('suspend') || $role === 'citizen')) {
                return false;
            }
            if ($role) {
                $user->role = $role;
            }
            if ($this->option('suspend')) {
                $user->is_active = false;
            }
            if ($this->option('resume')) {
                $user->is_active = true;
            }
            if ($this->option('reset-mfa')) {
                $user->forceFill(['mfa_secret' => null, 'mfa_confirmed_at' => null, 'mfa_last_step' => null, 'mfa_recovery_codes' => null, 'mfa_failures' => 0, 'mfa_locked_until' => null]);
            }
            if ($this->option('reset-mfa') || $this->option('authorize-mfa')) {
                $user->mfa_enrollment_until = now()->addMinutes(15);
            }
            $sessions->invalidate($user);
            $events->record($this->option('reset-mfa') ? 'operator_mfa_reset' : ($this->option('authorize-mfa') ? 'operator_mfa_authorized' : ($role ? 'role_changed' : ($this->option('suspend') ? 'account_suspended' : 'account_resumed'))), $user);

            return true;
        });
        if (! $changed) {
            $this->error('No change made. Check the account and last-active-administrator rule.');

            return self::FAILURE;
        }
        $this->info('Account updated. Previous credentials are revoked. Administrator access requires MFA.');

        return self::SUCCESS;
    }
}
