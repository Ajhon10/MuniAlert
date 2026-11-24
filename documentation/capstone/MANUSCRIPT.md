# Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking

**Application:** MuniAlert (BANTAY project)\
**Institution:** [School / college — edit later]\
**College / department:** [Edit later]\
**Degree / course:** [Edit later]\
**Proponents:** [Names — edit later]\
**Adviser:** [Name and academic designation — edit later]\
**Study locale:** Basista, Pangasinan, Philippines\
**Proposed operator:** BASISTA MUNICIPAL HALL\
**Academic year / submission date:** [Edit later]

**Document status:** Editable developmental capstone draft, prepared 24 November 2026. Chapters 1–5 describe the implemented prototype and available engineering evidence. Municipal acceptance, participant evaluation, a deployed service and a signed Android release have not been established. Replace academic fields and follow the institution's approved template before submission. Approval sheets, signatures and research results are not fabricated.

## Abstract

This capstone presents MuniAlert, a municipal emergency and public hazard reporting application intended to support organized incident records and response tracking in Basista, Pangasinan. The application accepts categorized descriptions, compressed photographs and a confirmed location while distinguishing original device GPS from a citizen-selected pin and administrator-verified coordinates. Community members can track submissions, and authorized municipal administrators can review evidence, record factual checks, assign responsible offices and maintain response histories. Public discovery and news use separately reviewed information rather than exposing original evidence. The implementation comprises a Vue 3 and TypeScript client, Capacitor Android project, Laravel 12 and Sanctum API, MySQL database and MapLibre mapping interface. Account safeguards include SMTP email verification, versioned policy acceptance and optional manual review of one Philippine government ID for a verified profile badge, available to community and administrator accounts. Administrator two-factor authentication remains independently required. The available interview review supports organized record-keeping without establishing that the existing telephone-reporting process has failed. Development and automated testing provide technical evidence, while an evaluation protocol is proposed for later approval. No participant scores, response-time improvement, field adoption or production availability are claimed. Subsequent evaluation must determine whether the implementation is usable and operationally appropriate for its intended users.

**Keywords:** municipal reporting, hazard records, incident tracking, volunteered geographic information, location evidence, privacy, Basista


---

# CHAPTER 1 — THE PROBLEM AND ITS BACKGROUND

## 1.1 Introduction

Emergency information is useful when it reaches an appropriate person with enough context for a considered response. A report may describe what happened, where it occurred, when it was observed and whether immediate assistance is requested. A municipality also needs records that can be inspected afterward, including the actions taken and the information subsequently verified. Software can organize these records, but it cannot replace the judgment, staffing and authority of emergency personnel.

MuniAlert is developed as a supplementary reporting and record-management channel for Basista, Pangasinan. It provides community forms and municipal administration through responsive web screens and an Android-capable client. The formal title retains the approved emphasis on photographs and location-based incident tracking. BANTAY identifies the development workspace; MuniAlert is the application name presented to users.

The Philippine Disaster Risk Reduction and Management Act establishes a framework that includes local institutions and community participation. That context supports studying tools for municipal coordination, but it does not endorse this application or establish that it meets an agency's operating requirements (Republic Act No. 10121, 2010). Municipal approval and field evaluation are therefore distinct from implementation.

## 1.2 Background and local context

The project's documented review of a supplied Filipino interview contains fourteen question-and-answer items. Its most consequential finding is that the respondent did **not** identify a major failure in the existing telephone-reporting process. The respondent described manual record-keeping and interest in organized, complete and timely information, including consolidation by period. These statements support an opportunity to improve record organization and traceability. They do not prove delayed rescue, excessive casualties or a particular error rate.

The review identifies photographs and location descriptions as useful context while emphasizing victim and family privacy. MuniAlert consequently keeps original descriptions, exact locations and evidence photographs private to the submitting user and authorized administrators. Public information uses reviewed summaries. The operator must decide who may access municipal records and how that access is supervised.

An application submission remains a report, not an independently established fact. A citizen may misunderstand an event, select an imprecise location or lack a strong GPS reading without acting dishonestly. The design therefore separates account identity checks, location review, image review and factual incident verification. None is a guarantee that a report is true.

