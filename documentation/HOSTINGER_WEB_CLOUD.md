# Hostinger Web/Cloud deployment

## Current deployment handoff ? 24 November 2026

The owner selected a **one-year term**, not an exact hosting tier or domain. The SMTP settings in the ignored local environment pass configuration checks and a real SMTP authentication-only connection from the workstation. No message was sent. Hostinger authentication/delivery and final HTTPS links still need testing. No hosting account has been accessed or deployed. A public privacy contact is still required. Both roles require verified email and current policy acceptance. Government ID is optional and earns a profile badge; no account is auto-approved.

### Things the owner must supply or approve

| Requirement | Needed action |
| --- | --- |
| Domain and hosting tier | Select the actual domain/tier; verify PHP, SSH/terminal/Composer, MySQL, private sibling storage and at least one-minute cron capability. A one-year term does not establish those features. |
| Host runtime | Verify CLI and web PHP separately against composer.lock. The project supports a PHP ^8.2 baseline and local tooling uses PHP 8.5; select a supported patched host runtime. Check GD/FreeType, fileinfo, mbstring, PDO MySQL, OpenSSL, cURL, XML/DOM and ZIP plus actual composer platform requirements. |
| Database | Create a dedicated MySQL database/user and private credentials; never deploy using root or copy local records by default. |
| Privacy decisions | Approve a public PRIVACY_CONTACT_EMAIL, policy wording, reviewer authority, assisted alternatives and account/incident/backup retention. SMTP sender/login stays private unless separately approved for publication. |
| Administrator | Designate a real admin; complete email, current policies and authenticator MFA. ID review is optional, with no self-approval. |
| Services | Transfer BANTAY's SMTP settings privately and verify real delivery. Use local private evidence by default. Firebase/private R2 are optional and need separate setup/acceptance. |
| Security/operations | Confirm provider patching, a supported private ClamAV service/signatures for the existing upload-security gate, storage permissions, monitoring and tested encrypted backup restoration. Do not simply enable scanner flags without a working service or bypass a failed gate. |
| Acceptance | Run host checks and synthetic smoke tests, then obtain municipal permission before real collection/public operation. |

### Prepare and upload

