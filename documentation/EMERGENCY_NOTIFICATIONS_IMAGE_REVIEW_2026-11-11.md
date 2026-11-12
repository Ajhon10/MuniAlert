# Emergency-first reports, actionable notifications, sound alerts and image review

The user requested bell-based notifications instead of a sidebar page; a yes/no immediate-assistance question before reporting; searchable nearby agency hotlines with an online-report fallback; per-hazard administrator sound alerts; image authenticity screening with administrator confirmation; and a bottom-right highlighted guide like DEOS. They explicitly selected keeping photos on BANTAY and using a local detector when configured. DEOS was inspected read-only for its floating tour launcher; no unrelated code/data was changed.

## Citizen experience

Report a hazard and the mobile Report action first ask whether someone needs immediate assistance. No opens the normal reporting flow. Yes opens approved agency contacts, searchable by name, service, number or area. With permission, Show closest agencies sorts records that have saved coordinates by straight-line distance; contacts without coordinates remain available below. It does not infer travel time, availability, dispatch coverage or a verified local agency location. Permission failure leaves search usable. No map/geolocation permission is needed to view numbers or continue reporting.

Continue to report online remains reachable in the modal footer. Guests authenticate before sending; reporting still needs internet, safe photos and a confirmed incident location. The selected assistance flag survives normal sign-in/provider return and is highlighted privately to staff. It is a reporter request, not established fact, verified severity or a guaranteed dispatch. No calls/messages are made automatically, and no unsupported claim about call charges or free cellular access is made.

Hotlines come only from Content management. Administrators can publish agency type, service area, address and optional paired latitude/longitude with the number. The localhost directory currently has no published contacts or geocoded agencies. No fictional or unverified real phone numbers were seeded. Municipal staff must publish approved agency contacts before residents can find them. Tests use clearly synthetic numbers and never dial them.

Notifications open in a dialog from the bell or mobile Updates action. The sidebar destination/page is removed. A legacy activity URL opens the dialog on the dashboard. Selecting an item loads its authorized report, marks only that item read, and navigates to My reports or the administrator review queue with private details open. Mark all read remains optional. Missing/unauthorized reports show a toast without bypassing report ownership; role/identity changes discard stale reads. Admin notifications use a separate report-submitted type so later demotion cannot expose the old admin inbox.

## Administrator attention and coordination

Each committed submission creates an ID-scoped alert for active administrators in the same report transaction. Alert payloads contain the reference, category, assistance flag and safe message, without original descriptions, exact addresses, reporter identity or photos. Failed submissions roll back alerts, image jobs and records and remove prepared files. A visible client polls its private inbox every 15 seconds; current polling/list bounds are not a delivery SLA or workload acceptance claim.

Sound is off until an administrator enables it with a browser gesture. Settings provides per-category chime/pulse/urgent patterns, volume, test and mute. Category frequencies differ across the ten supported categories. Preferences are account-scoped on the device. Initial inbox synchronization establishes a baseline so old unread reports do not ring on login. Seen history is bounded and shared locally to reduce repeated alerts; cross-tab suppression is best effort. Browser/OS suspension, silent mode or a closed app can prevent audio. Notifications and visual report flags remain available, including when muted.

Private report details expose Contact agencies & responders before factual/image review. Staff can call an approved number or explicitly copy a minimal responder brief containing reference, category, area, request flag and selected/verified coordinates. Review before forwarding to an authorized agency. Original photos, identity, contact data and private notes are omitted. This does not automate dispatch or contact an agency by itself.

## Local image screening and required human review

New incident and resolution photos receive an authenticity record. New incident evidence requires a recorded administrator decision before factual acceptance. Existing photos are not retroactively classified or certified. Public and reporter responses omit detector scores and internal review notes; only authorized administrators see the private assessment panel. The original evidence limits, malware protection, private storage and original GPS separation remain unchanged.

IMAGE_DETECTOR_ENABLED=false is the default. Without a local detector, images say Not assessed. A configured server-side classifier receives only bounded sanitized image bytes through a numeric loopback URL; no public host, DNS-controlled name, credentials/query in URL or redirect is accepted. No image is sent to an external provider. One ID-only database job per photo runs through a separate bounded scheduler queue. Requests have 2-second connection and 8-second total/read timeouts, a 1-MB image bound and 16-KB response bound. Provider errors/bodies/credentials are excluded from ordinary failures.

The adapter expects JSON with numeric ai_probability in [0,1] and a model identifier including its revision. Scores at least0.8 flag Possible AI-generated; at most0.2 show Low AI signal; intermediate results are Inconclusive. These are advisory display thresholds, not a validated accuracy or truth guarantee. Outages/malformed responses show Unavailable, never Genuine. No score accepts/rejects a report or publishes news.

