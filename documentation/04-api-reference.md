# 04 — REST API reference

## Conventions and authentication

Development API origin: `http://localhost:8000`. Vite proxies `/api`, `/auth`, `/sanctum`, and `/storage`. Production should use one HTTPS origin for the frontend and Laravel routes. Set `VITE_API_BASE_URL` to the HTTPS server origin for Android; it must not include a trailing `/api`.

Send `Accept: application/json`. JSON mutations use `Content-Type: application/json`; uploads use multipart form data with repeated `photos[]` parts. Single-report responses use `{ "data": { ... } }`. Lists use Laravel's `data`, `links`, and `meta` pagination envelope, with 100 reports per page.

For web authentication: `GET /sanctum/csrf-cookie`, then `POST /auth/register` or `/auth/login`, sending the decoded `XSRF-TOKEN` cookie as `X-XSRF-TOKEN` and including cookies. For Android: `POST /api/auth/register` or `/api/auth/login` returns a Sanctum token. Send it as `Authorization: Bearer <token>`. Tokens expire after 90 days without authenticated use by default (AUTH_SESSION_DAYS); middleware renews valid mobile tokens at most hourly. They are revoked at logout and removed from secure native storage. Web sign-in issues an HttpOnly remember cookie and a persistent session cookie; SESSION_LIFETIME defaults to 129600 minutes and SESSION_EXPIRE_ON_CLOSE=false. Clearing site/app data, revocation or prolonged inactivity requires sign-in again.

The health endpoint returns `{ "status": "ok" }` after a successful database query, or HTTP 503 with `{ "status": "unavailable" }` when that query fails. It does not return database error details. Local development adds a hashed workspace identity header for safe launcher reuse; production omits it.

## Endpoint inventory

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| GET | `/api/health` | Public | Database-backed API health check |
| GET | `/api/catalog` | Public | Categories and departments |
| GET | `/api/stats` | Public | Total, active, pending, responding, resolved counts |
| GET | `/api/admin/analytics` | Administrator | Bounded weekly/monthly record totals by PHT date and optional category |
| GET | `/api/reports/{report}/photos/{photo}` | Owner/admin | Authorized WebP bytes; private/no-store; scoped photo ID |
| POST | `/api/reports/{report}/resolution-photos` | Administrator | One to three private resolution uploads; three total per report |
| GET | `/api/reports` | Public | Report list; rejected reports excluded |
| GET | `/api/reports/nearby` | Public | Recent duplicate candidates |
| GET | `/api/settings` | Public | Municipal map center/zoom and emergency hotlines |
| GET | `/api/badges` | Public | Badge definitions and reward descriptions |
| GET | `/api/news` | Public | Confirmed-report news, 20 per page; optional `report` UUID |
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

`/api/news` includes only `verified`, `assigned`, `responding`, `resolved`, and `closed` reports with both an admin-reviewed `public_title` and `public_summary`. Verification alone does not publish news. Each item contains a report, reaction counts, comment count, and the signed-in user's reaction. A shared news link uses `?news=<report UUID>` in the client and the API's `report` filter. Reporter identity and original GPS stay private in guest and other-citizen responses. The profile endpoint counts reports currently in those confirmed states; deleted/rejected reports do not count. Badge milestones are fixed at 50, 100, 1,000, and 10,000. Admin reward metadata is informational and never sends payment or dispatches a gift.

Profile password changes require `current_password`, a new password of at least 12 characters, and `password_confirmation`. Map values are validated as latitude −90..90, longitude −180..180 and zoom 3..19. Hotline numbers permit digits, spaces, parentheses, hyphens and `+`. CMS and profile mutations require explicit confirmation in the supplied client. Authentication, report submission, news comments, and reactions use separate rate-limit buckets so browsing does not exhaust the registration limit.

## Comment pagination

`GET /api/news/{report}/comments?after=123` returns `{ "data": [...], "meta": { "has_more": true, "next_cursor": 148 } }`. Omit `after` for the first page; supply `next_cursor` to continue. Ordering is ascending by comment ID, limited to 25. IDs from another report never expose its comments. Negative/noninteger cursors return 422. The client offers Load more comments; posting/deleting reloads the first page and retains the draft on failure.

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

Admin PATCH additionally accepts nullable `public_title` (5?120 characters) and `public_summary` (15?1,000 characters). They must be supplied together and may be nonempty only for confirmed statuses. Both null unpublishes news; rejection clears them. Raw report text and evidence are never copied automatically into publication fields. Public lists/news always omit private fields even if an administrator is signed in; public locations are rounded to two decimal places. Private record endpoints retain exact coordinates and original text.

Private photo GET returns authorized `image/webp` bytes with `Cache-Control: private, no-store`, `nosniff` and no-referrer. Guests receive 401, other citizens 403, and a mismatched report/photo pair 404. Android sends its bearer credential on the image request and uses a temporary blob URL, never a token URL. Resolution-photo POST uses multipart `photos[]`, the same image limits as incident uploads, and a separate maximum of three resolution photos per report. Only an administrator on assigned/responding/resolved records may upload; invalid writes clean up newly stored files. It adds an audit entry but does not independently claim dispatch or change the report status.

Admin analytics GET requires `from`/`to` as `YYYY-MM-DD`, `group=week|month`, and optional category ID. The range is at most 366 calendar days, inclusive in Asia/Manila; weeks begin Monday. It aggregates all matching records in SQL rather than the displayed report page. Response: `from`, `to`, `group`, `timezone`, `date_basis`, `total`, `known_time`, and `periods[{period,total,accidents,known_time}]`. Incident time is used when known and submission time otherwise. First/last periods may be partial. Unverified reports count; rejected and deleted records do not. The aggregate has no victim identity, injury totals or diagnosis, and does not prove unique incidents or causation. Invalid input returns 422, and non-admin access fails. The client exports the returned summary as CSV with its dates/group in the filename.


## Basista reporting scope

New report submissions accept only the 13 barangays in server `config/municipality.php`, matching Basista Municipal Hall’s user-approved coverage. Case and ASCII spelling variants normalize to the canonical name (for example, `Osmena Sr.` becomes `Osmeña Sr.`). Unknown barangays receive 422 with a `barangay` validation error. Coordinates remain confirmed incident evidence; the reference boundary is not a legal geofence. Existing records are preserved.