The source of this local context is the project's interview-alignment review dated 8 November 2026. The original respondent details, consent, interview date and institutional authorization must be confirmed before material is quoted or submitted as research evidence. No additional interviews or surveys were conducted while preparing this manuscript.

## 1.3 Statement of the problem

The development problem is how to provide a supplementary application that organizes submitted evidence and response history while preserving privacy and making uncertainty visible. The following questions guide the project:

1. How can community members submit categorized reports with suitable photographs and an explicitly confirmed location?
2. How can original GPS, the selected hazard location and administrator-verified coordinates remain separate evidence?
3. How can authorized administrators review a submission and track response without exposing private originals publicly?
4. How can administrators and community accounts complete proportionate account-verification and policy-consent requirements?
5. What behavior is supported by reproducible tests, and what usability, hosting and field behavior still requires evaluation?

These questions do not assume that a new channel will improve rescue times. Such a conclusion requires a separate approved study with a defensible baseline and measurements.

## 1.4 Objectives

### 1.4.1 General objective

To develop and technically verify a municipal emergency and public hazard reporting prototype with private photographic evidence, explicit location confirmation and traceable response records for the proposed use of Basista Municipal Hall.

### 1.4.2 Specific objectives

1. Provide accessible forms for report category, description, barangay, landmark, photographs and confirmed location.
2. Preserve original GPS and accuracy, user-selected coordinates and administrator-verified coordinates independently.
3. Implement own-report tracking, municipal review, history, office assignment, response updates and reviewed public news.
4. Require SMTP email verification and current policy acceptance for reporting and administration; offer optional one-ID review for a blue verified profile badge, retain administrator MFA and prohibit self-approval.
5. Protect evidence and IDs through server-side permissions, private storage, ID-image encryption and documented deletion/expiry.
6. Provide bounded period/category statistics and municipal exports with an explicit submission/incident date basis.
7. Prepare a single-origin Hostinger release and editable documentation, with external prerequisites clearly stated.
8. Define technical verification and an adviser-approved protocol for later municipal usability and operational evaluation.

## 1.5 Significance of the project

For community members, the intended value is a guided way to submit information and inspect recorded updates. Public news and emergency contacts remain available before verification. The application must not encourage unsafe photography or waiting for an online acknowledgment during immediate danger.

For municipal administrators, the intended value is organized access to submissions, response histories and consistent record filters. These functions may support existing communication channels. Their actual usefulness depends on staffing, procedures and adoption, which a software build cannot establish.

For the proposed operator, the project provides a basis for deciding access roles, privacy responsibilities, retention, hosting and maintenance ownership. For the proponents and institution, it provides an implementation assessable against explicit requirements. Future researchers may examine location confidence, accessibility and review workload without assuming that identity verification is a complete fraud detector.

## 1.6 Scope and delimitations

The geographic reference scope is Basista and the thirteen barangay names maintained in the approved configuration. The application includes category selection, up to three incident photographs, GPS/manual pin selection, location confirmation, nearby duplicate suggestions, own-report history, administrative review, response states, reviewed news, administrative settings, period statistics and office-document exports.

The stack remains separate Vue 3/TypeScript and Laravel applications. Capacitor provides the Android project. MySQL is the deployment database; SQLite is also used in isolated tests. MapLibre renders maps. Private local evidence storage is supported, with configurable private Cloudflare R2 and optional Firebase push delivery.

The capstone does not include iOS, automatic dispatch, an external agency portal, continuous citizen tracking, guaranteed offline submission, government registry access or certified document-authenticity detection. The optional local incident-image screening integration has not established a calibrated detector. Human incident review remains necessary.

Hostinger deployment, a selected domain, live mail, external services, physical Android acceptance, backup restoration and municipal UAT remain separate activities. The owner has indicated a one-year hosting term but has not confirmed the tier or domain. This manuscript does not describe an already operating municipal service.

## 1.7 Assumptions and limitations

The operator must designate reviewers, maintain approved contacts and provide secure hosting. Internet is required for online submission. GPS accuracy, photo clarity and network availability vary. Residents may submit reports without government IDs once email and policies are complete; the ID badge is voluntary. Assisted reporting for connectivity or accessibility needs remains an operating decision.

