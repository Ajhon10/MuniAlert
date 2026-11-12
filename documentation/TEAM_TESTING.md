# GitHub workspace and team testing

The public source repository is [Ajhon10/MuniAlert](https://github.com/Ajhon10/MuniAlert). Reading and cloning need no invitation; the owner configures collaborator write access separately. Public visibility was explicitly confirmed on 12 November 2026. One clone contains `hazard-client`, `hazard-server`, shared tests/documentation and the separate Remotion authoring project. No submodule permissions or additional repositories are needed.

For a first download, use [the complete collaborator quickstart](COLLABORATOR_QUICKSTART.md). [The file checklist](COLLABORATOR_FILE_CHECKLIST.md) explains what is included, what gets generated locally and what the owner must provide for shared/native testing. A basic independent local test needs no private file from the owner. A ZIP can run the app after extraction, but developers should clone for Git updates/hooks/full verification.

## Start on a member's development PC

Install Git, Node 22.13 or newer compatible Node 22, PHP 8.5, Composer 2 and MySQL 8. The Windows launchers require `C:\php85\php.exe`; configure that CLI's extensions and PATH. Run Composer's locked platform check before continuing. Android Studio is optional for the browser-first phone test.

```powershell
git clone https://github.com/Ajhon10/MuniAlert.git
cd MuniAlert
$env:Path = 'C:\php85;' + $env:Path
if (!(Test-Path hazard-server/.env)) {
    Copy-Item hazard-server/.env.example hazard-server/.env
}
if (!(Test-Path hazard-client/.env)) {
    Copy-Item hazard-client/.env.example hazard-client/.env
}
npm.cmd ci
composer --working-dir=hazard-server install
composer --working-dir=hazard-server check-platform-reqs
```

Create an **empty development** MySQL database named `municipal_hazards` and a database user that can access it. Edit only the ignored `hazard-server/.env` with that member's credentials. Retain the example's localhost frontend/session configuration. Do not copy someone else's application key, passwords, database export, incident evidence, Firebase service account or signing key into Git. Never overwrite an existing environment file during an update.

**Blocked at Workbench's password prompt/Test Connection?** Use [the Workbench setup and Access denied checklist](COLLABORATOR_QUICKSTART.md#4b-get-workbench-test-connection-working-first) first. Workbench needs the member's local MySQL Server account password. Website/seeder passwords are separate. The quickstart covers server startup, saved passwords, a dedicated schema user and a read-only Laravel database check before migrations.

For the first setup of this new development database:

```powershell
node scripts/php.mjs hazard-server/artisan key:generate
node scripts/php.mjs hazard-server/artisan config:clear
npm.cmd run db:migrate
npm.cmd run db:seed
npm.cmd run dev
```

Open `http://localhost:5173`. Create a citizen account through the application. An authorized maintainer can create a local administrator using a chosen test email and a hidden password prompt:

```powershell
node scripts/php.mjs hazard-server/artisan hazard:admin admin@example.test
```

The normal seed provides categories and routing; it does not publish shared administrator passwords. The normal `db:seed` command also creates missing CSV sample accounts when their three distinct local environment passwords are configured. Without those passwords, it seeds reference data only. Existing accounts and administrator edits are preserved. `?demo=1` is a read-only fictional preview; leave it before testing a real submission to your development database.

Leave the client API origin blank for this website/USB route. Keep the PC services open at `http://localhost:5173`; `/api/health` on that same origin should return `status: ok`. An empty dashboard and an absent owner login are expected with a new database. For the full new-database and account steps, use the quickstart above.

## Test an Android phone

Follow [the Android development guide](ANDROID_TESTING.md). The recommended first test is Chrome over USB port forwarding, using the phone's `http://localhost:5173`. It exercises the phone's browser and GPS permission flow without an APK. The installed Capacitor build is a separate test for native camera, secure storage and push.

The public GitHub repository gives access to source code. It does **not** run Laravel/MySQL or make a PC's localhost reachable to remote members. Each member can run their own development copy; a shared remote phone test requires a separately configured HTTPS staging backend. No staging deployment has been performed by creating this repository.

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
