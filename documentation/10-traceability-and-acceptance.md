# 10 — Requirements traceability and acceptance

## Traceability matrix

| Requirement | Implementation entry points | Automated or manual evidence |
| --- | --- | --- |
| FR-01 Separation | `hazard-client/`, `hazard-server/`, root workspace scripts | Type check, production build, Laravel suite |
| FR-02 Authentication | `Api/AuthController`, `services/api`, `config/api` | Registration/token/role and persistent-session tests; browser signup, remembered reopen, cross-device password revocation and logout |
| FR-03 Photos | `ReportForm`, `mediaService`, `StoreReportRequest`, `PhotoStorage` | Photo count/size/content/dimension tests; browser compression/upload/retrieval |
| FR-04 GPS | `mediaService`, `ReportForm`, report schema | GPS completeness and adjusted-GPS feature tests; simulated browser GPS |
| FR-05 Manual/adjusted pin | `HazardMap`, `ReportForm`, `StoreReportRequest` | Manual submission, invalid-source test, browser adjustment |
| FR-06 Confirmation | `ReportForm`, `StoreReportRequest` | Unconfirmed location rejected; browser confirmation reset |
| FR-07 Admin evidence | `HazardResource`, `ReportDetail`, `Location` | Original coordinates preserved and discrepancy flag test; field validation pending |
| FR-08 Discovery | `useDashboard`, `DashboardView`, `ReportController` | Browser filters/export/mobile layout; live provider approval pending |
| FR-09 Duplicates | `ReportController::nearby`, `Location` | Recent/category/radius test |
| FR-10 Confirmations | `ReportController::confirm`, unique DB constraint | Idempotency and inactive-report rejection tests |
| FR-11 Workflow | `Workflow`, `ReportController::update`, `ReportDetail` | Full lifecycle and invalid-skip/permission tests |
| FR-12 Audit/history | History/audit tables and controller transactions | Persistence and update counts in feature suite |
| FR-13 Notifications | Inbox routes, `SendReportPush`, `notificationService` | Inbox creation tested; actual FCM delivery pending credentials/device tests |
| FR-14 Android | Capacitor config/project/plugins/manifest | Asset/plugin sync; actual signed build and device tests pending |
| FR-15 Documentation/gate | `TASKS.md`, `MEMORY.md`, `AGENTS.md`, scripts, hook, CI | Documentation checker and full `npm run verify` |
| FR-16 Private evidence/publication | `HazardResource`, `EvidenceController`, `SecureEvidence`, `ReportDetail`, `EvidencePhotos` | `InterviewAlignmentTest`: public projection/search/news, cross-user and cross-report media access, no-store headers and legacy repair |
| FR-17 Incident observations | New migration, `StoreReportRequest`, `ReportForm`, `ReportDetail` | `InterviewAlignmentTest`: incident timezone, input limits, public-field protection; Android browser guidance |
| FR-18 Resolution evidence | `EvidenceController`, `ReportDetail`, typed photos | Count/status/ownership/upload cleanup tests and complete municipal browser lifecycle |
| FR-19 Period records | `AnalyticsController`, `IncidentAnalytics` | More-than-one-page totals, PHT boundaries, unknown-time fallback, weekly starts, rejected/deleted exclusion, phone CSV export |

## Municipal UAT scenarios

| Case | Role | Procedure | Expected acceptance |
| --- | --- | --- | --- |
| UAT-01 | Citizen | Create account and sign in on a phone | Can access reporting and own reports without admin privileges |
| UAT-02 | Citizen | Photograph a safe simulated hazard using GPS | Photo is compressed; accuracy shown; confirmation required; reference returned |
| UAT-03 | Citizen | Deny GPS and place a manual pin | Submission works after pin confirmation; source is manual; no invented GPS |
| UAT-04 | Citizen | Move a GPS-derived pin | Original GPS persists; source changes; reconfirmation required |
| UAT-05 | Citizen | Submit near a same-category active report | Duplicate suggestion appears; same-hazard confirmation is not duplicated |
| UAT-06 | Admin | Review large GPS-to-pin distance | Difference and accuracy visible; original evidence retained; no automatic rejection |
| UAT-07 | Admin | Verify, assign, respond, resolve and close | Only allowed transitions work; history and citizen inbox update |
| UAT-08 | Citizen | Open another user's private detail route | Access denied; public overview remains available |
| UAT-09 | Operator | Disable network during submission | Clear failure; no false success/ref number; controlled retry |
| UAT-10 | Operator | Validate real R2 and FCM | Photo retrieval and foreground/background push documented with timestamps |
| UAT-11 | Maintainer | Restore an isolated backup | Reports, photos, roles and configuration are consistent after recovery |
| UAT-12 | Maintainer | Run release verification before push | Every required check passes; failures block the push |