1. Run `npm run verify` from the project root on the exact candidate state. It includes backend/browser/build/security/document and release-package safeguards. Resolve any failed/unavailable check before treating the candidate as accepted.
2. Run `npm run package:hostinger`. This creates a new ZIP and staging directory under ignored `.cache/releases/`; the command prints their exact paths. Its manifest hashes the prepared files. The public/private layout is already arranged. It contains **no vendor**, so Composer/terminal access or a separately prepared target-compatible production vendor directory is required.
3. For a new empty site's domain directory, upload `bantay/hazard-server` beside `public_html`, then upload the package's `public_html` contents to the real document root. Preserve `.htaccess`. Never replace an existing live environment/storage/database without an approved backed-up release procedure. Application source, vendor, .env, IDs and uploads must remain outside public_html.
4. In the private server directory use the verified host PHP/Composer to run:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
```

5. **New installation only:** copy the supplied production `.env.example` to private `.env`, fill credentials/domain and generate one unique key with `php artisan key:generate`. Never regenerate an existing APP_KEY: encrypted IDs and other secrets depend on it. Replace every `your-domain.example` value and configure `APP_URL`, `SECURITY_FRONTEND_URL`, `FRONTEND_URLS` and `SANCTUM_STATEFUL_DOMAINS` consistently. Keep production/debug-off/secure-cookie settings from the template. Do not copy the workstation `.env` into public_html or paste credentials into issue/chat logs.
6. Confirm database access, back up any existing data, then run:

```sh
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
```

Normal production seeding creates missing reference catalogs and the configured initial administrator; no demo residents/shared passwords are included. Set INITIAL_ADMIN_NAME, a real INITIAL_ADMIN_EMAIL and private INITIAL_ADMIN_PASSWORD first. Existing/deleted administrator records are preserved rather than recreated. Do not use `migrate:fresh`, testing seeders or database reset commands here. Only private storage and bootstrap/cache need the host's appropriate writable permissions; do not use 777.

### Establish the first administrator

InitialAccountSeeder is called by the normal DatabaseSeeder in local and production deployments. It provides a starter administrator identity (Municipal Administrator / admin@munialert.test) and requires a private INITIAL_ADMIN_PASSWORD; production also requires a real INITIAL_ADMIN_EMAIL. No shared password or demo residents are included. Set INITIAL_ADMIN_NAME/EMAIL/PASSWORD privately, run db:seed --force, and sign in within the 15-minute MFA setup window. Use hazard:account-security <email> --authorize-mfa if that window expires. Reruns preserve existing accounts and never recreate a deleted starter admin. Later administrators are created/deleted in Settings > Administrator accounts, with recent authentication, no self-deletion, last-active-admin protection and retained municipal history. A replacement admin must complete MFA/email/policies and sign in before removing the starter.

Both roles require locally verified email and acceptance of current policies (2026-11-24-r3); administrators additionally require MFA. One Philippine government ID is optional. Approval earns a blue verified identity badge on the profile; absent, pending, rejected, expired, withdrawn or name-changed ID status does not block reporting or administration. Identity review is independent of incident factual review. Self-approval remains prohibited; another authorized administrator reviews optional admin IDs, or an independently attested operator can perform that optional review. The badge disappears after withdrawal/name changes or unmet email/policy requirements.

### Cron, security checks and smoke tests

Enable one hPanel custom cron calling the **verified PHP binary** and private `artisan schedule:run` every minute; see the existing cron section below. The scheduler handles bounded security-mail batches, hourly ID deletion, token/device cleanup and monitoring. Keep `HAZARD_SCHEDULED_PUSH=false` unless Firebase is configured and scheduled delivery is deliberately enabled. Do not run a second persistent worker for these bounded queues.

Run `php artisan hazard:security-check --phase=2 --json` after setup. It now checks administrator email/current-policy setup as well as MFA, private storage, HTTPS/cookies/CORS, real SMTP configuration, schema and scanner selection. The production template intentionally does not invent a privacy contact or working scanner; those gates remain unsatisfied until the operator supplies them. Check actual service operation in addition to configuration flags. Use `php artisan schedule:run`, inspect the cron heartbeat and verify real email, cleanup and delivery timing. A local configured sender does not establish host deliverability.

Before opening to residents: test signup/email redemption, consent/ID upload and independent review, rejected/expired resubmission, inability to self-approve, citizen/admin role restrictions, private photos, reporting/location confirmation, response history, exports, logout/revocation and no public access to `.env`, vendor, logs or ID/evidence storage. Test backup restoration in isolation with the same protected encryption key. Confirm provider patch status and municipal operational acceptance. Physical Android and a signed APK remain separate.

Regenerate a revision-3 candidate with optional ID and administrator management; earlier mandatory-ID ZIPs are superseded. Use the current candidate with locked Laravel 12.73.0 (manifest minimum ^12.71), which resolves the advisory found during preparation. Earlier ZIPs with Laravel 12.69.3 are superseded and must not be deployed. The ZIP is a prepared candidate, not proof of deployed or municipal acceptance. Keep the release manifest, fresh verification report, protected backup and operator acceptance record together. Do not blindly roll back additive migrations after real records exist.

---

Confirmed target: **Hostinger Web/Cloud hosting, Android app and responsive web**.
This supersedes the earlier VPS target. Keep the existing source folders and
deploy the compiled web interface and Laravel API on one HTTPS domain.

## Prepare the release

1. Run the full verification gate, install locked production PHP dependencies in
   an environment matching the destination, and run `npm run build:web` from
   BANTAY. Node runs locally/CI; it is not needed as a production web server.
2. Create the Hostinger MySQL database/user, HTTPS domain and private application
   directory. Confirm the account's PHP CLI and web versions/extensions separately
   with `composer check-platform-reqs --lock`. The local workstation uses PHP 8.5;
   Hostinger PHP and cron paths must be configured independently.
3. Use this layout, adapting the username and domain:

```text
/home/u123456789/domains/example.org/
  bantay/hazard-server/       private Laravel app, vendor, .env, storage
  public_html/
    index.php                adapted public entry point
    .htaccess                from hazard-server/public/.htaccess
    app/                     from hazard-server/public/app (compiled Vue)
    # Evidence stays in the private application; no public evidence link.
```

4. Upload the private Laravel application with production `vendor`, excluding
   development `.env`, test databases, local evidence, logs, dependencies for Node,
   Android source, `.git` and local caches. Create a new production `.env` privately;
   do not reuse seeded demo credentials. Provision the authorized administrator.
5. Upload only the public files to `public_html`. Use
   [the public entry template](../deployment/hostinger/public-index.php.example)
   as `public_html/index.php`; it loads the sibling private app and binds Laravel's
   public path to `public_html`, so `/` finds the compiled Vue HTML there. Keep
   `.htaccess` when transferring hidden files. If the account supports changing
   the document root directly to `hazard-server/public`, the normal Laravel entry
   point works instead and this relocation template is unnecessary.

Never place `.env`, service-account JSON, application source, `vendor` or logs
inside `public_html`. Do not use an HTTP route to run migrations or workers.

## Environment and storage

Configure `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, correct
`APP_URL`, MySQL, `SESSION_SECURE_COOKIE=true`, exact `SANCTUM_STATEFUL_DOMAINS`
(host, without scheme) and `FRONTEND_URLS` (full HTTPS origin). Include the existing
Android localhost origins in the allowed native CORS configuration as documented
in `.env.example`; native bearer authentication remains separate from web cookies.

