# BANTAY engineering acceptance

Updated 8 November 2026. This records source/local verification separately from
deployment and municipal acceptance.

## Requirements and boundaries

Citizens submit and confirm hazards, view their history/inbox, edit a profile and
participate in verified news. Administrators verify location, route/advance
reports, moderate comments and manage municipal settings/badge descriptions.
Guests see public reports, maps, news and approved hotlines. The API independently
authorizes private/administrative operations; original GPS and reporter identity
are private to the reporter and administrators. Raw evidence, descriptions, precise locations and incident observations
are now owner/admin only. Public maps use approximate areas; news requires
a separate administrator-reviewed title and summary.

Stack remains Vue 3 / TypeScript / Capacitor Android / Laravel 12 / Sanctum /
MySQL / MapLibre / configurable R2 / FCM. Source repositories remain separate;
the new web package supports one public domain/document root. The user confirmed **Hostinger Web/Cloud hosting** and **Android app + responsive web**. This replaces the previous VPS target. A bounded CLI scheduler worker replaces the persistent-worker requirement. No iPhone app or framework migration is in scope. See [the selected hosting guide](HOSTINGER_WEB_CLOUD.md).

## Operational requirements still to be agreed

- Peak active users, request mix/rate, shared-IP distribution and data growth.
- Latency/availability targets, retention period, backup RPO and recovery RTO.
- Exact Web/Cloud tier/account limits, budget, domain, service owners and maintenance access.
- Official municipality, geographic boundaries, departments and emergency numbers.
- Real-device support matrix and municipal UAT acceptance criteria.

No capacity or recovery-time promise follows from local functional tests. No
synthetic evaluation scores, respondents or municipal approval are claimed.

## State

Implemented: LUMA-style system typography and liquid-glass navigation/controls;
phone dock, filter clearing, URL/history navigation; preview-only news; paginated
comments; transactional per-device push queue; same-origin web packaging; corrected
worker/scheduler paths; compatible dependency updates; private evidence,
reviewed publication, incident observations, resolution uploads, weekly/monthly
PHT consolidation and Android reading/interaction improvements.

Local verification: see [the dated review](UI_STACK_REVIEW_2026-11-04.md) for
commands, counts, screenshots and the exact release gate result. Tests use isolated
SQLite databases, synthetic accounts and mocked FCM responses. PHP 8.5 locked
platform checks are local evidence only.

Not deployed: no push, domain, Hostinger activation or cloud provisioning occurred.

Not accepted operationally: a Capacitor/API 36 upgrade for new Google Play submissions, Laravel 13 maintenance before 24 February 2027, real R2 retrieval/retention, real FCM delivery, a signed
APK and physical camera/GPS/Keystore checks, MySQL 8 target acceptance, backup
restoration, capacity and municipal UAT remain to be verified with the intended
accounts/infrastructure/devices.

Latest interview alignment: [source review and evidence](INTERVIEW_ALIGNMENT_2026-11-08.md). Android phones are primary; physical-device, access-policy and municipal acceptance remain open.
