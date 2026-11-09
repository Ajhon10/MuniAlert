# Responsive content and persistent sessions - 3 November 2026

The user requested fuller use of desktop/mobile space, phone testing instructions,
and sessions that remain signed in after closing the website or Android app.
The Vue 3 / Laravel 12 / Sanctum / Capacitor 7 / MySQL architecture is retained.
This follow-up supersedes the earlier memory-only Android session decision.

## Layout

Applied the `ui-ux-pro-max` skill's responsive layout, content priority, readable
type, accessible feedback and keyboard guidance. The existing Bantay identity is
retained. `responsive.css` contains a readable adaptation layer over the existing
base stylesheet; `ReportCards.vue` supplies the phone representation.

- Desktop: 216-256px sidebar, fluid content up to 1920px, readable metric cards,
  flexible search/filters and a wide map with an attention panel. Smaller laptops
  stack map/attention content rather than compressing the map into a narrow strip.
- Tablet: compact drawer below 1024px, four metrics where space permits, one-column
  forms/CMS as needed, no reserved desktop sidebar space.
- Phone: two-column metrics, a full-width report button, readable wrapped headings,
  reflowing filters and report cards in place of the horizontal report table.
  Below 400px the category filter occupies its own row so its label remains visible.
- Forms/news/profile: larger text, shrinkable content, responsive photo grids,
  stacked phone fields and controls, viewport-bounded dialogs and safe-area offsets.
- Accessibility: focus outlines, keyboard drawer focus/escape, focus return,
  inert hidden navigation/background, selected-navigation semantics, skip link,
  darker status/priority text and primary buttons. Existing reduced-motion handling
  is preserved. This is not a full assistive-technology certification.

Tests exercise 320, 375, 390, 768, 844 landscape, 1024, 1366 and 1920px viewports,
report-card opening, bounds, keyboard drawer behavior and wrapped headings.
The fixture screenshots contain fictional demo reports.

## Session policy

| Target | Persistence | Default expiry | Revocation |
| --- | --- | --- | --- |
| Website, including Android Chrome | HttpOnly session and Laravel remember cookies; CSRF retained | 90-day idle session cookie; 90-day remembered-login cookie | Logout invalidates session/remember credential; password changes reject other sessions/remember credentials |
| Installed Android app | Capacitor secure-storage 7.1.3, using Android Keystore-backed AES-GCM storage | 90 days, renewed by authenticated use at most hourly | Logout revokes current token and clears saved credential; password changes revoke other tokens |

`SESSION_LIFETIME=129600` is in minutes; `SESSION_EXPIRE_ON_CLOSE=false` and
`AUTH_SESSION_DAYS=90` are the defaults. Only these nonsecret values were updated
in the ignored local server environment. Existing sessions adopt the longer cookie
on their next response; existing users should sign in once for remembered login.
Production still requires HTTPS and secure-cookie settings.

Tokens/passwords are never persisted in browser localStorage. Native credentials
are restored before requests. A 401 clears the stored credential, while a server
outage does not erase it. A failed secure-storage write prevents a misleading
successful login and attempts to revoke the just-issued credential. An old
startup 401 cannot clear a newer successful login. Native persistence requires
building/installing the updated app; old memory-only credentials cannot be recovered.

Expired/revoked tokens are rejected by Sanctum before renewal. Renewal updates an
existing row conditionally rather than recreating a deleted token. Login and
password changes lock the same user row; password changes revoke other tokens
and rotate remember credentials while preserving the current device. Multi-process
MySQL race acceptance is not established by SQLite's sequential tests.

Clearing cookies or app data, private browsing, uninstalling, revocation and the
inactivity window can still require sign-in. Sessions are intentionally not immortal.

## Android testing and tooling

Follow [Android phone testing](ANDROID_TESTING.md): USB forwarding in Chrome tests
the running website immediately. Native testing requires JDK 21, SDK 35, a
phone-reachable HTTPS API, a Capacitor sync and an Android Studio build/install.

The secure-storage version is pinned to 7.1.3 to retain the existing Gradle 8.11.1
toolchain; the Android Gradle plugin is aligned to 8.7.3. Version 7.1.6 declares
AGP 8.13.0 and was not selected for this older Android toolchain. Both workspace
and standalone client lockfiles include the pinned dependency.

Official references: [Laravel authentication](https://laravel.com/docs/12.x/authentication),
[secure-storage plugin](https://github.com/aparajita/capacitor-secure-storage),
[Android Keystore](https://developer.android.com/privacy-and-security/keystore),
[Chrome USB forwarding](https://developer.chrome.com/docs/devtools/remote-debugging/local-server).

## Verification and limitations

Final results: **23 Laravel tests / 150 assertions** and **16 browser tests**
(39.2 seconds) passed. Composer manifest/locked platform requirements, PHP
formatting, TypeScript, production build, Android asset/plugin sync and
documentation checks passed. The normal app passed its read-only desktop/375px
browser smoke check after restarting the stopped development servers.

`npm run verify` stopped at the npm audit (nine vulnerabilities: four moderate,
three high, two critical); the separately executed Composer audit still reports
the existing `symfony/mime` advisory. No audit suppression or forced downgrade
was used. The release gate remains failed.

Fictional preview screenshots: [desktop](evidence/responsive-desktop-2026-11-03.png)
and [320px phone](evidence/responsive-mobile-2026-11-03.png).

Final executed counts, source hashes and release-gate results are retained in
[the follow-up evidence](evidence/responsive-sessions-2026-11-03.json). The new
tests include remembered-cookie reopening after discarding the old session cookie,
logout, password changes across two browser contexts, mobile expiry/renewal and
token revocation, plus simulated Capacitor bridge restoration/outage/error paths.

No configured Android SDK/JDK or physical device is available in this workspace.
Asset/plugin sync and a simulated bridge do not establish APK compilation or
physical Keystore, camera, GPS or Firebase behavior. The previous dependency-audit
and push-durability findings still prevent release acceptance. No GitHub push,
deployment or load/field acceptance is claimed.
