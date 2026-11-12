# 05 — Testing and validation

## Single validation toasts and password visibility — 11 November 2026

The client refinement checks one visible/persistent sign-in error, retry/dismissal behavior, native validation cancellation, first-field focus, accessible field links/cleanup, all eight password fields, value/autocomplete preservation, no submission from visibility buttons, registration-mode reset and 200% phone reflow. Existing MFA, location-denial and session checks retain their assertions while looking for the sole toast. Database inspection uses read-only metadata/count queries on localhost and reports no cleanup action. Final results and inventory are in [the refinement](TOAST_PASSWORD_SCHEMAS_2026-11-11.md) and its evidence. Earlier dated results below are historical.

Final result: **all 19 checks passed; 110 backend tests / 994 assertions; 72 browser tests; zero PHPStan errors and dependency findings**. Production/native/web builds, Android asset synchronization and refreshed manuals/technical exports are included. Old selectors were updated to target the same intended input/error outcomes after the presentation changed; no assertions or cases were skipped. MySQL schemas were inspected without modification. No deployment or physical-device acceptance is claimed.

After that gate, screenshot review found the global input-padding rule overriding password-toggle spacing at 200%. The final scoped-selector correction passed **11 focused browser checks**, including a computed text-reservation versus button-width assertion. Native/web builds and Android asset synchronization were refreshed. The full gate precedes this final CSS refinement; a future push still requires a fresh exact-state gate.

## Reading and feedback follow-up — 10 November 2026

The new [reading/feedback review](ACCESSIBILITY_FEEDBACK_2026-11-10.md) records 51 passing isolated Chromium browser tests, including six new cases for 200% reflow, saved/reset preferences, denied storage, device reduced motion, correct number animation, modal-safe error feedback, dismissal/pause behavior and enlarged-text landscape controls. Existing citizen/admin reporting, CMS, profile, news, map, session, provider-return and modal regressions passed. Vue type checking, production build, same-origin web packaging, Capacitor Android asset sync, documentation consistency and Git whitespace checks also passed. The generated technical appendix was refreshed.

These are relevant local checks for the client change, not a fresh full 14-check release gate. Backend source and existing local records were preserved; the browser runner used only its isolated test database. No APK/physical-device/TalkBack, cloud, deployment, workload or municipal acceptance is claimed. Evidence: [local check record](evidence/accessibility-feedback-2026-11-10.json).

## Latest confirmed-hosting follow-up

The user selected Hostinger Web/Cloud with Android plus responsive web. [The hosting guide](HOSTINGER_WEB_CLOUD.md) records the bounded scheduled worker and relocated public entry; its final verification record supersedes the earlier UI baseline for these changes. Scheduler tests cover bounded batches, overlap, expired-lock recovery, preservation without Firebase and environment-controlled registration.

## Latest UI / stack review

See [4 November 2026 review](UI_STACK_REVIEW_2026-11-04.md) for current PHP 8.5, liquid-glass/responsive UI, comments, push durability, dependency and combined-hosting verification. Earlier records below describe their stated dates and must not override the latest result. No signed Android or real cloud-service result is claimed.

## Mandatory release rule

The user requires **all tests to pass before a GitHub push**. Run parent `npm run verify` on the exact code intended for the push and run each application's own verification command. Each repository has a pre-push hook and CI workflow. Do not bypass the hooks, skip failing tests, or describe incomplete execution as a pass. Any subsequent change invalidates affected results.

The gate covers Composer validation, PHP formatting, Vue type checking, production build, Laravel tests, browser integration, Capacitor synchronization, dependency audits, and documentation consistency. Its local record is `.cache/verification-last.json`. GitHub submission still requires a user request.

## Automated test environment

- Windows development workspace; Node 22.21.0; PHP 8.2.30 with GD/WebP, PDO MySQL and PDO SQLite.
- Laravel 12, Sanctum 4, PHPUnit 11; Vue 3 with Vite and TypeScript; Playwright Chromium.
- SQLite in memory for the default Laravel suite.
- Isolated local MariaDB/MySQL-compatible databases on a separate port for migration/workflow and browser verification. These are not existing municipal databases.
- Browser tests start their own API and Vite processes and shut them down after testing. They use ports 18000 and 15173 by default so normal development servers can stay running. The default browser database is `.cache/e2e.sqlite`; the optional MySQL database is explicitly named `municipal_hazards_e2e`.

