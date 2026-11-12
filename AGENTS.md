# Project instructions

Read `MEMORY.md`, `TASKS.md`, and `documentation/02-system-architecture.md` before changing this project.

## User requirements that persist

- Keep separate `hazard-client/` and `hazard-server/` applications.
- Deployment target is Hostinger Web/Cloud hosting with one web/API origin; use bounded scheduled push workers rather than a required persistent daemon. Mobile targets are Android plus responsive web; iOS is not in scope.
- Use Vue 3, TypeScript, Capacitor Android, Laravel 12, Sanctum, MySQL, MapLibre, Cloudflare R2, and Firebase Cloud Messaging. The earlier Next.js/NestJS implementation was explicitly replaced.
- Use PDEA's relevant separation of API controllers, services, support code, frontend API configuration, feature tests, and operational documentation. Do not copy its application data, secrets, branding, or unrelated modules.
- Maintain phased tasks in `TASKS.md`. Keep implementation status, tested status, and external-service readiness distinct.
- Update the capstone documentation alongside changes to requirements, architecture, schemas, API behavior, testing, or deployment.

## Mandatory rule before every GitHub push

**All required tests and checks must pass on the exact project state being pushed.** Run `npm run verify`. Do not push if any check fails, is skipped, cannot run, or has stale results after changes. Fix the issue, rerun the gate, and record the result. Do not bypass the pre-push hook, disable tests, or weaken assertions to obtain a pass.

The user selected one private `Ajhon10/MuniAlert` workspace repository on 9 November 2026. Keep `hazard-client/` and `hazard-server/` as separate applications inside it. The root `.githooks/pre-push` and root CI verify the full workspace, including isolated browser tests and promotional source. Legacy application Git histories are preserved locally in the ignored `.cache/git-history-backups/`. Do not reintroduce embedded repositories. The user has explicitly authorized this initial private publication; collaborator access remains for the owner to configure. Future pushes still require authorization and fresh passing checks.

On 12 November 2026, the user explicitly confirmed **keep the repository public and push** after its live GitHub visibility was checked. This supersedes the earlier private-repository preference. Preserve public visibility unless the user requests a change. Environment files, keys, private records and runtime data remain excluded; all pushes still require the exact-state verification gate.

## Development conventions

- Administrative statistics must aggregate the full filtered dataset with Philippine date boundaries and explicit submission/incident basis. Share chart/table/export controls, retain applied filters for downloads, zero-fill periods and bound query ranges. Office exports must be genuine documents with literal spreadsheet text, private temporary-file cleanup and independent administrator authorization; never infer complete totals from a paginated report list.

- Reuse `Modal`, shared native-dialog lifecycle, `AppForm`, common input/select/textarea/password components and `DataTable` for common UI behavior. Change shared sizing tokens instead of copying width/padding rules into callers. Modal bodies own their padding; render footers only for meaningful actions and keep dismissal in the header. Toasts overlay the active dialog without shrinking its body. Bound notification previews and keep independent expansion cards aligned at the start.
- Keep validation rules/messages authoritative in backend JSON field errors. Preserve device/file safety checks, accessible error focus and privacy. Seed data is reviewed CSV with environment-key references for credentials; one `db:seed` entry point preserves existing accounts, routing and administrator edits. See `documentation/REUSABLE_WORKFLOWS_2026-11-12.md` for sharing/recovery limits and verification requirements.
- API controllers: `hazard-server/app/Http/Controllers/Api`. Validation: `Http/Requests`; serialization/privacy: `Http/Resources`; integrations: `Services`; asynchronous FCM: `Jobs`; business rules: `Support`.
- Frontend: `views`, `components`, `composables`, `services`, `config`, `types`, `data`, and `assets`. Keep network access and device APIs out of templates.
- Migrations are the schema source of truth. Tests must use isolated test databases; never run destructive test migrations on an existing application database.
- Original GPS, selected hazard coordinates, and administrator-verified coordinates are separate evidence. Never overwrite original GPS during verification.
- Require explicit location confirmation. Retain `gps`, `gps_adjusted`, or `manual` as the source. Flag discrepancies for review; do not automatically reject them.
- Keep the three-photo, 1920-pixel, 1-MB-per-final-photo limits enforced on the server as well as the client.
- Do not expose private GPS, live reporter identity/contact fields, credentials, or evidence metadata through public responses. The user's 10 November news-detail request permits an explicitly admin-reviewed submitter display-name snapshot on a published post; preserve the option to withhold it. See `documentation/COMMUNITY_NAVIGATION_NEWS_DETAILS_2026-11-10.md`.
- Keep demo records clearly labeled. Do not claim tests, cloud delivery, device builds, deployment, field trials, respondent counts, or capstone findings that have not actually been verified.
- Never commit `.env`, Firebase service-account keys, signing keys, database exports, uploaded evidence, or dependency folders.
