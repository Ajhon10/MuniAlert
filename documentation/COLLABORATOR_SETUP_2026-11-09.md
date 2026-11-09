# Collaborator setup audit and fresh-source verification

9 November 2026, Philippine time. The user requested instructions for a teammate downloading the private repository, testing the website/Android and identifying files the owner must send.

## Results

All 16 specifically audited setup/source artifacts were tracked, including both environment examples, lockfiles, startup helpers, database seeding, geographic assets and Android Gradle wrapper JAR/scripts. No required core application source was missing. Real environment files, dependency directories, uploaded evidence and generated builds are intentionally excluded.

The inspected local setup has no frontend environment file/native HTTPS API origin, debug APK, local Android SDK-path file, native Firebase file, configured Firebase push or configured Google/Facebook OAuth. Those optional/native/shared-service items are not needed for basic local email-login/reporting. The guides now state what is generated, what is installed and what a backend maintainer must supply for broader tests.

## Fresh source experiment

A Git source archive of published commit `e8ae7c644efe048bb9a17625b8a48526a5241dfa` was extracted under ignored local cache. It contained no `.git`, original `.env`, installed packages, compiled app or uploaded evidence. On the existing Windows workstation:

1. Example-based environments were created with local test connection settings, a new app key and a separate empty MySQL schema. No owner app key or database dump was copied.
2. Root `npm ci`, server Composer install, locked platform checks, migrations, normal category/department seeding and a frontend production build passed.
3. The real administrator command handler ran with scripted prompt answers. The headless test does not certify Windows interactive password entry; the documented command is intended for a normal interactive terminal.
4. Separate development startup helpers served the clean copy on isolated ports, leaving the normal application servers in place. Proxy/API health passed.
5. At 390 × 844, Chromium registered a citizen, submitted a synthetic report with a confirmed Basista pin and loaded the private uploaded photo without copied storage or a public symlink.
6. At 1440 × 900, a separate administrator session opened a report and saved a response. No browser page errors were recorded.

Screenshots: [phone-sized report](evidence/collaborator-fresh-phone-2026-11-09.png) and [desktop review](evidence/collaborator-fresh-admin-2026-11-09.png). [Structured evidence](evidence/collaborator-setup-2026-11-09.json) records the source/configuration audit and smoke result without credentials.

This verifies a clean application source setup on the existing workstation, not installation of every prerequisite on a fresh OS, physical USB/GPS/camera operation or native APK compilation. Windows hidden-prompt/API-request selector issues in the automation harness were corrected; application source was unchanged.

## Instructions updated

The [root README](../README.md) now has an explicit first-install flow. The [quickstart](COLLABORATOR_QUICKSTART.md) covers Git/ZIP, prerequisites, both environments, MySQL/key/migrations/accounts, website testing, Android Chrome USB and native/shared alternatives. The [file checklist](COLLABORATOR_FILE_CHECKLIST.md) states that no additional owner private file is required for a new local test. Shared/native testing requires a real HTTPS backend address, appropriate test access and, for native use, the matching APK/client configuration.

The client README's stale APK artifact name was corrected to `munialert-android-debug`. Root commands avoid workspace/install confusion; environment copying is guarded, and a public storage symlink is no longer described as necessary for private photos.

The pre-push hook repeats all 14 required workspace checks on the committed documentation state. Final remote commit/privacy/CI results are recorded separately in ignored `.cache/collaborator-publication-result.json`. No collaborator invitations, shared hosting deployment, real incident-data transfer or physical Android/native acceptance was performed.
