# Private submissions, fact-checking, community posts and hazard achievements

This continuation implements the user's request to hide other members' unreviewed reports, record administrator factual review, publish separate verified news and offer achievements for each hazard category. Existing stack, persistent login, MFA, reading controls, rolling counters and dashboard backgrounds are preserved. Source implementation, local verification, deployment and municipal acceptance remain separate.

## Visibility and review

`HazardReport::communityVisible` is the shared acceptance boundary: a nondeleted report needs `fact_checked_at` and a status of Verified, Assigned, Responding, Resolved or Closed. Public list/map, statistics and nearby suggestions use that boundary. The public pending count is zero; full queue statistics are behind the administrator route. Public searches use safe category/reference/barangay data and published titles, so unpublished legacy draft text cannot be inferred through search. Members keep all their own undeleted submissions in My reports. Administrators retain the private review queue. Raw descriptions, photos, precise coordinates, original GPS, reporter identity and response history remain owner/admin protected.

Browser snapshots are cleared on logout, session expiry and identity changes before replacement requests resolve. This prevents previously loaded administrator/owner data from lingering in the community view after authority changes. A browser check blocks replacement public data and verifies the private snapshot still disappears.

The new Fact-check incident action requires an administrator with recent verification, an explicit confirmation that the incident happened, a method, a 15–1,000-character internal note and independent verified coordinates. Methods are on-site inspection, municipal-responder confirmation, official incident record or independently cross-checked evidence. The action records time/actor/method/notes, accepts a new report, preserves original evidence, creates a reporter-safe history/inbox update and records audit/security events in one transaction. Internal fact-check notes/methods are administrator-only; the reporter receives the acceptance outcome. This is a human attestation process, not automatic truth, image-authenticity or fraud detection. Municipal evidence standards and actual field review remain operational responsibilities.

Ordinary response PATCH cannot bypass factual review or publish text. Status changes retain workflow/assignment checks. Rejection removes public eligibility and clears publication metadata. Verification and publication remain separate explicit actions.

## News and sharing

Create community news post appears in private admin report detail after acceptance. It provides public title/summary, a live public preview, a privacy-review confirmation and explicit Publish news post action. Existing posts can be edited or unpublished, including accepted closed reports. The publication API locks/rechecks the report, requires the factual acceptance boundary, records actor/time and never copies original text or evidence photos automatically.

News, comments and reactions require accepted status, factual review, publication timestamp and nonempty reviewed title/summary. Public projections remain redacted even when an administrator reads the feed. Unpublishing removes the feed/share page; rejection removes alerts, news and eligibility for achievements. Previous copies/caches on external platforms cannot be recalled by this application.

Members can use native sharing/copy link, Facebook, X or WhatsApp. Links point to `/news/{report}`: a public, escaped, server-rendered page with reviewed text, Open Graph metadata and the existing MuniAlert emblem exported to PNG. It works without JavaScript and refuses unpublished, unaccepted, rejected or deleted records. The page links back to the newsfeed. Original evidence is never a social thumbnail. Real platform crawling/sharing requires a reachable deployment and was not sent/performed by the agent.

## Hazard achievement collection

Every account's private profile includes ten category tracks with three milestones at 1, 5 and 15 accepted own reports. Names include Firewatch Scout/Flame Sentinel/Firewatch Beacon, Rainwatch Scout/Waterway Guardian/Floodwatch Champion, Circuit Scout/Powerwatch Guardian/Grid Sentinel, Light Finder/Nightwatch Guide/Beacon Keeper, and equivalent road/accident/tree/vegetation/waste/general tracks. Category icons, medallions, level states and accessible progress bars distinguish progress without color alone. Locked tracks disclose full milestones on demand; active tracks show earned milestones.

One report contributes once in its category. Unreviewed, legacy-status-only, rejected, soft-deleted and other users' reports and confirmation taps do not contribute. Counts are derived from current accepted records, so status transitions and repeated publication do not multiply credit. Withdrawal/rejection can remove eligibility. Existing overall milestone/reward definitions remain; no cash/gift is awarded automatically. Badge progress is private to the account and does not create a public leaderboard. Reporting safety and municipal anti-abuse/reward policy still matter.

[View the synthetic achievement collection](evidence/hazard-achievements-2026-11-10.png). The illustrated account has five accepted fire reports and two earned fire milestones; other category tracks stay locked.

## Migration and evidence

Additive migration `2026_11_10_000005_add_fact_checks_and_news_publication.php` adds nullable review/publication timestamps, actor FKs and private review fields. It does not certify or publish old records. Legacy accepted statuses/public text stay private until an administrator records factual review and explicitly publishes a post. Do not backfill acceptance from status alone. Apply migrations without resets on other installations. Local activation preserves application account/report counts and does not approve an account or publish a real incident.

Local results are recorded in [the evidence](evidence/community-review-achievements-2026-11-10.json). Backend checks cover list/stat/search/nearby/direct access, private review notes, explicit publication and escaped public HTML, withdrawal/rejection and category progress. Browser checks exercise the real admin/member flow, share-page response, own-versus-other achievements, 200% phone reflow and cached-private-data removal. Existing workflow/inbox/evidence/CSRF/MFA/push checks remain in the gate. Real factual correctness, municipal UAT, hosting/social-platform/device/service acceptance are not established by synthetic tests. No push or deployment is part of this work.

The [final local release gate](evidence/community-review-gate-final-2026-11-10.json) passed all **19 checks**, including **108 backend tests / 928 assertions and 68 browser tests**, PHPStan with zero errors, production builds, Android asset synchronization and zero dependency findings. The focused isolated MySQL suite passed **31 tests / 345 assertions**. The nine focused browser checks include private-cache/session regressions. Both user-manual PDFs and the technical appendix have been regenerated. Final evidence/document edits are followed by documentation consistency and source-secret checks; these local results do not authorize a push or deployment.

Lenovo ownership was restored across 62 scoped project paths, with all 16 sampled access-control lists preserved. No wildcard Git ownership exception was added.

References: [Open Graph protocol](https://ogp.me/) and [WhatsApp click-to-chat](https://faq.whatsapp.com/5913398998672934). No tool was used to send an external social message.
