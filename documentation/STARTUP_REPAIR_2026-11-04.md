# Startup repair — 4 November 2026

The user could not start BANTAY from separate terminals. Composer/Git reported
that the sandbox account owned `hazard-server`, and Vite refused to start because
port 5173 was occupied. The existing browser/API/database path was healthy; the
terminal commands were attempting to start additional servers. Two PHP server
trees and an existing Vite process were identified and restarted as one matched
pair. Only verified BANTAY development processes were stopped.

## Changes

- Restored the Lenovo account as owner of both BANTAY repository roots and their
  `.git` directories, preserving access permissions. The requested wider audit
  restored 14 additional sandbox-owned project/repository directories under
  PERSONAL/WORK. Git ownership protection remains enabled; no wildcard trust was
  added. Windows sandbox identities are execution accounts, separate from Git
  authorship or GitHub credentials.
- Parent `npm run dev` starts/checks the PHP API first, starts/checks Vite, then
  confirms `/api/health` through the frontend proxy. It reuses healthy instances
  from this workspace, rejects another project's occupied ports, and stops only
  processes it started when a sibling fails or the launcher is interrupted.
- Client `npm run dev` and server `composer run serve` also reuse matching
  instances. Windows API serving always uses `C:/php85/php.exe`. Direct Composer
  invocation in Git Bash should prefix `PATH="/c/php85:$PATH"` so Composer itself
  also uses PHP 8.5. No machine PATH or web-server runtime was changed.
- Development responses carry a hashed workspace identifier and the web proxy's
  API port, allowing startup to distinguish an existing matching service from an
  unrelated occupant. API identity is limited to `APP_ENV=local`; Vite identity
  exists only on its dev server. A failed database health query returns a safe
  503 without internal error details.

## Run

From `C:/Users/Lenovo/Desktop/PROJECTS/PERSONAL/BANTAY`:

```sh
npm run dev
```

In PowerShell use `npm.cmd run dev` if script execution policy blocks `npm.ps1`.
Open `http://localhost:5173`. When both services are already running, the launcher
prints the URLs and exits successfully without starting duplicates. The existing
service terminals remain responsible for those processes. For a newly started
pair, keep the launcher terminal open and stop it with Ctrl+C.

`HAZARD_API_PORT` / `HAZARD_WEB_PORT` are explicit port overrides. Use the same API
port for the client proxy; a running client with another proxy target is rejected
with restart guidance. Normal web cookies still require the chosen frontend host
in Laravel's stateful-domain configuration. No `.env` secret values are printed.

## Verification

Five Node launcher tests cover healthy reuse, refusing an unrelated service,
database-health failure, mismatched proxy ports and a repeated combined launch.
Three PHP health tests cover local identity, safe database failure and no identity
header in production. Full release-gate and normal runtime results are recorded
in [dated evidence](evidence/startup-repair-2026-11-04.json). All **12 checks passed**: **35 backend tests / 221 assertions**, **20 browser tests**, **five launcher tests**, builds, formatting, Android asset synchronization, both dependency audits and documentation checks. Composer startup and Git repository access were verified under the actual Lenovo account.

The normal browser showed Connected, returned HTTP 200 for catalog/statistics/reports/settings and proxied health, fit a 390px viewport and had no page errors. A fresh combined launch and repeated combined/individual commands worked without duplicate processes. BANTAY ownership was restored on 52,955 files/folders with zero failures; newly created verification files were also restored.

An initial browser run discovered the Node tests as browser files and inherited a temporary test port. The Node suite was moved to `scripts/tests`, keeping all five launcher tests in the release gate, and the complete gate was rerun successfully.

The repair does not reset or reseed the existing development database. Tests use
isolated databases. Hosting deployment and real-device/cloud acceptance remain
separate from this local-startup repair.
