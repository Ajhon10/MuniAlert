# 09 — Security, privacy and operational decisions

This attachment describes implemented controls and open operational decisions. It does not assert legal compliance, certify the application, or establish a municipality's retention policy.

The user's pasted security guidance is mapped into [three implementation phases](SECURITY_IMPLEMENTATION_PLAN.md). See [Phase 1 evidence](SECURITY_PHASE_1_2026-11-09.md) and [local test accounts](TEST_ACCOUNTS.md). ASVS Level 2 is a proposed assessment target; no complete ASVS certification or production acceptance is claimed.

For false reports, follow [the operational review guide](FRAUD_PREVENTION.md): account/rate-limit/duplicate controls support human verification, while GPS and photo contents remain untrusted evidence. Verified-local-email recovery and operator suspension are implemented with separate service/policy acceptance. Phone verification and automatic fraud classification are not implemented. Public discovery requires explicit factual acceptance; published news additionally requires an administrator-reviewed post.

## Access matrix

| Information/action | Guest | Citizen | Report owner | Administrator |
| --- | --- | --- | --- | --- |
| Accepted approximate list/map; published reviewed summary | Accepted/published only | Accepted/published only | Accepted/published only | Accepted/published only |
| Other users pending/unreviewed submission metadata | No | No | Own report only | Yes |
| Internal fact-check method/notes/actor | No | No | No | Yes |
| Raw description, exact location and incident/resolution photos | No | No | Own report | Yes |
| Submit a report | No | Yes | Yes | No (current citizen-only workflow) |
| Confirm another active report | No | Yes | Yes, except own report | Yes, except own report |
| Original GPS and accuracy | No | No | Own report | Yes |
| Private account record (name/email) and full history | No | No | Own report | Yes |
| Reviewed display-name snapshot on a published news post | Published credit only | Published credit only | Published credit only | Published credit only |
| Modify status/verified pin/assignment | No | No | No | Yes |
| Private notification inbox | No | Own account | Own account | Own account |
| Administrator provisioning | No | No | No | Maintainer CLI process |

## Implemented controls

- Sanctum cookie sessions/CSRF and revocable persistent device proofs for web; hashed, revocable native tokens with persistent default and optional expiry policy.
- Registration validates allowed fields and cannot assign the admin role.
- API role/ownership checks apply independently of hidden/visible UI controls.
- Server-side field validation, coordinate ranges, location-source rules, file size/count/dimension checks and actual image decoding.
- Server WebP re-encoding strips original image metadata; images are stored under generated UUID keys.
- Three separate location records prevent administrators from overwriting original GPS evidence.
- Public JSON projection omits private device evidence and live reporter identity/contact fields. An administrator-reviewed display-name snapshot may accompany an explicitly published post; it can be withheld, and legacy names are not backfilled.
- Unique confirmations, foreign keys, transactional report/history/audit writes, and locked workflow updates.
- Authentication and report submission throttles; parameterized database queries.
- HTML templates escape report text; map markers use text content; CSV fields escape formula-leading characters.
- Secrets and private artifacts are ignored by Git; R2/Firebase secrets are backend-only. Firebase web application identifiers are public configuration, not service-account credentials.
- Dependency audits and tests are part of the required pre-push gate.
- Local/test account seeding is explicit, transactional, environment restricted and preserves existing accounts. It uses configurable test email and three distinct private passwords; no default password is committed.
- New credentials require at least 12 characters and at most 72 UTF-8 bytes for the current bcrypt driver. Login/current-password inputs enforce the same byte ceiling; malformed email arrays return validation errors.
- Laravel responses include `nosniff`, frame denial, referrer policy and permissions allowing same-origin camera/GPS while denying microphone use. API/authentication responses are private/no-store. HTTPS production responses include HSTS without subdomain/preload assumptions.
- Production requests disable debug exception details and force Secure/HttpOnly session cookies before session middleware. Local HTTP development remains supported. `hazard:security-check` checks stored Phase 1 production configuration without returning secret values; it does not validate TLS, private bucket access, firewall, MFA, CSP, backups or operational readiness.

## Important limitations

GPS is client-supplied evidence and can be inaccurate or spoofed; consistency flags are review aids, not proof. New reports select from the 13 user-approved Basista barangays and the API enforces the same allowlist. Coordinate bounds only prompt a reference-area warning; there is no legal polygon-based geofence. Raw evidence and descriptions are now private. A separately reviewed public summary or citizen comment can still disclose personal information if its author includes it; there is no automatic face detection or text anonymization.

