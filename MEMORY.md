# Persistent project memory

Last updated: 2026-11-09. This is repository-local memory for future development sessions.

## Decisions from the user

1. Create a new municipal hazard reporting project with separate frontend and backend folders.
2. Replace the original Next.js/NestJS/PostgreSQL approach with **Vue 3 + TypeScript + Capacitor + Laravel + Sanctum + MySQL + MapLibre + Cloudflare R2 + Firebase Cloud Messaging**, originally targeting Hostinger VPS; the current user choice below supersedes that hosting target.
3. Provide both phone GPS and a manually placed/adjusted map pin. Explicitly confirm the location, preserve GPS accuracy and location source, and show administrators the GPS-to-pin distance.
4. Limit each report to three photos and each final photo to 1 MB, with a longest dimension of 1920 pixels.
5. Apply relevant architecture principles from existing projects, especially PDEA's Laravel/Vue applications.
6. Maintain a Markdown task tracker organized into phases and provide technical documentation suitable as capstone attachments.
7. **Before any GitHub push, every required automated test/check must pass.** Failed, unrun, skipped, or outdated verification is not a passing release gate. `npm run verify` and the pre-push hook enforce this requirement. The user explicitly authorized a private GitHub publication on 9 November 2026; use Ajhon10 and let the owner configure collaborator access.

## Current implementation choices

- Application name: **MuniAlert**. Formal capstone title: **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking**. The user confirmed Basista Municipal Hall, Pangasinan and the 13 covered barangays; school details remain to be supplied.
- Laravel 12 declares PHP `^8.2` and matches PDEA's Laravel major version. The workstation CLI is now PHP 8.5.1 at `C:/php85/php.exe`; locked platform requirements passed on 2026-11-03. The MySQL configuration uses the modern PDO driver constant conditionally to avoid PHP 8.5 deprecation output corrupting JSON. Other web/remote runtimes require separate verification.
- The user now requires persistent browser and Android login across restarts. Web uses HttpOnly session/remember cookies and CSRF. Android uses secure persistent storage (Capacitor secure-storage 7.1.3, Android Keystore), with rolling Sanctum expiry. Defaults: 90 days of inactivity, configurable with SESSION_LIFETIME and AUTH_SESSION_DAYS. Explicit logout revokes the device credential; password changes revoke other devices while retaining the current one. This supersedes the earlier memory-only mobile token choice. Physical Android validation remains outstanding.
- MySQL is the deployment database. SQLite is only the default isolated automated-test database. A separate local MariaDB instance was used to exercise MySQL-compatible migrations and workflows.
- Original evidence is private local storage by default (`HAZARD_EVIDENCE_DISK=evidence`), or a verified private R2 bucket. Owner/admin authorization is required; public news uses separately reviewed text and no raw images. The 8 November privacy decision supersedes the earlier public-photo baseline.
- Database notifications work without Firebase. FCM web/native registration and queued delivery need real Firebase configuration and deployment validation.
- MapLibre offers Clear (OpenFreeMap Positron), Streets (OpenStreetMap raster), Dark and an optional configured style. Basista's 13 barangays and nearby municipality/province context are bundled as attributed reference GeoJSON; municipal review remains pending.
- Demo reports are fictional, read-only frontend fixtures enabled explicitly with `?demo=1`. They are not seeded into the application database.
- The explicit `DemoAccountSeeder` creates one local administrator and two sample citizens using distinct passwords stored only in the ignored server `.env`. It is not part of normal `migrate --seed` or production setup.
- The interface distinguishes a municipal report from an emergency call. The emergency phone is configurable and must be locally verified.

## Where to resume

- The user selected one private `Ajhon10/MuniAlert` workspace repository. `hazard-client` and `hazard-server` remain separate application folders; the root includes documentation, cross-application tests and Remotion source. Earlier local app Git histories are backed up in ignored `.cache/git-history-backups/`.

- The local MySQL account now authenticates on port 3306, all application migrations have run, and the explicit demo seeder created one administrator plus two citizens. The demo passwords are stored only in the ignored `hazard-server/.env` and must be rotated before any real deployment.
- Run setup steps individually. An existing storage link can be retained. The administrator command requires a plain email address and rejects Markdown links before database access.

