# Security activation and remaining operations

This is an operator handover, not permission to deploy, scan external systems or change a real account. Source implementation and local tests do not establish production acceptance.

## Local/schema activation

The additive migration is `2026_11_10_000001_add_account_security.php`. It adds security state and tables without changing existing roles/passwords or incident data. It was applied to this workstation's configured localhost MySQL application on 10 November; account/report counts were preserved and no account was approved or enrolled. Other installations must apply it with the selected PHP runtime through `npm run db:migrate` after reviewing the migration. Never use `migrate:fresh` on an application database. Existing password-only administrator credentials stop working; complete the following enrollment before operational cutover.

An authorized operator independently verifies the administrator's identity and opens a 15-minute enrollment window:

```powershell
# Run inside hazard-server. Replace the placeholder with the intended plain email.
& 'C:\php85\php.exe' artisan hazard:account-security admin@example.test --authorize-mfa
```

The command asks for confirmation. The administrator then signs in with the existing method, adds the locally generated QR/manual key to an authenticator and verifies a fresh code. Save the ten recovery codes privately, separately from the phone. New administrators created through `hazard:admin` receive the same short enrollment window. Expired windows need fresh operator approval. No existing account is automatically enrolled or approved by this implementation.

Operator-only alternatives are `--suspend`, `--resume`, `--role=citizen`, `--role=admin` or `--reset-mfa` (one action per command). MFA reset is for independently verified lost-factor recovery and revokes previous credentials. The last active administrator cannot be suspended/demoted. Review operating-system/hosting access to Artisan; the command is not an HTTP administration endpoint.

## Email and scanning

Approve enrollment, verified-address changes, lost-phone recovery and support responsibilities first. Configure a real SMTP/provider transport and the trusted HTTPS frontend origin; then select `SECURITY_RECOVERY_ENABLED=true`. Never enable a log transport for reset mail. Existing provider-verified email is not automatically eligible for local password recovery. Users verify their local email from Account security when delivery is enabled. Verify actual recipient, token expiry/replay, provider outage and native/browser return behavior using synthetic accounts before release.

The existing single hPanel `schedule:run` cron invokes the bounded security-mail worker and cleanup tasks. Verify job backlog/failed jobs and cron ownership. SMTP acknowledgement loss can duplicate mail. Test operator retry and expiry behavior rather than promising exactly-once delivery.

Hostinger Web/Cloud may not provide a ClamAV daemon or the required connection capability. Confirm an approved scanner/service and signature updates before enabling `SECURITY_SCAN_UPLOADS=true`; configure `CLAMAV_HOST`, `CLAMAV_PORT` and the bounded timeout. If enabled but unavailable, uploads fail closed. Do not enable the flag without confirming that reports can be submitted safely. Quarantine is private, capped and cleaned by cron; verify filesystem permissions and actual HTTP denial on the host.

`hazard:security-check --phase=2 --json` performs read-only configuration checks without returning secret values. It is expected to fail while administrators, mail/scanning or the production build/configuration are unready. It does not connect to a scanner, deliver an email or certify hosting.

## CSP and deployment

`npm run build:web` emits the same-origin assets, CSP meta/manifest and `/app/.htaccess`. Configure equivalent headers if the host ignores `.htaccess` or uses Nginx. Keep the Laravel/public directory as the only document root; deny dotfiles and private storage. Preserve CSP on `/` and directly served `/app/index.html`, including errors. Do not add broad script wildcards or `unsafe-eval` to fix a provider error. Add only reviewed exact HTTPS API/map data origins through `VITE_CSP_EXTRA_ORIGINS`, rebuild and retest.

Inspect actual HTTPS/proxy/cookie/HSTS/CSP responses, PHP patches, database least privilege, private R2 access and public configuration/evidence denial on the intended host. These checks have not been executed against Hostinger by this task.

## Phase 3 acceptance still needed

1. Owner: confirm private-repository protection availability, require the exact `verify` status on the default branch, require reviewed pull requests, block force pushes/deletion and define trusted release approvers/secrets. Check actual GitHub runs for the exact authorized SHA. CI definitions alone are not remote verification.
2. Owner/operator: provide an isolated staging origin with synthetic records and written scan scope. Run authorized web/API scanning there, retain build/scope/findings and retest fixes. Do not scan the live municipality or third-party map/mail/provider systems.
3. Records/operator: approve backup encryption, key custody, retention and RPO/RTO. Back up both MySQL and private evidence, restore into a new isolated database and private storage root, compare row counts/file hashes, test owner/admin/public denial and measure recovery duration. Keep the restored app unable to send live email/FCM. Delete only the explicitly verified restore target after review. Local functional tests are not this production backup drill.
4. Operator: configure private alerts for repeated authentication/MFA failures, unusual admin activity, availability, failed/old jobs, quarantine/storage growth and backup failure. Assign contacts, containment steps, credential revocation, evidence access and recovery responsibilities; test the escalation path.
5. Municipality/device team: validate physical Android secure storage/camera/GPS, authenticator and lost-phone flows, real email/push, older-user accessibility, representative shared-IP workloads and recovery. Record results and approval separately from local source checks.


## Follow-up device, decoration and local Phase 3 tools

The [latest continuation](SECURITY_PHASE_3_DASHBOARD_2026-11-10.md) adds durable device registrations, default non-expiring native access, approved dashboard decoration, aggregate monitoring and an isolated encrypted synthetic MySQL rehearsal. Three follow-up migrations are applied to localhost with unchanged account/report counts. The rehearsal does not complete the real production backup/restore requirement above. Configure private alert collection/contacts separately; no external alert is sent by this task. Existing administrator enrollment still needs independent approval.
