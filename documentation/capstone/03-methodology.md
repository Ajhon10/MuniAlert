# CHAPTER 3 — METHODOLOGY, SYSTEM ANALYSIS AND DESIGN

## 3.1 Development and evaluation design

The technical work follows iterative development: inspect requirements/source evidence, implement a coherent workflow, verify expected and negative behavior, correct defects and update documentation. The technical component is developmental. A later descriptive evaluation is proposed for usability and operating suitability; it has not been completed in the available evidence.

Source, migrations, reproducible tests and sanitized screenshots demonstrate selected behavior. They cannot supply participant opinions, adoption rates or improved rescue outcomes. Human evaluation requires institutional and municipal approval before recruitment or collection.

## 3.2 Requirements and change control

The requirements baseline includes the project overview, task records, documented interview review and explicit owner decisions about stack, privacy, reporting and hosting. Screenshots were design references, not proof of working functions. The existing choice of separate applications and Android plus responsive web is preserved.

Requirements map to observable outcomes and negative cases. Optional government-ID verification includes private-image authorization, denial of self-approval, deletion, expiry and a server-derived profile badge. Separate tests confirm that skipping ID does not prevent reporting and that deployment seeders support first-administrator setup. Changes are tested using synthetic data before an additive application migration is applied. The technical appendix contains the detailed traceability matrix.

## 3.3 Locale and proposed participants

The intended locale is Basista, Pangasinan. Proposed groups include adult community users, designated municipal staff and technical reviewers. Final numbers, recruitment, inclusion/exclusion criteria and representation must be approved and entered by the proponents. This draft does not assert a population size or calculate a sample from an unknown population.

For formative task observation, purposive recruitment by role and relevant device/accessibility experience may be suitable if approved. Such results should describe the selected participants, not all residents statistically. If a survey or inferential analysis is required, establish a defensible sampling plan before using formulas or significance tests.

**Complete before evaluation:** approval reference [edit]; participant groups/target numbers [edit]; recruitment procedure [edit]; data-collection dates [edit]; consent/data custodian [edit]; research retention/deletion period [edit].

## 3.4 Ethics and data handling

Participation must be voluntary and separate from eligibility for emergency services. Explain tasks, observations recorded, withdrawal and recipients of study data. Do not promise anonymity if names, voices or identifying video are recorded.

Use synthetic incidents, locations, photographs and ID specimens in usability sessions. Real ID images are operational account data, not research attachments. Do not include them in questionnaires, appendices, screenshots or repositories. Collect approved variables only, use study codes where possible and designate access/retention before collection.

The operator separately approves its ID policy, public privacy contact, authorized reviewers, assisted alternatives and operational/backup retention. Current code blocks ID collection without an operator public privacy contact. Private SMTP credentials are not automatically published as that contact.

## 3.5 System architecture

Vue 3/TypeScript serves responsive web and Capacitor Android. Laravel 12 exposes JSON/multipart routes using Sanctum browser sessions and native bearer credentials. MySQL stores canonical records, verification metadata, notifications and queue work. MapLibre renders geographic information.

The Hostinger web architecture places compiled frontend/API on one HTTPS origin. Application source, environment, vendor and storage remain outside the public root. Bounded scheduled mail and optional push avoid requiring a persistent worker daemon. Actual hosting capabilities must be checked.

| Layer | Responsibility | Boundary |
| --- | --- | --- |
| Vue / Capacitor | Forms, local image preparation, location confirmation | Client input remains untrusted |
| Middleware / requests | Authentication, current account status, role checks, validation | Server decides access independently of UI |
| Services / transactions | Incident rules, identity decisions, delivery orchestration | Related durable changes commit consistently |
| MySQL | Records, constraints, histories and queue metadata | Test schemas are isolated from application data |
| Private storage | Incident evidence and separate encrypted IDs | Originals are not public assets |
| SMTP / optional FCM/R2 | Configured delivery and object storage | Provider acceptance remains separate |

## 3.6 Account and authority design

Community reporting/confirmation requires verified local email and current policy acceptance. Administrators additionally need authenticator MFA. Government ID is voluntary for both roles; missing, pending, rejected, expired or withdrawn ID review does not prevent reporting or administration. A missing public privacy contact prevents ID collection, while ordinary reporting remains available after email/policy setup.

Volunteers submit one Philippine photo ID with separate consent. It is resized locally, validated, re-encoded and encrypted privately. No ID number, selfie, biometric template or government-registry result is collected. Independent approval awards a blue verified identity badge beside the profile name. Withdrawal/name changes remove the badge. Reviewers cannot approve their own IDs, and a badge never certifies report truth.

