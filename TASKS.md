# Phased task tracker

## Startup and repository ownership repair - 4 November 2026

- [x] Restore the user's ownership of affected project/repository directories without disabling Git ownership checks.
- [x] Replace duplicate-prone development startup with healthy-instance reuse, fixed ports and PHP 8.5.
- [x] Add launcher collision, reuse, proxy and safe-health tests.
- [x] Record final release checks and the normal runtime browser/API smoke result: all 12 checks, 35 backend / 20 browser / five launcher tests passed.

## Confirmed Web/Cloud hosting follow-up - 4 November 2026

- [x] Record Hostinger Web/Cloud and Android + responsive web as the confirmed targets.
- [x] Implement bounded scheduled push delivery with database overlap locking and interrupted-lock recovery tests.
- [x] Supply the relocated public entry and one hPanel cron template with deployment instructions.
- [x] Record the final follow-up release-gate result: 32 backend tests / 209 assertions, 20 browser tests, all 11 gate checks passed.
- [ ] Validate actual account PHP, process/cron limits, storage, HTTPS and real FCM latency.

## UI, feature completion and Hostinger review - 4 November 2026

- [x] Review the existing stack and record a combined web deployment recommendation while retaining the Android-capable client.
- [x] Apply LUMA system typography, glass controls, readable content and mobile navigation.
- [x] Implement view URLs/back navigation, filter clearing and protected sign-in resumption.
- [x] Keep demo news separate from live data and paginate comments beyond 100.
- [x] Queue per-device push jobs in the report/inbox transaction and add failure/retry coverage.
- [x] Prepare single-origin web packaging, worker/cron paths and PHP 8.5 command helpers.
- [x] Refresh compatible dependencies and both npm lockfiles; resolve current audits without exceptions.
- [x] Record final full verification and screenshots in [the dated review](documentation/UI_STACK_REVIEW_2026-11-04.md).
- [ ] Upgrade Capacitor/native plugins and target API 36 before a new Google Play release; validate on Android hardware.
- [ ] Plan Laravel 13 maintenance before Laravel 12 security support ends on 24 February 2027.
- [ ] Configure/validate real cloud services, physical Android, Hostinger operations, backups and municipal UAT.

Earlier unchecked review items below are historical. The dated review above supersedes them only for work explicitly evidenced there.

## Responsive layout and remembered sessions - 3 November 2026

- [x] Apply ui-ux-pro-max layout guidance to desktop, tablet and phone content.
- [x] Add mobile report cards, readable controls, drawer keyboard handling and responsive forms.
- [x] Persist web login through HttpOnly remember cookies and Android login through secure native storage.
- [x] Revoke other browser/mobile credentials after a password change while preserving the current device.
- [x] Document USB browser testing and the native Android build/install procedure.
- [ ] Build and validate an APK and persistent Keystore storage on a physical Android device.
- [ ] Resolve dependency advisories and push-delivery durability before release acceptance.

## Runtime review - 3 November 2026

- [x] Repair PHP 8.5 JSON contamination and reject invalid/incomplete dashboard responses.
- [x] Repair the moved project's local photo-storage junction.
- [x] Prevent CMS loading from replacing in-progress hotline edits.
- [x] Inspect the feature inventory and record [the feature audit](documentation/FEATURE_AUDIT_2026-11-03.md).
- [ ] Resolve current npm/Composer advisories and rerun every release gate.
- [ ] Close credential-revocation and durable-push findings before operational acceptance.

Historical phase checkmarks below retain their original evidence dates and do not override this review.

Owner: project development team. Updated: 2026-10-02.

`[x]` = implemented or evidenced as stated. `[ ]` = outstanding. Code completion does not imply external-service or field validation.

## Phase 1 — Requirements and project foundation

