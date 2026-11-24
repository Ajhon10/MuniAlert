# CHAPTER 1 — THE PROBLEM AND ITS BACKGROUND

## 1.1 Introduction

Emergency information is useful when it reaches an appropriate person with enough context for a considered response. A report may describe what happened, where it occurred, when it was observed and whether immediate assistance is requested. A municipality also needs records that can be inspected afterward, including the actions taken and the information subsequently verified. Software can organize these records, but it cannot replace the judgment, staffing and authority of emergency personnel.

MuniAlert is developed as a supplementary reporting and record-management channel for Basista, Pangasinan. It provides community forms and municipal administration through responsive web screens and an Android-capable client. The formal title retains the approved emphasis on photographs and location-based incident tracking. The application and its documentation consistently use the name MuniAlert.

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
