# MuniAlert — Municipal Hazard Reporting

**Current handoff (24 November, revision 3):** email/password login only; old demo accounts and Google/Facebook authentication are removed. Email/current policies remain required; one government ID is optional for both roles and earns a blue verified profile badge. Deployment seeding creates a privately configured starter administrator; Settings manages later admins. See [initial-account setup](documentation/TEST_ACCOUNTS.md), [Hostinger checklist](documentation/HOSTINGER_WEB_CLOUD.md), [editable capstone](documentation/capstone/MUNIALERT-CAPSTONE.docx) and [PDF](documentation/capstone/MUNIALERT-CAPSTONE.pdf).

[Emergency-first reports, actionable notifications, hazard sounds and private image review](documentation/EMERGENCY_NOTIFICATIONS_IMAGE_REVIEW_2026-11-11.md) records the current workflow; real contacts and a local classifier need configuration.

[Settings, saved reading preferences, authenticator 2FA and Gmail readiness](documentation/SETTINGS_2FA_EMAIL_2026-11-11.md) records the earlier refinement. MuniAlert's separate credentials are now configured locally; host-side delivery remains unverified.

Public source repository: **[Ajhon10/MuniAlert](https://github.com/Ajhon10/MuniAlert)**. One clone contains both apps, shared tests, documentation and Remotion source.

**New collaborator? Start with [the complete download/setup guide](documentation/COLLABORATOR_QUICKSTART.md).** For a new local website and Android-Chrome test, you do not need the owner's private `.env`, app key, database dump or installed dependency folders. Use the included examples and create your own development database/accounts. [Included/missing-file checklist and what the owner should send](documentation/COLLABORATOR_FILE_CHECKLIST.md).

| What you want to test | Start here |
| --- | --- |
| Website on your PC | [First-time setup](documentation/COLLABORATOR_QUICKSTART.md) |
| Website on your Android phone | Start the PC services, then [USB Chrome port forwarding](documentation/ANDROID_TESTING.md#2-connect-android-chrome-over-usb--recommended-first-test) |
| Installed Android app | [Native build/setup](documentation/ANDROID_TESTING.md#4-installed-capacitor-app); requires a reachable HTTPS backend |
| Android phone only / shared remote test | Ask the owner for a running HTTPS test URL, or the configured APK plus backend/test accounts |

GitHub hosts the source; it does not run this PHP/MySQL application. Downloading the ZIP onto a phone does not start the program.

The applications remain separate:

| Folder | Application | Local command |
| --- | --- | --- |
| [hazard-client](hazard-client/README.md) | Vue 3 and Capacitor client | `npm run dev` |
| [hazard-server](hazard-server/README.md) | Laravel and MySQL API | `composer run serve` |

## Run both locally

Install Node 22.13+ (Node 22), **PHP 8.5 at `C:\php85` on Windows**, Composer 2 and MySQL 8. Use the repository root, not only one app folder. A GitHub ZIP must be extracted first. Run each command separately and fix errors before continuing:

```powershell
$env:Path = 'C:\php85;' + $env:Path
if (!(Test-Path hazard-server/.env)) {
    Copy-Item hazard-server/.env.example hazard-server/.env
}
if (!(Test-Path hazard-client/.env)) {
    Copy-Item hazard-client/.env.example hazard-client/.env
}
npm.cmd ci
composer --working-dir=hazard-server install
composer --working-dir=hazard-server check-platform-reqs
```

Get **MySQL Workbench → Test Connection** working first with your own PC's MySQL account/password. This is separate from a MuniAlert website/admin password; `.env.example` does not supply a MySQL password. Follow [the Workbench connection, Access denied and database setup steps](documentation/COLLABORATOR_QUICKSTART.md#4-connect-mysql-and-create-a-new-development-database) to create an empty `municipal_hazards` schema and local application database user. Match that working connection in your copied `hazard-server/.env`, run the guide's read-only database check, then migrate. Leave `VITE_API_BASE_URL` blank for local website/USB browser testing.

For this **new installation only**:

```powershell
node scripts/php.mjs hazard-server/artisan key:generate
node scripts/php.mjs hazard-server/artisan config:clear
npm.cmd run db:migrate
npm.cmd run db:seed
node scripts/php.mjs hazard-server/artisan hazard:admin admin@example.test
```

Choose the administrator name/password when prompted. Create a separate citizen account through the app. Fresh installations have no owner accounts or incident records. Do not regenerate an existing app key or reset an existing database when updating.

Deployment seeding creates a starter administrator using private INITIAL_ADMIN_NAME/EMAIL/PASSWORD configuration. No public demo password or demo resident is included. See [initial-account setup](documentation/TEST_ACCOUNTS.md); existing accounts and reference-catalog edits are preserved.

From the repository root, start both services and keep the terminal running:

```sh
npm run dev
```

Use `npm.cmd run dev` in PowerShell if `npm.ps1` is blocked. Open `http://localhost:5173`. The launcher selects PHP 8.5 on Windows, checks database health, starts the API before the client, and checks the web-to-API proxy. Existing healthy MuniAlert instances are reused, so running the command again does not create duplicates or produce a port-5173 error. Occupied ports belonging to another project are reported and left running.

The separate client/server commands in the table also reuse their existing matching service. Do not start extra Vite/Artisan instances manually. In Git Bash, use `PATH="/c/php85:$PATH" composer run serve` when invoking Composer directly, so Composer itself uses the required PHP runtime. [Startup troubleshooting and verification](documentation/STARTUP_REPAIR_2026-11-04.md).

For development accounts, follow the [server's local demo seeder instructions](hazard-server/README.md#local-demo-accounts). For an installable Android app, follow the [client's Android setup](hazard-client/README.md#dedicated-android-app).

The [capstone documentation](documentation/README.md), [task tracker](TASKS.md), [deployment examples](deployment/), and cross-application browser tests live at the workspace root. Local environment files, incident evidence, dependencies and generated builds are excluded from publication. The earlier app Git histories remain as local backups, while members use the single public workspace repository, as explicitly confirmed on 12 November 2026.

## Web and mobile stack / Hostinger

Keep the existing Vue + Capacitor Android + Laravel stack. Client/server source separation does not require separate hosting. Run `npm run build:web` from this folder to package the web client inside `hazard-server/public/app`, then serve **only** `hazard-server/public` on one HTTPS domain. Node is needed for builds, not as a production server. Android still builds independently with its configured HTTPS API origin.

See [stack and Hostinger options](documentation/STACK_AND_HOSTINGER.md), [UI design](documentation/UI_DESIGN.md), and [engineering acceptance](documentation/ENGINEERING_ACCEPTANCE.md). On Windows, use PHP 8.5 (`C:/php85/php.exe`) and `npm.cmd` if PowerShell blocks `npm.ps1`; prepend `C:/php85` to the command process PATH before npm/Composer scripts. Do not use XAMPP PHP as a workaround.

Confirmed target: **Hostinger Web/Cloud hosting; Android app + responsive web**. Follow [the Web/Cloud deployment guide](documentation/HOSTINGER_WEB_CLOUD.md) for `public_html`, private Laravel files and a single scheduled push cron. A VPS or persistent worker is not required for this mode; validate actual hosting limits and delivery latency before operational use.

Google and Facebook authentication have been removed: provider/prepare/callback/exchange/link routes, frontend integration, native callback, Socialite and native App/Browser dependencies are retired. Email/password, MFA and email/saved-code recovery remain. Historical social tables/migrations are preserved without reset; they provide no sign-in route. Former provider-only accounts must establish a password using the existing verified-email or saved-code recovery process. Social sharing of reviewed news remains a separate feature.

Read the [8 November interview/screenshot and Android review](documentation/INTERVIEW_ALIGNMENT_2026-11-08.md) for corrected requirements, screenshot verdicts, private evidence, resolution photos and weekly/monthly incident records.

Application name: **MuniAlert**. Full capstone title: **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking**. Open **Guide & user manual** or `?view=guide` for feature highlights, a six-step replayable tour and searchable citizen/admin instructions. The promotional source and exports live in [promo-video](promo-video/README.md).


The managing office is **Basista Municipal Hall, Pangasinan**, covering the 13 barangays confirmed by the user. See [map modes, boundary sources and filtering](documentation/BASISTA_MAP_2026-11-09.md). Open `?view=map` and choose Clear/Streets/Dark, boundary levels or the hazard type.


For false or abusive reports, follow [the municipal review guide](documentation/FRAUD_PREVENTION.md). Current controls combine verified email, reviewed identity and human incident verification; automated fraud detection is not implemented.

## Continued security phases

Administrator MFA and fresh verification, versioned/revoked credentials, verified-local-email recovery, production CSP, fail-closed upload scanning and private security events are implemented. See [the activation guide](documentation/SECURITY_OPERATIONS_2026-11-10.md) before migrating an operational system: existing password-only administrator sessions are rejected and enrollment needs operator approval. SMTP is configured locally; host delivery and an approved scanner service remain prerequisites. The expanded exact-state gate includes PHP static analysis, TypeScript and pinned/redacted source secret scanning. No push/deployment is implied.

## Community review and achievements

Members see accepted hazards and explicitly published community news; own submissions stay in My reports. Administrators record factual review before acceptance and publish a separate reviewed post. Profiles include ten category tracks at 1/5/15 accepted own reports. See [the implementation and local evidence](documentation/COMMUNITY_REVIEW_ACHIEVEMENTS_2026-11-10.md).

## Latest community navigation refinement

Verified hazards is removed from the community menu; use Hazard map for accepted incidents, Verified news for shareable reviewed updates and My reports for every own undeleted submission/status. News includes reviewed incident details and optional submitter display-name credit. See [implementation and local evidence](documentation/COMMUNITY_NAVIGATION_NEWS_DETAILS_2026-11-10.md).