Manual ID review cannot establish document ownership in every case or prevent all false reports. Email verification demonstrates mailbox access at the time of verification. Public map coordinates are approximate. Automated tests use synthetic accounts/incidents and are not a sample of Basista's population or events. Conclusions remain limited to demonstrated behavior until approved evaluation is added.

## 1.8 Definition of terms

| Term | Operational meaning |
| --- | --- |
| Report | A submitted account of an observed hazard; initially unverified |
| Factual verification | Recorded corroboration of an incident, separate from account verification |
| Original GPS | A device reading and its reported accuracy retained as evidence |
| Selected location | The map pin explicitly confirmed by the user |
| Verified location | Coordinates recorded separately after municipal review |
| Government-ID review | Manual account review of one Philippine photo ID, not a registry lookup |
| Public news | A separately reviewed and deliberately published summary |
| Prototype | Implemented software still requiring operational acceptance |
| SMTP | The configured mail transport for eligible account/report email |
| UAT | Acceptance testing by authorized representative users under an approved protocol |


---

# CHAPTER 2 — REVIEW OF RELATED LITERATURE AND SYSTEMS

## 2.1 Review approach

This chapter uses a focused review of identifiable scholarly and primary institutional sources about citizen-contributed locations, mobile disaster information, privacy and evaluation. It is not represented as a systematic literature review conducted by the proponents. Expand its search record and source coverage to meet adviser requirements. Publisher metadata and available abstracts were checked; when full text was unavailable, observations are limited to the abstract.

A result from another country or dataset cannot be treated as a measurement of Basista. Vendor documentation describes capabilities or implementation contracts; it is not independent evidence of local outcomes.

## 2.2 Citizen-contributed geographic information

Goodchild (2007) discusses volunteered geographic information, participation, accuracy, privacy and relationships with conventional sources. This supports treating community observations as potentially useful inputs requiring quality/context review, rather than automatically authoritative records. The publisher abstract was consulted; no independent validation of its underlying data is claimed.

MuniAlert applies that concern through reviewable submissions. It preserves original location evidence and subsequent decisions while keeping reporter identity private. The account-access policy is the project's decision, not a published finding that government-ID collection guarantees geographic accuracy.

Haklay (2010) compares OpenStreetMap with Ordnance Survey datasets in England. The comparison illustrates geographic quality as an empirical question tied to a dataset and reference. It does not establish an accuracy threshold for hazard reports in Basista.

MuniAlert consequently retains the phone reading when a citizen adjusts a pin. A discrepancy or poor accuracy prompts review rather than automatic rejection. Its municipal interpretation needs local evaluation and should not equate uncertainty with dishonesty.

## 2.3 Mobile crowdsensing and disaster information

Cicek and Kantarci (2023) review smartphone crowdsensing in disaster management, including data integration and real-world testing challenges. The work supports the relevance of mobile information gathering while cautioning against treating conceptual or laboratory results as field effectiveness.

MuniAlert is narrower than a general crowdsensing platform. It requests a location for a specific reporting action rather than continuously gathering device data. Users intentionally supply photographs/descriptions. Physical-device and municipal trials remain necessary: browser emulation cannot establish camera, Keystore or real-network behavior.

UNDRR's multi-hazard early-warning material describes connected institutional and community functions rather than only an interface (UNDRR, 2026). MuniAlert supports reporting and records within a possible local workflow. It does not claim complete forecasting/early-warning capability, and a notification does not establish dispatch.

## 2.4 Philippine institutional and privacy context

Republic Act No. 10121 (2010) provides a national framework including local participation and responsibilities. Its relevance here is institutional alignment: the operator must identify responsible offices, coordination and acceptance. Application development does not create a municipal mandate.

The Data Privacy Act and NPC guidance make identifiable-data processing a design concern. The NPC's right-to-be-informed guidance identifies disclosures concerning data, purpose, recipients, controller/contact, retention and rights before processing (National Privacy Commission, n.d.). MuniAlert provides a versioned notice and separate ID consent; the operator must approve wording, public contact and retention before actual collection.

