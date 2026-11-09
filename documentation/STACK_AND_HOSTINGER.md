# Stack and Hostinger review — 4 November 2026

## Recommendation

Keep **Vue 3 + TypeScript + Capacitor Android + Laravel 12 + Sanctum + MySQL**.
MapLibre, R2 and FCM meet the documented mapping, evidence-photo and push needs.
There is no measured requirement that justifies a new frontend/backend framework
or native rewrite. This stack shares most interface code between web and Android.
Laravel 12 is currently in its security-fixes-only period; its published security
support ends **24 February 2027**. Plan a tested Laravel 13 upgrade before that date
as maintenance of the existing stack. This review preserves the established major
version rather than combining a framework migration with the UI/deployment change.
The existing native target is Android; an iPhone app would require a separately
configured Capacitor iOS project, signing, APNs/FCM integration and device testing.

The existing **Capacitor 7 / Android target API 35** native project also needs
maintenance before a new public Google Play release. Google requires API 36 for
new apps/updates from 31 August 2026; the published extension ended 1 November.
Plan a tested upgrade to the stable Capacitor 8 line with compatible camera,
geolocation, push and secure-storage plugins, Android toolchain/API 36, native
permission/edge-to-edge checks and a signed-device build. Asset synchronization
on Capacitor 7 is not evidence of Play submission eligibility. This is a native
release prerequisite; responsive web operation does not depend on that upgrade.

Keep the source folders/repositories separate as previously requested, but
**deploy the web application and API together on one HTTPS origin**. Source
separation does not require separate domains or two runtime web servers. Vue is
compiled static JavaScript; Node/Vite are build tools, not production daemons.
An Inertia/Blade migration would add conversion work and reduce straightforward
reuse of the client in Capacitor without solving the actual deployment issue.

The user confirmed **Hostinger Web/Cloud hosting** and **Android app + responsive web**. This supersedes the previous VPS target. Follow [the concrete Web/Cloud deployment guide](HOSTINGER_WEB_CLOUD.md): one public origin, a private Laravel directory, compiled static assets, and a bounded scheduled push worker. iPhone packaging is outside the confirmed scope. No hosting account or domain has been provisioned.

## One-origin package implemented

From BANTAY, run `npm run build:web` (`npm.cmd run build:web` in PowerShell).
It builds into `hazard-client/dist-web`, then copies compiled files into
`hazard-server/public/app`. This command forces an empty API origin for the web
build and `/app/` for asset URLs. Android continues to use `hazard-client/dist`
and its explicitly configured HTTPS API origin; web packaging does not overwrite
the Android asset directory. Laravel serves the packaged HTML at `/`, with
`Cache-Control: no-store`; `/api`, `/auth`, `/sanctum` and `/storage` retain their
existing contracts. Shared news links use `/?news=UUID`; views use `?view=map`,
`?view=reports`, etc., so no broad SPA fallback can hide missing API routes.

Serve **only `hazard-server/public`**. Keep `.env`, `vendor`, logs, application
code and Firebase service-account credentials outside the public directory.
The package is an asset preparation step, not a deployment or database change.
Release to a fresh directory so obsolete hashed assets do not accumulate;
the local packaging command intentionally does not delete existing release files.

## VPS alternative (not the selected target)

Use the included Nginx example, configured TLS, PHP-FPM, MySQL, a supervised
`php artisan queue:work report-push --sleep=3 --tries=3 --timeout=60` process,
and the scheduler cron. Worker/scheduler examples now reference `hazard-server`
instead of the obsolete `backend` path. Confirm the PHP binary, FPM socket and
extensions on the actual host. PHP 8.5 at this workstation does not configure
Hostinger, cron or Apache. Run `composer check-platform-reqs --lock` there.

Set `APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL`, HTTPS session
cookies, exact `SANCTUM_STATEFUL_DOMAINS` and `FRONTEND_URLS`, plus private database,
storage and Firebase configuration. Install locked production Composer packages,
back up, migrate, link local storage if used, and optimize using the host's PHP.
Test cookie/CSRF login, uploaded-photo retrieval, admin denial for citizens,
report transitions, inbox/push, worker recovery and backup restoration there.

## Hostinger Web/Cloud (selected target)

Hostinger documents Laravel support and scheduled Artisan commands on Web/Cloud
hosting. Its scheduling/process controls differ from VPS. These are hosting
capabilities, not proof that this application's queue latency or load is accepted.

Build locally/CI, upload Laravel with the packaged `public/app` assets, and arrange
the document root to expose only `public`. If hPanel requires `public_html`, keep
the Laravel application outside it and place only the public entry/assets there;
adjust `index.php`'s application paths deliberately. Never upload the whole
Laravel project into a publicly served directory.

Enable `HAZARD_SCHEDULED_PUSH=true` and run one hPanel `schedule:run` cron. The scheduler runs `hazard:work-push`, which uses a database overlap lock and processes at most three jobs with a 30-second between-job budget. See the linked guide for process-limit caveats and recovery. Confirm minimum cron frequency, process
limits, PHP CLI binary, permissions, outbound FCM access and failed-job monitoring
with the actual plan. Scheduled draining adds latency; it is unsuitable if the
municipality requires prompt push delivery under load. Use a VPS in that case.

## Sources checked

- [Hostinger framework support](https://www.hostinger.com/support/which-programming-languages-and-frameworks-are-supported-at-hostinger/)
- [Hostinger server and scheduled-process capabilities](https://www.hostinger.com/support/which-server-capabilities-are-supported-at-hostinger/)
- [Hostinger PHP configuration](https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/)
- [Laravel queue workers](https://laravel.com/docs/12.x/queues#running-the-queue-worker)
- [Laravel 12 support policy](https://laravel.com/docs/12.x/releases#support-policy)
- [Google Play target API requirements](https://support.google.com/googleplay/android-developer/answer/11926878?hl=en)
- [Capacitor 8 migration](https://capacitorjs.com/docs/updating/8-0)

These sources describe provider/framework capabilities. The recommendation to
keep the stack and combine its deployment is an engineering assessment of this
repository, not a benchmark or Hostinger account verification.