- [x] Read both supplied requirements and apply the replacement stack.
- [x] Create a dedicated project with separate frontend and backend directories.
- [x] Split the applications into local `hazard-client` and `hazard-server` repositories under `BANTAY` and add independent development commands.
- [ ] Publish the user-selected single private Ajhon10/MuniAlert workspace after exact-state verification; preserve earlier local histories.
- [x] Scaffold Vue 3/TypeScript and Laravel 12/Sanctum with lockfiles.
- [x] Record the accepted stack and persistent user preferences in `MEMORY.md`.
- [x] Inspect PDEA's current Laravel/Vue code and identify applicable conventions.
- [x] Separate API controllers, requests, resources, services, jobs, support rules, Vue views, composables, services, configuration, and types.
- [ ] Confirm official municipality name, geographic coverage, branding, school template, and project proponents.

## Phase 2 — Data and authentication

- [x] MySQL-compatible migrations, relationships, foreign keys, and indexes.
- [x] Seed hazard categories and department-routing defaults without shared passwords.
- [x] Add an explicit local-only demo-account seeder with environment-provided passwords for one administrator and two citizens.
- [x] Citizen registration and login; administrator creation through a hidden-password CLI prompt.
- [x] Sanctum cookie/CSRF web authentication and expiring mobile tokens.
- [x] Enforce administrative permissions and reporter-only private history.
- [x] Include audit logs and public/private response filtering.
- [x] Validate administrator email before database access and document MySQL authentication/setup troubleshooting.
- [x] Verify the user's regular local MySQL connection and completed application migrations on port 3306.
- [x] Seed and verify one local administrator and two sample citizen accounts without storing plaintext passwords in Git.

## Phase 3 — Citizen reporting and location evidence

- [x] Mobile-friendly category, description, barangay, and landmark fields.
- [x] Browser photo selection and Capacitor camera integration.
- [x] Client compression and server re-encoding; three photos, 1920 pixels, 1 MB each.
- [x] Browser/Capacitor current GPS with reported accuracy.
- [x] Manual pin placement, adjustment, and coordinate-entry fallback.
- [x] Explicit location confirmation; edits invalidate earlier confirmation.
- [x] Preserve GPS, selected coordinates, and `location_source` independently.
- [x] Nearby duplicate suggestions within 100 meters and six hours.
- [x] Idempotent citizen confirmation of an existing hazard.
- [x] Citizen report history and an emergency-call distinction.

## Phase 4 — Municipal monitoring and response

- [x] Map dashboard, statistics, search, category/status filters, pagination, CSV page export.
- [x] Separate verified coordinates and GPS-to-pin distance/review indication.
- [x] Validate the report status progression and record transition history.
- [x] Route categories to departments; administrator override and responder assignment.
- [x] In-app notifications and administrative audit entries in the update transaction.
- [ ] Import authoritative municipal/barangay boundaries and approve actual department names.

## Phase 5 — Cloud and mobile delivery

- [x] Cloudflare R2 filesystem configuration and local-storage fallback.
- [x] Firebase web token registration, Android token registration, and queued FCM HTTP v1 delivery code.
- [x] Queue/scheduler commands and token cleanup.
- [x] Generate the Capacitor Android project and declare GPS/notification permissions.
- [x] Document the dedicated Android installation workflow and phone-reachable API requirement.
- [x] Add a manual CI workflow to build a debug Android APK from a supplied HTTPS API origin.
- [ ] Validate real R2 upload, public retrieval, deletion/retention, and account limits.
- [ ] Supply Firebase project configuration and validate foreground/background delivery.
- [ ] Build and sign an APK/AAB using a configured Android SDK/JDK and test on physical devices.
- [ ] Validate camera/GPS behavior with denied permissions, poor reception, and real mobile networks.

## Phase 6 — Verification and GitHub release gate

- [x] Add feature tests for location evidence, photo limits, privacy, authorization, workflow, confirmations, and token revocation.
- [x] Exercise the feature suite with SQLite and an isolated MySQL-compatible database.
- [x] Add browser tests for signup/report tracking, photo compression/upload, GPS confirmation, filtering, export, and mobile layout.
- [x] Complete browser/build/format/dependency/documentation verification; rerun the full gate after final evidence updates.
- [x] Record executed test results and attach reproducible evidence.
- [x] Install the tracked pre-push hook and configure CI verification (remote execution requires a future authorized push).
- [ ] **Before every GitHub push: run `npm run verify`; every check must pass on the exact state being pushed.** Never bypass, skip, or weaken checks. Repeat after relevant changes.
- [ ] The user authorized the initial private Ajhon10/MuniAlert push on 9 November; later pushes still require authorization.

