# 04 — REST API reference

## Conventions and authentication

### Shared workflow additions — 12 November 2026

Validation failures return HTTP 422 with `message`, `code: validation_error` and field-keyed `errors`, available in browser Network responses. No password, token or evidence is echoed for debugging. See [shared workflow behavior](REUSABLE_WORKFLOWS_2026-11-12.md).

| Method / path | Access and behavior |
| --- | --- |
| GET `/api/admin/departments` | Administrator; all offices and category routing |
| POST `/api/admin/departments` | Administrator; unique lowercase/hyphen office `id` and `name`; creates active office |
| PATCH `/api/admin/departments/{department}` | Administrator; `name`, boolean `is_active`; archive requires reassigned categories and preserves report history |
| PATCH `/api/admin/categories/{category}/routing` | Administrator; existing active `department_id`; applies to future reports |
| POST `/api/auth/recovery/code` | Public, rate limited; `email`, saved `code`, `password`, `password_confirmation`; consumes one saved code under the user lock, revokes credentials, retains admin 2FA, generic invalid/inactive/unknown failure |

POST `/api/security/recovery-codes` also supports password-bearing citizen accounts after recent verification. It returns ten new codes once, revokes previous codes/other credentials, and preserves the current verified session. Enrolled users share this set with existing 2FA backup codes. Email-link recovery remains separately configured. No sign-in session is issued by password recovery.

Development API origin: `http://localhost:8000`. Vite proxies `/api`, `/auth`, `/sanctum`, `/storage`, and `/news`. Production should use one HTTPS origin for the frontend and Laravel routes. Set `VITE_API_BASE_URL` to the HTTPS server origin for Android; it must not include a trailing `/api`.

Send `Accept: application/json`. JSON mutations use `Content-Type: application/json`; uploads use multipart form data with repeated `photos[]` parts. Single-report responses use `{ "data": { ... } }`. Lists use Laravel's `data`, `links`, and `meta` pagination envelope, with 100 reports per page.

For web authentication: `GET /sanctum/csrf-cookie`, then `POST /auth/register` or `/auth/login`, sending the decoded `XSRF-TOKEN` cookie as `X-XSRF-TOKEN` and including cookies. For Android: citizen registration/login returns a Sanctum token; administrator login returns a limited MFA challenge first, as specified below. Send it as `Authorization: Bearer <token>`. Native credentials persist until logout/security revocation by default (AUTH_PERSISTENT_LOGIN=true); the optional expiring policy still rejects expired/revoked tokens before renewal. They are revoked at logout and removed from secure native storage. Web sign-in issues an HttpOnly remember cookie and a persistent session cookie; SESSION_LIFETIME defaults to 129600 minutes and SESSION_EXPIRE_ON_CLOSE=false. Browser device proofs can rebuild session/MFA state without granting recent-action verification; browser/OS retention, cleared site/app data and security revocation can still require sign-in.

The health endpoint returns `{ "status": "ok" }` after a successful database query, or HTTP 503 with `{ "status": "unavailable" }` when that query fails. It does not return database error details. Local development adds a hashed workspace identity header for safe launcher reuse; production omits it.

All API/authentication responses now use private/no-store caching and baseline security headers. Password creation requires 12 or more characters and no more than 72 UTF-8 bytes; login/password confirmation rejects inputs above the same byte limit. Malformed credential fields return 422. Public registration/profile updates cannot set privileged account fields. Production disables debug details and enforces Secure/HttpOnly session cookies; local HTTP remains supported.

