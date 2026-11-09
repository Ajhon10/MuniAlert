# 09 — Security, privacy and operational decisions

This attachment describes implemented controls and open operational decisions. It does not assert legal compliance, certify the application, or establish a municipality's retention policy.

For false reports, follow [the operational review guide](FRAUD_PREVENTION.md): account/rate-limit/duplicate controls support human verification, while GPS and photo contents remain untrusted evidence. Contact verification, account suspension and automatic fraud classification remain unimplemented. Public map/list entries may be awaiting verification; separate reviewed news and municipal response procedures remain necessary.

## Access matrix

| Information/action | Guest | Citizen | Report owner | Administrator |
| --- | --- | --- | --- | --- |
| Approximate public list/map and reviewed summary | Yes | Yes | Yes | Yes |
| Raw description, exact location and incident/resolution photos | No | No | Own report | Yes |
| Submit a report | No | Yes | Yes | No (current citizen-only workflow) |
| Confirm another active report | No | Yes | Yes, except own report | Yes, except own report |
| Original GPS and accuracy | No | No | Own report | Yes |
| Reporter name/email and full history | No | No | Own report | Yes |
| Modify status/verified pin/assignment | No | No | No | Yes |
| Private notification inbox | No | Own account | Own account | Own account |
| Administrator provisioning | No | No | No | Maintainer CLI process |

## Implemented controls

- Sanctum cookie sessions and CSRF headers for web; hashed, expiring and revocable tokens for mobile.
- Registration validates allowed fields and cannot assign the admin role.
- API role/ownership checks apply independently of hidden/visible UI controls.
- Server-side field validation, coordinate ranges, location-source rules, file size/count/dimension checks and actual image decoding.
- Server WebP re-encoding strips original image metadata; images are stored under generated UUID keys.
- Three separate location records prevent administrators from overwriting original GPS evidence.
- Public JSON projection omits private device evidence and reporter identity for unauthorized users.
- Unique confirmations, foreign keys, transactional report/history/audit writes, and locked workflow updates.
- Authentication and report submission throttles; parameterized database queries.
- HTML templates escape report text; map markers use text content; CSV fields escape formula-leading characters.
- Secrets and private artifacts are ignored by Git; R2/Firebase secrets are backend-only. Firebase web application identifiers are public configuration, not service-account credentials.
- Dependency audits and tests are part of the required pre-push gate.

## Important limitations

GPS is client-supplied evidence and can be inaccurate or spoofed; consistency flags are review aids, not proof. New reports select from the 13 user-approved Basista barangays and the API enforces the same allowlist. Coordinate bounds only prompt a reference-area warning; there is no legal polygon-based geofence. Raw evidence and descriptions are now private. A separately reviewed public summary or citizen comment can still disclose personal information if its author includes it; there is no automatic face detection or text anonymization.

Uploaded images are resized/compressed before receipt, so the stored SHA-256 value is not a forensic hash of the original camera image. Original full-resolution files and their EXIF metadata are not retained. A closed report is an application workflow state, not an immutable records archive.

Administrator MFA, account recovery, verified email enrollment, malware-scanning service integration and a formal record-retention/purge workflow are not implemented. Native mobile tokens are retained through Capacitor secure-storage 7.1.3 using Android Keystore-backed AES-GCM storage. Browser tokens/passwords are never placed in localStorage. Web sessions use HttpOnly session/remember cookies with CSRF protection. The default inactivity window is 90 days; logout and password changes still revoke access. Simulated bridge tests verify lifecycle, not physical Keystore security. Browser Google Fonts and map tile requests contact third-party providers; a municipality may self-host fonts or choose an approved map provider.

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