Uploaded images are resized/compressed before receipt, so the stored SHA-256 value is not a forensic hash of the original camera image. Original full-resolution files and their EXIF metadata are not retained. A closed report is an application workflow state, not an immutable records archive.

Administrator MFA, recovery/local-email verification, CSP, a scanner adapter and security-event cleanup are now implemented in source. Real mail/scanner configuration and operational approval remain pending; a formal incident-retention/purge workflow is not implemented. Native mobile tokens are retained through Capacitor secure-storage 7.1.3 using Android Keystore-backed AES-GCM storage. Browser tokens/passwords are never placed in localStorage. Web sessions use HttpOnly session/remember cookies with CSRF protection. The default inactivity window is 90 days; logout and password changes still revoke access. Simulated bridge tests verify lifecycle, not physical Keystore security. Browser Google Fonts and map tile requests contact third-party providers; a municipality may self-host fonts or choose an approved map provider.

## Decisions required before operational deployment

1. Approve who can administer reports and how access is reviewed, revoked and recovered.
2. Approve the public fields/photos policy, user-facing notice, moderation process and process for correcting/removing sensitive uploads.
3. Define retention and deletion periods for reports, photos, notifications, logs, devices and backups.
4. Define backup encryption, access, off-site copies, restore testing and incident contacts.
5. Configure HTTPS, secure session cookies, production error handling and least-privilege service credentials.
6. Approve the emergency hotline and explain that reports do not replace emergency calls.
7. Review cloud and map providers, account ownership and operating budgets without assuming permanent free tiers.
8. Complete the institution's research approval, participant information and consent process before collecting evaluation data.

## Incident response outline

Record the time, affected component and observed behavior without copying secrets into tickets. Restrict the affected credential/account, preserve relevant logs under authorized access, determine affected records, restore service, and verify the fix using a reproduction test. Follow the municipality's approved notification and escalation procedures. Review the event and update this document and the task tracker.

## Community content and account changes

Confirmed-report news requires explicit publication of a separate admin-reviewed title/summary and always applies the public projection, including for a signed-in administrator. No original photo, raw title/description, exact address, precise GPS, injury observation or private workflow history is returned by that feed. Comments display the commenter's account name and body; authors and administrators may remove comments. Reaction uniqueness is enforced by a database constraint. Admin CMS and reward edits require an authenticated administrator; client navigation alone is not an authorization boundary.

Report deletion is a soft delete available only to the owner before verification. It removes the report from application listings and news, and records an audit entry. Database records and uploaded evidence remain subject to a municipal retention and removal policy; this feature is not a personal-data erasure workflow. New uploads default to a private evidence disk. Existing local public evidence must be inspected and secured using `hazard:secure-evidence --apply`; the command verifies copied bytes before updating the storage pointer and removing the old public copy. R2 must use a private bucket with public/custom-domain access disabled; application authorization cannot protect an independently public bucket. Historical external copies/cache entries require separate operator remediation. Do not publish a real cash/gift promise until the municipality approves and funds it; the application records descriptions only.

Profile password changes require the current password. Separate rate-limit prefixes prevent public browsing from exhausting authentication or mutation quotas.

## Social identities and sign-in attempts

Google/Facebook identities are validated server-side through Socialite's session-bound state flow. New Google email must be provider-verified; Facebook email is recorded without a verified-email claim. Matching emails never merge accounts. Linking requires an authenticated password account and current password, then rechecks the user fingerprint/current credential at callback. Provider identities are unique, role remains citizen for new users, and provider access/refresh tokens are discarded. Social-only password authentication is disabled.

Android uses an external browser, a fixed app callback and S256-bound opaque code exchange. The native verifier is stored separately in secure storage, the code expires in 90 seconds and can be consumed once, and five incorrect proofs exhaust it. No bearer tokens/profile data appear in deep links; web continues using HttpOnly cookies. Redirect origins are exact allowlisted origins; callbacks/exchange responses disable caching and referrer forwarding. Provider exception logs contain only provider/class, never remote error details/codes/tokens. OAuth attempts are pruned after expiration plus a day. Follow [setup and privacy requirements](SOCIAL_AUTH_SETUP.md), including the provider's publication/deletion process; no municipal acceptance is claimed.

## Phase 2 implementation and operational boundary — 10 November 2026