These sources inform controls, not a legal certification. One-ID collection adds privacy and accessibility burdens. Its suitability, reviewer supervision and alternatives require institutional consideration alongside the intended reduction in account abuse.

## 2.5 Software quality as an evaluation frame

ISO/IEC 25010:2023 defines a product-quality model with nine characteristics for specifying and evaluating ICT quality (ISO/IEC, 2023). This project uses it as a reference for organizing questions, not as a certification claim. Consult the complete standard and approved instruments before claiming conformance.

Observable areas include task completion, understandable interactions, permissions, failure recovery, compatibility and maintainability. Automated tests address selected functional/security behavior; users, real devices and hosting measurements address other areas. A favorable rating does not replace a failed access-control check, and passing tests are not satisfaction scores.

## 2.6 Related system: Ushahidi

Ushahidi's official overview describes collecting/managing reports, workflow and map/other views. The consulted page concerns its version-3 design, not independent verification of every currently available service configuration (Ushahidi, n.d.).

This comparison supports examining contribution, triage and visualization together. MuniAlert specializes a local workflow with private originals and separate location evidence. It does not claim these features are absent from every Ushahidi deployment.

| Criterion | Ushahidi documentation consulted | MuniAlert |
| --- | --- | --- |
| Reporting | Describes collection/management and workflow | Categorized reports, private history and municipal review |
| Geographic presentation | Describes maps and other views | MapLibre with barangay reference scope and filters |
| Publication | Describes tasks before publication | Factual acceptance distinct from reviewed news publication |
| Access | Describes role-based permissions | Community/admin roles plus verified-account gate |
| Study relevance | General crowdsourcing platform | Prototype aligned to the project's Basista requirements |

## 2.7 Synthesis and project contribution

The literature establishes questions about participation, quality, privacy and evaluation, not the local success of this prototype. The project's contribution is a traceable implementation within a specific municipal workflow: distinct location claims, explicit review, limited public disclosure and stated test evidence.

Identity review, evidence review, corroboration and publication answer different questions. One accepted ID is an optional identity signal, not an account-access prerequisite. A photograph can remain misleading, an event can need corroboration, and a verified private record can still contain information unsuitable for publication. These distinctions guide the methodology and interpretation of results.

The remaining research question is local suitability: whether users can complete tasks safely, understand verification/privacy requirements and fit the workflow into municipal practice. Chapter 3 proposes an evaluation without inventing participants or outcomes.


---

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


---

# CHAPTER 4 — SYSTEM IMPLEMENTATION, RESULTS AND DISCUSSION

## 4.1 Status and interpretation

This chapter reports implemented functions and evidence available on 24 November 2026. The application is a locally verified prototype, not an accepted municipal deployment. These technical observations do not establish satisfaction, emergency-response improvement or reduced false-report rates.

Before academic submission, record the precise release/revision and current verification report. Preserve the scope of historical counts and final retests. Changes after a test invalidate an exact-state publication claim until the relevant gate is rerun.

## 4.2 Community reporting

The interface provides emergency-first navigation, category-based forms, compressed photos and explicit location confirmation. Denied GPS retains a manual-pin fallback. Location edits invalidate earlier confirmation. Nearby suggestions help users inspect similar hazards without automatically declaring reports equivalent.

Successful submission produces a reference and private timeline. Users can inspect all own undeleted submissions, including rejected ones. Other members cannot retrieve private originals. Discovery uses accepted information, while news requires reviewed publication. Storing a submission and publishing information are separate actions.

## 4.3 Municipal administration

Authorized administrators inspect evidence, corroborate incidents, record verified coordinates, assign offices/responders and maintain history. Statistics aggregate filtered records with Philippine date boundaries and explicit date basis. PDF, DOCX and XLSX exports preserve applied filters and use actual document formats. Counts describe records, not necessarily unique incidents or proven causes.

Account-scoped inbox notifications are stored independently of Firebase. Optional device delivery needs configuration/scheduling. Hotlines and minimal responder briefs support human coordination; the app does not place automatic calls, guarantee monitoring or establish dispatch.

