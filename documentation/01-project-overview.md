# 01 — Project overview and requirements

## Project identity

**Formal title:** Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking.\
**Application name:** MuniAlert (short for the municipal emergency and public hazard reporting system).\
**Managing office:** Basista Municipal Hall, Pangasinan; the 13 barangays confirmed by the user.\
**Proponents, institution, adviser, and academic year:** to be supplied by the project team.

## Problem statement

The supplied Filipino interview reports an existing phone-based response process that the respondent says has no major reporting problem. Incidents are recorded manually, and the proposed system can improve organized records, completeness, timeliness and weekly/monthly consolidation. Road accidents are a frequent report; rescue, police and RHU coordinate according to need. Victim privacy and simple instructions are explicit requirements. This is qualitative evidence from the supplied document, not a measured response-time failure, respondent-count claim or proof of municipal acceptance. See the [interview and screenshot review](INTERVIEW_ALIGNMENT_2026-11-08.md).

## General objective

Develop and evaluate a mobile-friendly system through which citizens submit photographed, geographically identified hazards and municipal administrators verify, assign, respond to, and close those reports.

## Specific objectives

1. Provide a citizen workflow for a hazard category, description, photo evidence, barangay, and landmark.
2. Support device GPS and manually placed or adjusted pins, requiring explicit location confirmation.
3. Preserve GPS coordinates/accuracy and the selected pin independently, and display their distance for administrator review.
4. Provide a map-centered dashboard, filtering, status history, department routing, and responder assignment.
5. Reduce duplicate dispatches by suggesting recent, same-category reports within 100 meters and recording citizen confirmations.
6. Reduce storage and bandwidth through photo compression and configurable object storage.
7. Notify reporters of status changes through an in-app inbox and configured push integrations.
8. Verify the implementation using automated tests and subsequently evaluate it with actual municipal users.

## Stakeholders and roles

| Actor | Responsibilities |
| --- | --- |
| Guest | Browse accepted hazards/published news and accepted-only community statistics; sign in to report |
| Citizen | Submit reports, confirm another active hazard, track own private history, read notifications |
| Municipal administrator | Verify evidence/location, set priority, select department/team, post response notes, resolve and close reports |
| Student maintainer | Maintain code, tests, documentation, deployment configuration, backups and supported dependencies |
| Municipal operator/adviser | Approve operational workflow, public information policy, acceptance criteria and study procedures |

Responder assignment is a team/name field in this version. A separately authenticated responder app and dispatch roster are not implemented.

## Functional requirements

| ID | Requirement | Acceptance criterion |
| --- | --- | --- |
| FR-01 | Separate web and API applications | Vue frontend and Laravel backend build/run independently |
| FR-02 | Authentication and role enforcement | Public registration creates only citizens; admin operations reject citizens; remembered sessions survive browser/app restarts and logout revokes access |
| FR-03 | Photo reporting | At least one and at most three valid photos; final images at most 1 MB and 1920 pixels per side |
| FR-04 | GPS capture | GPS latitude, longitude and accuracy stored as a complete group |
| FR-05 | Manual/adjusted location | A manually selected pin is valid without GPS; adjusted GPS remains separate |
| FR-06 | Location confirmation | Submitting without explicit confirmation fails; editing a pin resets confirmation |
| FR-07 | Location verification | Admin sees source, distance and accuracy, and stores independent verified coordinates |
| FR-08 | Map and discovery | Only fact-checked, accepted hazards appear in community map/list/search; owners and admins retain private submission access |
| FR-09 | Duplicate detection | Suggest only accepted active same-category hazards within 100 m and six hours |
| FR-10 | Confirmations | One confirmation per citizen/report; reporters cannot confirm their own report |
| FR-11 | Workflow and routing | Valid transitions only; assigned status requires a response team; defaults route by category |
| FR-12 | Traceability | Status notes, actor references, timestamps and audit entries are retained |
| FR-13 | Notifications | Status updates create a private inbox entry; configured FCM job delivers push |
| FR-14 | Android integration | Capacitor project contains camera, geolocation and push integrations |
| FR-15 | Documentation and release verification | Phase tracker and capstone attachments maintained; all required checks pass before any GitHub push |
| FR-16 | Private evidence and reviewed publication | Raw descriptions, exact locations, observations and photos are owner/admin only; public news requires a separate admin-reviewed title/summary |
| FR-17 | Incident observations | Optional incident time and affected-person estimate; explicit yes/no/unknown injury observation; no victim identity fields |
| FR-18 | Resolution evidence | Administrators may add up to three private resolution photos, separately typed from the three incident photos |
| FR-19 | Period consolidation | Admin can filter up to 366 days and export weekly/monthly report counts with PHT dates and unknown-time fallback disclosed |
| FR-20 | Consistent application identity | MuniAlert is displayed in the web interface and Android label; the full user-approved capstone title appears in the guide and current documents |
| FR-21 | Feature guidance and user manual | Guests, citizens and admins can browse role-specific instructions, search topics and run a six-step highlighted tour |
| FR-22 | Promotional deliverables | Remotion source and editable preview plus user-requested MP4 exports use clearly labeled synthetic demonstrations |
| FR-23 | Clear, configurable map | Clear/Streets/Dark styles, independently switchable boundary levels, fit/area controls and synchronized hazard-category filtering remain usable on phones |
| FR-24 | Basista service area | Basista Municipal Hall covers exactly the 13 user-selected barangays; report entry uses an allowlist and map assets carry source provenance |

## Nonfunctional requirements

