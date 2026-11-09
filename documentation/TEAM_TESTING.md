# Private GitHub workspace and team testing

The private repository is [Ajhon10/MuniAlert](https://github.com/Ajhon10/MuniAlert). The owner will configure collaborator access. One clone contains `hazard-client`, `hazard-server`, shared tests/documentation and the separate Remotion authoring project. No submodule permissions or additional repositories are needed.

## Start on a member's development PC

Install Git, Node 22.13 or newer compatible Node 22, PHP 8.5, Composer 2 and MySQL 8. Use PHP 8.5 for Composer/Artisan; this workstation uses `C:\php85\php.exe`. Check the lockfile on any different runtime before claiming compatibility. Android Studio is optional for the browser-first phone test.

```powershell
git clone https://github.com/Ajhon10/MuniAlert.git
cd MuniAlert
$env:Path = 'C:\php85;' + $env:Path
npm.cmd ci
composer --working-dir=hazard-server install
if (!(Test-Path hazard-server/.env)) {
    Copy-Item hazard-server/.env.example hazard-server/.env
}
```

Create an **empty development** MySQL database named `municipal_hazards` and a database user that can access it. Edit only the ignored `hazard-server/.env` with that member's credentials. Retain the example's localhost frontend/session configuration. Do not copy someone else's application key, passwords, database export, incident evidence, Firebase service account or signing key into Git. Never overwrite an existing environment file during an update.

For the first setup of this new development database:

```powershell
node scripts/php.mjs hazard-server/artisan key:generate
npm.cmd run db:migrate
npm.cmd run db:seed
npm.cmd run dev
```

Open `http://localhost:5173`. Create a citizen account through the application. An authorized maintainer can create a local administrator using a chosen test email and a hidden password prompt:

```powershell
node scripts/php.mjs hazard-server/artisan hazard:admin admin@example.test
```

The normal seed provides categories and routing; it does not publish shared administrator passwords. The optional demo-account seeder requires three distinct local environment passwords and is unnecessary for ordinary team setup. `?demo=1` is a read-only fictional preview; leave it before testing a real submission to your development database.

## Test an Android phone

Follow [the Android development guide](ANDROID_TESTING.md). The recommended first test is Chrome over USB port forwarding, using the phone's `http://localhost:5173`. It exercises the phone's browser and GPS permission flow without an APK. The installed Capacitor build is a separate test for native camera, secure storage and push.

A private GitHub repository gives access to source code. It does **not** run Laravel/MySQL or make a PC's localhost reachable to remote members. Each member can run their own development copy; a shared remote phone test requires a separately configured HTTPS staging backend. No staging deployment has been performed by creating this repository.

## Work safely together

Pull before starting. Create a branch, use synthetic test incidents, and submit a pull request. Avoid parallel edits to the same file. Keep native credentials and private evidence outside Git. On an existing installation, use `db:migrate`; never use `migrate:fresh` against a populated application database. The automated browser runner resets only its isolated test database.

Before a push, commit the intended source changes and run:

```powershell
npm.cmd ci --prefix promo-video
npx.cmd playwright install chromium
git config core.hooksPath .githooks
npm.cmd run verify
git push
```

The hook runs the required gate again and rejects an uncommitted publication state. Failed, skipped or stale checks do not qualify. Root CI verifies the whole workspace; historical workflows under the app folders are retained as standalone examples and are not the active GitHub workflows.

Root **Build Android debug APK** is manual. Supply an approved phone-reachable HTTPS API origin, wait for the build, then download the `munialert-android-debug` artifact from Actions. A source push alone does not create a working remote API or guarantee push delivery. Debug APKs are for development; release signing and physical acceptance remain separate.

The earlier application Git directories are preserved locally under the ignored `.cache/git-history-backups/` during consolidation. They are not nested repositories in a fresh team clone.