## Phase 7 — Capstone documentation and handover

- [x] Complete the requirements, architecture, ERD/data dictionary, API reference, test plan/results, user manual, security notes, deployment/maintenance guide, and traceability matrix.
- [x] Provide a capstone manuscript guide and a consolidated printable technical appendix in Markdown, HTML and PDF.
- [x] Validate document links, review schema/route consistency, and distinguish implemented from externally verified features.
- [x] Capture representative screenshots with demo data clearly labeled.
- [ ] Have the school adviser review the manuscript against the institution's required format.
- [ ] Conduct and record actual municipal UAT; collect approved participant consent/evaluation forms under the institution's process.
- [ ] Add actual evaluation findings and conclusions only after the study is performed.

## Phase 8 — Deployment and operations

- [ ] Configure the selected Hostinger Web/Cloud account, domain, HTTPS, PHP, MySQL, R2, Firebase and the hPanel scheduler cron.
- [ ] Apply migrations, create the authorized administrator, and verify public/private access.
- [ ] Complete and record backup-and-restore, field workflow, and release acceptance checks.
- [ ] Obtain the municipality's operational approval and perform an explicitly authorized deployment.

## Phase 9 ? Role workspaces, community content, and achievements

- [x] Implement distinct citizen and municipal administrator navigation and server authorization for CMS actions.
- [x] Add admin-managed emergency hotlines, default map center/zoom, and badge/reward descriptions.
- [x] Add confirmed-only news feed with citizen reactions, comments, and social sharing links.
- [x] Add profile editing and milestone badges for 50, 100, 1,000, and 10,000 verified reports.
- [x] Add Philippine time clock and confirmation dialogs for submission, logout, report discard/deletion, status updates, CMS publication, and comments.
- [x] Run migration `2026_10_02_000002` against the configured local development database.
- [x] Add isolated API feature tests and extend the citizen browser flow for confirmations/profile.
- [x] Verify the admin CMS in an isolated desktop browser flow.
- [x] Verify confirmed news, citizen reactions/comments, and social sharing links in an isolated browser flow.
- [ ] Validate the Android build and new screens on a physical device.
- [ ] Approve real emergency numbers, municipality coordinates, badge language and reward fulfillment policy before public release.

## Google and Facebook authentication follow-up

- [x] Implement server Socialite redirects, citizen creation and stable identity login.
- [x] Add explicit password-confirmed account linking and collision protection.
- [x] Add responsive provider buttons and Android external browser/cold-launch code exchange.
- [x] Finish local regression gate and record current evidence (52 backend / 390 assertions, 28 browser, five launcher tests).
- [ ] Configure actual Google/Meta app credentials, approved callbacks and public audience/review.
- [ ] Validate real providers on HTTPS and physical Android; no deployment is authorized.

## Interview and Android alignment ? 8 November 2026

- [x] Read all 14 Filipino interview responses and review six supplied proposal screenshots.
- [x] Record corrected rationale, screenshot verdicts, architecture/ERD/permission differences and scope gaps.
- [x] Protect original evidence, observations and precise incident data; require a separate reviewed summary for news.
- [x] Add optional incident time/affected-person estimate, explicit unknown injury observation, and private resolution photos.
- [x] Add complete weekly/monthly PHT record summaries with date/category filters and CSV.
- [x] Enlarge phone text/touch controls, repair help-text overrides, add Filipino hints and a full-height phone form.
- [x] Apply the additive local migration; inspect legacy public evidence (zero local records).
- [x] Record final full verification: 12 checks, 60 backend tests / 475 assertions, 31 browser tests, five launcher tests, and eight separate MySQL alignment tests / 85 assertions; zero audit findings.
- [x] Apply the user's final capstone-title decision (MuniAlert follow-up).
- [ ] Obtain adviser/municipal manuscript approval, real hotlines and administrator access policy.
- [ ] Validate physical Android, cloud/hosting operations, workload and municipal UAT.
- [ ] Agree scope before implementing screenshot-only category/department CRUD, citizen administration or an audit-log viewer.