Administrators inspect images and independent corroboration, choose accepted supporting evidence / suspected AI or manipulation / inconclusive, enter a note of at least15 characters and explicitly confirm review. Assessment-version checks reject stale decisions. A manual review can cancel a queued automatic check; an in-flight late result cannot overwrite it. Suspected/inconclusive/unreviewed required incident evidence prevents factual acceptance but does not block contacting responders. Factual verification and public posting remain separate actions.

The detector adapter is implemented and tested with synthetic local responses. No classifier model is installed, configured, benchmarked or remotely validated by this change. A maintained local model/service and evaluation on representative evidence are still required. Detection accuracy can degrade across generation/manipulation methods; independent review is essential. See [NIST's technical overview](https://www.nist.gov/publications/reducing-risks-posed-synthetic-content-overview-technical-approaches-digital-content) and [its image-discriminator evaluation plan](https://www.nist.gov/publications/2025-nist-genai-pilot-evaluation-plan-image-discriminators).

## Configure a local detector

Provide a maintained classifier on the same trusted server, without forwarding inputs or storing them outside BANTAY's approved evidence policy. The HTTP contract is POST multipart field image (sanitized WebP) and optional bearer token; response example: {"ai_probability":0.73,"model":"your-evaluated-model@revision"}. This example is a protocol fixture, not an included detection model.

Set these server-only values in the ignored environment:

```dotenv
IMAGE_DETECTOR_ENABLED=false
IMAGE_DETECTOR_URL=http://127.0.0.1:8099/classify
IMAGE_DETECTOR_TOKEN=
```

Enable only after the chosen model is installed, licensed, secured, locally tested and calibrated. Keep credentials out of frontend variables/chat/source. Shared hosting must actually support the local inference service; the adapter does not establish that capability on Hostinger. Leave screening disabled and use explicit human review if it cannot host a local classifier. Never replace the loopback URL with a third-party API.

The existing single scheduler invokes hazard:work-image-screening only when enabled. Each non-overlapping batch handles up to3 jobs/30seconds, with one attempt and bounded time/memory. Ordinary report creation never waits for inference. Failed checks require human review rather than a false automated pass. There is no persistent PHP worker requirement.

## Tour, migrations and verification boundary

A fixed bottom-right Help tour button launches the existing highlighted walkthrough. It stays above the mobile dock, respects large text/safe areas and is hidden during modal tasks; Escape/close restores launcher focus. The searchable manual remains in Settings.

Additive migrations 2026_11_11_000002/000003 add assistance and private per-photo assessment/review fields. They were applied only to localhost municipal_hazards with four accounts and zero reports preserved; no roles, passwords, existing evidence or factual records were rewritten. Automated tests use isolated databases and synthetic evidence. Local checks and final evidence are recorded separately from deployment, actual classifier/agency delivery, physical Android/TalkBack and municipal/workload acceptance. No external agency was contacted, no real image was screened and no push/deployment is included.

## Final local result

Final emergency-workflow verification passed all **19 checks**, including **122 backend tests / 1131 assertions and 77 browser tests**, zero PHPStan errors and zero dependency findings. Separate isolated MySQL checks passed **33 tests / 427 assertions**. The native/web builds, single-origin package and Android synchronization are current. Both user manuals and the technical appendix are refreshed. No real classifier, agency call, external image transfer, deployment or physical-device/workload acceptance is claimed. Approved contacts and a maintained evaluated local classifier remain configuration tasks. Result-record changes receive documentation/secret checks; a future push requires a fresh exact-state gate.

The first full run exposed one ambiguous CMS Service locator after adding Service area; it now selects the exact field and retains the publication assertions. Focused checks also corrected dropdown-label matching, initial sound baselines, 200% dock/launcher geometry and web-session test context. Urgent flags/contacts appear before long report details; automatic sounds are explicitly distinguished from custom category patterns. No assertions were removed to obtain a pass.

See [redacted evidence](evidence/emergency-workflow-2026-11-11.json), [all nineteen checks](evidence/emergency-workflow-gate-final-2026-11-11.json), [phone hotlines](evidence/emergency-hotlines-phone-200-2026-11-11.png), [actionable notifications](evidence/actionable-notifications-2026-11-11.png), [private image review](evidence/private-image-review-2026-11-11.png) and [floating guide](evidence/floating-guide-phone-200-2026-11-11.png). All screenshots use synthetic test data.

### Ownership and record checks

Lenovo ownership was restored across 75 affected BANTAY scopes. All 42 sampled access-control lists were preserved; no wildcard Git ownership exception was added. Generated-document, expanded source-secret and whitespace checks cover the final result records.