## Endpoint inventory

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| GET | `/api/health` | Public | Database-backed API health check |
| GET | `/api/catalog` | Public | Categories and departments |
| GET | `/api/stats` | Public | Accepted-only counts; pending is always zero |
| GET | `/api/admin/analytics` | Administrator | Bounded weekly/monthly record totals by PHT date and optional category |
| GET | `/api/reports/{report}/photos/{photo}` | Owner/admin | Authorized WebP bytes; private/no-store; scoped photo ID |
| POST | `/api/reports/{report}/resolution-photos` | Administrator | One to three private resolution uploads; three total per report |
| GET | `/api/reports` | Public | Only fact-checked, accepted reports with safe public fields |
| GET | `/api/reports/nearby` | Public | Only accepted active duplicate candidates |
| GET | `/api/settings` | Public | Municipal map center/zoom and emergency hotlines |
| GET | `/api/badges` | Public | Badge definitions and reward descriptions |
| GET | `/api/news` | Public | Explicitly published fact-checked news, 20 per page; optional `report` UUID |
| GET | `/api/news/{report}/comments` | Public | 25 comments per page; optional nonnegative `after` comment ID |
| POST | `/auth/register` | Web/CSRF | Register a citizen and start a session |
| POST | `/auth/login` | Web/CSRF | Start a web session |
| POST | `/api/auth/register` | Public/mobile | Register and issue a mobile token |
| POST | `/api/auth/login` | Public/mobile | Issue a mobile token |
| GET | `/api/auth/me` | Authenticated | Current user |
| GET/PATCH | `/api/profile` | Authenticated | Profile, verified-report count and earned badges; edit name/email/password |
| POST | `/api/auth/logout` | Authenticated | Revoke current mobile token or invalidate session |
| GET | `/api/reports/mine` | Authenticated | Current reporter's records, including rejected |
| GET | `/api/admin/reports` | Administrator | Municipal list including rejected |
| POST | `/api/reports` | Citizen | Submit report and photos |
| GET | `/api/reports/{report}` | Owner/admin | Full report and private history/evidence |
| PATCH | `/api/reports/{report}` | Administrator | Verify location, route and update workflow |
| POST | `/api/reports/{report}/confirm` | Authenticated | Confirm someone else's active report |
| DELETE | `/api/reports/{report}` | Owner, before verification | Soft-delete an unverified report; audited and removed from public lists |
| PUT | `/api/admin/settings` | Administrator | Publish map center, zoom and up to 20 hotlines |
| PATCH | `/api/admin/badges/{badge}` | Administrator | Edit badge name, description, and cash/gift information |
| POST | `/api/news/{report}/reactions` | Citizen | Toggle one `helpful` or `support` reaction |
| POST | `/api/news/{report}/comments` | Citizen | Post a 2–1000 character comment |
| DELETE | `/api/news/{report}/comments/{comment}` | Comment owner/admin | Remove a comment |
| GET | `/api/notifications` | Authenticated | Latest 50 private notifications |
| PATCH | `/api/notifications/read` | Authenticated | Mark the user's unread inbox items as read |
| POST | `/api/devices` | Authenticated | Register/reassign a push token to the current user |
| DELETE | `/api/devices` | Authenticated | Remove a token owned by the current user |

Public list queries accept `search` (maximum 120 characters), `category` (category ID), `status`, and `page` (positive integer). Statistics are municipality-wide, not the filtered page's counts. Nearby queries require `category`, `latitude`, and `longitude` and return at most five matches with `distance` in meters.

`/api/news` requires an accepted lifecycle status, factual review, publication timestamp and reviewed public title/summary. Verification alone does not publish. Public links use `/news/{report}`; the older `?news=<UUID>` app return link remains supported. The private profile counts only its own currently accepted reports and includes ten category tracks at 1/5/15. Existing overall 50/100/1,000/10,000 milestones/reward descriptions remain; no payment or gift is dispatched automatically.

Profile password changes require `current_password`, a new password of at least 12 characters, and `password_confirmation`. Map values are validated as latitude −90..90, longitude −180..180 and zoom 3..19. Hotline numbers permit digits, spaces, parentheses, hyphens and `+`. CMS and profile mutations require explicit confirmation in the supplied client. Authentication, report submission, news comments, and reactions use separate rate-limit buckets so browsing does not exhaust the registration limit.