## 4.4 Verification for both roles

Email and current policies remain necessary for both roles, with authenticator MFA for administrators. One-ID verification is now optional: a resident can report before submitting an ID, while a manually approved submission earns a blue verified identity badge on the profile. Awaiting, failed or withdrawn ID review does not block reporting or administration. Badge withdrawal and account-name changes remove that signal.

Independent reviewers cannot approve themselves. Required email/policy state is still checked server-side with existing sessions. Password-based registration/sign-in replaces Google/Facebook authentication; retired provider routes cannot issue credentials.

Initial accounts are generated by deployment seeders using a private starter-admin password. Settings provides admin creation, MFA setup authorization and confirmed removal; no self-deletion or last-active-admin removal is allowed. Deleted admin access is revoked while municipal-history references and action audits remain. Five known local demo accounts were removed in two guarded stages: three current sample logins and two older demo residents. Two other existing accounts and report counts were preserved. No real ID was approved for this work.

## 4.5 Technical verification evidence

<!-- verification-summary:start -->
The final revision-3 implementation passed all 21 preparation checks, including 133 backend tests with 1,273 assertions and all 93 Chromium browser tests. A separate isolated MySQL run passed 25 tests with 247 assertions. Builds, Android synchronization, static analysis, package safeguards, document checks, source-secret checks and dependency audits passed. The first broad browser run passed 85/93; fixes addressed retired demo-name/provider-return expectations, enlarged-text profile wrapping and a notification layout-measurement race. All eight failures were included in a passing 24-case retest before the complete passing gate. Historical revision-2 counts are not substituted for these results. Result documentation and exported artifacts received refreshed checks after the gate; a future publication requires a fresh exact-state gate. These are local engineering results, not evidence of deployment, host mail delivery, a signed Android release or municipal evaluation.
<!-- verification-summary:end -->

| Evidence | What it establishes | What it does not establish |
| --- | --- | --- |
| Feature tests | Selected validation, consent, ownership, workflow, expiry and permission outcomes | Complete security or legal certification |
| MySQL checks | Relevant behavior in an isolated MySQL schema | Production hosting/load acceptance |
| Browser tests | UI/API tasks, protected navigation and responsive behavior | Physical camera, Keystore or real networks |
| Build/static checks | Compilable assets and selected source/document properties | Absence of all defects or operational adoption |
| Screenshots | Reviewed layout/state at a specific size | Municipal usability results |
| SMTP configuration/authentication | Required local settings present; workstation authentication-only connection accepted without sending | Host-side authentication, mailbox delivery and production link behavior |

## 4.6 Defects and corrections

The earlier broad browser run passed 91 of 97 cases. Failures exposed synthetic email-normalization assumptions, a stray fixture call, guide-return navigation and missing Settings responses in a simulated native bridge. A focused 22-case retest passed, including all six failures. Another 15-case form/phone run passed after correcting small checkbox text. These remain historical scoped results, not one new full-gate run.

Verification cases examine injected account status, stale/replayed mail proofs, missing SMTP, policy changes, image bounds/encryption, cross-account denial, recent admin authentication, image deletion and stale decisions. Additional cases cover incomplete administrators, another administrator's approval, independent attestation, badge removal after withdrawal/name changes, continued no-ID reporting, and administrator creation/deletion access and auditing.

## 4.7 Hostinger preparation and readiness

The prepared layout combines frontend/API on HTTPS while keeping source/private storage outside public_html. Packaging includes explicit application source, fresh compiled assets, relocated public entry, production environment template and hash manifest. Local environment, evidence, IDs, logs and workstation dependencies are excluded. Install production Composer dependencies against the host platform.

The owner supplied local SMTP settings; redacted checks confirm required values are present, and a real workstation SMTP authentication-only connection passed without sending a message. This is distinct from delivery or testing from Hostinger. Hosting tier and domain remain undecided, and a public privacy contact is not yet supplied. HTTPS links, host mail, resources, cron, scanner availability, backup recovery and first-administrator MFA/email/policy setup remain prerequisites.

Android shares the gate, but synchronized assets are not a signed APK. Native tooling, a reachable HTTPS backend and physical tests remain required. Store distribution also requires the native target/version work in the Android guide.