Use safe, labeled simulated hazards for evaluation. Do not create a real hazard or delay emergency reporting to conduct a test.

## Acceptance record — blank form

| Field | Value to be completed by evaluator |
| --- | --- |
| Project/version/commit | |
| Municipality/institution | |
| Evaluator and role | |
| Test date and location | |
| Device, OS and browser/app build | |
| Network and deployment environment | |
| UAT case / requirement IDs | |
| Expected result | |
| Actual observation | |
| Pass / fail / not executed | |
| Screenshot/log evidence reference | |
| Defect and severity | |
| Retest result/date | |
| Reviewer approval and remarks | |

## Study measurements — planning template

Before collecting results, define target users, authorized sample selection, approved consent/information procedures, tasks, measurement units, success criteria and analysis methods. Potential observations include task completion, input errors, time to a valid report, GPS-to-landmark consistency and clarity of status tracking. Choose an appropriate validated usability instrument with the adviser if required. Leave scores and respondent counts blank until collected; avoid retrofitting success thresholds after observing results.

## Release acceptance conditions

- The requested feature scope and known limitations are documented.
- `npm run verify` passes on the exact revision; optional live-integration/release-specific checks are completed before claiming those capabilities validated.
- No secrets, real evidence or private user information are included in source control or capstone attachments.
- Documentation and environment examples match code and migrations.
- The administrator, service owners, backup operator and support contacts are approved.
- Production publication occurs only after explicit authorization and recorded operational acceptance.

## FR-02 social sign-in extension

Implementation: `SocialAuthController`, `SocialAccounts`, social-auth migration, `socialAuth.ts`, `SocialLoginButtons`, AuthForm/ProfilePanel, Android App/Browser plugins and manifest callback. Evidence: isolated backend `SocialAuthenticationTest`, browser `social-auth.spec.ts` and full gate. Required external acceptance: real Google/Facebook new/repeat/cancel/link flows on HTTPS and physical Android including a killed-app callback. Provider console credentials/audience/review, publication privacy/deletion requirements and deployment remain pending. See [provider setup](SOCIAL_AUTH_SETUP.md).

## Identity, guidance and promotion extension

| Requirement | Implementation | Evidence |
| --- | --- | --- |
| FR-20 | Shared client identity, HTML title, modal/brand header, native display label, CSV filenames and capstone export builder; stable auth identifiers | Brand/title assertions in `guide.spec.ts`, persistent-session cookie check, summary CSV check, typecheck/build and Android asset sync |
| FR-21 | `UserGuide`, `FeatureTour`, static manual data and `?view=guide` navigation | Phone/desktop reflow, search/no-match recovery, citizen/admin instructions, complete tour/ESC, API-unavailable reading |
| FR-22 | Independent `promo-video/` Remotion authoring project | Studio preview, source checks, sampled frames, MP4 metadata/audio and recorded rendering result |
| FR-23 | `MapControls`, map preferences, boundary-layer service and persistent MapLibre instance | `maps.spec.ts`: actual rendered boundary change, fire/accident/empty filtering, synchronized expansion, retained markers/preferences, failed-style recovery and 320–1440px reflow |
| FR-24 | Client/server municipality config, report allowlist, local GeoJSON/provenance and conditional camera migration | `BasistaScopeTest`, map-asset name/hash consistency, 13-feature browser check, count-preserving local migration and real-provider visual review |