## Comment pagination

GET `/api/news/{report}/comments?after=123` returns `{ "data": [...], "meta": { "has_more": true, "next_cursor": 148 } }`. Omit `after` for the first page; supply `next_cursor` to continue. Ordering is ascending by comment ID, limited to 25. IDs from another report never expose its comments. Negative/noninteger cursors return 422. The client offers Load more comments; posting/deleting reloads the first page and retains the draft on failure.

## Account payloads

```json
{
  "name": "Example Citizen",
  "email": "citizen@example.test",
  "password": "at-least-twelve-characters"
}
```

Login uses only email and password. Registration ignores unsupported privilege fields; it never creates an administrator. Provision administrators with `php artisan hazard:admin <email>` and the hidden password prompt.

## Report submission

The following scalar fields are multipart fields, not a single JSON `data` part:

```json
{
  "category_id": "tree",
  "title": "Fallen tree along Riverside Road",
  "description": "A fallen tree blocks half of the road near the school entrance.",
  "address": "Riverside Road, beside the school",
  "barangay": "Anambongan",
  "severity": "high",
  "selected_latitude": 14.59963,
  "selected_longitude": 120.98431,
  "gps_latitude": 14.599512,
  "gps_longitude": 120.984221,
  "gps_accuracy": 9.2,
  "location_source": "gps_adjusted",
  "location_confirmed": "1"
}
```

Append one to three image files under `photos[]`. JPEG, PNG, and WebP are accepted only when decoding succeeds. Each upload must already be at most 1 MB and 1920 pixels per side; the server re-encodes it to WebP and enforces the final size again. The provided frontend compresses larger camera photos before sending.

For `manual`, omit all GPS fields. For `gps` or `gps_adjusted`, provide the complete GPS trio. A `gps` source requires the selected point to equal the GPS point within the documented tolerance. Administrator-only fields such as status, verified coordinates and reporter ID are not accepted from the citizen payload.

Successful creation returns HTTP 201 with a unique reference, the submitted status and authenticated photo API paths (not public object URLs). The client does not claim success until the API responds successfully. A nearby-check failure does not silently bypass the duplicate check in the supplied frontend.

## Administrator update

```json
{
  "status": "verified",
  "severity": "high",
  "department_id": "engineering",
  "assigned_responder": null,
  "note": "Location reviewed; tree is obstructing the northbound lane.",
  "verified_latitude": 14.59964,
  "verified_longitude": 120.98432
}
```

Both verified coordinates are required when transitioning to `verified`. A team/responder is required when transitioning to `assigned`. The allowed progression is documented in the architecture attachment. The caller supplies a response note of 5–1000 characters. Updates preserve original selected/GPS values.

## Push token payload

```json
{ "token": "device-token-returned-by-firebase", "platform": "android" }
```

Use `web` for browser messaging. Native iOS packaging/FCM registration is outside this release; do not pass a raw APNs token as an FCM token. Logout unregisters the locally remembered push token before ending the session.

## Errors and limits

| Status | Meaning | Client action |
| --- | --- | --- |
| 401 | Missing/expired login | Sign in again |
| 403 | Wrong role or private record | Do not retry with the same permissions |
| 404 | Report missing | Refresh the list |
| 419 | Web session/CSRF expired | Reload and sign in again |
| 422 | Invalid fields, workflow or inactive confirmation | Display `errors` or `message` and correct input |
| 429 | Rate limit | Wait before retrying |
| 500/503 | Storage, database or service failure | Show failure; inspect server logs; do not report success |

The API route group allows 120 requests/minute per throttle identity; login and report creation have additional 10/minute limits and registration 5/minute. Enforce infrastructure body limits of at least 4 MB for three permitted images plus fields. Never expose production stack traces; set `APP_DEBUG=false`.