| ID | Requirement | Verification approach |
| --- | --- | --- |
| NFR-01 | Responsive access | Browser checks from 320px to 1920px, including tablet and landscape, with responsive report cards and no page-wide horizontal overflow |
| NFR-02 | Privacy and authorization | Public responses omit raw descriptions, precise coordinates, injury observations, original photos, private account/contact fields and GPS; an explicit reviewed news post may credit the submitter display name; direct unauthorized media requests fail |
| NFR-03 | Data integrity | Foreign keys, unique confirmations, transactional history/notifications/audits, guarded status transitions |
| NFR-04 | Maintainability | Separate services/composables/configuration, migrations, reproducible lockfiles and documented commands |
| NFR-05 | Honest failure feedback | Unavailable server, denied GPS, image failure and unconfigured push show understandable messages |
| NFR-06 | Low operating dependency | Local development storage works; R2, map provider and Firebase are configurable |
| NFR-07 | Deployment safety | HTTPS, private environment files, least-privilege DB credentials and worker/scheduler supervision |

Performance targets, availability SLA, recovery time, retention period and usability thresholds must be approved before evaluation. No numerical performance achievement is claimed without a measured test.

## Scope and limitations

Implemented: citizen/admin workflows, photo compression, GPS/manual pins, evidence comparison, map/list dashboard, basic routing, status history, confirmations, in-app notifications, integration code, and technical documentation.

Not yet externally validated: real R2 access, FCM delivery, signed APK/AAB, actual barangay boundaries, Hostinger deployment, device field performance, backup restoration on the target host, and municipal UAT.

Outside this version: offline submission/synchronization, native iOS packaging, continuous location tracking, emergency dispatch guarantees, AI hazard classification, real-time responder tracking, verified identity enrollment, heatmaps, advanced analytics, and an administrative account-management UI. A report is not an emergency call.

## Social authentication extension ? 4 November 2026

FR-02 now includes Google and Facebook signup/sign-in through Laravel Socialite for responsive web and Android. Password accounts can explicitly connect a provider after confirming their password. New social accounts are citizens; matching email never silently links identities. Provider configuration, real consent/callback tests, physical Android and target-host acceptance remain external requirements. See [provider setup](SOCIAL_AUTH_SETUP.md).

## FR-25 — Readable controls, feedback and motion

The interface provides shared success/error/information toast feedback for user operations, animated dashboard/summary counts and account-scoped reading settings in Settings, with device-local guest preferences available without sign-in. Text sizes span 100%–200%, with bold text, high contrast, wider reading spacing, reduced motion and persistent messages. Validation uses one accessible toast with focus and field descriptions. Device reduced-motion preferences override animation, and browser zoom remains enabled. Verify small-screen reflow and keyboard behavior locally; physical Android/TalkBack and broad-age usability require separate acceptance. See [the dated implementation review](ACCESSIBILITY_FEEDBACK_2026-11-10.md).

## FR-26 — Privileged identity and continued security phases

Administrators must complete operator-approved authenticator enrollment and MFA before receiving an administrator session/token, including social and Android paths. Sensitive administration requires recent device/session verification. Versioned credentials enforce suspension and authority changes. Generic, verified-local-email recovery, one-use proofs/codes, encrypted secrets, private queued mail, enforced production CSP, optional fail-closed scanner/quarantine integration, redacted security events and maintained source checks extend the baseline. Real services, deployment policy and operational acceptance remain separate. See [the Phase 2/3 record](SECURITY_PHASE_2_2026-11-10.md).

## FR-27 — Durable device access and dashboard appearance

Closing/reopening the browser or Android app retains login on the same device. Explicit logout revokes that device; password/security changes still revoke affected credentials. MFA remains required for initial administrator login, and restored access does not grant recent verification. Administrators can preview, publish and remove an approved bounded dashboard background, with readable cards and a high-contrast override. Reading preferences in Settings and initial/navigation entry motion respect reading/motion preferences. Phase 3 adds aggregate monitoring and a guarded synthetic MySQL backup rehearsal. See [the continuation](SECURITY_PHASE_3_DASHBOARD_2026-11-10.md).

## FR-28 — Private submissions, factual review, news posts and hazard achievements

Other members cannot discover unaccepted reports through list/map/stat/search/nearby endpoints; owners retain My reports and admins the private queue. Admin acceptance records factual method, attestation, private note, actor/time and verified location. A separate privacy-reviewed publication action is required for news/share pages. Members can share reviewed posts through native/copy/Facebook/X/WhatsApp controls. Each private profile tracks three creative milestones for each of ten hazard categories using only that account's currently accepted reports. See [the implementation](COMMUNITY_REVIEW_ACHIEVEMENTS_2026-11-10.md).

## FR-29 — Actionable notifications and emergency-first reporting

Notifications use the bell/mobile Updates dialog with owned single-item read and authorized report navigation, replacing the sidebar page. Reporting asks about immediate assistance, provides searchable approved hotlines with optional coordinate-based nearest sorting and retains online submission without a call. Committed submissions alert active administrators with configurable per-category sounds while the app is active. Private assistance flags, manual agency contacts/minimal responder briefs and a floating highlighted tour improve navigation without automating emergency dispatch. Approved contacts, actual delivery/audio/device/workload acceptance remain separate.

## FR-30 — Private local image screening and administrator confirmation

Photos remain on BANTAY; an optional loopback-only local classifier produces advisory estimates through bounded ID-only jobs. Unavailable/unconfigured checks never claim authenticity. New incident images need explicit recorded human review and independent corroboration before factual acceptance; stale results cannot overwrite manual decisions. Public/reporting-user responses exclude internal assessment metadata. A maintained configured classifier, measured accuracy and hosting capability remain pending. See [implementation/readiness](EMERGENCY_NOTIFICATIONS_IMAGE_REVIEW_2026-11-11.md).