Read `TASKS.md` for phase completion and `documentation/05-testing-and-validation.md` for evidence and limitations. External-service tests, a signed APK/AAB, real-device validation, municipal UAT, and production deployment must not be presented as completed without evidence.

## Community features added 2026-10-02

- Citizens and administrators have distinct navigation. Admin CMS controls map center/zoom, emergency hotlines, and badge descriptions/reward information. The client clock formats `Asia/Manila` time.
- Confirmed statuses (`verified`, `assigned`, `responding`, `resolved`, `closed`) determine news eligibility and badge counts. Cash/gift fields describe possible municipal benefits; fulfillment is manual. Reporters can soft-delete only their own unverified reports, with audit/evidence retained for retention review.
- Migration `2026_10_02_000002` was applied to the configured local development database. It seeds an empty hotline list and default map center; approved local values are still needed.

## Runtime and feature review 2026-11-03

- Repaired malformed API-response handling and validated dashboard snapshots, recreated the moved project's public-storage junction, and guarded CMS edits until loading completes.
- Final functional checks passed: 19 Laravel tests / 133 assertions, nine browser tests, client build/type checking, PHP formatting, Android asset synchronization and documentation checks. Normal running app also passed a read-only browser smoke check.
- The release gate is **failing**: npm reports nine vulnerabilities and Composer reports one advisory. Credential revocation on profile password changes and durable push enqueue/recovery also need work. Cloud services, signed Android/device tests, deployment, capacity and municipal UAT remain unaccepted.
- See [the full feature audit](documentation/FEATURE_AUDIT_2026-11-03.md) and its dated evidence. Historical successful gates must not override this result. No push or deployment was requested or performed.

## Responsive layout and persistent sign-in follow-up

- Used ui-ux-pro-max guidance: fluid desktop panels, readable type, accessible controls, phone report cards, narrow-screen filter stacking, keyboard drawer focus/escape, safe areas and reduced-motion support.
- Added persistent web/Android sessions and password-change revocation tests. Login and password changes lock the same user row before credential mutations. Android storage is guarded to native platforms; browsers retain HttpOnly cookies and never use token localStorage.
- Added [Android phone testing instructions](documentation/ANDROID_TESTING.md), including Chrome USB forwarding for the running website. JDK/SDK and a connected Android phone are not available here; do not claim an APK or physical Keystore test.
- See [the follow-up record](documentation/RESPONSIVE_SESSIONS_2026-11-03.md) for the latest evidence. The earlier 19-test/nine-browser baseline is historical. Dependency findings and push durability remain open.
- Final follow-up checks passed 23 Laravel tests / 150 assertions and 16 browser tests, build/type checking, formatting, Android sync and documentation. Normal-runtime smoke passed after restarting dev servers at ports 5173/8000. The release gate still fails at the nine-vulnerability npm audit; Composer separately still reports one advisory. No APK or physical-device result is claimed.

## UI / stack review - 4 November 2026

The user asked for mobile/web stack review, LUMA's font/liquid-glass UI, feature completion and easier Hostinger deployment. Keep the existing stack and source folders. `npm run build:web` now packages a same-origin website into `hazard-server/public/app` without overwriting Android's `dist`. The user confirmed Hostinger Web/Cloud hosting and Android plus responsive web; this supersedes the previous VPS baseline and excludes an iPhone app. See [stack guidance](documentation/STACK_AND_HOSTINGER.md).

LUMA system typography/glass navigation, the phone dock, filter clearing, view URLs/browser back, protected sign-in resumption, preview-only news, paginated comments and transactional per-device database push jobs are implemented. Parent scripts explicitly select PHP 8.5 on Windows even when the shell still resolves XAMPP PHP. Compatible dependency fixes and standalone/root lockfile refreshes remove current audit findings. Final command evidence is maintained in [the dated review](documentation/UI_STACK_REVIEW_2026-11-04.md); previous failing audit records remain historical. Real cloud/device/hosting/municipal acceptance is still open.

