# 07 — Deployment, Android build and maintenance

## Prerequisites

Development: Node 22.12 or later compatible with the lockfile, npm, PHP 8.2+ with `gd` (WebP), `fileinfo`, `mbstring`, `openssl`, PDO MySQL and PDO SQLite, Composer 2, and MySQL. SQLite is required for default tests, not the production database. PHP upload limits must permit the accepted multipart request: `upload_max_filesize=2M`, `post_max_size=8M` are sufficient for three 1-MB photos.

Production target: **Hostinger Web/Cloud hosting**, one HTTPS origin, PHP/MySQL, compiled Vue assets and one hPanel scheduler cron with bounded push batches. Clients are **Android app + responsive web**. Node is needed only at build time. Follow [the concrete Web/Cloud guide](HOSTINGER_WEB_CLOUD.md), including the private Laravel/public_html split and relocated entry point. No hosting account is provisioned by this repository.

## Local setup

From the `BANTAY` parent folder:

```sh
npm ci --prefix hazard-client
composer --working-dir=hazard-server install
```

Copy `hazard-server/.env.example` to `hazard-server/.env` and `hazard-client/.env.example` to `hazard-client/.env`. On PowerShell use `Copy-Item`; on Linux/macOS use `cp`. Generate the Laravel key:

```sh
php hazard-server/artisan key:generate
```

Create a new MySQL database named `municipal_hazards`, configure its credentials in `hazard-server/.env`, then:

```sh
php hazard-server/artisan migrate --seed
php hazard-server/artisan storage:link
php hazard-server/artisan hazard:admin admin@example.test
```

Start both services from BANTAY with `npm run dev` (`npm.cmd run dev` in PowerShell). It checks/reuses existing matching instances and verifies the client-to-API proxy before reporting success. The separate `composer run serve` and client `npm run dev` commands remain available. Use the PHP 8.5 PATH prefix for Composer in Git Bash. See [the startup repair](STARTUP_REPAIR_2026-11-04.md).

The administrator command prompts for the name and a hidden password; no shared password is seeded. Open `http://localhost:5173`. API: `http://localhost:8000/api/health`. Use one hostname consistently for browser session cookies. A separate terminal can run `php hazard-server/artisan queue:work --tries=3`; the default database queue is required for configured FCM delivery.

`http://localhost:5173/?demo=1` provides an explicitly fictional read-only preview without a backend database. The default map center is Basista, Pangasinan. Local boundaries are reference data and still require municipal review before operational use.

## Local database troubleshooting

`SQLSTATE[HY000] [1045] Access denied ... (using password: NO)` means the server was reached but rejected login without a password. Passing isolated automated tests does not configure credentials for the separately installed local MySQL server.

1. Stop the server and client terminals with Ctrl+C before restarting with changed configuration.
2. Edit `hazard-server/.env` locally using the credentials for the intended MySQL server. Do not send passwords through chat or commit them. Preserve the rest of the environment file; copying `.env.example` over it would erase saved settings.

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=municipal_hazards
DB_USERNAME=root
DB_PASSWORD="replace-with-your-actual-local-password"
```

`root` and port `3306` are examples, not guaranteed settings. If multiple MySQL/MariaDB installations run on the machine, use the port and account belonging to the intended instance. Quote passwords containing spaces or `#` appropriately for dotenv. Keep the real password out of shell command arguments and documentation.

3. Run `php hazard-server/artisan config:clear` after editing. For an unknown-database error (1049), create `municipal_hazards` through your database administration client with `utf8mb4` character set. A permission error requires an account with access to this project's database; resetting the server or changing unrelated databases is unnecessary.
4. Run each command separately and stop if it fails:

```sh
php hazard-server/artisan migrate --seed
php hazard-server/artisan hazard:admin admin@example.test
```

Start both applications separately as shown above.

Use a **plain email address**, not `[admin@example.test](mailto:admin@example.test)`. The command validates the email before database access, then prompts privately for the administrator password. An existing `hazard-server/public/storage` link does not need to be recreated; the `storage:link` message saying it already exists is harmless when that link points to `hazard-server/storage/app/public`.

The Vite and PHP server startup messages only confirm that HTTP servers started. They do not prove database authentication or migrations succeeded. If the MySQL password is unknown, obtain it from the owner of that local installation or follow that installation's authorized account-recovery process before continuing.

For local development accounts, set three distinct `HAZARD_DEMO_*_PASSWORD` values of at least 12 characters in `hazard-server/.env`, clear configuration, then run `php hazard-server/artisan db:seed --class=DemoAccountSeeder`. The seeder creates `itsajhon@gmail.com` as an administrator plus two `example.test` citizen accounts. It is explicit, local-only, and skips existing accounts without resetting their passwords. Do not deploy these demo credentials.

## R2 configuration

Create a bucket and narrowly scoped credentials in the owning Cloudflare account. Configure:

