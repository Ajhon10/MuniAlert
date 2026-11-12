# Capstone technical documentation

[Admin charts, PDF/Word/Excel exports and the header account menu](ADMIN_STATISTICS_ACCOUNT_MENU_2026-11-12.md) describes the shared statistics workflow and updated notification sizing.

[Emergency-first reports, actionable notifications, hazard sounds and private image review](EMERGENCY_NOTIFICATIONS_IMAGE_REVIEW_2026-11-11.md) records the current workflow; real contacts and a local classifier need configuration.

[Settings, saved reading preferences, authenticator 2FA and Gmail readiness](SETTINGS_2FA_EMAIL_2026-11-11.md) records the current refinement; real delivery needs BANTAY's separate app password.

[Single validation toasts, password visibility and schema inventory](TOAST_PASSWORD_SCHEMAS_2026-11-11.md) explains the latest UI changes and which local databases serve the app versus tests/archived setup.

[Community navigation and reviewed news details](COMMUNITY_NAVIGATION_NEWS_DETAILS_2026-11-10.md) records the latest page simplification, all-status My reports and reviewed public submitter credit.

**Project:** Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking\
**Application name:** MuniAlert\
**Baseline date:** 2 October 2026\
**Document status:** Implementation documentation and capstone attachment draft; school approval and municipal evaluation are pending.

This package describes the actual code in this repository. It does not claim a completed research study, production deployment, live R2/FCM validation, or a signed Android release. Fill institutional details and research findings only with approved, collected evidence.

| Attachment | Purpose | Typical manuscript placement |
| --- | --- | --- |
| [01 — Project overview and requirements](01-project-overview.md) | Problem, objectives, scope, actors, requirements | Chapter 1 / requirements appendix |
| [02 — System architecture](02-system-architecture.md) | Components, source structure, sequence and state diagrams, PDEA alignment | Design chapter |
| [03 — Database design](03-database-design.md) | ERD, data dictionary, indexes, evidence handling | Design chapter / database appendix |
| [04 — API reference](04-api-reference.md) | Endpoints, payloads, authentication, error responses | Technical appendix |
| [05 — Testing and validation](05-testing-and-validation.md) | Test strategy, executed evidence, open validation work | Results chapter / test appendix |
| [06 — User manual](06-user-manual.md) | Citizen and municipal administrator procedures | User manual appendix |
| [07 — Deployment and maintenance](07-deployment-and-maintenance.md) | Hostinger Web/Cloud, environment, private R2, FCM, Android, backup and recovery | Deployment / turnover appendix |
| [08 — Manuscript guide](08-capstone-manuscript-guide.md) | Chapter outline, abstract draft, evidence and citation guidance | Manuscript preparation |
| [09 — Security and privacy](09-security-and-privacy.md) | Access matrix, data exposure, controls, operational decisions | Security / ethics appendix |
| [10 — Traceability and acceptance](10-traceability-and-acceptance.md) | Requirement-to-code/test mapping and UAT forms | Traceability / evaluation appendix |
| [Task tracker](../TASKS.md) | Phased implementation and validation status | Development plan appendix |
| [Project memory](../MEMORY.md) | Persistent decisions and pre-push testing requirement | Maintainer handover |

Run `npm run docs:build` to create `CAPSTONE-TECHNICAL-DOCUMENTATION.md` and `CAPSTONE-TECHNICAL-DOCUMENTATION.html`. Open the HTML in a browser and use **Print → Save as PDF** for an attachment draft. The school’s prescribed manuscript formatting takes precedence over this technical appendix layout. Mermaid blocks in Markdown are editable diagram sources; the printable version includes a static architecture diagram and preserves other diagram sources for review.

Document control: record the Git commit, software versions, test date, test environment, evaluator, and unresolved issues when submitting a release. Do not represent the fictional demo screenshots as real municipal incident records.

Ready-to-attach exports: [PDF technical appendix](CAPSTONE-TECHNICAL-DOCUMENTATION.pdf), [printable HTML](CAPSTONE-TECHNICAL-DOCUMENTATION.html), and [editable consolidated Markdown](CAPSTONE-TECHNICAL-DOCUMENTATION.md). Regenerate all three after source edits with `npm run docs:pdf` (requires Playwright Chromium). The Markdown/HTML exports are checked automatically; the PDF is an exported attachment and must be regenerated for a new submission.

