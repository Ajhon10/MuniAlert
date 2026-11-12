# Security implementation phases

Requested 9 November 2026. Source: the user's pasted security architecture guidance. Its example NestJS code and suggested agent roles are reference material; this project retains Vue 3, Laravel 12, Sanctum and MySQL. No infrastructure changes, external scans, email delivery, or deployments are implied by this plan.

Use [OWASP Top 10:2025](https://top10.owasp.org/2025/en/) for risk categories and [ASVS 5.0.0](https://asvs.owasp.org/) for a future requirement-by-requirement assessment. ASVS Level 2 is a proposed verification target, not a certification or a claim that every requirement is implemented. Each phase must record implementation, local verification, deployment and operational acceptance separately.

## Phase 1 — Accounts and application baseline

Implemented in this change; verification results are recorded in [the dated review](SECURITY_PHASE_1_2026-11-09.md).

- Audit existing accounts by role without publishing personal records/passwords.
- Keep the explicit local/testing-only `DemoAccountSeeder`; add `npm run db:seed:accounts`, configurable neutral administrator email and transactional creation. Require distinct private passwords, refuse production, validate all inputs before writing, and preserve existing accounts/roles/passwords.
- Use the same password input bounds for registration, administrator provisioning, demo accounts, password changes, login and social-account password confirmation. The current bcrypt driver supports at most 72 bytes; reject null bytes and enforce the byte limit for multibyte text. Do not truncate or pre-hash passwords. Existing valid credentials are unchanged.
- Return validation errors for malformed email input rather than casting arrays into strings.
- Apply `nosniff`, frame denial, referrer and camera/location-preserving permissions headers to Laravel responses; prevent caching of API/authentication responses. Add HTTPS-only HSTS in production, without subdomain/preload assumptions.
- Disable detailed debug responses and enforce Secure/HttpOnly session cookies in production even if misconfigured. Preserve local HTTP development and the user's existing persistent-login behavior.
- Verify the existing server-side private API, role, ownership, field allowlist, upload, CSRF, CORS, throttling, logout/revocation and privacy boundaries with negative tests.
- Include a read-only deployment configuration check and document its limits.

This phase is not the complete pre-deployment approval from the pasted checklist: privileged MFA, CSP and the operational checks below remain release prerequisites.

## Phase 2 — Stronger identity, browser and upload protections

Implemented in source and locally exercised as recorded in [the 10 November review](SECURITY_PHASE_2_2026-11-10.md). Real mail/scanning, enrollment/recovery policy, hosting and physical-device acceptance remain separate and are not enabled by this record.

- [x] Source: Add administrator MFA enrollment, challenge, recovery codes and enforcement for both browser sessions and native tokens. Include social sign-in paths, replay/attempt/expiry protection, concurrent challenge consumption and credential-change invalidation. Never issue a full administrator credential before required MFA succeeds.
- [x] Source: Add recent reauthentication for sensitive administrative/security operations with short-lived, account/device-bound proof.
- [x] Source: Implement a safe account-recovery flow: generic responses, bounded request rates, expiring single-use tokens, atomic consumption, session revocation and configured email delivery. Provider-email verification and local email ownership must remain distinct. Confirm municipal enrollment/recovery policy before real use.
- [x] Source: Review account suspension and privilege changes, with current-status checks and revocation of previously issued credentials; provide an authorized operational process.
- [x] Source: Enforce CSP for the packaged website and its static entry point, and a compatible native policy. Cover MapLibre workers/styles/tiles, blob photo URLs, Firebase and optional custom providers with explicit origins; test real build behavior without `unsafe-eval` or broad script wildcards. Add equivalent web-server/static-asset headers and verify Hostinger behavior.
- [x] Source: Evaluate an available malware scanner for bounded private uploads, timeouts, unavailable-scanner behavior and quarantine. Existing image decoding/WebP re-encoding and type/size/count checks remain in force; these are not a malware-scanning service.
- [x] Source: Extend redacted authentication/admin security events and document bounded retention and operator access. Do not log passwords, tokens, full incident payloads or precise private GPS.

## Phase 3 — Continuous checks and operations

Local PHPStan/Larastan, strict TypeScript, checksum-pinned Gitleaks and full Action revision pins are implemented. External/owner operations below remain pending; tools in the pasted guidance are options, not a requirement to install every scanner.

The [local Phase 3 continuation](SECURITY_PHASE_3_DASHBOARD_2026-11-10.md) adds a redacted aggregate monitor/cron heartbeat and a guarded encrypted synthetic MySQL restore rehearsal. These do not complete live alert routing, real hosting/staging checks or production backup/key-custody acceptance below.

- [x] Local source: aggregate operational CLI/log signals, scheduler heartbeat, fixed isolated synthetic restore targets, authenticated archive/tamper checks and evidence-hash verification.

- [x] Source: Add maintained PHP/TypeScript static analysis and expanded current-source secret scanning to CI; review false positives with explicit narrow suppressions. CodeQL availability/licensing for this private repository must be checked before enabling it. Existing dependency audits and targeted publication scanning remain mandatory.
- [ ] Configure protected branches/release approvals and secret access with the repository owner. Pin reviewed action revisions and document dependency updates.
- [ ] Run authorized scanning on an isolated staging instance with synthetic data. Record the exact build, scan scope, findings and retest results; never actively scan third-party hosts or the owner's live database.
- [ ] Verify deployed HTTPS, trusted proxy settings, firewall/network restrictions, document root, PHP/web-server patch level, database least privilege and public access denial for private storage/configuration.
- [ ] Perform a backup-and-restore drill in isolation, covering database and private evidence; define approved RPO/RTO, encryption, retention and recovery ownership.
- [ ] Configure redacted suspicious-activity/availability/queue/storage alerts, incident contacts and a tested containment/recovery procedure. Obtain municipal data-retention and access decisions.
- [ ] Verify Android camera/GPS/session security on a physical phone; perform municipal UAT and representative workload/recovery testing.

## Mapping the pasted security matrix

| Area | Current baseline / evidence | Remaining work |
| --- | --- | --- |
| Authentication | Hashed passwords, generic incorrect-login response, throttled login/registration; persistent-session tests | Source MFA/local-email recovery implemented; real enrollment/delivery and abuse alerts pending |
| Roles / private objects | Admin/owner checks, private projections, current active/version checks and tested operator suspension/role controls | Municipal operator/access acceptance and staging review |
| API validation / SQL | Allowed validated fields, bounded list/analytics inputs, bound ORM/raw-query values, isolated tests | Ongoing route review, static analysis and staging scans |
| Database | Migrations/constraints, locked workflow changes and transactional writes | Actual least-privilege hosting credentials/network rules and restore drill |
| Uploads/downloads | Decode/re-encode, UUID names, size/count limits; private evidence; tested fail-closed ClamAV adapter and bounded quarantine | Real scanner service/signatures and verified private cloud settings |
| Vue / browser | Vue escaping and text map markers; no untrusted `v-html`; response-header baseline | Built CSP locally tested; actual static-host headers still need acceptance |
| Sessions | CSRF, Secure/HttpOnly, revocation, version checks, device-bound recent verification and native secure-storage adapter | Physical Keystore and real-host cookie acceptance |
| Admin/audit | Shared admin MFA, recent verification, report audit and redacted security events with bounded cleanup | Operational alert delivery and municipal retention/access decisions |
| Hosting | Source examples and deployment checklist; debug suppression guard | Real account configuration, TLS/firewall/patch/secret validation |
| Dependencies/CI | Locked installs/audits, PHPStan/TypeScript, redacted Gitleaks, pinned Actions and full local gate | Owner-configured release protection and exact-SHA remote CI |
| Monitoring/recovery | Incident-response outline and documented private storage boundaries | Tested operational alerts, backup restore, retention and contacts |
| Password reset/email | Tested generic reset/verified-local-email flows, atomic expiring proofs and ID-only mail queue; delivery flag disabled | Municipal recovery approval, real mail configuration and delivery/outage acceptance |

Documentation and test passes establish local evidence only. Do not label the whole website “hacker-proof”, “OWASP compliant” or production approved on that basis.
