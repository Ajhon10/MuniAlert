# Settings, per-account reading, authenticator 2FA and Gmail

The user requested reading/accessibility and the guide inside Settings, separate saved display customizations for each account, removal of the sidebar community callout, a sample default administrator and PDEA-style 2FA/email. They confirmed authenticator-app codes and the same provider with separate credentials; read-only PDEA configuration inspection identified Gmail SMTP. No PDEA credentials or application data were copied or modified.

## Interface and account preferences

Settings replaces the guide navigation entry and has one header shortcut. Its reading card opens the existing 100–200% slider, bold text, contrast, spacing, motion and message controls. The guide card opens the searchable manual with Back to settings. Account security and email move from My profile to Settings. The sidebar callout and individual reading/guide shortcuts are removed.

Six validated display preferences save to the current account through an authenticated allowlist. Per-account local caches preserve pending changes; debounced serialized saves expose retry status. A matching account-context ID rejects delayed writes after account switching. Guest state stays separate, and a fresh signed-in device restores server preferences. Reset saves defaults for the current scope. Only display/email preferences bypass administrative recent-action prompts; full administrator MFA/version checks remain mandatory.

Additive migration 2026_11_11_000001 adds nullable reading_preferences JSON and email_report_updates default false. It was applied to localhost municipal_hazards with four accounts and zero reports preserved. Existing accounts, roles, passwords, MFA and records were not reseeded or changed. Demo seeding now labels Sample Administrator and rejects real-address admin domains; its default/local override is admin@example.test.

## Authenticator 2FA

The six-digit authenticator entry follows PDEA's interaction pattern with six labeled numeric cells, whole-code paste/autofill, keyboard arrows/backspace and an explicit recovery-code mode. It reflows into two rows on phones/large text. Enrollment retains local QR/manual setup and has no recovery-code alternative before setup. Existing operator approval, full-credential boundary, single-use time steps/recovery codes, account-wide attempt limit and sensitive-action verification remain enforced. Email is separate from sign-in 2FA.

## Gmail configuration and privacy

BANTAY's ignored environment uses the user-authorized sender with smtp.gmail.com, port587, required STARTTLS and a separate MAIL_PASSWORD. The shared example contains sample values only. SMTP availability fails closed when credentials/TLS/sender are missing or a log mailer is selected. Reserved/sample domains cannot receive real mail. Delivery awaits the separate app password; no real email has been sent or delivery claimed.

HTML and plaintext verification/reset emails use encrypted, one-use, 30-minute proofs and the exact configured frontend origin. Real deployment requires HTTPS. Locally verified email is separate from a provider email assertion; provider-only accounts retain provider recovery and password reset does not bypass administrator MFA.

Report update emails are off by default and enabled in Settings after verification. Opt-out remains available even when delivery or verification becomes unavailable. A transactional job holds only a notification UUID. Delivery rechecks active/current owner, locally verified address, opt-in and undeleted report. Messages contain only reference, status and a My reports link; private evidence, original descriptions, notes, exact locations and contact details are excluded. Opt-out/deletion stops pending mail. SMTP acknowledgement loss may cause a retry; these emails are informational, not emergency dispatch.

The existing single scheduler invokes hazard:work-security-mail under an overlap lock, with at most three jobs/30seconds per batch, bounded retries and redacted failures. No persistent shared-hosting daemon is introduced. Mail templates/jobs are tested with fake mail, not a real mailbox. Follow [Google's app-password setup](https://support.google.com/accounts/answer/185833) and [Laravel's mail configuration](https://laravel.com/docs/12.x/mail); store the secret directly in BANTAY's server environment and never in chat/source/frontend variables.

## Local verification and external limits

Focused PHP coverage validates preference ownership/schema/stale-account guards, transactional rollback, ID-only payloads, eligibility/opt-out/deletion and mail-readiness checks. Browser coverage validates account switching, fresh-device restoration, removed shortcuts/callout, Settings/manual navigation and 200% phone 2FA entry. Existing enrollment, replay, recent-verification and delayed-response cancellation tests remain in the gate. Final counts and screenshots are recorded in [redacted evidence](evidence/settings-2fa-email-2026-11-11.json) and [the gate report](evidence/settings-2fa-email-gate-final-2026-11-11.json).

No push, deployment, real SMTP delivery, real provider sign-in, physical Android/TalkBack or municipal/workload acceptance is included. The separate Gmail app password and authorized delivery test are still required.

## Final local result

The final Settings/2FA/email gate passed all **19 checks**, including **114 backend tests / 1030 assertions and 74 browser tests**, zero PHPStan errors and zero dependency findings. Separate isolated MySQL checks passed **25 tests / 190 assertions**. Native/web production assets and Android synchronization are current. Initial failures were stale motion counting after entering Settings and repeated dismissal during a toast leave transition; both test corrections retain the behavior assertions. Both manuals and the technical appendix are refreshed. No real email, deployment or physical-device acceptance is claimed; BANTAY still needs its separate Gmail app password. Result-record changes receive documentation/secret checks, and future pushes require a fresh exact-state gate.

Reviewed synthetic screenshots: [saved account Settings](evidence/settings-account-saved-2026-11-11.png), [200% phone reading controls](evidence/settings-reading-phone-200-2026-11-11.png) and [200% phone 2FA entry](evidence/two-factor-phone-200-2026-11-11.png). The authenticator screenshot has empty code cells.
Lenovo ownership was restored across 64 affected BANTAY scopes. All 32 sampled access-control lists were preserved, including the unchanged owner of the ignored server environment. No wildcard Git ownership exception was added.