Final 4 November evidence: all 11 parent gate checks passed; 27 Laravel tests / 188 assertions and 20 browser tests, zero npm findings (root and standalone lockfiles), no Composer advisories, locked platform requirements on PHP 8.5.1. Normal-runtime read-only smoke passed on 5173/8000 and the dev launcher remains running for review. Demo screenshots are in the dated review. Native API 36/Capacitor maintenance is needed before new Google Play submissions; plan Laravel 13 before 24 February 2027. No APK, real cloud/device validation, push or deployment occurred.

## Confirmed hosting follow-up - 4 November 2026

The user selected **Hostinger Web/Cloud hosting** and **Android app + responsive web**. Preserve the source split and combined one-origin web package; do not require a VPS/systemd worker. `HAZARD_SCHEDULED_PUSH=true` enables a CLI-only bounded scheduler task, `hazard:work-push`, under one hPanel `schedule:run` cron. The worker uses a database overlap lock, three-job/30-second between-job limits and preserves jobs when Firebase is absent. Actual hosting limits, cloud delivery and device/operational acceptance still need validation. See [the concrete Web/Cloud guide](documentation/HOSTINGER_WEB_CLOUD.md). Local verification for this follow-up is recorded separately from the earlier UI baseline.

Confirmed-hosting follow-up verification passed all 11 gate checks: 32 Laravel tests / 209 assertions and 20 browser tests, zero npm findings and no Composer advisories. The public_html entry template is PHP-linted. The scheduler invokes push/token pruning in the same PHP process, avoiding proc_open for those tasks. See [hosting verification](documentation/evidence/hostinger-web-cloud-2026-11-04.json). Earlier 27-test evidence is the prior UI baseline. No Hostinger deployment or real cloud/device validation is claimed.

## Startup / repository ownership repair - 4 November 2026

The user's terminal hit Git ownership protection because repository roots/.git were owned by CodexSandboxOffline, plus a Vite strict-port failure because an older BANTAY instance already used 5173. Restore the Lenovo user's ownership of project/repository directories while preserving DACLs; do not use a blanket Git safe.directory exception. Managed sandbox execution accounts are separate from GitHub sign-ins.

The combined `npm run dev` launcher now checks/reuses matching healthy services, selects PHP 8.5, uses direct child processes, starts/checks the API before Vite, verifies the frontend proxy, and stops only its own children on failure. Independent client and Composer dev commands reuse their matching service. Development identity hashes prevent reusing another workspace; production health does not expose them. Health failures return a safe 503. See [startup verification](documentation/STARTUP_REPAIR_2026-11-04.md). The existing development database was not reset or reseeded during repair.

Startup repair final verification passed all 12 gate checks as the Lenovo user: 35 backend tests / 221 assertions, 20 browser tests and five Node launcher tests. The normal browser/API/database smoke and fresh/repeated combined and individual startup commands passed. BANTAY ownership repair processed 52,955 items with zero failures, plus the wider project-directory repair. No normal database reset/reseed, GitHub push or deployment occurred. Keep Node launcher tests in scripts/tests, outside Playwright's tests directory. The combined launcher is the recommended local command.

## Google/Facebook authentication ? 4 November 2026

Socialite signup/login, explicit password-confirmed provider linking, glass-styled signup/login buttons and secure Android external-browser return handling are implemented. Provider email never silently merges accounts. New social users are citizens with disabled password login. The app stores its pending verifier separately in native secure storage and exchanges a short-lived one-use code; no Sanctum token appears in URLs. The existing local database received only the additive social-auth migration, preserving user/report counts.

Local verification: 52 Laravel tests / 390 assertions, 28 browser tests, five launcher tests and the twelve-check gate; PHP 8.5.1 locked platform checks; zero npm findings and no Composer advisories. Neither OAuth provider has credentials configured; real-provider, physical-device, Hostinger and municipal acceptance remain pending. Normal dev servers remain under the user's control; no push/deployment was requested or performed. See [implementation evidence](documentation/SOCIAL_AUTH_2026-11-04.md) and [setup](documentation/SOCIAL_AUTH_SETUP.md).

