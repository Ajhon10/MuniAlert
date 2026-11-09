# hazard-server

Laravel 12, Sanctum, and MySQL API for MuniAlert. It lives in the `hazard-server` folder of the private Ajhon10/MuniAlert workspace; the Vue client is in the sibling `hazard-client` folder. Start with [team setup](../documentation/TEAM_TESTING.md).

## Local development

Requires PHP compatible with the lockfile, Composer 2, MySQL, and Node 22.12+ for the development launcher. Use workstation PHP 8.5 (`C:/php85/php.exe`) for Composer and Artisan. Use a separate MySQL schema named `municipal_hazards`; the schema is created in MySQL Workbench or another database client, while Laravel migrations create its tables.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Copy `.env.example` and generate an app key only for a fresh installation. An existing local `.env` was moved with this project; keep its `APP_KEY` and other settings. On PowerShell, use `Copy-Item .env.example .env` only if `.env` does not already exist. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` to the correct MySQL connection. The password belongs to the MySQL user. Never commit `.env` or share its password.

Run these one at a time, stopping if a command fails:

```sh
php artisan config:clear
php artisan migrate --seed
php artisan storage:link
php artisan hazard:admin admin@example.test
composer run serve
```

If `storage:link` says the link exists, keep the existing link. The development launcher selects PHP 8.5 on Windows, reuses an existing healthy API from this workspace, and binds port 8000 without silently falling back to 8001. From Git Bash, invoke Composer with `PATH="/c/php85:$PATH" composer run serve`. The API runs at `http://127.0.0.1:8000`; start `hazard-client` separately with `npm run dev` on port 5173. Run `composer test` for isolated Laravel tests. Never run destructive test migrations against the application database.

Migration `2026_10_02_000002` adds admin-managed map/hotline settings, milestone badge definitions, confirmed-report news reactions/comments, and soft deletion for a reporter's own unverified report. Run `php artisan migrate` after updating an existing installation. The seeded hotline list is empty until an administrator publishes locally approved numbers in the client CMS. Reward descriptions are informational; the API does not pay cash or deliver gifts.

## Local demo accounts

`DemoAccountSeeder` creates one administrator (`itsajhon@gmail.com`) and two regular citizens (`citizen.one@example.test` and `citizen.two@example.test`). It only runs when called explicitly in a local or testing environment. The normal `migrate --seed` command still seeds only categories and departments.

Set **three different** passwords of at least 12 characters in your local `.env`:

```dotenv
HAZARD_DEMO_ADMIN_PASSWORD=choose-a-private-admin-password
HAZARD_DEMO_CITIZEN_ONE_PASSWORD=choose-a-private-citizen-one-password
HAZARD_DEMO_CITIZEN_TWO_PASSWORD=choose-a-private-citizen-two-password
```

Then run `php artisan config:clear` and `php artisan db:seed --class=DemoAccountSeeder`. The seeder hashes passwords and skips any account whose email already exists; it never resets existing credentials. Keep these settings out of Git and remove or rotate them before a real deployment.

## Persistent sessions

Use SESSION_LIFETIME=129600 (minutes), SESSION_EXPIRE_ON_CLOSE=false and AUTH_SESSION_DAYS=90 for the default remembered-login policy. Set SESSION_SECURE_COOKIE=true with HTTPS in production. New web logins receive an HttpOnly remember cookie. Native mobile tokens renew only after successful authentication; expired/revoked tokens never renew. Password changes revoke other mobile credentials, rotate remember tokens and invalidate old browser password-hash sessions while retaining the current device. After changing environment configuration, run config:clear with the workstation PHP 8.5 CLI.

Interview alignment (8 November): run the additive migrations. New evidence uses `HAZARD_EVIDENCE_DISK=evidence` (private local storage), or `r2` with a verified private bucket. A public storage link is not required for evidence. Inspect legacy local public files with `php artisan hazard:secure-evidence`; after backup use `--apply` to verify/copy/remove public copies. Raw evidence is owner/admin only; news requires separate reviewed public text. New admin endpoints support resolution-photo uploads and bounded weekly/monthly PHT summaries.
