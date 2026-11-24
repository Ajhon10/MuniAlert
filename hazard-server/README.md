# hazard-server

Laravel 12, Sanctum, and MySQL API for MuniAlert. It lives in the `hazard-server` folder of the public Ajhon10/MuniAlert workspace; the Vue client is in the sibling `hazard-client` folder. Start with [team setup](../documentation/TEAM_TESTING.md).

## Local development

Requires PHP compatible with the lockfile, Composer 2, MySQL, and Node 22.12+ for the development launcher. Use workstation PHP 8.5 (`C:/php85/php.exe`) for Composer and Artisan. Use a separate MySQL schema named `municipal_hazards`; the schema is created in MySQL Workbench or another database client, while Laravel migrations create its tables.

For a new installation, from this server folder in PowerShell:

```powershell
composer install
if (!(Test-Path .env)) {
    Copy-Item .env.example .env
}
# New installation only:
php artisan key:generate
```

GitHub does not contain a real `.env`. Copy `.env.example` and generate your own app key for a fresh installation; keep your existing `.env`/key when updating. On PowerShell, copy only if `.env` does not exist. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` using your own local MySQL connection. The password belongs to the MySQL user. An owner's SQL dump, `.env` or app key is unnecessary for a new development database. Follow [the root collaborator quickstart](../documentation/COLLABORATOR_QUICKSTART.md) for the complete first-install sequence.

If **Workbench → Test Connection** says **Access denied**, resolve the local MySQL account/password/allowed-host issue first. The MuniAlert sample password does not authenticate MySQL, and changing `DB_PASSWORD` does not reset a MySQL account. Follow [the Workbench checklist](../documentation/COLLABORATOR_QUICKSTART.md#4b-get-workbench-test-connection-working-first). When Workbench connects, match that connection in `.env`, run `php artisan config:clear` and `php artisan db:show --database=mysql` from this server folder, and continue only after it shows the intended schema.

Run these one at a time, stopping if a command fails:

```sh
php artisan config:clear
php artisan migrate --seed
php artisan hazard:admin admin@example.test
composer run serve
```

New incident evidence is private and does not need `storage:link`; use that command only for separately required legacy public assets. The development launcher selects PHP 8.5 on Windows, reuses an existing healthy API from this workspace, and binds port 8000 without silently falling back to 8001. From Git Bash, invoke Composer with `PATH="/c/php85:$PATH" composer run serve`. The API runs at `http://127.0.0.1:8000`; from the repository root prefer `npm run dev` to start both API and client on 5173. Run `composer test` for isolated Laravel tests. Never run destructive test migrations against the application database.

Migration `2026_10_02_000002` adds admin-managed map/hotline settings, milestone badge definitions, confirmed-report news reactions/comments, and soft deletion for a reporter's own unverified report. Run `php artisan migrate` after updating an existing installation. The seeded hotline list is empty until an administrator publishes locally approved numbers in the client CMS. Reward descriptions are informational; the API does not pay cash or deliver gifts.

## Local demo accounts

The old shared demo logins are retired. Initial accounts now use the normal deployment seeder. Configure private INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL and INITIAL_ADMIN_PASSWORD, then run `php artisan db:seed` (add `--force` in production). The sample local identity is Municipal Administrator / admin@munialert.test; production requires your real admin email. No password is published in source. See [initial administrator setup](../documentation/TEST_ACCOUNTS.md).

Sign in within the 15-minute authenticator enrollment window; an operator can renew it using `php artisan hazard:account-security admin@your-domain.example --authorize-mfa`. Complete email verification and current policies. ID verification is optional and earns a profile badge. Use Settings > Administrator accounts for later admins; seeding preserves all existing accounts and never recreates a deleted starter.

## Persistent sessions

Use AUTH_PERSISTENT_LOGIN=true (default) to keep native logins until explicit logout or security revocation. Browser logins register a revocable device proof in an encrypted HttpOnly cookie, restoring session/MFA state after closure without granting recent-action verification. Keep SESSION_EXPIRE_ON_CLOSE=false and set SESSION_SECURE_COOKIE=true with HTTPS in production. The server session still has its configurable lifetime; device proof can rebuild it while browser storage remains. AUTH_SESSION_DAYS=90 controls the optional expiring native policy when persistence is disabled. The migration never revives expired credentials. Password/security changes revoke affected credentials while retaining the intended current device. After environment changes, run config:clear with PHP 8.5. See documentation/SECURITY_PHASE_3_DASHBOARD_2026-11-10.md at the workspace root.

Interview alignment (8 November): run the additive migrations. New evidence uses `HAZARD_EVIDENCE_DISK=evidence` (private local storage), or `r2` with a verified private bucket. A public storage link is not required for evidence. Inspect legacy local public files with `php artisan hazard:secure-evidence`; after backup use `--apply` to verify/copy/remove public copies. Raw evidence is owner/admin only; news requires separate reviewed public text. New admin endpoints support resolution-photo uploads and bounded weekly/monthly PHT summaries.

## Account Settings and Gmail

Apply the additive 2026_11_11_000001 preferences migration without resets. Personal preference APIs require the authenticated account and a matching account_id. Deployment seeders require private credentials and never promote an existing personal account.

Gmail SMTP uses a separate server-only app password. Delivery is unavailable with missing credentials or log transport; reserved sample addresses are excluded. Settings provides verification and opt-in own-report status mail alongside existing reset links. Keep the existing bounded hazard:work-security-mail scheduler; no persistent worker is required. See [configuration and verification limits](../documentation/SETTINGS_2FA_EMAIL_2026-11-11.md).


## Reporter verification

Signup now requires current policy acceptance and queues the existing SMTP verification challenge. Citizen report/confirmation routes require verified local email, current policy acceptance and approved private government-ID review. Apply migration 2026_11_24_000001. Configure the separate Gmail app password and operator-approved PRIVACY_CONTACT_EMAIL before collecting IDs; the private SMTP sender is not the public privacy contact. The scheduler runs bounded identity expiry cleanup hourly. See [audit, API, privacy and activation](../documentation/REPORTER_VERIFICATION_AUDIT_2026-11-24.md).
