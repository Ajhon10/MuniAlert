<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountRecovery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckSecurityConfiguration extends Command
{
    protected $signature = 'hazard:security-check {--json : Return check results without configuration values} {--phase=1 : Configuration phase: 1 or 2}';

    protected $description = 'Read-only phased configuration checks; not a full security or deployment approval';

    public function handle(): int
    {
        if (! in_array($this->option('phase'), ['1', '2'], true)) {
            $this->error('Choose phase 1 or 2.');

            return self::FAILURE;
        }
        $key = config('app.key');
        $decodedKey = is_string($key) && str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : false;
        $origins = config('cors.allowed_origins', []);
        $checks = [
            ['environment', 'Production environment selected', app()->environment('production')],
            ['debug', 'Debug disabled in stored configuration', config('app.debug') === false],
            ['app_url', 'Application URL is an HTTPS origin', $this->httpsOrigin(config('app.url'))],
            ['app_key', 'Non-placeholder AES-256 application key configured', is_string($decodedKey) && strlen($decodedKey) === 32 && $decodedKey !== str_repeat("\0", 32)],
            ['session', 'Secure HttpOnly same-site session cookies configured', config('session.secure') === true && config('session.http_only') === true && in_array(config('session.same_site'), ['lax', 'strict'], true)],
            ['database', 'MySQL with a dedicated non-root account', config('database.default') === 'mysql' && is_string(config('database.connections.mysql.username')) && trim(config('database.connections.mysql.username')) !== '' && strtolower(trim(config('database.connections.mysql.username'))) !== 'root'],
            ['evidence', 'Private evidence storage selected', in_array(config('hazards.photo_disk'), ['evidence', 'r2'], true)],
            ['cors', 'Explicit HTTPS web origins and fixed native origins only', is_array($origins) && count($origins) > 0 && collect($origins)->every(fn ($origin) => in_array($origin, ['https://localhost', 'capacitor://localhost'], true) || $this->httpsOrigin($origin)) && config('cors.allowed_origins_patterns') === []],
            ['password_hash', 'Bcrypt work factor is at least 12', config('hashing.driver') === 'bcrypt' && (int) config('hashing.bcrypt.rounds') >= 12],
            ['demo_credentials', 'Demo passwords absent from deployment', ! config('hazards.demo_admin_password') && ! config('hazards.demo_citizen_one_password') && ! config('hazards.demo_citizen_two_password')],
        ];
        if ($this->option('phase') === '2') {
            try {
                $schema = Schema::hasColumn('users', 'mfa_enrollment_until') && Schema::hasTable('security_challenges');
                $adminsReady = $schema && User::where('role', 'admin')->where('is_active', true)->exists()
                    && ! User::where('role', 'admin')->where('is_active', true)->whereNull('mfa_confirmed_at')->exists();
            } catch (\Throwable) {
                $schema = $adminsReady = false;
            }
            $file = public_path('app/security-policy.json');
            $policy = is_file($file) ? (json_decode(file_get_contents($file), true)['policy'] ?? '') : '';
            $checks = [...$checks,
                ['security_schema', 'Account security migrations applied', $schema],
                ['admin_enrollment', 'Active administrators have completed MFA enrollment', $adminsReady],
                ['csp_build', 'Built web policy forbids inline/evaluated scripts and framing', is_string($policy) && str_contains($policy, "script-src 'self';") && str_contains($policy, "frame-ancestors 'none'") && ! str_contains($policy, 'unsafe-eval')],
                ['security_mail', 'Verified-email recovery has a real configured mail transport', app(AccountRecovery::class)->available()],
                ['upload_scanner', 'Private upload scanner selected; service acceptance remains separate', config('security.scan_uploads') === true],
                ['event_retention', 'Bounded security event retention configured', config('security.event_retention_days') >= 1 && config('security.event_retention_days') <= 90],
            ];
        }
        $result = [
            'scope' => 'Phase '.$this->option('phase').' configuration only; real hosting, mail/scanner delivery, backup restore and municipal acceptance require separate verification.',
            'passed' => collect($checks)->every(fn ($check) => $check[2]),
            'checks' => array_map(fn ($check) => ['id' => $check[0], 'description' => $check[1], 'passed' => $check[2]], $checks),
        ];
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->line($result['scope']);
            foreach ($result['checks'] as $check) {
                $this->line(($check['passed'] ? 'PASS ' : 'FAIL ').$check['description']);
            }
        }

        return $result['passed'] ? self::SUCCESS : self::FAILURE;
    }

    private function httpsOrigin(mixed $value): bool
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($value);

        return $parts !== false && ($parts['scheme'] ?? '') === 'https' && ! str_contains($parts['host'] ?? '', '*')
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && ! isset($parts['query']) && ! isset($parts['fragment']) && in_array($parts['path'] ?? '', ['', '/'], true);
    }
}