Set `QUEUE_CONNECTION=database`, `CACHE_STORE=database` and
**`HAZARD_SCHEDULED_PUSH=true`**. Supply Firebase credentials outside the public
directory when enabling push. Missing Firebase configuration leaves the in-app
inbox usable, and the scheduled command retains any previously queued jobs.
Rebuild cached configuration after changes. Apply reviewed migrations and seeds
with the verified host PHP, then optimize. Back up before schema changes.

Use `HAZARD_EVIDENCE_DISK=evidence` for local private evidence under
`storage/app/private/evidence`, or `HAZARD_EVIDENCE_DISK=r2` for a dedicated
**private bucket**. Disable R2 public URLs/custom-domain access. The authenticated
API streams evidence after checking owner/admin access; neither a public
`storage` link nor a public photo delivery domain is required. Never copy evidence
into `public_html`. The old `HAZARD_PHOTO_DISK` setting no longer controls uploads.
Inspect and secure historical local public evidence with `hazard:secure-evidence`
and `hazard:secure-evidence --apply` after backup. Confirm public URLs deny access;
review any prior R2/CDN publication separately.

## Configure one hPanel cron

Create one **Custom** cron entry in Websites → Manage → Cron Jobs. Select every
minute if the account permits it. Set its command to the adapted version of
[the command example](../deployment/hostinger/cron-command.example):

```text
/verified/path/to/php /home/u123456789/domains/example.org/bantay/hazard-server/artisan schedule:run
```

Use the real verified CLI binary, not a guessed PHP path. Enter the schedule in
hPanel's schedule controls; do not paste `* * * * *`, `www-data` or shell redirection
into the command field. Hostinger describes cron scheduling in UTC. No firewall,
systemd service, root access or persistent worker is required for this mode.

The scheduler invokes **`hazard:work-push`** inside the same PHP process. Token pruning also uses an in-process scheduler callback, so these tasks do not require `proc_open` to start additional PHP processes. It takes a database lock, processes
at most **three jobs** with a **30-second between-job time budget**, and exits when
the queue is empty. Other simultaneous invocations skip the batch. Locks expire
after 180 seconds if a process is killed. Each job has a 25-second worker timeout,
three attempts and existing backoff. OAuth and FCM requests have their own network
timeouts; the database queue's retry interval is 90 seconds.

`--max-time` is checked between jobs, so a final job can extend the batch beyond
30 seconds. PHP signal timeouts require `pcntl`, which shared hosting may omit;
these limits are not a provider-enforced hard runtime ceiling. Verify real process,
memory and cron limits on the account. A killed worker leaves its reserved job
eligible for a later retry; FCM acceptance followed by a crash can still cause an
at-least-once duplicate. The database inbox is saved with the report update and
can be read on refresh without waiting for cron delivery.

Do not run a second push cron or a persistent worker in this mode. If migrating
to a supervised worker later, set `HAZARD_SCHEDULED_PUSH=false` and deliberately
switch the operations setup.

## Verify the account before acceptance

- Run `hazard:work-push` once through SSH/terminal before enabling cron, then
  inspect hPanel cron output and actual run times. The command is CLI-only.
- Test browser cookie/CSRF login, reload/sign-out, Android HTTPS access, citizen
  and administrator permissions, report upload and photo retrieval.
- Validate real foreground/background FCM delivery, retry after a temporary
  provider failure, and an overlapping or interrupted cron run. Inspect pending
  `jobs` count/oldest creation time and `queue:failed`; review before `queue:retry`.
- Start with low-volume synthetic reports. Three jobs per run is a conservative
  resource bound, not a capacity claim. Delivery delay includes the cron interval,
  backlog, retry delay and provider latency. Agree an acceptable delay and raise
  limits only after measurement on the actual account.
- Rehearse backup restoration and complete municipal UAT. Hosting configuration,
  real services and physical Android acceptance remain unverified locally.

The repository contains templates and locally tested scheduling code. No Hostinger
account was accessed or deployed by this change.

Sources: [Hostinger cron setup](https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/),
[cron troubleshooting and resource limits](https://www.hostinger.com/support/1583514-troubleshooting-cron-jobs-at-hostinger/).

## Local verification — 4 November 2026

All 11 parent verification checks passed: **32 backend tests / 209 assertions**, **20 browser tests**, both web build variants, TypeScript, PHP formatting, Android asset synchronization, zero npm vulnerabilities, no Composer advisories and documentation consistency. The relocated public entry template passed PHP 8.5 syntax checking. Tests use isolated SQLite databases and synthetic jobs; the five new scheduler cases verify bounded work, overlap exclusion, lock-expiry recovery, preserving work without Firebase and environment-controlled scheduler registration.

See [the dated verification record](evidence/hostinger-web-cloud-2026-11-04.json). The earlier UI review remains historical. Actual Hostinger execution, MySQL target acceptance, real cloud delivery, physical Android and deployment are still unverified. No live account configuration or deployment was performed.
