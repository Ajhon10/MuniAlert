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
