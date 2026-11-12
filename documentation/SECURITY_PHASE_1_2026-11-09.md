# Security Phase 1 and test accounts — 9 November 2026

The user requested administrator/citizen seeding and application of the pasted security guidance in phases. [The implementation plan](SECURITY_IMPLEMENTATION_PLAN.md) maps the complete checklist and remaining controls. This record covers Phase 1 only.

## Account review

A read-only local query counted **two administrators and two citizens**. It returned role counts and configuration-presence flags only; no personal identities, password hashes or secret values were published. Existing local demo password settings are present. No new account was necessary in the maintainer's normal database; that database was not seeded/reset by this task.

`DemoAccountSeeder` already existed. It now defaults to configurable `admin@example.test`, validates three distinct passwords before any writes and creates missing accounts transactionally. It refuses production and preserves existing accounts/roles/passwords. Normal seeding remains reference data only. `npm run db:seed:accounts` and [the account guide](TEST_ACCOUNTS.md) expose the optional local path. Existing accounts retain their original email addresses; this is not an account migration.

## Implemented protections

- Shared password validation checks bcrypt's 72-byte ceiling and refuses embedded null bytes across password provisioning/entry paths. A direct PHP 8.5 probe confirmed that null-byte bcrypt input raises `ValueError`; it now becomes a validation error. New passwords still require 12 characters. Input is rejected, never truncated; existing hashes are unchanged.
- Registration/profile normalization checks email type before trimming. Array input returns 422 instead of an array-to-string error.
- Global middleware adds MIME/frame/referrer/permissions headers and private/no-store API/auth responses. Camera/GPS remain allowed. HTTPS production responses receive HSTS without subdomain/preload assumptions.
- Production requests force debug off and Secure/HttpOnly session cookies before session middleware. Stored configuration still needs correction and verification for real HTTPS/proxy hosting. Local HTTP and the user's persistent sign-in policy are retained.
- `hazard:security-check` checks Phase 1 production configuration without returning secret values or connecting to external services. Failure is expected against the normal local HTTP/demo/root-database setup. This is not a full deployment approval.

## Verification

Focused verification passed **16 tests / 133 assertions**: seeder environment/password/email guards, preservation of existing accounts, private API denial, field escalation attempts, malformed/oversized credentials, byte-boundary acceptance, generic/throttled login, real CSRF middleware, CORS denial, cache/security headers and secure production cookies/debug suppression. The first production-seeder test hit Laravel's CLI confirmation prompt; it was corrected to invoke the seeder guard directly, verifying refusal independently of the prompt and `--force`.

The final full workspace gate passed **all 14 checks**, including **79 backend tests / 626 assertions, 45 browser tests and five launcher tests**, with zero npm findings and no Composer advisories. The null-byte fix and its regression tests are included in that final run. Documentation was regenerated and checked after adding this result. See [machine-readable evidence](evidence/security-phase-1-2026-11-09.json). Tests use isolated databases and synthetic accounts; browser administrator credentials are generated per run. No original environment values are included.

## Remaining work

MFA, recent reauthentication, recovery/email verification, CSP, malware scanner integration, security events/static analysis, staging scans, real hosting/network permissions, backup restore, monitoring and municipal acceptance remain in Phases 2–3. Image validation/re-encoding is not malware scanning. Middleware does not configure headers on directly served static files; complete static CSP/header coverage is part of Phase 2.

No deployment, physical Android test, cloud acceptance, production account change or GitHub push is performed by this phase. The pasted NestJS examples and suggested agent roles did not change this project's stack or create additional agents.

References: [OWASP ASVS 5.0.0](https://asvs.owasp.org/), [OWASP password storage](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html), [Laravel 12 deployment/debug guidance](https://laravel.com/docs/12.x/deployment).