## 4.8 Municipal evaluation — awaiting collection

No municipal UAT, survey, timing dataset or acceptance rating was collected in this implementation activity. The following table is deliberately blank and is not a study result.

| Field | Entry after approved collection |
| --- | --- |
| Approval and evaluation dates | [Edit after approval/collection] |
| Actual consenting participants by role | [Enter actual count] |
| Tasks attempted/completed/assisted | [Enter observations and denominators] |
| Timing rules and summaries | [Enter measurements, if collected] |
| Ratings and missing responses | [Enter actual instrument results] |
| Problems and retest | [Describe recorded evidence] |
| Municipal acceptance | [Actual decision/sign-off reference] |

## 4.9 Discussion of objectives

The implementation provides a traceable reporting/account workflow, separate location evidence, private originals and reviewed publication. Tests support selected behaviors and the development objectives; they do not answer adoption/effectiveness questions reserved for evaluation.

Optional identity review can encourage accountability while allowing residents without suitable IDs to report. Maintaining public contacts is necessary, and assisted reporting needs an operating decision. Minimal review metadata supports accountability, whereas images require supervised access, deletion and protected backup rotation.

The supported result is a documented, tested prototype with explicit prerequisites. Better response, fewer false reports or higher satisfaction must be established through approved observation rather than inferred from features.


---

# CHAPTER 5 — SUMMARY, CONCLUSIONS AND RECOMMENDATIONS

## 5.1 Summary

MuniAlert supplements municipal reporting and organizes incident evidence/history for the proposed Basista context. The development baseline includes documented owner requirements and an interview review whose respondent did not identify a major breakdown in telephone reporting. The rationale therefore emphasizes organization, traceability and controlled access.

The implementation uses separate Vue/TypeScript and Laravel applications, an Android-capable client and MySQL. It preserves original/selected/verified locations and provides private evidence, municipal workflow, reviewed news, notifications and statistics. Email and current policies apply to both roles; administrator MFA remains required. Optional one-ID review earns a blue profile badge and requires independent approval.

## 5.2 Evidence-supported conclusions

First, the project implements a reporting workflow that preserves separate location claims and inspectable history. Source and tests support that implementation conclusion, not a claim that every reported location is accurate.

Second, explicit server contracts and reviewed publication separate public information from private evidence. Tests support the boundaries they exercise. Hosting acceptance and continuing security review remain necessary.

Third, voluntary ID review can offer the same profile trust signal to both roles without blocking residents who decline it. Email/policy requirements and administrator MFA protect access independently. Independent review and no self-approval preserve a meaningful badge boundary without certifying every document or event.

Fourth, the project has a defined single-origin Hostinger release process. Actual readiness depends on domain, plan capabilities, configuration, privacy decisions and operational checks. A local build and configured mail password do not establish a working hosted service.

Finally, no conclusion about municipal satisfaction, improved rescue times, acceptance levels or statistical effectiveness is warranted before evaluation and operational trials.

## 5.3 Recommendations

1. Finalize the domain and a Hostinger tier meeting PHP, Composer/SSH, MySQL, private-storage and cron requirements; verify actual limits.
2. Approve the public privacy contact, review authority, assisted alternatives and account/incident/backup retention before real collection.
3. Seed the initial administrator with private credentials, complete MFA/email/policy setup and test replacement/deletion in Settings. Exercise voluntary ID approval/withdrawal and verify that reporting stays available without the badge.
4. Test mail delivery, HTTPS links, cookies/CORS, private-file denial, scheduler, scanner and isolated restore. Enable optional FCM/R2 only after acceptance.
5. Conduct approved task-based evaluation with representative roles/devices and report failures/assistance as well as successes.
6. Build/sign Android and verify camera, GPS, Keystore, push and accessibility on hardware. Maintain versions before store distribution.
7. Assign maintenance ownership, monitoring, incident handling, credential rotation and access review. Measure load/recovery before promising capacity.
8. Edit this manuscript with actual institutional details, approved methods and observations; align format/citations with the adviser.

## 5.4 Subsequent studies