## Feature tests

Source: `hazard-server/tests/Feature/HazardWorkflowTest.php`.

| Case | Verified behavior |
| --- | --- |
| Manual submission | Unconfirmed location rejected; valid manual submission persists a compressed photo, history and audit |
| Adjusted GPS | Distant GPS is preserved and flagged while the report remains submitted |
| Source and coordinate validation | Incorrect `gps` labels, missing coordinates and out-of-range values rejected |
| Photo safety | More than three photos, oversized images, invalid content and excessive dimensions rejected |
| Authorization/privacy | Citizens cannot update reports or access another reporter's private evidence; public data excludes private GPS/identity |
| Admin progression | Invalid skipping rejected; full workflow, independent verified coordinates, history, notifications and audits retained |
| Nearby and confirmations | Same-category/radius filtering; unique confirmations; resolved reports cannot be confirmed |
| Account/token controls | Registration cannot assign admin privileges; logout revokes the mobile token |
| Distance calculation | Zero and known-distance cases remain correct |

## Browser tests

Source: `tests/reporting.spec.ts`.

1. Explicit demo dashboard, category filtering, report detail, CSV download, phone viewport, navigation drawer and absence of page-wide horizontal overflow.
2. Real web signup through CSRF/session authentication, client-side photo compression, manual coordinates, explicit confirmation, API submission, photo retrieval, private history, session persistence after reload, own-report list and logout.
3. GPS capture with browser permission, confirmation requirement, pin adjustment, `gps_adjusted` source and invalidation of previous confirmation.

## Current execution record

Executed on **2 October 2026** in the local environment described above:

| Check | Recorded result |
| --- | --- |
| Composer manifest and lockfile | Passed strict validation |
| PHP formatting | Passed |
| Vue TypeScript validation | Passed |
| Production frontend build | Passed |
| Laravel tests, SQLite | 11 passed / 71 assertions |
| Browser tests, isolated SQLite API | 3 passed |
| Laravel tests, isolated MariaDB/MySQL-compatible engine | 11 passed / 71 assertions |
| Browser tests, isolated MariaDB/MySQL-compatible API | 3 passed |
| Capacitor Android synchronization | Passed; not an APK build |
| npm dependency audit | Zero vulnerabilities reported |
| Composer dependency audit | No vulnerability advisories reported |
| Documentation | Local links, 15 requirements, API-route coverage and generated appendix freshness passed |

The two Laravel scaffold smoke tests are included in the 11-test count. The [baseline gate record](evidence/verification-baseline.json) preserves a successful consolidated run. Subsequent evidence/documentation cleanup is verified again with the same gate; `.cache/verification-last.json` is the current local result. Before every push, rerun the full gate on the exact project state. A historical pass does not authorize a later push.

GitHub Actions is configured but has not run remotely because this project has not been pushed. The local compatibility run used MariaDB; the workflow separately specifies MySQL 8.4, whose remote execution remains unobserved.

## Interface evidence

### Local setup regression follow-up

After the split into `hazard-client` and `hazard-server`, the user's local MySQL instance authenticated successfully. `php artisan migrate:status` showed all application migrations as run. The explicit `DemoAccountSeeder` created one administrator and two citizens, and local read-only checks verified each role and distinct password hash. The Laravel suite now passes **14 tests / 83 assertions**, including two seeder cases. No phone APK or real-device result is claimed.

Initially, the local MySQL instance rejected the example's empty password. The correct account password was added to the ignored `.env`, and migrations subsequently completed. `AdminCommandTest` still checks that a pasted Markdown email link is rejected before any database connection. The full release gate must be rerun before a future push.

The following screenshots were captured by the browser test using **fictional, read-only demo records**. They demonstrate interface layout, not real municipal incident collection or field evaluation.

![Desktop demo dashboard](evidence/dashboard-desktop.png)