Follow-up guides: [Android phone testing](ANDROID_TESTING.md) and [responsive layouts / persistent sessions](RESPONSIVE_SESSIONS_2026-11-03.md). These supplement the earlier feature audit and retain release limitations.

[Google and Facebook sign-in setup](SOCIAL_AUTH_SETUP.md) covers optional server credentials, callback URLs, account linking and Android return handling.

[Social authentication implementation and local evidence](SOCIAL_AUTH_2026-11-04.md).

[8 November interview, screenshot and Android review](INTERVIEW_ALIGNMENT_2026-11-08.md) maps all 14 Filipino responses and six reference screenshots to implemented behavior, corrected requirements and remaining acceptance.

The source review is available as [phone-readable HTML](INTERVIEW_ALIGNMENT_2026-11-08.html) and [PDF](INTERVIEW_ALIGNMENT_2026-11-08.pdf).

[MuniAlert branding, guide and promotional-video handover](BRANDING_GUIDE_PROMO_2026-11-09.md) records the user-approved title, new guidance screens, local verification and both MP4 exports. The [user manual](06-user-manual.md) and printable technical appendix include the new name and guidance instructions.

Short manual downloads: [citizen PDF](MUNIALERT-CITIZEN-MANUAL.pdf) and [administrator PDF](MUNIALERT-ADMIN-MANUAL.pdf), exported from the same bundled instructions used in the application.

[Basista map and coverage handover](BASISTA_MAP_2026-11-09.md) documents Clear/Streets/Dark modes, the 13 barangays, boundary sources and map-category filtering.

For members: [private workspace setup](TEAM_TESTING.md), [Android USB/native testing](ANDROID_TESTING.md), and [false-report review and fraud-prevention guidance](FRAUD_PREVENTION.md). The latter distinguishes existing controls from unimplemented proposals.

[Header and private team-repository handover](HEADER_TEAM_2026-11-09.md) records the responsive fixes, publication safeguards and local verification.

[Fixed modals, desktop heading and refreshed video](MODALS_HEADER_VIDEO_2026-11-09.md) records the follow-up layout fix and the current MP4 exports, superseding the earlier promotional output hashes.

[KIKOS narrator and flush mobile header](NARRATOR_MOBILE_2026-11-09.md) records the latest narrator, top-spacing correction and MP4 hashes. Its export record supersedes the preceding video versions.

For a new teammate: [first-download quickstart](COLLABORATOR_QUICKSTART.md), [files and owner handoff](COLLABORATOR_FILE_CHECKLIST.md), and [fresh-source setup verification](COLLABORATOR_SETUP_2026-11-09.md).

[Administrator/citizen test accounts](TEST_ACCOUNTS.md), [the three-phase security plan](SECURITY_IMPLEMENTATION_PLAN.md), and [Phase 1 implementation/evidence](SECURITY_PHASE_1_2026-11-09.md) cover the latest account and security follow-up. Pending controls remain explicit.

[Continued security phases](SECURITY_PHASE_2_2026-11-10.md) and [activation/remaining operations](SECURITY_OPERATIONS_2026-11-10.md) describe administrator MFA, recovery, CSP/scanning adapters and source automation, with explicit service/hosting acceptance limits.

[Durable login, dashboard appearance and Phase 3 local operations](SECURITY_PHASE_3_DASHBOARD_2026-11-10.md) records the latest session/background/sidebar/motion follow-up, aggregate monitoring and synthetic encrypted MySQL restore rehearsal, with separate production acceptance limits.

[Text-size slider and rolling dashboard counters](SLIDER_ROLLING_NUMBERS_2026-11-10.md) records the native range control, saved intermediate sizes, digit-reel motion and reduced-motion behavior.

## Verified community publication and hazard collections

See [private submissions, factual review, separate news and category achievements](COMMUNITY_REVIEW_ACHIEVEMENTS_2026-11-10.md) for current visibility, admin actions, sharing and milestone rules.