## Social signup and login (FR-02)

| Method | Path | Access and behavior |
| --- | --- | --- |
| GET | `/api/auth/providers` | Public; enabled flags for google/facebook only; no credentials. |
| POST | `/api/auth/social/{provider}/attempts` | Public for login; authenticated plus current_password for link. Provider limited to google/facebook; 10/minute plus global throttle. |
| POST | `/api/auth/social/exchange` | Public Android code/verifier exchange, 10/minute plus global throttle; one-use, no-store. |
| GET | `/api/auth/social/accounts` | Authenticated; connected provider names only. |
| GET | `/auth/social/{provider}/redirect` | Session-bound browser redirect; one-use prepared attempt, 20/minute. |
| GET | `/auth/social/{provider}/callback` | Stateful provider callback, 20/minute; web cookie sign-in or fixed Android deep link. |

Prepare body: `platform` web/android; `mode` login/link; optional whitelisted `intent` (overview/report/profile/mine/activity/news/reports/map). Web requires `return_origin` with no path/query/userinfo and exact origin allowlisting. Android requires URL-safe 43-character S256 `code_challenge`. Linking requires current_password and an authenticated account with password enabled. Returns 201 `{attempt,redirect_url}`; navigate top-level or use the Android system browser. Credentials missing => 503, invalid input/origin => 422, missing link session => 401. Browser POSTs retain the normal CSRF cookie/header requirements.

Exchange body: attempt UUID, code (64 characters), code_verifier (43?128 RFC 7636 characters). Returns `{user,intent,linked,token?}`; token exists for Android login, not link. Replays, expiration, incorrect proof or exhausted five-try limit => 422. Provider denial/errors return safe reason codes; email collisions require explicit linking. Browser query status is never trusted as authentication: client confirms the real session with `/api/auth/me`. See [configuration and security](SOCIAL_AUTH_SETUP.md).

## Interview-aligned fields, private media and consolidation ? 8 November 2026

Submission optionally accepts `occurred_at` (ISO timestamp with offset, no future time), `injuries` (`unknown`, `yes`, `no`) and nullable integer `people_affected` (0?1,000,000, an estimate). The phone converts local incident time to UTC. Omitted incident time stays null; it is never invented during migration. Citizen-supplied publication fields are not accepted.

Response PATCH cannot publish text or bypass factual acceptance. Use the explicit fact-check and publication endpoints below. Public list/news projections remain redacted even for an authenticated administrator; private endpoints retain owner/admin evidence and original data.

Private photo GET returns authorized `image/webp` bytes with `Cache-Control: private, no-store`, `nosniff` and no-referrer. Guests receive 401, other citizens 403, and a mismatched report/photo pair 404. Android sends its bearer credential on the image request and uses a temporary blob URL, never a token URL. Resolution-photo POST uses multipart `photos[]`, the same image limits as incident uploads, and a separate maximum of three resolution photos per report. Only an administrator on assigned/responding/resolved records may upload; invalid writes clean up newly stored files. It adds an audit entry but does not independently claim dispatch or change the report status.

Admin analytics GET requires `from`/`to` as `YYYY-MM-DD`, `group=week|month`, and optional category ID. The range is at most 366 calendar days, inclusive in Asia/Manila; weeks begin Monday. It aggregates all matching records in SQL rather than the displayed report page. Response: `from`, `to`, `group`, `timezone`, `date_basis`, `total`, `known_time`, and `periods[{period,total,accidents,known_time}]`. Incident time is used when known and submission time otherwise. First/last periods may be partial. Unverified reports count; rejected and deleted records do not. The aggregate has no victim identity, injury totals or diagnosis, and does not prove unique incidents or causation. Invalid input returns 422, and non-admin access fails. The client exports the returned summary as CSV with its dates/group in the filename.


## Basista reporting scope

