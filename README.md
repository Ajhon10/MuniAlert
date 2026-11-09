# MuniAlert — Municipal Hazard Reporting

Private team repository: **[Ajhon10/MuniAlert](https://github.com/Ajhon10/MuniAlert)**. One clone contains the two application folders, shared tests, documentation and Remotion source. Start with [team setup](documentation/TEAM_TESTING.md) and [Android phone development testing](documentation/ANDROID_TESTING.md).

The applications remain separate:

| Folder | Application | Local command |
| --- | --- | --- |
| [hazard-client](hazard-client/README.md) | Vue 3 and Capacitor client | `npm run dev` |
| [hazard-server](hazard-server/README.md) | Laravel and MySQL API | `composer run serve` |

## Run both locally

Create an empty MySQL schema named `municipal_hazards`. In `hazard-server/.env`, set the credentials for the MySQL account that can access it. A password belongs to the MySQL account, not the project. Follow each application's README for first-time dependency installation, app key, migrations, and administrator creation.

From this BANTAY folder, start both services with one command:

```sh
npm run dev
```

Use `npm.cmd run dev` in PowerShell if `npm.ps1` is blocked. Open `http://localhost:5173`. The launcher selects PHP 8.5 on Windows, checks database health, starts the API before the client, and checks the web-to-API proxy. Existing healthy BANTAY instances are reused, so running the command again does not create duplicates or produce a port-5173 error. Occupied ports belonging to another project are reported and left running.

The separate client/server commands in the table also reuse their existing matching service. Do not start extra Vite/Artisan instances manually. In Git Bash, use `PATH="/c/php85:$PATH" composer run serve` when invoking Composer directly, so Composer itself uses the required PHP runtime. [Startup troubleshooting and verification](documentation/STARTUP_REPAIR_2026-11-04.md).

For development accounts, follow the [server's local demo seeder instructions](hazard-server/README.md#local-demo-accounts). For an installable Android app, follow the [client's Android setup](hazard-client/README.md#dedicated-android-app).

The [capstone documentation](documentation/README.md), [task tracker](TASKS.md), [deployment examples](deployment/), and cross-application browser tests live at the workspace root. Local environment files, incident evidence, dependencies and generated builds are excluded from publication. The earlier app Git histories remain as local backups, while members use the single private workspace repository.

## Web and mobile stack / Hostinger

Keep the existing Vue + Capacitor Android + Laravel stack. Client/server source separation does not require separate hosting. Run `npm run build:web` from this folder to package the web client inside `hazard-server/public/app`, then serve **only** `hazard-server/public` on one HTTPS domain. Node is needed for builds, not as a production server. Android still builds independently with its configured HTTPS API origin.

See [stack and Hostinger options](documentation/STACK_AND_HOSTINGER.md), [UI design](documentation/UI_DESIGN.md), and [engineering acceptance](documentation/ENGINEERING_ACCEPTANCE.md). On Windows, use PHP 8.5 (`C:/php85/php.exe`) and `npm.cmd` if PowerShell blocks `npm.ps1`; prepend `C:/php85` to the command process PATH before npm/Composer scripts. Do not use XAMPP PHP as a workaround.

Confirmed target: **Hostinger Web/Cloud hosting; Android app + responsive web**. Follow [the Web/Cloud deployment guide](documentation/HOSTINGER_WEB_CLOUD.md) for `public_html`, private Laravel files and a single scheduled push cron. A VPS or persistent worker is not required for this mode; validate actual hosting limits and delivery latency before operational use.

Google and Facebook signup/sign-in is implemented for web and Android. Configure server-only provider apps using [the social authentication guide](documentation/SOCIAL_AUTH_SETUP.md); unconfigured providers remain unavailable and email login continues working.

Read the [8 November interview/screenshot and Android review](documentation/INTERVIEW_ALIGNMENT_2026-11-08.md) for corrected requirements, screenshot verdicts, private evidence, resolution photos and weekly/monthly incident records.

Application name: **MuniAlert**. Full capstone title: **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking**. Open **Guide & user manual** or `?view=guide` for feature highlights, a six-step replayable tour and searchable citizen/admin instructions. The promotional source and exports live in [promo-video](promo-video/README.md).


The managing office is **Basista Municipal Hall, Pangasinan**, covering the 13 barangays confirmed by the user. See [map modes, boundary sources and filtering](documentation/BASISTA_MAP_2026-11-09.md). Open `?view=map` and choose Clear/Streets/Dark, boundary levels or the hazard type.


For false or abusive reports, follow [the municipal review guide](documentation/FRAUD_PREVENTION.md). Current controls support human verification; verified contact enrollment and automated fraud detection are not implemented.