## Interview alignment and primary Android device ? 8 November 2026

The user supplied a Filipino interview and six prior ChatGPT proposal screenshots for BANTAY alignment, with Android phones as the primary device. All 14 responses were read. The respondent explicitly reported no major current phone-process problem; use organized manual-record consolidation, privacy, completeness and timeliness as the rationale, not an invented response failure. The interview uses an alternate formal title; retain BANTAY and the existing formal title pending adviser/team agreement. Screenshots use ?CivicAlert? and Vuetify; neither overrides the existing name/custom Vue UI or accepted stack.

New original evidence is private local storage (`HAZARD_EVIDENCE_DISK=evidence`) or a separately validated private R2 bucket. The legacy `HAZARD_PHOTO_DISK` setting no longer selects uploads. Only evidence/r2 disks are accepted. Original title/description/address/precise coordinates, injury observations and photos are owner/admin only. Public map data is approximate and uses generic/reviewed text; news requires a separate admin-reviewed public title/summary. Public projection is enforced even for signed-in admins. Media uses authenticated requests and temporary blob URLs, never bearer-token query strings. `hazard:secure-evidence` inspects historical local public files; `--apply` verifies bytes, updates disk pointers and removes public copies with retry handling. The local inspection found zero legacy public records. R2/custom-domain/CDN privacy remains an operator task. This supersedes earlier public-photo/news-publication decisions above.

The additive migration adds optional incident time, yes/no/unknown injury observation, optional affected-person estimate, public title/summary and incident/resolution photo type. Admins can add three resolution photos independently of the three incident photos. Admin period summaries aggregate all matches (not a report page), use PHT dates/Monday weeks, disclose submission-time fallback and unverified reports, and export CSV. No victim identity/clinical record, unique-incident conclusion, causal analysis or automatic agency dispatch is claimed. The existing Road accident category already existed and was retained.

Android CSS follows existing typography/glass direction: larger readable text, 48 CSS-pixel controls where applicable, full-height phone forms, safe areas, visible labels and Filipino reporting hints. This is not full localization or a physical Android accessibility certification. Screenshot-only category/department CRUD, citizen-account management and audit browsing remain unimplemented and are documented honestly. See [the review](documentation/INTERVIEW_ALIGNMENT_2026-11-08.md); final automated evidence is recorded separately from physical/cloud/municipal acceptance. No push/deployment is authorized.

Interview follow-up validation: eight alignment tests / 85 assertions passed on isolated MySQL 8.0.44. The test application refuses unapproved database names before RefreshDatabase. The first gate found eight new Symfony advisories; five minimal 7.4.23 updates resolve them, retaining Laravel 12. The normal startup/proxy/MySQL smoke and a simulated 200% text-size check at 320px passed after repairing nonwrapping buttons. New source evidence and readable HTML/PDF exports are in the dated review.

Final 8 November gate passed all 12 checks as Lenovo: 60 backend tests / 475 assertions, 31 browser tests, five launcher tests, zero npm findings and no Composer advisories. The eight alignment tests / 85 assertions also passed separately on MySQL 8.0.44. Updated PHP 8.5.1 locked-platform and both Git whitespace checks passed. Lenovo ownership was restored on 53,564 paths with zero failures, preserving access permissions. Normal dev servers are running at 5173/8000 for review. No app records were reset, no credentials were published, and no push/deployment or physical Android acceptance occurred.

## User-approved title, guide and Remotion promotion ? 8 November 2026

The user explicitly selected the full capstone title **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking** and asked for a shorter app name, feature-highlight guide, in-app user manual and Remotion promo after completion. The selected short name is **MuniAlert**, subtitle **Emergency & Public Hazard Reporting**. This supersedes earlier pending-title decisions and Bantay display branding. The local workspace and stable app/auth identifiers are retained for compatibility.

The user explicitly confirmed **MP4 plus editable Remotion preview**. Video authoring is a separate React/TypeScript Remotion artifact; the app remains Vue/Laravel. Use synthetic UI fixtures and original local audio, no real incidents/credentials, and do not imply an available Play Store release, guaranteed dispatch or cloud/device acceptance.