New report submissions accept only the 13 barangays in server `config/municipality.php`, matching Basista Municipal Hall’s user-approved coverage. Case and ASCII spelling variants normalize to the canonical name (for example, `Osmena Sr.` becomes `Osmeña Sr.`). Unknown barangays receive 422 with a `barangay` validation error. Coordinates remain confirmed incident evidence; the reference boundary is not a legal geofence. Existing records are preserved.

## Phase 2 identity contracts

Administrator password/provider success returns a limited MFA response (`mfa_required`, `enrollment_required`, `challenge_token`, `expires_in`), without `user`/`token`. Approved enrollment also returns a private setup key/otpauth URI. A web social return uses `social_auth=mfa` without a proof in its URL. No administrator credential is issued before factor verification.

| Method | Path | Access / behavior |
| --- | --- | --- |
| POST | `/api/auth/mfa/verify` | Native limited challenge + code; full credential only after atomic verification |
| POST | `/auth/mfa/verify` | Web challenge + session binding + code, with CSRF; no bearer token returned |
| GET | `/auth/mfa/pending` | Pull this browser's pending provider MFA challenge once |
| GET | `/api/auth/security-options` | Public delivery-availability flag, no account information |
| POST | `/api/auth/recovery` | Email input; same generic response for all account states, throttled |
| POST | `/api/auth/recovery/consume` | One-use fragment proof, purpose reset/verify; reset requires confirmed bounded password |
| GET | `/api/security/status` | Current account factor/recovery-count/local-email/delivery flags; no secret values |
| POST | `/api/security/reauthenticate` | Current password when present plus fresh factor when enabled; current-device proof lasts five minutes |
| POST | `/api/security/recovery-codes` | Fresh verification required; replace codes once and revoke other credentials |
| POST | `/api/security/verify-email` | Authenticated request to the stored email; admin requires fresh verification; delivery must be configured |

Code/proof errors return generic 422; expired/revoked/suspended or insufficient administrator credentials return 401. A sensitive write requiring fresh verification returns 428 with `reauthentication_required`, `password_required` and `mfa_required` before changing data. The client verifies then retries once. Each TOTP step and recovery code is single-use. Setup requires a separately approved operator window. Generic recovery does not auto-login or remove MFA. Admin settings/report/evidence writes also record redacted security events.

If scanner integration is enabled, infected images return 422 and scanner outages return 503 before accepted evidence storage. Direct web HTML receives enforced CSP; directly served static files need the emitted host headers. See [the complete implementation/limits](SECURITY_PHASE_2_2026-11-10.md).

## Approved dashboard background

| Method | Path | Access / behavior |
| --- | --- | --- |
| GET | `/api/dashboard-background/{id}` | Public approved decoration only; current UUID or 404; image/webp and nosniff |
| POST | `/api/admin/dashboard-background` | Administrator plus recent verification; multipart `image` JPG/PNG/WebP, up to 1 MB and 1920px per side; sanitized/scanned through existing pipeline; returns `dashboard_background` URL |
| DELETE | `/api/admin/dashboard-background` | Administrator plus recent verification; restores default and removes old image |

`/api/settings` includes nullable `dashboard_background` URL and no private storage path. Oversized/invalid images return 422, stale sensitive verification returns 428 and enabled-scanner unavailability returns 503. Browser/native login still uses established endpoints; a verified persistent device restores browser access without recent-action proof. Mobile tokens default to no automatic expiry, with logout/security revocation retained.

## Factual acceptance, reviewed posts and category achievements