Future work may evaluate verification usability/fairness, assisted reporting, location confidence, staff review workload and notification acknowledgment. Preserve privacy and distinguish descriptive observation from causal evidence. A trained detector or government identity integration would need separate validation and operating/legal decisions; neither is represented as complete here.


---

# REFERENCES

Cicek, D., & Kantarci, B. (2023). Use of mobile crowdsensing in disaster management: A systematic review, challenges, and open issues. *Sensors, 23*(3), 1699. https://doi.org/10.3390/s23031699

Goodchild, M. F. (2007). Citizens as sensors: The world of volunteered geography. *GeoJournal, 69*, 211–221. https://doi.org/10.1007/s10708-007-9111-y

Haklay, M. (2010). How good is volunteered geographical information? A comparative study of OpenStreetMap and Ordnance Survey datasets. *Environment and Planning B: Planning and Design, 37*(4), 682–703. https://doi.org/10.1068/b35097

International Organization for Standardization / International Electrotechnical Commission. (2023). *ISO/IEC 25010:2023: Systems and software engineering—Systems and software Quality Requirements and Evaluation (SQuaRE)—Product quality model.* https://www.iso.org/standard/78176.html

National Privacy Commission. (n.d.). *The right to be informed.* https://privacy.gov.ph/the-right-to-be-informed/

Republic Act No. 10121. (2010). *Philippine Disaster Risk Reduction and Management Act of 2010.* https://lawphil.net/statutes/repacts/ra2010/ra_10121_2010.html

Republic Act No. 10173. (2012). *Data Privacy Act of 2012.* National Privacy Commission reproduction. https://privacy.gov.ph/Data-Privacy-Act/

United Nations Office for Disaster Risk Reduction. (2026). *Global status of multi-hazard early warning systems 2026.* https://www.undrr.org/reports/global-status-mhews-2026

Ushahidi. (n.d.). *Overview* [Version-3 overview documentation]. https://www.ushahidi.com/support/overview/

MuniAlert project. (2026). *Interview, screenshot and Android review; technical attachments; task records and verification evidence* [Unpublished project documentation]. See accompanying documentation/technical appendix. Confirm original interview consent, attribution and institutional permissions before submission.

Primary sources were checked on 24 November 2026. Publisher abstracts were used where full text was unavailable. Adapt references to the required style and add adviser-required literature before final submission.

# APPENDICES AND EDITING GUIDE

## Appendix A — Proposed evaluation record

**Study approval:** [Edit]\
**Participant code / role:** [Edit; avoid unnecessary identity data]\
**Consent confirmed / date:** [Edit]\
**Device, OS, browser / build:** [Edit]\
**Synthetic scenario and task:** [Edit]\
**Outcome:** [Completed / assisted / not completed / not attempted]\
**Elapsed time and timing rule, if approved:** [Edit]\
**Observed error / assistance:** [Edit]\
**Participant comment, if consented:** [Edit]\
**Proponent interpretation and evidence reference:** [Edit separately]

This instrument has not been administered or validated. Obtain adviser approval, pilot wording and define research retention/access before use.

## Appendix B — Evidence index

The technical appendix supplies requirements, architecture, schema, API contracts, test plan, user manual, deployment, privacy and traceability. Task records distinguish implementation, local verification and external acceptance. The Hostinger guide/template provide deployment details. Never include credentials, real IDs, unapproved photographs, database exports or private logs in academic attachments.

## Appendix C — Editing and submission checklist

- Replace school, degree, proponents, adviser, year and submission date.
- Confirm the formal title, municipal endorsement and interview permissions.
- Obtain approval before changing/administering the proposed evaluation; enter actual dates, participants and observations afterward.
- Update Chapter 4 from final evidence and retain failed-run/retest distinctions.
- Revisit Chapter 5 so every conclusion follows demonstrated or measured results.
- Apply required margins, typography, citations, pagination and front matter.
- Obtain genuine signatures through the institution's process.
- Edit the DOCX in Word or compatible software, or edit the chapter Markdown sources and regenerate. Regeneration replaces the generated DOCX/PDF: transfer Word-only changes back into the source first if they must be preserved.