The [Phase 2 record](SECURITY_PHASE_2_2026-11-10.md) supersedes earlier not-implemented statements only for the source capabilities it identifies. Admin MFA covers password, browser-provider and native-provider paths before full credential issuance. Operator-approved enrollment prevents password-only bootstrap. Recent verification is credential-bound; version/status checks and operator changes revoke prior authority. Recovery uses distinct verified local email, generic responses, atomic expiring proofs, queued private mail and retained MFA. Secrets use encrypted casts; hashes and current-source scanning are not claims of zero risk.

The packaged web/native CSP is enforced and locally tested. Malware scanning is an implemented fail-closed adapter with private bounded quarantine, but real scanning is disabled/unaccepted until a maintained daemon is supplied. Security logs have bounded cleanup; incident/backup retention, real service delivery, hosting access, recovery/alerts, physical Android and municipal/workload approval remain open. See [activation and acceptance tasks](SECURITY_OPERATIONS_2026-11-10.md).

## Durable device and public-background boundary

Persistent browser device proofs are hashed in MySQL and encrypted in HttpOnly same-site cookies; restoration checks active status, credential version and administrator MFA state. Rebuilt sessions do not gain recent verification. Logout deletes the current registration; version changes revoke affected registrations/tokens. Active native credentials have no automatic expiry by default, as requested; explicit logout/security revocation remain required. Browser/OS storage retention is outside the application's guarantee.

Dashboard decoration is explicitly public approved content. Its API serves only the current opaque image ID after administrator/recent-verification upload, decoding/re-encoding and optional scanning. Original incident photos remain private and cannot be selected by a path/URL field. High contrast suppresses decoration locally. Operational monitoring exposes aggregates only; synthetic backup credentials/key/artifacts are temporary and excluded from publication. Real production backups, alert routing/contacts, staging authorization and municipal approval remain open.

## Explicit factual acceptance and public-post boundary

CommunityVisible requires accepted lifecycle status plus factual review; Published adds publication time and reviewed nonempty public text. One shared query/predicate boundary protects discovery, counters, nearby suggestions, news and share-page access. Public search does not infer unpublished draft titles. Owner/admin private routes remain separate, and internal factual notes never enter reporter notifications/public resources. Role/session changes clear browser snapshots before replacement reads finish.

Verification and publication are locked, separately authorized actions with audit records and fresh administrative verification. Legacy data is not auto-certified. A human administrator can still make an incorrect decision; software enforces the record/permission boundary rather than establishing truth. Achievements derive only from the current account's accepted records, with no public leaderboard, no confirmation-based credit and no per-status double award. Rejection/removal changes current eligibility. Approved social pages contain only reviewed text and the application emblem, never original evidence. External publication/caching requires separate host/platform verification and cannot be recalled automatically.

## Account display preferences and optional email

Reading settings are private to the current account. The server ignores client ownership choices and requires a matching account-context guard before saving only allowlisted display fields. Guest preferences and signed-in caches use separate storage scopes; signing out restores the guest scope. Public resources never expose settings/email opt-in.

Authenticator 2FA uses six-digit TOTP from an app, not email sign-in codes. QR generation stays local; hashed recovery codes, replay prevention, enrollment approval, lockout and recent-action verification remain in force. Sample administrator seeding rejects real-address domains, and existing personal accounts are untouched.

Real mail requires enabled TLS SMTP with separate credentials and a deliverable sender. Reserved/sample recipient addresses are blocked. Report emails are off by default, require a locally verified recipient, contain only a reference/status/sign-in link and recheck eligibility at delivery. Private notes/photos/locations and credentials are excluded from ordinary queue payloads/logs. Security proofs remain encrypted and expire after30minutes. SMTP acknowledgement loss can cause an at-least-once retry; delivery is not guaranteed by the local fake-mail tests. See [readiness and limits](SETTINGS_2FA_EMAIL_2026-11-11.md).

## Emergency inbox and image-authenticity boundary

Active-admin report alerts contain no original title, identity, address, image or private note. Current-role inbox filtering and owner-scoped item-read prevent former-role/foreign-notification exposure. Report opening reauthorizes through the existing private endpoint; no caller-supplied redirect URL is followed. Stale identity replies cannot reopen another account's data.

User consent restricts private photos to BANTAY. The detector adapter rejects public/DNS names and redirects, streams at most16KB of response, bounds image/time, and suppresses response bodies/credentials in failures. Queue payloads contain UUIDs only. Scores never establish factual authenticity or automatically accept/reject/publish. Admin human confirmation and corroboration are required for new incident evidence. Report/photo locks and version checks reject stale decisions; late automatic results cannot overwrite a manual choice. Public/reporter responses omit internal scores/notes. No actual external transfer, model accuracy or emergency dispatch is claimed.
