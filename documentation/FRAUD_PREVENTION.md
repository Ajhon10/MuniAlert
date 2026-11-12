# Reducing false and abusive reports in MuniAlert

False reports cannot be eliminated by a photo or GPS reading. Treat them as evidence to review. Separate account abuse controls, report verification and urgent field response. This is an operational guide for the current application, with proposed additions identified below.

## Controls already implemented

| Control | What it helps with | Limit |
| --- | --- | --- |
| Sign-in before submission | Links a report to an account and private history | Email/password signup is not verified email or verified residency |
| Submission limiter | Ten submission attempts per minute per authenticated account; other routes have their own limits | A determined actor can use multiple accounts; this is not a fraud detector |
| Required photos and validated fields | Rejects missing/corrupt/oversized files and incomplete reports | A valid image can be old, copied, staged or edited |
| Nearby same-category suggestions | Encourages confirmation instead of repeated reports within 100m/six hours | A citizen can legitimately report a different incident; suggestions do not prove duplication |
| Separate GPS, selected and verified locations | Lets an administrator inspect accuracy, source and discrepancy | GPS can be inaccurate/spoofed; a manual pin may be legitimate |
| Basista's 13-barangay allowlist | Keeps new report names within the configured coverage | Reference polygons do not prove the reporter was physically present |
| Administrator workflow and audit | Records verification, rejection reasons, assignment and response notes | A human must actually examine the evidence; administrators have broad municipal access |
| Separate reviewed news summary | Verification alone does not publish raw incident text/images | Only fact-checked, accepted records appear in public discovery; a separate explicit publication is required for news |

## Review procedure for municipal administrators

1. Move a new report to **Under verification**. Inspect the description, category, incident time if known, barangay, landmark, photo content and the independent location evidence.
2. Compare recent nearby reports and response records. Repeated text or a similar photo warrants a closer look; multiple witnesses can legitimately report the same event. Do not reject solely because the GPS differs from a manually selected pin.
3. Use Basista's approved contact or field-confirmation process where needed. The app does not currently verify phone ownership, residence or photo authenticity, and it does not automatically call the reporter.
4. Verify only when supported by the municipality's evidence standard. When rejecting a Submitted, Under verification or Verified report, record a specific reason in the required response note. Assigned/responding records follow the existing response workflow; do not invent an unsupported status transition.
5. Keep original photos, precise locations, observations and account details private. Publish only a separately reviewed community summary. Audit activity is recorded; a dedicated audit-log browsing/account-suspension UI is not implemented.
6. Handle urgent calls/field assistance under the municipality's emergency process. Documentary verification must not delay necessary emergency action.

Badges depend on verified reports, and rewards are not automatically paid. Any reward program needs a separate municipal review to avoid encouraging repeated or fabricated submissions.

## Recommended next controls, not implemented by this change

- Verify a contact channel before routine submissions, with a documented exception for urgent assistance. Email verification/phone OTP need actual provider configuration, recovery and support processes.
- Add administrator account review and a reversible suspension/appeal process with audit reasons. Do not automatically ban someone for a GPS mismatch or one rejected report.
- Add configurable per-hour limits and repeated-content/image flags for human review after measuring legitimate reporting needs. Image fingerprints indicate reuse, not proof of fraud.
- Add risk-based bot challenges only where abuse warrants them, preserving an accessible reporting path. Do not require government-ID uploads by default merely to prevent spam.

These recommendations apply the idea of protecting sensitive submission flows against automated misuse described by [OWASP API6:2023](https://api-security.owasp.org/editions/2023/en/0xa6-unrestricted-access-to-sensitive-business-flows/). They require Basista's policy decisions, implementation and separate validation; no automatic fraud-classification accuracy is claimed.

## Recorded fact-check and publication workflow

The current interface requires **Fact-check incident** with a method, internal supporting note, explicit factual attestation and verified location. The record is administrator-only; the reporter receives the acceptance outcome. Acceptance does not create a post. Create/review/confirm a separate public title and summary to publish; original evidence and identifying details remain private. Category milestones use only accepted own reports. Rejected/deleted records lose eligibility and confirmation taps do not earn credit. These controls record human review; they do not detect fabricated photographs or establish factual correctness automatically. See [the continuation](COMMUNITY_REVIEW_ACHIEVEMENTS_2026-11-10.md).
