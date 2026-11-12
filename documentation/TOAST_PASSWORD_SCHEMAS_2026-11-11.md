# Single validation toasts, password visibility and local schema inventory

The user requested one toast instead of duplicate inline validation, show/hide password controls and an explanation of the local MuniAlert schemas. The Vue/Laravel stack and server validation/authorization remain in place. Existing application, test and archived MySQL schemas were only inspected; no MySQL schema was dropped, reset, migrated or reconfigured. Automated browser/backend checks use their isolated SQLite databases.

## Feedback and passwords

Eighteen duplicate inline error elements were removed across seventeen components, including sign-in, MFA, recovery, profile, report/location forms and administrator confirmations. Scoped error sources use a single persistent toast. Retrying/clearing a source or leaving the form clears its stale error. Connection/retry panels and instructions remain useful page state. Provider errors use the shared handler.

HTML required/type/length constraints still prevent submission. The invalid handler cancels the browser's extra bubble, focuses the first invalid field and associates its accessible description with the toast. Go to field returns focus; correcting the field clears its toast/owned ARIA state. Dismissal and unmount remove stale associations. Messages stay in the active native modal's accessible notification region. This follows the user's toast-only preference while retaining keyboard access and field descriptions. Server validation is unchanged.

PasswordInput supplies eight independent fields across sign-in/registration, profile/current/new/confirmation/provider linking, recovery/new/confirmation and identity verification. Each starts masked, preserves autocomplete/value/selection, has explicit Show/Hide labels and pressed state, uses a 48-pixel control and never submits. Empty values, mode changes, unmount and backgrounding hide the password. Visibility is not stored. MFA and password constraints remain enforced.

Synthetic screenshots: [single login error with password toggle](evidence/login-single-toast-2026-11-11.png) and [password control at 200% phone text](evidence/password-toggle-200-phone-2026-11-11.png). No real account/password was captured.

## Which MySQL schema is used

The inventory bootstrapped Laravel's configured connection and checked SELECT DATABASE(); both identify **municipal_hazards**. Only names, table/record counts and aggregate connection counts were read. No credentials or personal records were exported. The main application held four accounts and zero reports/photos at inspection.

| Schema | Purpose | Cleanup guidance |
| --- | --- | --- |
| municipal_hazards | Current configured application | Keep; required for the local app |
| municipal_hazards_test | Isolated MySQL backend tests, allowlisted before reset traits execute | Keep for MySQL testing; the normal app does not use it and it can be recreated if deliberately removed |
| municipal_hazards_e2e | Isolated browser tests when HAZARD_E2E_MYSQL=1 | Keep for MySQL browser tests; default browser tests use .cache/e2e.sqlite |
| munialert_collab_20261109_070339 | Earlier fresh-download/collaborator setup test | Cleanup candidate when that old installation/data is no longer needed; contains six accounts, three reports and three photo records |

The archived .cache/collaborator-download-20261109-150221/hazard-server/.env still references the dated schema. Launching that copy would use it. No connections to the auxiliary schemas were observed, but that snapshot alone does not prove a database is disposable. Preserve a backup before intentionally deleting records you may need. The current application does not depend on that dated copy. Separate test schemas protect application data from test resets; their setup/CI may recreate them. Keep unrelated luma/pdea schemas and MySQL's sys schema outside BANTAY cleanup.

See [the redacted inventory](evidence/schema-inventory-2026-11-11.json) and [verification record](evidence/toast-password-schemas-2026-11-11.json). These are local checks, separate from deployment and physical Android/TalkBack acceptance.

## Final local result

The [release gate](evidence/toast-password-gate-final-2026-11-11.json) passed all **19 checks**, including **110 backend tests / 994 assertions and 72 browser tests**, zero PHPStan errors and zero dependency findings. It includes native/web builds, single-origin packaging, Android asset synchronization and retained MFA/session/reporting/privacy checks. Initial failures were an early native-event observation and old inline-error/substring input selectors; the timing/targets were corrected without dropping outcome assertions. Screenshot review then found a global input-padding rule overriding the visibility control's reservation at 200% text size. The final scoped selector correction and padding-versus-button-width assertion receive focused browser/type/build/sync checks recorded in the evidence. Both user manuals and the technical appendix were regenerated. Result-recording edits receive documentation/source-secret checks. No push/deployment, MySQL cleanup, real account publication or physical-device acceptance is included; a future push requires a fresh exact-state full gate.

After the final spacing correction, all **11 focused browser checks**, native/web builds and Android asset synchronization passed. Lenovo ownership was restored across 52 scopes with all 23 sampled access-control lists preserved; no wildcard Git ownership exception was added.