## MuniAlert identity, guide and promotional video ? 8 November 2026

- [x] Apply the user-approved full capstone title and shorter MuniAlert display name.
- [x] Add citizen/admin feature highlights, searchable manual and replayable highlighted tour.
- [x] Check guide navigation, search, tour, phone/desktop reflow and API-unavailable reading.
- [x] Finish current documentation and phone screenshots, including 320px simulated 200% text checks and separate citizen/admin manual PDFs.
- [x] Build the Remotion promotion from the finished UI with synthetic demonstration data.
- [x] Deliver portrait/landscape MP4s plus editable preview; review sampled frames, decoded audio, metadata and browser playback.
- [x] Record all 12 final gate checks: 61 backend tests / 487 assertions, 36 browser tests, five launcher tests and zero dependency findings. Restore Lenovo ownership on 76,868 files with zero failures; preserve access permissions.
- [x] Distinguish local results from physical Android, cloud/hosting and municipal acceptance.

## Basista map and geographic coverage — 9 November 2026

- [x] Record Basista Municipal Hall and the user's exact 13 covered barangays.
- [x] Add Clear/Streets/Dark modes, separate boundary switches, fit/service-area controls and local preference persistence.
- [x] Provide an explicit map hazard-type selector synchronized with report queries; preserve filters on map expansion and show map pagination.
- [x] Extract detailed local barangay and nearby municipal geometry, reject the overly coarse version and retain source/hash attribution.
- [x] Use a Basista barangay selector/API allowlist, canonical name normalization and a reference-area pin warning.
- [x] Apply the camera migration without changing local accounts/report counts; move fictional demo reports into Basista.
- [x] Finish all 12 exact-state checks: 65 backend tests / 502 assertions, 40 browser tests and five launcher tests; zero dependency findings. Update manual exports, real-provider screenshots and scoped Lenovo ownership checks with unchanged access permissions.
- [ ] Have Basista validate reference boundaries, actual hotlines, field behavior and operational access before release.


## Header, Android development and private team repository ? 9 November 2026

- [x] Remove the compact header menu; retain bottom More and keyboard focus restoration.
- [x] Use an opaque compact header and eliminate the stretched desktop utility bar.
- [x] Document implemented fraud controls, municipal review and unimplemented additions.
- [x] Update Android USB/native instructions and one-clone team setup.
- [x] Verify Ajhon10 authentication and create the private MuniAlert repository; owner handles collaborators.
- [x] Preserve local Git histories, consolidate the workspace and review the selected publication contents.
- [x] Pass all 14 workspace checks: 65 backend tests / 502 assertions, 42 browser tests, five launcher tests and clean dependency audits. The pre-push hook repeats the gate on the committed state.
- [x] Document the private publication workflow and separate physical Android testing from automated results. Record the external push/CI outcome in the local publication receipt.

## Fixed modal regions, shared desktop heading and refreshed video — 9 November 2026

- [x] Keep dialog titles/close buttons and action footers visible while only the body scrolls, including sign-in, reporting, details, confirmations and the feature tour.
- [x] Preserve native form validation, nested confirmation focus/scroll behavior and 320px/short-landscape layouts; prevent photo-focus changes from scrolling the outer dialog.
- [x] Move desktop utilities beside the page heading to remove the remaining empty toolbar row; retain the compact mobile header.
- [x] Refresh the nine synthetic UI captures with Basista locations, map filtering/boundaries and fixed modal actions. Re-render portrait/landscape MP4s and verify their changed hashes, audio, metadata, playback and eight sample frames per format.
- [x] Verify the editable Studio compositions, update both user manuals and technical appendix, and pass all 14 checks: 65 backend tests / 502 assertions, 45 browser tests and five launcher tests.
- [x] Prepare the checked source for the continuing private team repository update. The pre-push hook repeats the complete gate; the remote commit/CI outcome is recorded separately in the local publication receipt.
