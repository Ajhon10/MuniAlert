# Social authentication implementation ? 4 November 2026

Google and Facebook signup/sign-in are implemented in the existing Vue/Laravel applications. The form keeps BANTAY's system typography, rounded glass surface, keyboard focus and 48px provider controls. New provider users are citizens. Password accounts link providers only after authenticated password confirmation; matching email does not merge accounts.

Web uses state-protected Socialite redirects, a verified session on return, and the existing persistent HttpOnly cookies. Android uses the system browser, secure pending-verifier storage and a fixed app callback with a one-use, 90-second S256-protected code. Cold launch, provider cancellation, account collisions, stale/revoked link proof and replay handling are covered. A newly issued mobile token is revoked if secure storage fails or a pending exchange was cancelled.

Local verification: **52 Laravel tests / 390 assertions, 28 browser tests, five Node launcher tests**; the parent gate has twelve checks covering formatting, type checking, both builds, feature/browser tests, Android asset/plugin sync, dependency audits and documentation consistency. Locked platform requirements pass on workstation PHP 8.5.1. Root and standalone npm audits report zero findings; Composer reports no advisories. [Machine-readable evidence](evidence/social-auth-2026-11-04.json) records the final gate.

The additive migration was applied to the existing local development database. Existing user/report counts remained unchanged; it was not reset or reseeded. Normal development-server processes were not started, stopped or restarted for this feature; isolated test servers were shut down by their runner. Provider credentials were inspected only as booleans: neither Google nor Facebook is configured. Blank server-only placeholders are ready in the local environment and example file.

[Mobile account creation screenshot](evidence/social-signup-mobile-2026-11-04.png) uses synthetic browser-test data and unavailable provider configuration. It is not municipal data or proof of a live provider login.

**External acceptance remains pending:** real Google/Meta credentials, consent/audience/review settings and exact HTTPS callbacks; actual provider consent/new/repeat/link/cancel flows; a signed Android build and physical-device cold-launch/Keystore tests; Hostinger deployment; municipal privacy/deletion policy and UAT. No account, GitHub sign-in, production deployment or GitHub push was changed for this task. See [the concrete setup guide](SOCIAL_AUTH_SETUP.md).

Workspace ownership verification checked 61,228 entries and restored Lenovo ownership on 943 affected paths, with zero failures. Existing access permissions were preserved; junctions/symlinks were skipped. No wildcard Git trust exception or Windows/GitHub account removal was used.

The full browser regression also corrected legacy test selectors for changing pending-count labels and verified-news titles, used a separate account for the new return-flow case, and explicitly acknowledges duplicate suggestions when present. Required assertions, rate limits and duplicate safeguards remain enabled. The six municipal/reporting scenarios passed their targeted recheck before the final gate.

The final gate includes a deterministic regression for a delayed anonymous startup response: it preserves an opened signup form and its entered values, while authenticated session expiration still clears private state. All eight social-auth browser cases and the complete 28-case browser suite passed. The final evidence records application source hashes and the actual gate completion timestamp.
