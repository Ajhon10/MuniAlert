# Run a downloaded MuniAlert repository

This is the first-install guide for a teammate. The website and Android app use the same Laravel API and database. **No private file from the owner's PC is required for a new local website test.** Create local configuration from the included examples and install the locked dependencies.

## Choose a testing route

| Tester has | Route | What must be running |
| --- | --- | --- |
| A development PC | Website in desktop Chrome | Their local PHP/MySQL API and Vite client |
| A development PC and Android phone | The same website in Android Chrome through USB | Their PC services and Chrome USB forwarding |
| Android Studio and an Android phone | Installed Capacitor debug app | A reachable HTTPS backend, plus the built APK |
| An Android phone only | Open a shared HTTPS test website, or install a supplied APK | The owner must provide a hosted test backend; an APK also needs that backend |

Downloading a ZIP onto a phone does not run Node, Laravel or MySQL. GitHub stores source and build artifacts; it does not host this application's backend.

## 1. Get access and the source

The source repository [Ajhon10/MuniAlert](https://github.com/Ajhon10/MuniAlert) is public, as confirmed by the owner on 12 November 2026. Clone/download it without an invitation. The owner grants collaborator write access separately.

Recommended for developers:

```powershell
git clone https://github.com/Ajhon10/MuniAlert.git
cd MuniAlert
```

For **Code → Download ZIP**, extract the ZIP first. Open a terminal in the extracted folder containing `package.json`, `hazard-client` and `hazard-server`. Do not run commands inside the ZIP viewer or only inside one app folder. ZIP downloads do not contain `.git`; the app still runs, but Git updates, hooks and the repository-publication check require a real clone.

## 2. Install the PC prerequisites

- Node **22.13 or newer Node 22** and npm.
- PHP **8.5**, Composer 2 and PHP extensions required by `composer.lock`, including `fileinfo`, `gd`, `mbstring`, XML/DOM and `pdo_mysql`. Full automated tests also need `pdo_sqlite`/SQLite support.
- MySQL **8**, running locally, and an account allowed to create/migrate your development schema. MySQL Workbench is one way to manage it.
- Chrome on the PC and Android phone. Android Studio is needed only for a native build, not the USB website test.

**Windows:** the checked-in launchers expect PHP at `C:\php85\php.exe`. Install/extract PHP 8.5 there and enable extensions in its `php.ini`. XAMPP's Apache/PHP is not used by this development launcher. In the terminal used for installation:

```powershell
$env:Path = 'C:\php85;' + $env:Path
node --version
npm.cmd --version
php --version
php --ini
composer --version
```

Confirm that `php --version` reports 8.5. On macOS/Linux use PHP 8.5 on PATH; the Node helpers also support `BANTAY_PHP_BINARY` there. The commands below use PowerShell's `npm.cmd`; on macOS/Linux use `npm`, `cp` and the corresponding shell syntax.

## 3. Install dependencies and create your environment files

Run commands one at a time from the repository root. Stop and fix an error before continuing.

```powershell
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

`npm ci` at the root installs the Vue workspace too. Do not send/copy the owner's `node_modules` or `vendor` directories. The separate `promo-video` installation is unnecessary for running the website or its Android client.

For this local browser/USB route, leave `VITE_API_BASE_URL=` **blank** in `hazard-client/.env`; Vite forwards API and private-photo requests. Retain the server example's localhost frontend/session origins. No Firebase, R2, Google or Facebook credentials are needed for the basic email-login/reporting test.

## 4. Create a new development database

In MySQL Workbench or another MySQL administrator connection, create an empty schema:

```sql
CREATE DATABASE municipal_hazards
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

If you already have a populated schema with that name, choose a different empty development schema and set its name below. Do not drop/reset an existing application database.

Edit the copied `hazard-server/.env` using **your own local MySQL account**, not the owner's credentials:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=municipal_hazards
DB_USERNAME=your_local_mysql_username
DB_PASSWORD=your_local_mysql_password
HAZARD_EVIDENCE_DISK=evidence
```

Replace the username/password placeholders. The account needs access to this schema; the schema name is not a database password. Use the same MySQL host/port that works in your database client. Keep this `.env` private.

For a **new installation only**, generate its own key and database tables/reference data:

```powershell
node scripts/php.mjs hazard-server/artisan key:generate
node scripts/php.mjs hazard-server/artisan config:clear
npm.cmd run db:migrate
npm.cmd run db:seed
```

The included migrations create the tables; normal seeding creates hazard categories/departments. A SQL dump from the owner is unnecessary. A fresh installation has no existing reports or user accounts. Private evidence storage is created as needed; a public `storage:link` is not required for report photos.

## 5. Create your test accounts

Create an administrator on **your own development database**:

```powershell
node scripts/php.mjs hazard-server/artisan hazard:admin admin@example.test
```

Answer the name prompt and choose a password of at least 12 characters. The password prompt is hidden. Record your test login privately; the application has no self-service password-reset flow. The owner's login will not automatically exist in your database.

For a citizen test, register another account through the application's sign-in dialog. Use separate citizen/admin accounts to test permissions. The optional `DemoAccountSeeder` is not required, and the repository does not distribute a shared administrator password.

Alternatively, [seed local administrator and citizen test accounts](TEST_ACCOUNTS.md) using three different passwords from your private server `.env` and `npm.cmd run db:seed:accounts`. The default admin email is `admin@example.test`, configurable with `HAZARD_DEMO_ADMIN_EMAIL`; the seeder preserves existing accounts and refuses production. New passwords must be at least 12 characters and no more than 72 UTF-8 bytes.

## 6. Test the website on the PC

```powershell
npm.cmd run dev
```

Keep the terminal open. Visit **http://localhost:5173**. Check **http://localhost:5173/api/health** if the client cannot connect: it should report `status: ok`. Use the same `localhost` hostname consistently for sign-in. Do not double-click `index.html` or open the PHP source as a file.

Try sign-up/login, a synthetic hazard report with a safe image and confirmed Basista location, My reports, the admin review flow, map filters and the guide. An empty fresh dashboard is expected. `?demo=1` is an optional fictional read-only preview; exit it before testing a real submission to your local database.

## 7. Test the website on your Android phone

1. Keep the PC's `npm.cmd run dev` running. Enable **Developer options → USB debugging** on Android.
2. Connect a data-capable USB cable. Unlock the phone and approve **Allow USB debugging**.
3. On PC Chrome open `chrome://inspect/#devices`, enable **Discover USB devices**, and find the phone.
4. Open **Port forwarding**. Set device port **5173** to destination **localhost:5173** and enable forwarding.
5. In **phone Chrome**, open **http://localhost:5173**. This URL reaches the connected PC through USB; it does not refer to the owner's PC.
6. Sign in with accounts created in your local database. Allow location permission when testing GPS, or use manual pin entry. Test portrait/landscape, the onscreen keyboard and photo capture/gallery.

No same-Wi-Fi network is required for USB forwarding. Keep phone internet available for external basemap tiles. Normal API/photo requests use the forwarded 5173 proxy, so forwarding 8000 is optional. Follow [the full Android guide](ANDROID_TESTING.md) for drivers, `adb reverse`, native builds and the device test record. [Chrome's forwarding documentation](https://developer.chrome.com/docs/devtools/remote-debugging/local-server) describes this supported route.

## 8. Native APK or shared remote testing

For an installed app, follow [Android native setup](ANDROID_TESTING.md#4-installed-capacitor-app): JDK 21, SDK 35, Android Studio and a phone-reachable HTTPS API origin are required. Configure `VITE_API_BASE_URL` before `android:sync`; sync/build/install after changes. A PC's ordinary localhost URL is not a remote native backend.

If the owner provides a shared test website, a tester can simply open its **actual HTTPS URL** in phone/PC Chrome and use assigned test accounts. For an APK, the owner must provide the correct debug APK/artifact and the reachable backend it was built for. No shared staging site or APK is currently supplied by merely cloning this repository. See [what the owner needs to send](COLLABORATOR_FILE_CHECKLIST.md).

## Updates and optional developer checks

For an existing Git clone: pull, run `npm.cmd ci` and Composer install, then `db:migrate`. Keep your `.env`, app key and database. Do not repeat `key:generate` on every update, overwrite `.env`, or use `migrate:fresh` to update a populated installation. Restart dev services after environment changes; installed apps need a rebuilt/synchronized APK.

Developers contributing code must also install the validation dependencies:

```powershell
npm.cmd ci --prefix promo-video
npx.cmd playwright install chromium
git config core.hooksPath .githooks
npm.cmd run verify
```

Linux CPU-only authoring installs can use `ONNXRUNTIME_NODE_INSTALL_CUDA=skip npm ci --prefix promo-video`. The full gate includes video-source checks; it requires a Git clone and is distinct from running the website. All required checks must pass before a push.

## Common setup failures

| Symptom | Fix |
| --- | --- |
| Private GitHub URL returns 404 | Sign into the invited GitHub account and accept the collaborator invitation |
| `npm.ps1` blocked | Use `npm.cmd` in PowerShell |
| Required PHP path missing / wrong PHP version | Install PHP 8.5 at `C:\php85`; confirm PATH and `php --ini`; do not substitute XAMPP PHP |
| Composer reports missing extension | Enable it in that CLI's `php.ini`, reopen the terminal and rerun `check-platform-reqs` |
| `vendor/autoload.php` or Vite missing | Finish Composer install / root `npm ci` |
| No encryption key | Run `key:generate` once for this new environment |
| Access denied / unknown database | Correct your own `.env` credentials and create/grant access to the empty schema, then `config:clear` |
| Missing tables | Run migrations and normal seeding against the intended development schema |
| 419 / session problems | Use one localhost hostname, keep the example origins, clear stale browser cookies after a fresh setup and inspect `/api/health` |
| Phone cannot open localhost | Keep PC services running; approve USB debugging and enable the exact 5173 forwarding rule |
| Phone connected but map tiles fail | Check the phone's internet connection; the local API and external basemap are different connections |
| Google/Facebook/push unavailable | These are optional unconfigured integrations; email login and in-app report updates remain testable |