| Method | Path | Access / behavior |
| --- | --- | --- |
| GET | `/api/admin/stats` | Authenticated administrator; full queue counts including pending |
| POST | `/api/admin/reports/{report}/verify` | Admin plus recent verification; `confirmed_happened` accepted, `method` site_visit/responder_confirmation/official_record/cross_checked_evidence, `verification_note` 15–1,000 characters, required verified latitude/longitude. Records actor/time and accepts a new report. Repeated checks/rejected records fail 422. |
| POST | `/api/admin/reports/{report}/post` | Admin plus recent verification and accepted factual review; public_title 5–120, public_summary 15–1,000, privacy_reviewed accepted and optional boolean include_reporter_name. Publishes/edits a separate news post. |
| DELETE | `/api/admin/reports/{report}/post` | Admin plus recent verification; clears post text/publication metadata, removing feed/share access. |
| GET | `/news/{report}` | Public HTML share page for a currently published, fact-checked accepted incident; otherwise 404. Escaped reviewed text and social metadata only. |

Private admin report responses include factual method/note/actor; reporter responses omit those internal review fields. Public data omits original text/photos/private identity/contact fields/GPS and unpublished draft text. A published post may include public_reporter_name and public_occurred_at snapshots taken from the report at explicit publication; callers cannot supply an arbitrary name/time. Without include_reporter_name, existing attribution choice is preserved and a new API-only post remains anonymous. The composer defaults new posts to named credit and previews the choice. Both snapshots are cleared by unpublication/rejection and are null in unpublished public projections. GET `/api/profile` adds `hazard_achievements`: category id/name, verified_count, next_threshold and three levels with id/name/threshold/earned; it never selects another user's profile through query parameters. Status-only legacy records remain private until explicitly reviewed/published. Rejection removes public eligibility and progress; unpublishing alone does not remove accepted-report credit.

## Personal Settings preferences

| Method | Path | Access / behavior |
| --- | --- | --- |
| PUT | `/api/preferences/reading` | Authenticated, current secure account; account_id must equal the authenticated ID (403 otherwise). preferences contains exactly textSize integer100–200/multiple of5 and required boolean boldText/highContrast/readingSpace/reduceMotion/keepMessages. Unknown/invalid fields fail422. Returns preferences. |
| PUT | `/api/preferences/email` | Authenticated, account_id current-ID guard and required boolean email_report_updates. Returns the saved flag. Enabling does not bypass delivery-time verification/eligibility checks. |

These personal preference writes do not need administrative recent-action verification; administrator MFA and credential-version checks still apply. Auth/me and own-profile responses include reading_preferences and email_report_updates for restoration. Security status additionally exposes email_eligible and email_report_updates, without configuration/secret values. No other account can be selected by either endpoint. Guest display preferences remain local.

## Actionable notifications, assistance and image review

| Method | Path | Access / behavior |
| --- | --- | --- |
| PATCH | `/api/notifications/{notification}/read` | Current secure account, UUID, current-role owned notification only; unknown/foreign returns404. Marks one idempotently. No admin recent-action prompt; MFA/version checks retained. |
| POST | `/api/admin/reports/{report}/photos/{photo}/review` | Admin plus recent verification; report-bound photo UUID, assessment_version integer, verdict accepted_evidence/suspected_ai/inconclusive, review_note15–1000 characters, confirmed_reviewed accepted. Stale assessment409. Manual review can cancel pending inference. |

POST reports accepts optional boolean assistance_needed; the private resource returns it to owner/admin only. Active admins receive separate safe report_submitted notifications transactionally. GET notifications filters the current role; bulk read retains that scope. Selecting an item still requires the existing report authorization. No arbitrary notification redirect URL is accepted.

Private admin photo projections add authenticity.required/status/probability/model/version and nullable review.verdict/note/at/version. Owners retain photos without that metadata; public photos remain empty. New required incident evidence without a current accepted review blocks factual verification422, without blocking responder contact. No score changes report lifecycle automatically.

Hotline settings additionally accept optional service police/fire/medical/rescue/other, area<=80, address<=160 and paired bounded latitude/longitude. Old name/number-only contacts remain valid. No geolocation data is sent to a discovery service. See [workflow/local detector contract](EMERGENCY_NOTIFICATIONS_IMAGE_REVIEW_2026-11-11.md).
