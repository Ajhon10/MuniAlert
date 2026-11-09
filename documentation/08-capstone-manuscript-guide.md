# 08 — Capstone manuscript preparation guide

This is an adaptable technical manuscript guide, not a fabricated completed study. Follow the institution's required chapter structure, citation style, approval process and formatting.

## Suggested front matter

- Title page: formal title, proponents, department/institution, adviser, municipality and academic year.
- Approval sheet and institutional declarations using approved templates.
- Acknowledgments, table of contents, list of figures, list of tables and abbreviations.
- Abstract containing the actual implemented scope and measured evaluation results only.

## Abstract draft

The Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking is a mobile-friendly application intended to support citizen hazard reporting and municipal response monitoring. Citizens submit a category, description, compressed photographs and a confirmed location captured through GPS or a manually selected map pin. The system preserves device GPS, reported accuracy, citizen-selected coordinates and administrator-verified coordinates separately. Municipal administrators review reports through a map-centered dashboard, compare location evidence, assign departments and response teams, and record status updates. The implementation uses Vue 3, TypeScript and Capacitor for the client, Laravel and Sanctum for the API, MySQL for persistence, MapLibre for mapping, and configurable Cloudflare R2 and Firebase integrations. Automated verification covers reporting, authorization, photo limits, location validation, duplicate confirmation and status transitions. **Insert actual evaluation method, participants, measured findings and supported conclusions after approved testing.**

## Chapter 1 — Introduction

Develop the municipal context and supported problem statement. Present the general/specific objectives, stakeholders, significance, scope, limitations and definitions. Use [Project overview](01-project-overview.md) as the implementation-aligned requirements baseline. Establish the local problem using evidence gathered with authorization; do not claim interview or survey findings that were not collected.

## Chapter 2 — Related literature and systems

Review peer-reviewed and primary sources about participatory reporting, geographic information quality, mobile usability and public-service response tracking. Distinguish scholarly literature from vendor documentation. Compare real existing systems using criteria such as reporting workflow, location evidence, verification, privacy, cost model and maintainability. Do not invent citations, deployment statistics or competitor features.

Useful primary implementation references, consulted 2026-10-02, include:

- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum): session and mobile authentication.
- [Laravel queues](https://laravel.com/docs/12.x/queues): background processing.
- [MapLibre API](https://maplibre.org/maplibre-gl-js/docs/API/): interactive mapping.
- [Capacitor Geolocation](https://capacitorjs.com/docs/apis/geolocation): device location APIs and permissions.
- [Capacitor Push Notifications](https://capacitorjs.com/docs/apis/push-notifications): native push integration.
- [Cloudflare R2 PHP example](https://developers.cloudflare.com/r2/examples/aws/aws-sdk-php/): S3-compatible storage access.
- [Firebase HTTP v1 messaging](https://firebase.google.com/docs/cloud-messaging/send/v1-api): server-to-device messaging.

Check the applicable documentation version against the lockfiles and preserve access dates in the school's required reference style.

## Chapter 3 — Methodology and design

Describe the development method actually followed, including requirements elicitation, phased implementation, review and validation. Include approved research design, participant selection, evaluation instruments, data handling and analysis procedures. No respondents have been surveyed by this development task.

Use the architecture, report sequence/state diagrams, database ERD/data dictionary, requirements and API contracts from Attachments 01–04. Explain why original GPS and selected coordinates are distinct and why a location discrepancy triggers review rather than rejection. Describe student-maintenance constraints without assuming permanent cloud free tiers.

## Chapter 4 — Implementation and results

Present screens and workflows tied to requirements. Identify the tested software version and runtime. Include actual automated results and defects corrected; report untested R2/FCM/device/deployment items separately. Add measured UAT, usability and performance results only after those activities occur. Synthetic demo screenshots demonstrate layout, not real incident response.

Suggested result tables: requirement acceptance, automated case results, defect/retest log, device/browser compatibility, measured completion times, participant feedback, and unresolved limitations. Explain the sampling and analysis method before computing summary scores.

## Chapter 5 — Conclusions and recommendations

Relate each conclusion to evidence for a stated objective. Separate implementation achievements from study outcomes. Document remaining requirements such as authoritative boundaries, cloud validation, real-device build testing, account recovery, municipal operating policy and field deployment. Recommend work according to recorded limitations rather than claiming all operational requirements are complete.

## Suggested appendix package

1. Requirements and traceability matrix.
2. Architecture diagram, sequence/state model, ERD and data dictionary.
3. API reference and source-structure description.
4. Citizen/admin user manual with sanitized screenshots.
5. Test plan, automated results, defect log and signed UAT record.
6. Deployment, environment, backup/restore and maintenance guide.
7. Approved evaluation instruments and participant information/consent materials.
8. Project phases, version history and release-gate evidence.

Never attach secrets, passwords, service-account JSON, real reporter GPS/identity, unapproved hazard images, database dumps or private system logs to a capstone submission.