![Phone demo dashboard, 390-pixel viewport](evidence/dashboard-mobile.png)

## Reproduction

```sh
npm ci
composer --working-dir=hazard-server install
# Copy hazard-server/.env.example to hazard-server/.env if missing; generate APP_KEY.
php hazard-server/artisan key:generate
npx playwright install chromium
npm run verify
```

For the optional MySQL test run, create dedicated empty `municipal_hazards_test` and `municipal_hazards_e2e` databases. Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_DATABASE=municipal_hazards_test` for the Laravel suite; set `HAZARD_E2E_MYSQL=1` for browser tests. Never point either at a production database. CI provides an isolated MySQL 8.4 service.

## External validation still required

### Responsive layouts and persistent sessions follow-up

The [follow-up record](RESPONSIVE_SESSIONS_2026-11-03.md) supersedes the earlier
memory-only Android policy and closes the password-change credential-revocation
finding. It documents the responsive changes, 90-day configurable persistent-login
policy and [Android phone testing procedure](ANDROID_TESTING.md). Final executed
results and source hashes are in its linked evidence. Browser device emulation
and a simulated Capacitor secure-storage bridge are separate from physical
Android testing; no APK compilation or real-device acceptance is claimed.

Follow-up final result: **23 Laravel tests / 150 assertions and 16 browser tests
passed**. Production build, type checking, PHP formatting, Android sync and
documentation checks passed. A normal-runtime smoke check also passed. The
release script stops at the nine-vulnerability npm audit; the separately run
Composer audit still fails on `symfony/mime`. Earlier 19/nine counts below remain
historical. See the dated follow-up evidence for source hashes and exact results.

### Runtime and feature review - 3 November 2026

The [current feature audit](FEATURE_AUDIT_2026-11-03.md) records the PHP 8.5/API
rendering repair, moved photo-storage junction, CMS loading repair, feature
inventory and remaining implementation gaps. Historical clean dependency audits
above no longer represent the current state: npm reports nine vulnerabilities
and Composer reports one advisory. The release gate is not currently passing.

The normal running Vue/Laravel application passed a read-only browser smoke check
at 375px with no page errors. Laravel passed 19 tests / 133 assertions on isolated
in-memory SQLite. The production client build and Android asset/plugin sync
passed; no signed APK or physical-device claim is made. New browser coverage
checks malformed/partial API responses and recovery, municipal response through
closure, citizen inbox/mark-read, profile persistence and unverified deletion.

Final combined browser result: **9 tests passed (23.2 seconds)**. `npm run verify`
passed manifest, formatting, type checking, build, 19 Laravel tests, nine browser
tests and Capacitor synchronization, then stopped at the npm audit. Composer
audit separately failed on one advisory; documentation checks separately passed.
The [review evidence](evidence/feature-review-2026-11-03.json) retains these
results and source hashes. No passing release gate, deployment, field validation
or workload acceptance is claimed.


| Area | Evidence required before claiming completion |
| --- | --- |
| Cloudflare R2 | Actual upload, URL retrieval, error handling, deletion/retention, configured access control |
| Firebase web and Android | Real-device permission, token registration, foreground/background receipt, invalid-token cleanup |
| Native Android | Signed APK/AAB, SDK/JDK build log, physical device camera/GPS tests |
| Municipal geography | Approved boundary source, coordinates within/near actual covered barangays |
| Hostinger Web/Cloud | HTTPS, session/cookie behavior, bounded scheduled push, permissions and backup/restore |
| Usability and field accuracy | Approved participant procedure, observed completion time/errors and actual GPS results |
| Capacity and reliability | Defined workload, measured throughput/latency, outage and recovery tests |

Automated simulated GPS is not a measurement of real phone GPS accuracy. A successful database notification is not proof that a push reached a device. Map rendering requires network tiles and compatible WebGL.

## UAT record template

Record: case ID, requirement, software commit, tester role, date, device/OS/browser, network, prerequisites, steps, expected result, actual result, pass/fail, screenshot/log reference, defect ID, retest date, and evaluator approval. Use the cases and blank acceptance form in [Traceability and acceptance](10-traceability-and-acceptance.md). Do not fabricate respondent counts, satisfaction scores, performance percentages or deployment evidence.

## Community-feature verification (2026-10-02)

The isolated Laravel feature suite now checks CMS authorization/validation, confirmed-only news privacy, reactions and comments, badge thresholds, profile password checks, soft deletion, and separation of browsing and registration rate limits. The Playwright suite checks citizen submission and logout confirmations, profile badge visibility, an administrator publishing a hotline through the CMS, and confirmed-news reactions, comments and sharing links. The test runner creates random-password accounts only in its isolated test database. Real emergency numbers, map coordinates, physical-device behavior, and reward fulfillment remain to be validated by the municipality.

## Social authentication follow-up

`SocialAuthenticationTest` uses isolated SQLite and mocked provider identities. It verifies real Google redirect state/PKCE generation and invalid-state rejection, safe creation/repeat identity, collisions, explicit linking, stale proof, HttpOnly remembered sessions, native verifier binding, expiration, replay and five-failure exhaustion. `social-auth.spec.ts` covers both provider buttons, availability, cancellation, return intent, forged return queries and a simulated Android cold launch. Ordinary email/password, reporting and profile regression tests remain required. Browser-runner environments explicitly disable real provider credentials. Real Google/Facebook consent and physical-device/Hostinger acceptance remain pending; see [the setup checklist](SOCIAL_AUTH_SETUP.md).

Social-auth local evidence (4 November): 52 Laravel tests / 390 assertions, 28 browser tests and five launcher tests; the twelve-check parent gate passes. Both npm dependency lockfiles have zero findings; Composer has no advisories and the locked platform requirements pass on PHP 8.5.1. These results use isolated test data/mocked identity providers, including simulated Android cold launch/cancellation. Actual Google/Facebook, physical Android and target hosting acceptance remain pending. See [the dated record](SOCIAL_AUTH_2026-11-04.md).

## Interview and Android alignment ? 8 November 2026

See the [dated review](INTERVIEW_ALIGNMENT_2026-11-08.md) and [evidence record](evidence/interview-alignment-2026-11-08.json). Added API coverage checks private projections and search, owner/admin media access and no-store headers, reviewed publication, incident observation/time validation, resolution-photo limits/cleanup, more-than-one-page PHT period totals and legacy evidence repair. Browser coverage exercises the full lifecycle with distinct resolution evidence, reviewed news, phone reporting with denied-GPS fallback, and phone summary CSV. These are local automated results; no physical Android, actual R2/FCM/OAuth, deployment or municipal UAT is implied.

The new alignment suite additionally passed on isolated MySQL 8.0.44 (eight tests, 85 assertions). A pre-migration test-database guard rejects unapproved schemas. Normal startup/browser/database smoke and simulated 200% text enlargement at 320px passed; OS scaling and physical hardware remain untested. The initial Composer audit failure is preserved in the dated review; five minimal Symfony 7.4.23 patches address the reported findings without a framework migration.

Final local result: **all 12 release checks passed**, including **60 backend tests / 475 assertions, 31 browser tests and five launcher tests**, with zero npm findings and no Composer advisories. The separate MySQL alignment suite passed eight tests / 85 assertions. See [the final gate](evidence/interview-gate-final-2026-11-08.json). This records local verification, not physical Android, live cloud delivery, deployment, workload acceptance or municipal UAT.

## MuniAlert identity, guidance and promotion — 9 November 2026

The branding/guide follow-up passed 61 backend tests / 487 assertions, 36 browser tests and five launcher tests. Coverage includes the unchanged browser-session cookie, guide return after social sign-in, the complete highlighted tour, manual search and phone reflow. A separate normal-runtime guide smoke passed at 320px with simulated 200% text enlargement. Final gate, video lint/audit, MP4 audio/metadata/playback and preview results are recorded in [dated evidence](evidence/branding-guide-promo-2026-11-09.json) and the [handover](BRANDING_GUIDE_PROMO_2026-11-09.md). These results supersede earlier local test counts and retain physical-device/cloud/municipal limitations.

## Basista map follow-up — 9 November 2026

`BasistaScopeTest` covers the 13-name allowlist, ASCII/case normalization, rejection outside the list and the count-preserving conditional camera migration. `maps.spec.ts` uses real MapLibre rendering and the actual local boundary files with deterministic base styles, covering geometry visibility, Fire/accident filtering, synchronized expansion, persisted modes/layers, retry recovery, packaged paths, 320–1440px reflow, simulated 200% text and retained confirmed pins. A separate real-provider review loads Clear/Streets/Dark and attributed labels with synthetic reports. Final results and source hashes are in [the Basista map evidence](evidence/basista-map-2026-11-09.json) and [handover](BASISTA_MAP_2026-11-09.md). Reference boundaries and browser simulation do not establish physical-device or municipal operational acceptance.

## Fresh collaborator download — 9 November 2026

A separate source archive of the published repository was extracted without `.git`, real environments, dependencies, uploaded evidence or compiled builds. Example-based local configuration, a new app key and a new MySQL schema were created; locked npm/Composer installation, platform checks, migrations/seeding and a frontend production build passed. Phone-sized Chromium exercised citizen registration, a synthetic report, confirmed Basista coordinates and authenticated private-photo loading. A separately provisioned administrator signed in and saved a response. No owner database dump or original key/upload folder was needed. The administrator command used scripted prompt answers in the headless harness; interactive terminal password entry and physical USB/Android behavior remain manual checks.

See [the collaborator handover](COLLABORATOR_SETUP_2026-11-09.md) and [evidence](evidence/collaborator-setup-2026-11-09.json). This is a fresh-source test on the existing workstation with prerequisites already installed, not a clean operating-system or physical-device certification.

## Security Phase 1 — 9 November 2026

New tests exercise anonymous private/admin API requests, protected registration/profile fields, malformed email arrays, bcrypt's byte ceiling and multibyte boundary, generic/throttled login, actual CSRF enforcement, CORS denial, no-store/security headers and production debug/Secure/HttpOnly behavior. The explicit seeder is tested for production refusal, invalid/missing/reused passwords, configurable/noncolliding email and existing-account preservation. The read-only deployment configuration checker is tested with unsafe local and safe/unsafe synthetic production settings. See [the dated review](SECURITY_PHASE_1_2026-11-09.md), [machine-readable result](evidence/security-phase-1-2026-11-09.json) and [remaining phases](SECURITY_IMPLEMENTATION_PLAN.md). These checks do not establish MFA, CSP, malware scanning or production operational readiness.

The final gate passed all 14 checks: **79 backend tests / 626 assertions, 45 browser tests and five launcher tests**, with zero npm findings and no Composer advisories. It includes rejected embedded-null-byte credentials and the neutral, per-run synthetic administrator identity. All tests are local; this phase is not pushed or deployed.

## Security Phase 2 and Phase 3 automation — 10 November 2026

New checks exercise administrator MFA before credential issuance, encrypted setup material, operator-approved enrollment, one-use TOTP/recovery codes, account-wide five-failure lockout, challenge expiry/browser binding, suspension/role changes, last-active-admin protection and credential-bound recent verification. Recovery checks cover generic responses, trusted origins, separate local email ownership, ID-only transactional mail jobs, atomic consumption and retained MFA. Upload checks cover both original/converted bytes, private bounded quarantine and unavailable-scanner rejection. Mail/providers/scanner verdicts are isolated fakes; protocol parsing is tested separately from a real ClamAV service.

The focused MySQL suite passed **37 tests / 339 assertions**. Seven browser security checks passed on isolated MySQL, including a race across two independent PHP processes, cancellation of a delayed sign-in response, local QR/large-text reflow, preserved CMS edits through recent verification, recovery-fragment removal and real packaged CSP enforcement. Full regression and the expanded 18-check gate are recorded in [the current evidence](evidence/security-phase-2-2026-11-10.json) and [review](SECURITY_PHASE_2_2026-11-10.md). These local results do not establish real SMTP, maintained malware scanning, remote CI, hosting, backup/alerts, physical Android or municipal/workload acceptance.

The final gate passed **all 18 checks**, including **98 backend tests / 786 assertions, 58 browser tests and five launcher tests**, zero PHPStan errors, source-secret and negative-fixture checks, current production builds/Android asset synchronization and zero dependency findings. Final result recording and refreshed documentation/manual exports are local, uncommitted artifacts. The local production-readiness configuration check correctly remains unsuccessful while administrator enrollment and real mail/scanning/hosting requirements are unmet.

## Phase 3, durable sessions and dashboard follow-up

`PersistentSessionTest` adds default non-expiring native credentials, explicit revocation and migration boundaries while retaining the optional rolling-expiry tests. `DashboardBackgroundTest` checks administrator/recent-verification restrictions, image type/dimension rejection, sanitized public selection and old-file cleanup. `SecurityMonitorTest` checks stale heartbeat, aggregated authentication/queue failures and private-data omission. Browser tests restore administrator access from only a device cookie, reject replay after logout, preserve a second browser after the first logs out, retain password-change revocation, exercise background preview/publication/removal, 200% sidebar alignment and actual page-animation calls with OS/app reduced motion. See [current local evidence](evidence/security-phase-3-dashboard-2026-11-10.json); real hosting/service/device/production restore acceptance remains pending.


Final continuation result: **19 checks passed; 103 backend tests / 825 assertions; 63 browser tests; five launcher tests; zero dependency findings.** Separate MySQL verification: **22 backend tests / 171 assertions and 14 browser tests**, including a two-process MFA race. The synthetic encrypted restore/hash/tamper rehearsal passed without dumping application records. Current PDFs and ownership repair are recorded in [the continuation](SECURITY_PHASE_3_DASHBOARD_2026-11-10.md). Production alert/cron, real backup/keys, hosting/service/device and municipal acceptance remain unverified.


## Text-size slider and rolling counters

The UI continuation passed **65 browser tests**, then **eight focused checks after final percentage-badge polish**. Checks exercise drag/keyboard/min/max/5% steps, intermediate-size persistence, denied storage, 165%/200% phone reflow, exact grouped/zero-padded reel values, retained counter instances through rapid background updates, stale-animation cancellation and device reduced motion. Type checking, native/web builds, same-origin packaging and Android asset synchronization passed. Both manual PDFs and the technical export were refreshed. See [the follow-up](SLIDER_ROLLING_NUMBERS_2026-11-10.md) and [evidence](evidence/slider-rolling-numbers-2026-11-10.json). No fresh full backend/security gate, deployment or physical-device acceptance is claimed for this frontend-only change.

## Private community access, fact-checking, publication and achievements

The navigation/news refinement additionally verifies every own lifecycle status, rejection history, legacy community report-list redirects, reviewed attribution/time snapshots, stale-snapshot behavior on account edits, name withholding, escaped HTML and absence of private contact/evidence fields. News preview/feed use the same incident-detail component and pass 200% phone reflow. Results are recorded in [the refinement](COMMUNITY_NAVIGATION_NEWS_DETAILS_2026-11-10.md) and its linked evidence; earlier counts below remain historical.

Final navigation/news refinement: **19 checks passed; 110 backend tests / 994 assertions; 69 browser tests; zero PHPStan errors and dependency findings.** Focused isolated MySQL: **33 tests / 411 assertions**. The final gate includes the report-form/privacy-guide attribution notice, production/native/web builds and Android asset synchronization. Both user manuals and the technical appendix were regenerated. No real incident/identity publication, external social message, deployment or physical-device acceptance is claimed.

CommunityReviewTest covers accepted-only discovery and private owner/admin permissions. It checks legacy draft privacy, factual attestation, internal notes, separate explicit publication, escaped server-rendered sharing, withdrawal/rejection and per-category ownership/threshold counts. Existing workflow/inbox/push/publication tests now use the explicit review/post boundaries without bypassing authentication/MFA. Browser tests exercise real member/admin/owner flows, share URLs/HTML, separate profiles, 200% category-card reflow and private-cache clearing while public reads remain blocked. Final results and limitations are recorded in [the review](COMMUNITY_REVIEW_ACHIEVEMENTS_2026-11-10.md) and [evidence](evidence/community-review-achievements-2026-11-10.json). Synthetic tests do not determine real incident truth or complete municipal/hosting/social-platform/device acceptance.

Final local result: **19 checks passed; 108 backend tests / 928 assertions; 68 browser tests; zero PHPStan errors and dependency findings.** The focused isolated MySQL suite passed **31 tests / 345 assertions**. The nine focused browser checks also cover persistent-session and private-cache regressions. Production builds and Android asset synchronization passed; regenerated manuals and the technical appendix include the new review/publication instructions. No real incident was certified or published, and no external social message, push or deployment occurred.
## Settings, account preferences, 2FA and email refinement

AccountPreferencesMailTest checks allowlisted per-account reading saves, invalid/stale identity requests, transaction rollback and private ID-only report mail, delivery eligibility/opt-out/deletion and transport readiness. Account settings browser tests cover separate guest/account scopes, switching accounts, a fresh browser restoring settings, removed shortcuts/callout, Settings/manual navigation, opt-out with unavailable email and six-digit code paste/keyboard access at 200% phone text. Existing enrollment, replay, step-up and delayed-MFA cancellation checks remain in the full gate. The focused MySQL run passed 25 tests / 190 assertions on municipal_hazards_test. Real mail uses no live credentials in automated tests; Gmail delivery/public HTTPS links, physical Android/TalkBack and municipal acceptance remain pending. See [implementation and current verification](SETTINGS_2FA_EMAIL_2026-11-11.md).

### Final Settings verification

The final Settings/2FA/email gate passed all **19 checks**, including **114 backend tests / 1030 assertions and 74 browser tests**, zero PHPStan errors and zero dependency findings. Separate isolated MySQL checks passed **25 tests / 190 assertions**. Native/web production assets and Android synchronization are current. Initial failures were stale motion counting after entering Settings and repeated dismissal during a toast leave transition; both test corrections retain the behavior assertions. Both manuals and the technical appendix are refreshed. No real email, deployment or physical-device acceptance is claimed; BANTAY still needs its separate Gmail app password. Result-record changes receive documentation/secret checks, and future pushes require a fresh exact-state gate.

See [redacted feature evidence](evidence/settings-2fa-email-2026-11-11.json) and [nineteen-check report](evidence/settings-2fa-email-gate-final-2026-11-11.json).

## Emergency-first flow, notification routing, audio and image review

EmergencyImageReviewTest covers safe active-admin submission alerts, owned/role-scoped single reads, stale action proof exemptions, new-photo review gating, reporter/public analysis privacy, loopback-only endpoints, redirects/malformed/outage results, bounded ID-only jobs, stale-result/manual-review cancellation, probability outcomes without automatic lifecycle changes, transactional rollback/file cleanup and optional hotline coordinate validation. All122 backend tests /1131 assertions passed during implementation; isolated MySQL coverage passed33 tests /427 assertions. These are intermediate implementation checks, not the final gate result.

Browser scenarios cover emergency Yes/search/location sorting/no-call submission at200% phone text, assistance persistence through registration, configured category frequencies/patterns with a silent AudioContext fixture, mute/initial-inbox baseline, item read/navigation, private image review and floating-tour geometry/focus. Existing reporting tests explicitly complete No and human image confirmation without bypassing server gates. Real model accuracy, call/audio delivery on physical devices, agency coverage and dispatch/workload acceptance are not established by synthetic tests.

### Final emergency-workflow result

Final emergency-workflow verification passed all **19 checks**, including **122 backend tests / 1131 assertions and 77 browser tests**, zero PHPStan errors and zero dependency findings. Separate isolated MySQL checks passed **33 tests / 427 assertions**. The native/web builds, single-origin package and Android synchronization are current. Both user manuals and the technical appendix are refreshed. No real classifier, agency call, external image transfer, deployment or physical-device/workload acceptance is claimed. Approved contacts and a maintained evaluated local classifier remain configuration tasks. Result-record changes receive documentation/secret checks; a future push requires a fresh exact-state gate.

See [feature evidence](evidence/emergency-workflow-2026-11-11.json) and [gate report](evidence/emergency-workflow-gate-final-2026-11-11.json).