Final 9 November handover: MuniAlert web/native display branding, the full title, CSV filenames, feature cards, six-step highlighted tour and searchable citizen/admin manual are implemented. The stable `bantay_session` cookie, Android package/deep-link and secure-storage identifiers preserve compatibility. Guide return through email/social sign-in is covered. The normal-runtime guide smoke passed at 320px with simulated 200% text enlargement; citizen/admin PDFs were exported from the bundled manual.

All 12 final app gate checks passed under Lenovo: 61 backend tests / 487 assertions, 36 browser tests, five launcher tests, zero npm findings and no Composer advisories. Locked platform checks passed on PHP 8.5.1. Both 60-second Remotion MP4s (1080×1920 and 1920×1080, H.264/AAC, narration and original music) were rendered, sampled visually, checked for audio/metadata and played/seeked in Chromium. Studio has both formats and seven editable scenes at localhost:3030. Video lint/type checks and audit pass. Ownership was restored on 76,868 files with zero failures and sampled access permissions preserved. See [the dated handover](documentation/BRANDING_GUIDE_PROMO_2026-11-09.md) and its evidence. No push, deployment, external video publication, signed APK or physical Android/cloud/municipal acceptance occurred.

## Basista map and service coverage — 9 November 2026

The user confirmed **Basista Municipal Hall, Pangasinan**, and exactly these covered barangays: Anambongan, Bayoyong, Cabeldatan, Dumpay, Malimpec East, Mapolopolo, Nalneran, Navatat, Obong, Osmeña Sr., Palma, Patacbo and Poblacion. Client/server municipality config must agree. New submissions use a selector and API allowlist; ASCII/case variants normalize. Existing records are preserved; published reference geometry supplies a warning, not a legal geofence.

Clear/Streets/Dark modes, persisted boundary switches, an explicit synchronized map hazard-type selector, map pagination and Basista/Fit controls are implemented. Expansion retains filters; style changes retain camera, markers, GPS and confirmed pins. Local attributed assets cover 13 detailed barangays, six nearby municipalities and Pangasinan province, totaling 101,959 bytes. The coarse first street-level extract was rejected and replaced with detailed hash-verified WGS84 source geometry. Keep large source archives and the GIS reader out of app bundles. Source/acceptance notes: [Basista map handover](documentation/BASISTA_MAP_2026-11-09.md).

Final local verification passed all 12 checks: 65 backend tests / 502 assertions, 40 browser tests, five launcher tests, zero npm findings and no Composer advisories. PHP 8.5.1 locked platform and both Git whitespace checks pass. Real-provider Clear/Dark/Streets review had zero page errors; phone reflow and simulated 200% map-control text at 320px passed. Source/repository ownership was restored to Lenovo with sampled DACLs preserved; the already Lenovo-owned GIS extraction cache retained its permissions. The local camera migration preserved four accounts and zero reports. Manual PDFs include map guidance. No GitHub push, deployment, physical Android/municipal boundary acceptance or promo rerender occurred.


## Header, Android team testing and private GitHub publication ? 9 November 2026

The user requested removal of the redundant mobile header menu, more header reading space, a compact desktop header, fraud-prevention guidance, Android development instructions and an initial private GitHub push. The user confirmed **Ajhon10**, chose **one private repository** with both app folders/shared guides, and will configure collaborators personally. Private `Ajhon10/MuniAlert` was created and privacy verified; code publication is gated on the final checks. No collaborator invitations or hosting deployment are authorized by this task.

Root Git/CI/hooks are the active publication workflow. Keep old Git metadata as ignored local backups; do not publish credentials, private evidence, source archives, dependency/build folders or local environment files. `npm run verify` now includes repository-publication checks and promotional-source lint/audit alongside all existing checks. Publication completion is recorded in the local receipt `.cache/github-publication-result.json`.

The header now uses an opaque compact surface, with bottom More as the only compact navigation toggle. The long desktop utility bar is replaced by a content-sized header. All 14 local checks passed: 65 backend tests / 502 assertions, 42 browser tests and five launcher tests, with zero npm findings and no Composer advisories. Header checks cover 320–1023px compact layouts, scrolled guide readability, drawer focus and desktop width. The committed-state gate runs again inside the pre-push hook. See [the header and team handover](documentation/HEADER_TEAM_2026-11-09.md).

