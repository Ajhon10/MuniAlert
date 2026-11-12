# Community navigation and detailed verified news

The user's follow-up removes the redundant community Verified hazards menu and expands reviewed news with incident details and submitter credit. This supersedes the preceding continuation's blanket prohibition on public submitter names only for explicit reviewed publication. Original account/contact fields, evidence, precise addresses/GPS and internal review notes remain private.

## Distinct destinations

| Destination | Purpose |
| --- | --- |
| Dashboard | Accepted hazard overview and recent alerts |
| Hazard map | Accepted hazards by approximate area and category |
| Verified news | Explicitly published updates with reviewed incident details and share links |
| My reports | Every own undeleted submission across all eight statuses, including pending/rejected records and private response history |

Community `view=reports` links normalize to the hazard map, including navigation/social-return intents. Dashboard links lead to the map. Administrators retain Review reports and the private queue. My reports already had the correct owner-scoped server query; focused tests now explicitly cover every status, status filters, foreign-user query attempts and rejection notes in the browser. User-deleted submissions remain excluded.

## Reviewed public detail

Feed and share pages show the reviewed narrative, submitter credit, category, barangay, priority, current status, reported/fact-checked dates, incident time when known, responsible office and reference number. Unknown incident time is labeled Not confirmed. Narrative guidance asks for what happened, the response and useful safety advice. No private original text or photos are copied automatically.

The report form and privacy guide tell members that published news may credit their display name and include reviewed incident details. They retain the distinction between public attribution and protected account/contact information.

New composer posts preview the actual submitter display name by default. An administrator can withhold it for safety before confirming public review. Existing anonymous posts retain that choice. Only a published post exposes the approved `public_reporter_name`/`public_occurred_at` snapshots. Server publication derives them from the actual report/account, ignoring caller-supplied name/time fields; public JSON never returns reporter email, account ID or the private reporter object. Account name/time edits do not silently alter an existing public snapshot; explicit republishing refreshes it. Unpublish/reject clears both fields. Vue/Blade escape user-controlled values.

[View the synthetic verified-news example](evidence/verified-news-details-2026-11-10.png). This test fixture illustrates approved submitter credit and incident details; it is not a real incident or municipal acceptance record.

Migration `2026_11_10_000006_add_reviewed_news_attribution.php` adds nullable snapshot columns without backfilling old posts. It was applied to the configured localhost application with four accounts/zero reports preserved and no identity/role/password changes. No real incident or submitter was published. Other installations need this additive migration before running the updated source.

## Local verification and remaining acceptance

The focused backend suite passed seven tests / 169 assertions; the focused isolated MySQL regression passed 33 tests / 411 assertions. Four browser cases cover the actual admin/member flow, preview/feed/share details, rejected and accepted personal submissions, legacy navigation, 200% phone reflow, category achievements and stale private-cache removal. A full release-gate result and refreshed manual exports are recorded in [the evidence](evidence/community-navigation-news-details-2026-11-10.json).

The [final local release gate](evidence/community-navigation-news-gate-final-2026-11-10.json) passed **all 19 checks: 110 backend tests / 994 assertions and 69 browser tests**, zero PHPStan errors and zero dependency findings. It includes the corrected report-form/privacy-guide notice, native/web builds, single-origin packaging and Android asset synchronization. Citizen/admin manuals and the technical appendix were regenerated. Final result-recording edits are followed by documentation consistency and source-secret checks; a future push still requires an exact-state gate and authorization.

Lenovo ownership was restored across 49 scoped paths with all 19 sampled access-control lists preserved. No wildcard Git ownership exception was added.

Real factual accuracy, municipal attribution/retention policy, reachable-host/social-platform crawling, physical Android/TalkBack and intended-workload acceptance remain separate. No push, deployment or external social message was performed.
