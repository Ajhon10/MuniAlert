# Feature audit and runtime repair - 3 November 2026

Decision: **implemented core application; not accepted for production or field use**.

Follow-up: [responsive layouts and persistent sessions](RESPONSIVE_SESSIONS_2026-11-03.md)
adds remembered browser/Android login and closes the credential-revocation finding
below. This initial audit's counts and pre-fix findings are retained as historical
evidence; dependency, push durability and physical-device/deployment gaps remain.
The working tree includes earlier community-feature changes, which this review
preserves. No GitHub push or deployment was performed.

## Repairs

1. The running PHP 8.5 API printed deprecated `PDO::MYSQL_ATTR_SSL_CA` warnings
   before JSON. `/api/stats` had HTTP 200 and a JSON content type, but its body
   could not be parsed. `api.ts` swallowed the parse failure and returned `{}`;
   dashboard state was partially replaced before a later exception, and rendering
   failed at `card.value.toString()`.
   MySQL/MariaDB configuration now selects the modern driver constant on PHP
   8.4+, retaining a conditional fallback for the declared older PHP baseline.
   Composer's locked platform requirements pass on the local PHP 8.5.1 CLI.
   Other PHP runtimes have not been exercised in this review.
2. The client now rejects malformed JSON and validates dashboard counters and list
   metadata before replacing the previous snapshot. It handles HTTP 204 for
   successful deletion. Browser regressions cover malformed JSON, missing
   statistics, retry recovery and preservation of the last valid snapshot.
3. The Windows `hazard-server/public/storage` junction pointed to the old project
   location. Only that junction was replaced; photo directories were retained.
   It now targets this project's `hazard-server/storage/app/public`.
4. The CMS exposed editable fallback settings, then replaced them when its request
   finished. A browser test reproduced a disappearing hotline input. Editing now
   waits for settings and badge definitions; failed loading offers retry. The
   hotline pattern also escapes characters required by browser regex syntax.

## Feature inventory

| Capability | Reviewed evidence | Remaining acceptance |
| --- | --- | --- |
| Dashboard, maps and report lists | API/proxy JSON and real running browser verified; filters, CSV and demo browser coverage | Municipal geography/provider approval; large datasets; map/CSV cover the current page |
| Registration, login, logout, role navigation | Sanctum sessions/tokens; API authorization and browser account flows | Recovery, email verification and privileged MFA unimplemented |
| Photo reporting | Client compression/server MIME, size and dimension tests; browser upload/retrieval | Device camera, real photos and R2 |
| GPS/manual pin | API source/evidence validation; browser simulated GPS, confirmation and reset | Physical-device accuracy, denied permissions and poor reception |
| Duplicates/confirmations | Radius/category/time checks, unique confirmation API tests | Municipal triage and shared-network workload |
| Own reports/deletion | API ownership and soft-delete/audit tests; browser deletion | Approved retention/purge; existing photo URLs survive soft deletion |
| Municipal response | API lifecycle/evidence/audit tests; browser verification through closure | Operator acceptance and multi-process concurrency |
| Notifications | Transactional inbox creation; browser six updates, mark-read and detail opening | Firebase delivery and worker operations |
| CMS map/hotlines | API admin authorization; browser publication; loading race repaired | Approved real numbers, map center and ownership |
| Badges/rewards | API milestone/admin editing tests; four milestones displayed | Municipal approval/funding; fulfillment is manual |
| Profile editing | API validation/current-password check; browser persistence | Credential/session revocation gap below |
| Verified news/comments/reactions/sharing | Confirmed-status/privacy API tests; browser interactions and share links | Actual social-platform sharing, moderation and comment pagination |
| Responsive layout/Philippine clock | Live 375px viewport without overflow; desktop/390px browser checks; Asia/Manila clock code inspected | Physical devices, landscape and assistive-technology validation |
| Cloud/Android | R2/FCM code inspected; Capacitor plugin/asset synchronization passed | R2/FCM validation; SDK/JDK build, signing and physical devices |
| Documentation/release process | Traceability, route checker, generated appendix and verification script | Dependency findings prevent a passing release gate |

## Open implementation findings

- **Credential lifecycle:** `CommunityController::updateProfile` checks the
  current password but does not revoke previously issued bearer tokens or other
  browser sessions after a password change. This is a code-review finding;
  credential-transition security is not established by the current tests.
- **Push durability:** `ReportController::update` dispatches FCM after commit.
  A failure between commit and queue insertion can leave an inbox update without
  a push job. `SendReportPush` can redeliver to successful devices when a later
  device fails. An outbox/recovery policy and failure tests remain necessary.
- **Comments:** the API returns only the first 100, without pagination.
- Password recovery, administrator account management, advanced analytics,
  offline submission and a separate responder application are outside the
  documented scope. They must not be represented as available.

## Verification and release blockers

The running application was inspected at `http://127.0.0.1:5173`, with the API at
port 8000. Both return parseable JSON for health, statistics, catalog, settings,
news and reports. A fresh browser rendered overview, map, community reports and
news without page errors; the 375px layout fit the viewport. All five migrations
are applied in the normal development database. The smoke check modified no real
account or incident.

Laravel: **19 tests / 133 assertions passed**, using SQLite in memory. TypeScript,
production build, Composer manifest and Android asset synchronization passed.
The final combined browser suite passed **9 tests** (23.2 seconds), including
photo retrieval, CMS publishing and the full municipal/citizen lifecycle. The
release script passed its functional checks and stopped at the npm dependency
audit, with `passed: false`. Composer audit was executed separately and also
failed. Documentation checks were executed separately and passed. See the
[review evidence](evidence/feature-review-2026-11-03.json). This review does not repeat the
historical isolated MariaDB run, build a signed APK or establish capacity.

Dependency checks on 3 November 2026 fail:

- npm: **9 vulnerabilities: 4 moderate, 3 high, 2 critical**. Affected paths include
  `glob`/`minimatch` through Capacitor CLI, `markdown-it`, `re2js`,
  `shell-quote`/`concurrently`, and Vite. Several concern development/build tools;
  exact exposure and compatible fixes need review. Do not force the suggested
  Capacitor downgrade or suppress audit checks to obtain a pass.
- Composer: **CVE-2026-45070 / PKSA-wtxr-p26d-nn42**, `symfony/mime`, medium.
  Patch within compatible constraints and retest. No audit exception was added.

Before acceptance: resolve dependencies and credential/delivery findings,
approve municipal configuration and retention, validate R2/FCM, build/test signed
Android, configure HTTPS/worker/scheduler, exercise backup restoration, define
capacity/recovery objectives, and record municipal UAT. No deployed or
workload-accepted state is claimed.
