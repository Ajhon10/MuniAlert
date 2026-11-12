# Shared interfaces, routing, CSV data and recovery

Implemented for the 12 November 2026 screenshot review. Existing Vue/Laravel separation, private evidence, explicit factual review and publication gates remain in force.

## Interface contracts

`Modal.vue` owns body padding, header dismissal and optional action footers. Empty footers and duplicate footer Close/Go back controls are removed. `useNativeDialog` shares native dialog/scroll-lock lifecycle with the spotlight tour. Form shells use `AppForm` with server validation rather than browser validation bubbles. `FormInput`, `FormSelect`, `FormTextarea`, `PasswordInput` and `TwoFactorInput` preserve native labels, names, autocomplete, keyboard/pointer events and specialized control behavior. Common control dimensions are CSS variables in `assets/forms.css`; password controls reserve room for their visibility button. `DataTable` shares report/department table markup while each caller supplies its row content.

Toasts use a manual popover overlay inside the active dialog's accessible subtree. They do not take space from the modal body. Errors persist and offer dismissal/field focus; old informational messages are cleared when entering a new modal. Notifications have consistent row heights adjusted for text preferences, two-line ellipsis previews, and keep their existing authorized report destination. Achievement grids align cards at the start so opening one does not stretch its neighbors.

Community reporting hides raw coordinate fields. GPS and map click/drag remain available, with a keyboard-operable Place pin at map center action. The location must still be explicitly confirmed, and original GPS stays separate from the chosen pin. Unsupported WebGL requires another supported browser to choose a pin. Inactive Google/Facebook sign-in/link buttons are hidden; the tested integration/callback code is retained for future activation.

## Departments and routing

Content management includes a searchable office table, add/edit dialog and per-hazard responsible-office selection. The layout follows the DEOS heading/toolbar/table/action pattern while retaining MuniAlert styling. The backend independently requires administrator access. Active offices appear in the catalog. An office cannot be archived until its hazard categories are reassigned. Existing reports keep their recorded office; routing changes apply to new reports. The additive `2026_11_12_000001_add_department_availability` migration adds the active flag without replacing or deleting records.

## One seed entry point and visible CSV data

`hazard-server/database/data/` contains `departments.csv`, `categories.csv`, `badges.csv`, `settings.csv`, `sample_accounts.csv` and `hazard_achievements.csv`. Reference/sample data is inspectable without opening PHP arrays. Existing migrations retain their historical bootstrap defaults; the current seeder fills missing reference records and preserves admin edits, routing and account credentials. Achievement names/thresholds are read into configuration from their CSV.

Run `npm.cmd run db:seed` after migrations. This seeds reference data and, when all three distinct `HAZARD_DEMO_*_PASSWORD` values are configured, the sample administrator/two citizens in one transaction. Missing all three passwords means reference-only seeding. Partial/invalid account configuration fails and rolls back the seed transaction. Sample accounts are limited to local/testing and reserved `.test` addresses; existing emails are skipped. Passwords stay in private environment configuration, never CSV. `password_config` is an allowlisted configuration-key reference. The explicit `db:seed:accounts` command remains compatible for accounts-only setup. Browser test setup uses the same one-command seed path on its isolated database.

## Server validation and recovery

JSON validation failures return HTTP 422 with `message`, `code: validation_error`, and field-keyed `errors`, visible in browser Network responses. The client presents those errors as a single floating toast and links/focuses matching fields. Device/file-processing and navigation protections remain local where a request cannot safely proceed. Credentials are not logged for debugging.

Forgot password offers an email reset link and a saved recovery code. Email still requires previously verified local email, deliverable recipients and configured SMTP. Settings lets password-account holders create/replace ten saved codes after recent verification. Codes are hashed and encrypted at rest, returned once, and reused as the existing 2FA backup-code set for enrolled users. Password reset consumes one code under the user lock, enforces per-IP/account/factor limits, revokes prior credentials and security challenges, and retains administrator 2FA. Unknown/invalid/inactive account failures share the same response. Codes must be saved beforehand; this does not generate an account's lost codes. No real email delivery is claimed without configured credentials and an authorized delivery test.

## Social sharing limits

Share dialogs prepare the reviewed public title, summary, category, area, priority, reviewed incident time/submitter credit, responsible office and reference. Original evidence, exact coordinates, contact details and private landmarks are excluded. Native sharing, X and WhatsApp receive prepared text. Facebook opens its link composer with a separate explicit Copy post & open Facebook action; users paste the copied text because caption behavior is controlled by Facebook. Public server-rendered news pages retain Open Graph title/description and the public update. Localhost links cannot be reached by external crawlers or other users; a live preview needs the deployed public HTTPS site. No Facebook posting or public hosting is performed by this change.

## Verification and reusable lessons

Backend coverage includes role restrictions, category routing, repeat CSV seeding without resetting passwords, one-time code reset, lockout across sources, session revocation and preserved administrator MFA. Browser coverage includes dialog body geometry, floating validation/field links, long notification navigation, independent achievement expansion, office creation/routing, reviewed share text and recovery choices. Existing privacy/reporting/MFA assertions remain active; selectors are updated only where the requested UI removed a control.

Canonical PROJECTS engineering lessons and UI web-acceptance instructions now cover shared modal/control sizing, body padding, action-only footers, top-layer toasts, bounded previews, expansion alignment, server error responses, safe CSV seeding and platform share limits. Those workspace skills are maintained outside this application repository; project rules are recorded in `AGENTS.md` for collaborators.

The exact-state nineteen-check pre-push gate remains mandatory. Local verification, GitHub CI, Hostinger deployment, real social previews/email, physical Safari/Android/TalkBack and municipal acceptance are separate evidence states.
