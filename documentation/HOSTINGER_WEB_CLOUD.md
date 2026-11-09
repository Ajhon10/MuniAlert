# Hostinger Web/Cloud deployment

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
