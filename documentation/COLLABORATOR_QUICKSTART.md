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

## 4. Connect MySQL and create a new development database

### 4A. Know which password to use

**MySQL Workbench asks for a MySQL Server account password on your own PC.** MuniAlert does not supply that password.

| Where you are signing in | Which password belongs there |
| --- | --- |
| Workbench as `root` | The MySQL root password chosen when configuring MySQL Server on this PC |
| Workbench as the application database user | The password chosen when creating that MySQL user in step 4C |
| `DB_PASSWORD` in `hazard-server/.env` | The password for the MySQL user named in `DB_USERNAME` |
| MuniAlert website administrator/citizen | The separate application password created in step 5 or registration |

The demo-seeder passwords, Windows PIN, GitHub password, Gmail app password and `APP_KEY` do not authenticate a MySQL connection. The blank `DB_PASSWORD` in `.env.example` is a placeholder; it does not mean your local root password is blank. Editing `.env` or clicking **Store in Vault** does not create a database user or change its server-side password.

### 4B. Get Workbench Test Connection working first

1. Make sure **MySQL Server** is installed. Workbench is the management application; installing Workbench alone does not provide a running database server. On Windows, open **Win + R → `services.msc`** and find your MySQL service, often `MySQL80` or `MySQL84`. Start the intended installed service if stopped; service names can differ. If no server is installed, install/configure [MySQL Community Server](https://dev.mysql.com/downloads/mysql/8.4.html) and privately save the root password you choose.
2. Open Workbench. Click **+** beside MySQL Connections, or right-click an existing local connection and choose **Edit Connection**. Use these settings for the local server you installed:

| Workbench field | Value |
| --- | --- |
| Connection Name | `MuniAlert local setup` (a display label) |
| Connection Method | `Standard (TCP/IP)` |
| Hostname | `127.0.0.1` |
| Port | `3306`, or the actual port chosen for this MySQL Server |
| Username | `root`, or your existing local MySQL administrator account |
| Default Schema | Leave blank until the schema exists |

3. Click **Store in Vault…** to open the password entry dialog, enter that account's local MySQL password, and click **OK**. The main connection window uses a button rather than an editable password box. Alternatively, click **Test Connection** and enter the password when prompted. Password characters are masked.
4. Click **Test Connection**. Continue only when it succeeds. If it says **Access denied**, follow the next checklist before running Laravel commands.

**Access denied after entering a password:**

1. Check **Hostname**, **Port** and **Username** against the actual local server/account. A second MySQL installation or XAMPP/MariaDB can use a different port and different credentials. Use the same explicit `127.0.0.1` endpoint throughout these steps.
2. Edit this connection, click **Clear** beside its saved password, then **Store in Vault…** and re-enter the correct local MySQL password. Check keyboard layout, Caps Lock and accidental spaces. Clearing the saved password does not reset the MySQL account.
3. Test again. `using password: YES` only means a password was supplied; it does not confirm that password is correct. `using password: NO` means no password was supplied.
4. If the password is unknown, ask the person who configured **that PC's MySQL Server**. An authorized local database administrator can reset the affected account; for a forgotten root password, follow [MySQL's Windows root-password recovery procedure](https://dev.mysql.com/doc/refman/8.4/en/resetting-permissions.html). Confirm the installed server/version and preserve existing data first. Another person's BANTAY `.env` cannot recover this login.
5. If an administrator can connect but the new app user cannot, check that the app account exists, its password change was applied, and its allowed host matches the connection in step 4C. Retrying migrations, reseeding MuniAlert accounts or regenerating `APP_KEY` will not fix a rejected Workbench login.

If the exact message instead says **Cannot connect / 2003 / 10061**, check the running service and listening port; changing a password does not start a stopped server. If it says an authentication plugin is unsupported, record that exact error and check client/server compatibility rather than switching the server to legacy authentication.

After a successful connection, this read-only query identifies the server/account you actually reached:

```sql
SELECT VERSION() AS server_version, @@port AS server_port,
       CURRENT_USER() AS authenticated_account;
```

### 4C. Create the schema and a local application database user

Open the working administrator connection. In its SQL editor, create an empty schema:

```sql
CREATE DATABASE municipal_hazards
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

If you already have a populated schema with that name, choose a different empty development schema and set its name below. Do not drop/reset an existing application database.

Create a dedicated development MySQL account through Workbench so the app can migrate/use this schema without using the root account:

1. Open **Server → Users and Privileges → Add Account**.
2. Set **Login Name** to `munialert_local`, **Limit to Hosts Matching** to `127.0.0.1`, and use the server's modern `caching_sha2_password` authentication. Choose/confirm a new private database password and click **Apply**. If this account already exists, use its known credentials or choose a different new account name.
3. In **Schema Privileges → Add Entry**, choose **Selected schema → `municipal_hazards`** (or your alternative empty development schema). Grant `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER`, `INDEX`, `DROP` and `REFERENCES`, then **Apply**. These permissions support this local installation's tables/migrations. Limit the entry to this schema; the app does not need a global DBA role or permission to grant other users access.
4. Add a second Workbench connection named `MuniAlert app database`, using the same **Standard (TCP/IP)** host/port, username `munialert_local`, and the password you just chose. Set **Default Schema** to your new schema and click **Test Connection**. This connection must succeed too.

These are development migration permissions. Production account provisioning is a separate hosting task. See [Workbench's account/schema-privilege reference](https://dev.mysql.com/doc/workbench/en/wb-mysql-connections-navigator-management-users-and-privileges.html).

### 4D. Give Laravel the same working database connection

Edit your copied **`hazard-server/.env`**, not `.env.example` or `hazard-client/.env`. Match the successful **app database** connection from step 4C:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=municipal_hazards
DB_USERNAME=munialert_local
DB_PASSWORD='your_local_mysql_password'
HAZARD_EVIDENCE_DISK=evidence
```

Replace `your_local_mysql_password` with the password chosen for `munialert_local`; do not copy that placeholder literally. If you chose another port, schema or username, change those values too. The surrounding single quotes are `.env` syntax, not part of the password entered in Workbench; they preserve spaces, `#` and `$` in a value without an apostrophe. Values containing an apostrophe need appropriate dotenv quoting. Keep this file private. Do not leave a conflicting `DB_URL` from another setup; it can override the individual connection values.

From the repository root, clear Laravel's cached configuration and check database access **before migrations**:

```powershell
node scripts/php.mjs hazard-server/artisan config:clear
node scripts/php.mjs hazard-server/artisan db:show --database=mysql
```

Expected result: database information for your intended local schema. Zero tables is normal before migrations. If Workbench succeeds but this command fails, compare the exact host/port/user/schema, password quoting and `DB_URL`, clear configuration again and retry. Stop here until this check works.

For a **new installation only**, generate its own key and database tables/reference data:

```powershell
node scripts/php.mjs hazard-server/artisan key:generate
node scripts/php.mjs hazard-server/artisan config:clear
npm.cmd run db:migrate
npm.cmd run db:seed
```

The included migrations create the tables; normal seeding reads CSV reference data and creates configured local sample accounts when their three private passwords are set. A SQL dump from the owner is unnecessary. A fresh installation has no existing reports or user accounts. Private evidence storage is created as needed; a public `storage:link` is not required for report photos.

## 5. Create your test accounts

Create an administrator on **your own development database**:

```powershell
node scripts/php.mjs hazard-server/artisan hazard:admin admin@example.test
```

Answer the name prompt and choose a password of at least 12 characters. The terminal password prompt is hidden; type it and press Enter even if no characters appear. This is a **new MuniAlert website password**, separate from MySQL. Record your test login privately; email recovery requires configured real delivery and sample `.test` accounts cannot receive mail. The owner's login will not automatically exist in your database. At first administrator sign-in, complete authenticator enrollment within the 15-minute provisioning window; see [test-account and 2FA instructions](TEST_ACCOUNTS.md).

For a citizen test, register another account through the application's sign-in dialog. Use separate citizen/admin accounts to test permissions. The optional `DemoAccountSeeder` is not required, and the repository does not distribute a shared administrator password.

Alternatively, [seed local administrator and citizen test accounts](TEST_ACCOUNTS.md) using three different passwords from your private server `.env` and `npm.cmd run db:seed`. The default admin email is `admin@example.test`, configurable with `HAZARD_DEMO_ADMIN_EMAIL`; the seeder preserves existing accounts and refuses production. New passwords must be at least 12 characters and no more than 72 UTF-8 bytes.

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
| GitHub clone/download returns 404 | Use the public `https://github.com/Ajhon10/MuniAlert` URL; an invitation is needed only for write access |
| `npm.ps1` blocked | Use `npm.cmd` in PowerShell |
| Required PHP path missing / wrong PHP version | Install PHP 8.5 at `C:\php85`; confirm PATH and `php --ini`; do not substitute XAMPP PHP |
| Composer reports missing extension | Enable it in that CLI's `php.ini`, reopen the terminal and rerun `check-platform-reqs` |
| `vendor/autoload.php` or Vite missing | Finish Composer install / root `npm ci` |
| No encryption key | Run `key:generate` once for this new environment |
| Workbench `1045 Access denied` / `using password: YES` | Follow step 4B: verify the actual MySQL account/host/port, clear the saved password and re-enter its local password; YES does not mean correct |
| Workbench `using password: NO` | Supply the local MySQL account's password via Store in Vault or the connection prompt |
| Workbench `2003` / `10061` / Cannot connect | Check that MySQL Server is installed/running on the specified port; Workbench alone is not the server |
| `1044 Access denied to database` | The user needs privileges on the selected schema; check step 4C |
| `1049 Unknown database` / schema not listed | Create the intended empty schema or correct `DB_DATABASE`; refresh Workbench's Schemas list |
| Workbench succeeds, Laravel fails | Match the app database connection in `hazard-server/.env`, check quoting/DB_URL, run `config:clear`, then repeat `db:show` |
| Password appears not to type | Workbench: use Store in Vault to open its password dialog. Terminal hidden prompts: type the password and press Enter; no visible characters can be normal |
| Missing tables | Run migrations and normal seeding against the intended development schema |
| 419 / session problems | Use one localhost hostname, keep the example origins, clear stale browser cookies after a fresh setup and inspect `/api/health` |
| Phone cannot open localhost | Keep PC services running; approve USB debugging and enable the exact 5173 forwarding rule |
| Phone connected but map tiles fail | Check the phone's internet connection; the local API and external basemap are different connections |
| Google/Facebook/push unavailable | These are optional unconfigured integrations; email login and in-app report updates remain testable |

When requesting setup help, send the **exact error text/code**, the failing step, MySQL Server/Workbench versions and the configured host/port. Omit passwords, connection URLs containing credentials, `.env` contents and account recovery codes.

Connection behavior and saved-password handling follow [Workbench's TCP/IP reference](https://dev.mysql.com/doc/workbench/en/wb-mysql-connections-methods-standard.html) and [password-vault reference](https://dev.mysql.com/doc/workbench/en/wb-mysql-connections-vault.html). The error branches follow [MySQL's connection troubleshooting guide](https://dev.mysql.com/doc/refman/8.4/en/problems-connecting.html).
