# UI, stack and feature review — 4 November 2026

## Delivered changes

- Applied LUMA's Apple/system font stack, removed remote font downloads, and added
  shared liquid-glass materials for the header, controls, dialogs and phone dock.
  Kept report content readable with solid surfaces, status labels, focus indicators,
  reduced motion/transparency, high contrast and no-backdrop-filter fallbacks.
- Added a five-action phone/tablet dock with a direct reporting action and More
  drawer; retained administrator-specific review/manage actions. Added filter
  clearing and matching counts, view URLs/browser back, and sign-in resumption of
  a guest's selected protected destination. Dashboard inbox/data updates now use
  the same response-generation guard.
- Demo news uses fictional preview records and disables mutations/sharing rather
  than mixing live news with demonstration reports. Replaced emoji reactions with
  Lucide icons and added image dimensions/lazy loading and a news loading state.
- Replaced the fixed first-100-comments response with bounded 25-comment pages and
  an ascending ID cursor. The interface appends pages, deduplicates IDs, retains
  loaded comments on a failed request and allows retry.
- Report changes, audit/history, inbox and per-device push jobs now share the
  application database transaction. One failing device retries independently of
  successful devices. Unregistered or reassigned devices are skipped/removed.
  Existing batch jobs convert to per-device jobs. Delivery remains at least once
  across an FCM acceptance/worker crash boundary; stable notification IDs are sent.
- Added `npm run build:web`: Vue compiles to `dist-web`, then packages inside
  Laravel's `public/app`. Laravel can serve web and API on one domain. Android's
  separate `dist` output remains available. Corrected deployment worker/cron paths
  and simplified the Nginx document root to the Laravel public directory.
- Added explicit PHP 8.5 selection for Windows parent launch/test/verification
  scripts. The workstation shell still resolves plain `php` to XAMPP; no machine
  PATH, Apache runtime or remote PHP installation was changed.
- Updated compatible npm dependencies and Symfony Mime within the existing major
  constraints. Retained an explicit patched gRPC 1.x override for Firebase's unused
  Firestore dependency; no audit exception or forced framework downgrade was used.
  Both root and standalone-client lockfiles were refreshed.

## Stack decision

Keep Vue 3, TypeScript, Capacitor Android, Laravel, Sanctum and MySQL. Keep
source separation and use a combined web deployment. Hostinger VPS remains the
recommended operations target for a supervised worker. Web/Cloud hosting can be
considered only after verifying cron/process limits and accepting notification
latency. See [the deployment assessment](STACK_AND_HOSTINGER.md) and
[the interface direction](UI_DESIGN.md).

## Local verification

The final release gate and detailed command results are recorded in
[dated evidence](evidence/ui-stack-review-2026-11-04.json). The working record is
`.cache/verification-last.json`; historical passes do not establish the current
result. All **11 gate checks passed**. Laravel: **27 tests / 188 assertions**; browser integration: **20 tests**. Type checking, both production build variants, PHP formatting, strict Composer validation, Android asset synchronization and documentation checks passed. Root npm and standalone-client audits report **zero vulnerabilities**; Composer reports **no advisories**. Locked platform requirements pass on PHP 8.5.1. Synchronization is not an APK build.

A separate read-only smoke check of the normal development app on ports 5173/8000 returned Connected, fit a 390px viewport and produced no page errors. Public health/catalog/stats/settings responses returned HTTP 200 with valid JSON. Existing development records were not changed. `npm run dev` was left running for local review.

Interface evidence uses explicitly fictional demo reports:

- [Desktop workspace](evidence/glass-desktop-2026-11-04.png)
- [Phone workspace](evidence/glass-mobile-2026-11-04.png)
- [Phone news](evidence/glass-news-mobile-2026-11-04.png)

The two applications also retain their independent verification commands. Both independent commands passed: `composer run verify` on PHP 8.5 and `npm run verify --prefix hazard-client`. The capstone Markdown/HTML/PDF were regenerated; documentation consistency passed again after the evidence updates. The parent gate's complete per-check exit codes and timestamps are retained in the JSON evidence.

The PHP suite uses isolated SQLite memory databases. Browser tests use a separate
SQLite fixture, generated synthetic credentials, separate ports and test-owned
processes. FCM tests mock the provider and test transaction rollback, independent
retries, invalid tokens and reassigned-device privacy. They do not contact Firebase.

The expanded suite now clears only its isolated test cache between independent cases, because all synthetic users otherwise accumulate in one localhost-IP rate bucket. The fixture refuses to run against any other database. Production throttling remains enabled, and the PHP suite separately verifies its behavior.

The first browser attempt required the browser matching the updated Playwright
package. Subsequent checks caught outdated punctuation in two heading assertions
and a missing API-origin host in the isolated session configuration; these were
corrected without relaxing functional assertions.

## Remaining acceptance work

Real R2/FCM configuration and delivery, a signed APK and physical Android tests,
authoritative municipality/boundary/hotline data, target MySQL 8 verification,
Hostinger deployment, backup restoration, capacity testing and municipal UAT
remain open. The Android project targets API 35 and must be upgraded with compatible Capacitor/plugins to API 36 before a new Google Play release. Laravel 12 security support ends on 24 February 2027, so a tested Laravel 13 maintenance upgrade is also planned. No GitHub push, deployment, APK build or real-device/cloud success is
claimed. Password recovery, account administration, offline submission, advanced
analytics and a responder-only app remain outside the established feature scope.

The subsequent user reply confirmed **Hostinger Web/Cloud hosting** and **Android app + responsive web**, superseding this review's earlier VPS recommendation. See [the implemented scheduled-worker and public_html guide](HOSTINGER_WEB_CLOUD.md). This document's 27-test evidence remains the UI baseline; the hosting follow-up has its own current verification record.