The Android guide now covers one-clone setup, USB Chrome port forwarding, secure-context requirements and separate native builds. Contact verification, account suspension and automatic fraud classification remain unimplemented; the fraud guide and administrator manual distinguish these from existing authentication, submission limits, duplicate suggestions and human review. Creating a private source repository does not deploy Laravel/MySQL, build a release APK or validate a physical phone.

The initial private workspace was subsequently pushed as `bb687d8`; repository privacy, matching remote/local commit and successful GitHub CI were verified. The source/guide handover above intentionally preceded that external operation.

## Fixed modal regions and refreshed promotion — 9 November 2026

The user requested fixed modal headers/footers with scrolling bodies, removal of the remaining empty desktop toolbar row and an updated MP4. Shared native dialogs now clip their outer overflow, use one focusable scrolling body and a named fixed footer; authentication/report/detail components own their dialogs so action state stays local. Footer submit buttons use unique form IDs and retain native validation. Nested confirmations use a reference-counted scroll lock. The tour has the same three regions and can grow within the viewport for enlarged text. Android requests content resizing for the onscreen keyboard; physical-device validation remains pending.

Desktop utilities now share the page-heading row via `WorkspaceHeader`; the mobile header remains separate and sticky. The full local gate passed all 14 checks: 65 backend tests / 502 assertions, 45 browser tests and five launcher tests, with clean dependency audits. Tests cover photo-focus scrolling, fixed chrome, nested confirmation cancellation/discard, native validation, short viewports, enlarged tour text and the municipal response workflow.

Both 60-second promotional MP4s were re-rendered with nine fresh synthetic captures, actual Basista locations, a map-filter/boundary transition and the fixed dialog actions. Their hashes differ from the earlier exports; decoded audio, metadata, browser playback/seeking, eight samples per format and editable Studio compositions passed review. MP4s stay in ignored `promo-video/out`; the private repository carries editable source/assets. See [the modal and video handover](documentation/MODALS_HEADER_VIDEO_2026-11-09.md). The continuing private publication outcome is recorded in `.cache/modal-video-publication-result.json`; no hosting, native release or external video posting is implied.

The modal/header follow-up was pushed as `c1d880d`; matching remote/local commit, private visibility and successful GitHub CI were confirmed.

## Requested KIKOS narrator and mobile top edge — 9 November 2026

The user requested the narrator used in KIKOS Order Planner, replacing the robotic-sounding Windows voice, and removal of the remaining mobile header gap. The reference project uses **Kokoro `af_heart`**, `kokoro-js` 1.2.1, Transformers.js 3.8.1 and the q8 Kokoro-82M-v1.0-ONNX CPU model. Preset and cached model hashes match the reference. The reference project was read only; newly authored MuniAlert speech uses the generic preset, with no voice cloning or paid API. Do not revert to the Windows SAPI narrator. Regenerate with the separate video project's narration commands; model weights stay in ignored local cache.

All seven clips were replaced. Shorter privacy/guide lines keep the existing scene timing; natural-rate adjustments remain small. The source gate validates preset/clip hashes and actual WAV duration. A scoped Sharp 0.35.5 override resolves the added tool's transitive advisories; CI skips only optional CUDA binaries because inference uses CPU. Audits remain enabled.

The compact header now starts at the viewport top, with no forced outer margin or sticky offset. Actual safe-area padding stays inside its surface. Desktop heading layout remains intact. Local verification passed all 14 checks: 65 backend tests / 502 assertions, 45 browser tests and five launcher tests, with clean audits. Both 60-second MP4s were re-rendered; all seven encoded scene narrations correlate with their new WAVs, and playback/Studio/frame reviews pass. See [the current narrator/mobile handover](documentation/NARRATOR_MOBILE_2026-11-09.md). Final remote publication is recorded in `.cache/narrator-header-publication-result.json`. No physical Android or hosting acceptance is claimed.