Deployment invokes InitialAccountSeeder through the normal seeder. The starter admin uses private environment credentials, enrolls MFA, verifies email and accepts policies; no ID is required to begin administration. In Settings, an admin can create another admin, authorize a bounded MFA setup window and delete another account after confirmation. Current/last active account deletion is refused. Removed accounts lose access while soft deletion preserves municipal action history and an actor/target audit. Repeated seeding preserves existing accounts and does not resurrect deleted starter identities. Google/Facebook authentication has been removed; existing email/password and recovery remain.

## 3.7 Reporting and response design

The journey first distinguishes immediate assistance and offers approved contacts. Continuing online does not dispatch a team. Eligible users select a category, prepare photos and confirm location. Nearby accepted hazards can be reviewed before a separate submission.

The server validates the request and photo bounds and stores a reference, originals and initial history. Administrators distinguish location uncertainty, image concerns and factual corroboration. Accepted records may appear in public discovery; news requires another deliberate reviewed publication action. Office assignment and response notes preserve history.

Main states are submitted, under verification, verified, assigned, responding, resolved, closed and rejected. Closed/rejected are terminal in the normal workflow. The technical appendix specifies transitions. Original GPS is never overwritten by an administrator's verified coordinates.

## 3.8 Data and privacy design

Principal records include users, reports, categories, departments, photos, history, confirmations, notifications and audit/security events. Policy acceptance is unique by user/version. Identity verification stores a UUID, unique account reference, name snapshot, document type, consent version/time, status, expiry and reviewer/method/time. Only pending review retains an image path.

Approval/rejection deletes the uploaded ID; withdrawal and account-name changes discard pending evidence and remove the optional badge; reporting or administration is not revoked by ID withdrawal. Pending images expire after seven days, with bounded scheduled cleanup for expired and old orphaned encrypted files. Backups need independent encryption and rotation; immediate erasure of protected historical backups is not promised.

Public responses exclude IDs, contact email, original GPS and private evidence metadata. News contains reviewed fields and optionally approved display-name credit. Calling a route directly cannot substitute for its server-side permission check.

## 3.9 Technical verification procedure

1. Trace requirements through interface, validation, permission, transaction and data projection.
2. Implement success, rejection, expiry, unavailable-service and retry behavior.
3. Run isolated Laravel tests and relevant MySQL migration/workflow checks.
4. Exercise real UI/API paths, keyboard use, protected navigation and phone reflow.
5. Inspect screenshots and correct observed defects.
6. Build web/native assets and run static, formatting, documentation and secret checks.
7. Preserve failures and retests; do not call unavailable checks passes.
8. Before publication, run the complete current gate on the exact state and obtain any still-required deployment authorization.

## 3.10 Proposed municipal evaluation

The following instrument is a plan, not completed research. Obtain adviser approval, pilot wording and align it with the research questions before administration.

| Task | Action | Proposed observation |
| --- | --- | --- |
| T1 | Find emergency contact before registration | Completion and emergency/online distinction |
| T2 | Read policies, verify email, understand ID consent | Comprehension, help needed and privacy concerns |
| T3 | Submit synthetic report with manual pin | Completion, confirmation errors and time |
| T4 | Recover from denied GPS/network failure | Recovery and preservation of unsent information |
| T5 | Find own status/history | Interpretation and navigation difficulties |
| T6 | Review report as authorized staff | Correct evidence access and response recording |
| T7 | Review another account's ID | Self-approval prohibition and deletion understanding |
| T8 | Filter/export a period summary | Date basis and correct interpretation of counts |
| T9 | Use phone/enlarged-text controls | Readability, overflow, focus and assistance |

Record approved participant codes, role, device/version, task outcome, assistance, error and elapsed time. Define success and timing rules before sessions. Do not test using real urgent incidents.

## 3.11 Proposed analysis

Report outcomes as counts with denominators. Completion percentage is successful attempts divided by attempted tasks multiplied by 100. Report missing/abandoned tasks separately. For timing, define start/stop and assisted attempts; median/range may describe a small formative sample.

If an approved rating instrument is used, preserve its scale, show response distributions and justify summaries. Do not invent means, respondent totals, reliability coefficients or significance levels. Qualitative coding must distinguish participants' statements, observed behavior and proponent interpretation.

Performance evaluation needs its own data volume, concurrency, request mix, network, hardware and measurement window. Uptime, delivery delay, latency and recovery should be reported only after measurement. No capacity is inferred from local browser success or daily visit counts.
