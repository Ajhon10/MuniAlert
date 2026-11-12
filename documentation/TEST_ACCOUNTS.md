# Administrator and citizen test accounts

MuniAlert already has a `DemoAccountSeeder` for **one administrator and two regular users (citizens)**. The normal `db:seed` command creates reference data and invokes this sample seeder when all three private passwords below are configured. With no passwords configured, the database has no seeded user accounts.

The maintainer's local database was checked on 9 November 2026: two administrators and two citizens already exist. No existing account, password or role was changed by this update. These local accounts are not distributed through GitHub.

## Optional accounts for a collaborator's local database

Complete [the first-install guide](COLLABORATOR_QUICKSTART.md) through migrations/reference seeding. Keep `APP_ENV=local`. In your private `hazard-server/.env`, set:

```dotenv
HAZARD_DEMO_ADMIN_EMAIL=admin@example.test
HAZARD_DEMO_ADMIN_PASSWORD=
HAZARD_DEMO_CITIZEN_ONE_PASSWORD=
HAZARD_DEMO_CITIZEN_TWO_PASSWORD=
```

The demo administrator must use a sample **.test** address; real-address domains are rejected. Its default display name is **Sample Administrator**. Replace each empty password with a **different password of your own**, at least 12 characters and at most 72 UTF-8 bytes. Accented letters/emoji can use multiple bytes. Quote dotenv values correctly, especially if they contain spaces or `#`. Never commit or send this `.env` to another tester. There is no universal/default password.

From the repository root in PowerShell:

```powershell
node scripts/php.mjs hazard-server/artisan config:clear
npm.cmd run db:seed
npm.cmd run dev
```

| Login email | Role | Password |
| --- | --- | --- |
| `admin@example.test` (or your `HAZARD_DEMO_ADMIN_EMAIL`) | Administrator | Your `HAZARD_DEMO_ADMIN_PASSWORD` |
| `citizen.one@example.test` | Citizen | Your `HAZARD_DEMO_CITIZEN_ONE_PASSWORD` |
| `citizen.two@example.test` | Citizen | Your `HAZARD_DEMO_CITIZEN_TWO_PASSWORD` |

Open `http://localhost:5173` and sign in separately to test each role. The administrator reviews municipal reports; a citizen submits them. A newly seeded sample administrator has a 15-minute authenticator-enrollment window; scan the local QR code, enter the six-digit code and save recovery codes privately. Later setup needs approved operator provisioning. These `example.test` addresses are test identifiers and cannot receive real email. Use separately provisioned real accounts and authorized recipients for delivery tests.

The seeder validates every value before writing and creates missing accounts in a transaction. It skips existing emails without changing their roles, names or passwords. Consequently, reseeding does not reset a forgotten password or promote an existing citizen into an administrator. Changing a password in `.env` after an account exists does not change its saved password. It also does not seed fictional incident records; `?demo=1` is a separate read-only preview.

For direct Artisan use, run `node scripts/php.mjs hazard-server/artisan db:seed`. The `--class=DemoAccountSeeder` option remains available for accounts only. Visible rows and password-key references live in `hazard-server/database/data/sample_accounts.csv`; actual passwords never belong in that file. The seeder refuses every environment except `local` and `testing`, including when Artisan is invoked with `--force`.

## Individually provisioned or deployed accounts

For a fresh authorized administrator without demo seeding:

```powershell
node scripts/php.mjs hazard-server/artisan hazard:admin admin@example.test
```

Choose the name and a private password in the hidden prompt. Citizens register through the app. For production use a real approved administrator address, provision individual accounts and remove demo password settings. Keep the local demo accounts/database separate from real municipal operations. Account recovery and administrator MFA are tracked in [security Phase 2](SECURITY_IMPLEMENTATION_PLAN.md).