```dotenv
HAZARD_EVIDENCE_DISK=r2
R2_ACCESS_KEY_ID=your-access-key
R2_SECRET_ACCESS_KEY=your-secret
R2_BUCKET=your-bucket
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
R2_PUBLIC_URL=
```

Use a **private evidence bucket**: disable r2.dev and public/custom-domain access. Evidence is streamed only through the authenticated report/photo endpoint; `R2_PUBLIC_URL` is unused for private delivery. The endpoint is the S3 API origin and the SDK uses the R2-compatible `auto` region; see [Cloudflare's PHP S3 example](https://developers.cloudflare.com/r2/examples/aws/aws-sdk-php/). For local storage use `HAZARD_EVIDENCE_DISK=evidence`, rooted outside public storage. The old `HAZARD_PHOTO_DISK` setting no longer selects new-upload storage. The application rejects public or unsupported evidence disks.

Do not commit credentials or embed them in `VITE_*` variables. Confirm authenticated retrieval, unauthenticated denial, disabled public-bucket access, billing ownership, retention and cleanup before deployment. Inspect legacy local public evidence with `php hazard-server/artisan hazard:secure-evidence`; after backup use `--apply` to copy/verify/private-route the bytes and remove the public copies. The command can recover leftover public copies after interruption. Review R2 and previously cached public files separately. No fixed free-tier or pricing claim is assumed in this documentation.

## Firebase Cloud Messaging

1. Create/configure the municipality-owned Firebase project and enable the FCM API.
2. Place the service-account JSON outside `public/`, restrict file permissions, and set `FCM_PROJECT_ID` and the absolute `GOOGLE_APPLICATION_CREDENTIALS` path on the backend.
3. For web push, set all `VITE_FIREBASE_*` fields and the public VAPID key in the frontend environment, then rebuild. Serve over HTTPS (localhost is suitable for development).
4. For Android, register `ph.municipal.bantay` (or the deliberately changed application ID) in Firebase and place its `google-services.json` in `hazard-client/android/app/`. That file is ignored by Git.
5. Keep a queue worker running. Enable push from a signed-in client and change one of that citizen's report statuses using a separate administrator account.
6. Record real foreground/background delivery and token cleanup results. The in-app inbox is the fallback when push is unavailable.

The server requests a short-lived OAuth access token and uses [FCM HTTP v1](https://firebase.google.com/docs/cloud-messaging/send/v1-api). Per-device jobs are inserted into the same database transaction as the report update and private inbox row. The `report-push` database queue avoids the commit-to-enqueue gap; one failed device does not repeat all successful devices. Worker retries are still at least once: a crash after provider acceptance can repeat a push, whose data includes a stable `notification_id`. Monitor failed jobs and use reviewed `queue:retry` recovery. Native Android registration uses [Capacitor Push Notifications](https://capacitorjs.com/docs/apis/push-notifications). Native iOS/APNs conversion is outside this release.

## Map provider and boundaries

Set `VITE_MAP_STYLE_URL` to a compatible provider's style, and `VITE_BARANGAY_GEOJSON_URL` to an approved GeoJSON FeatureCollection. Publish the actual default center and zoom through the administrator's Content management screen; the map reads these from `/api/settings`. Configure provider domains/tokens and quotas before operational use.

The default OpenStreetMap tile endpoint is for small development use; preserve attribution and follow its [tile usage policy](https://operations.osmfoundation.org/policies/tiles/). The code does not bulk-download tiles or offer offline tile caching. A production municipality should approve an appropriate tile provider and capacity plan.

## Android project

See [Android phone testing](ANDROID_TESTING.md) for USB forwarding of the running website, persistent-login checks and the installed-app procedure. This workstation has no configured SDK/JDK or attached device.

The generated native project is in `hazard-client/android` and installs as **Bantay** (`ph.municipal.bantay`). Install Android Studio with its bundled JDK and SDK Platform 35, matching `hazard-client/android/variables.gradle`. Set `VITE_API_BASE_URL` in `hazard-client/.env` to a phone-reachable HTTPS Laravel origin. A phone cannot use the computer's `localhost`; the server must be reachable from the phone. Then:

```sh
npm run android:sync -w hazard-client
npm run android:open -w hazard-client
```

Connect an Android phone with USB debugging, select it in Android Studio and click **Run** to install the debug app. `npm run android:run -w hazard-client` is the command-line alternative once the SDK and device are ready. Android Studio can also build a debug APK for device validation and a signed APK/AAB for release. Keep signing material outside source control. GPS and notification permissions are declared; the camera plugin launches the system capture flow. Browser and native GPS implementations are in `services/mediaService.ts`; behavior should be checked against [Capacitor Geolocation](https://capacitorjs.com/docs/v7/apis/geolocation).

Syncing native assets is not evidence of a successful Gradle build or physical-device validation. The development environment used here does not have a configured Android SDK/JDK, so no signed artifact is claimed.

The client repository also includes a manual GitHub Actions workflow, **Build Android debug APK**, which accepts a public HTTPS API origin and uploads an installable debug APK artifact. It can be run after the private `hazard-client` repository is published. It has not yet executed remotely and does not replace phone testing or a signed release build.

## Alternative VPS deployment procedure (not the selected target)

1. Provision the approved domain, TLS certificate, Linux service account, MySQL database/user and PHP-FPM runtime.
2. Place an approved, tested release under a path such as `/var/www/bantay`. Do not expose the repository root through the web server.
3. Install backend production dependencies with `composer install --no-dev --prefer-dist --optimize-autoloader` inside `hazard-server`.
4. Install locked frontend dependencies using `npm ci`, then run `npm run build:web` to package same-origin assets inside `hazard-server/public/app`.
5. Configure `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, a unique `APP_KEY`, MySQL credentials, `SESSION_SECURE_COOKIE=true`, the exact frontend host in `SANCTUM_STATEFUL_DOMAINS`, and appropriate `FRONTEND_URLS`.
6. Restrict environment/service-account file permissions. Grant the PHP service account write access only to Laravel's `storage` and `bootstrap/cache` as required.
7. Back up before schema changes, then run `php artisan migrate --force --seed`, `php artisan storage:link` for local images, and `php artisan optimize`.
8. Adapt `deployment/nginx/bantay.conf.example`, serve only `hazard-server/public`, with compiled assets at `/app/` and dynamic requests through `index.php`, then validate Nginx configuration before reload.
9. Install/adapt the queue service example and add the scheduler cron. Restart workers after deploying new code.
10. Verify login/CSRF, upload/retrieval, admin authorization, notifications, HTTPS, failed-job handling and a restore rehearsal. Record acceptance before operational use.

The included Nginx example is an HTTP server block intended to sit behind configured TLS termination or be extended by the operator's certificate configuration. It is not a complete HTTPS deployment by itself.

## Worker and scheduler

For the selected Web/Cloud target, set `HAZARD_SCHEDULED_PUSH=true` and use the [single hPanel cron template](../deployment/hostinger/cron-command.example). `schedule:run` invokes the overlap-protected `hazard:work-push` command; no persistent worker is required. Follow the Web/Cloud guide for soft time limits, delayed push and failed-job monitoring. The systemd example and following persistent-worker commands apply only to the alternative VPS setup with scheduled push disabled.

```sh
php artisan queue:work report-push --sleep=3 --tries=3 --timeout=60
php artisan schedule:run
```

Run `schedule:run` every minute via cron, as shown in `deployment/scheduler.cron.example`. The registered daily jobs prune expired Sanctum tokens and stale push devices. Queue concepts and process supervision are described in [Laravel queues](https://laravel.com/docs/12.x/queues).

## Backup, recovery and upkeep

- Back up the MySQL database and photo objects together under authorized access. Keep `APP_KEY` and deployment credentials in the operator's secret-management/backup procedure.
- Use a restricted MySQL option file or secret-management tool for backup credentials rather than putting passwords in command arguments. Store dumps outside web roots and source control.
- Restore to an isolated environment first; run migrations/status checks, sample report/photo retrieval, role checks and notification checks, and record the restoration date and result.
- Keep worker logs, failed-job monitoring, storage usage, certificates and dependency updates under an assigned maintainer.
- Do not roll back a database migration containing real evidence without a reviewed backup/restore plan. Redeploy a compatible prior application release when appropriate.
- Complete all automated gates before pushing a maintenance update and update the capstone/change records when behavior changes.

## Optional Google/Facebook setup

Follow [SOCIAL_AUTH_SETUP.md](SOCIAL_AUTH_SETUP.md) for server environment keys, exact registered callback URIs, HTTPS/cookie/proxy requirements and Android app return links. Deploy the additive social-auth migration without resets. Keep secrets outside public_html and frontend build variables. The existing scheduler cron cleans expired attempts daily. Complete real-provider and physical Android tests before enabling public sign-in. The source feature can be locally verified while these external credentials/review steps remain pending.


## Basista map assets

The build includes `public/boundaries/` under the correct Vite base (`/app/` on the combined website and root on Android). Keep the GeoJSON files and `provenance.json` in the release. Clear/Dark basemaps use OpenFreeMap; Streets uses OpenStreetMap raster tiles. Preserve displayed attribution and validate real connectivity on the target hosting/device. Operators may set `VITE_BARANGAY_GEOJSON_URL`, `VITE_CITY_GEOJSON_URL` and `VITE_PROVINCE_GEOJSON_URL` to approved replacement FeatureCollections; use a `name` property for labels. Rebuild/synchronize Android after changing public configuration. The new camera migration replaces only the generic Manila center, retaining customized settings. See [the Basista map handover](BASISTA_MAP_2026-11-09.md).
