# What does the owner need to send a collaborator?

Checked 9 November 2026 against the private repository and local configuration presence. No secret values were included in this audit.

**For a fresh local website plus Android-Chrome test, send the public repository link (visibility confirmed on 12 November 2026) and [quickstart](COLLABORATOR_QUICKSTART.md). No additional private owner file is required.** The teammate installs dependencies, creates configuration from examples, uses their own MySQL account/database, generates their own app key and creates their own test accounts.

## Included versus intentionally excluded

| File/data | Included in GitHub? | What a teammate does / what to send |
| --- | --- | --- |
| Vue/Laravel source and startup scripts | Yes | Clone or extract the repository; run commands from its root |
| Root/client npm lockfiles and server `composer.lock` | Yes | Use root `npm ci` and server Composer install |
| Database migrations and category/department seeder | Yes | Create an empty schema, then migrate/seed; no owner's SQL dump needed |
| Both `.env.example` files | Yes | Copy to local `.env` files and fill local values |
| Real `.env` files and `APP_KEY` | No, intentionally | Generate their own new local key; do not send the owner's complete `.env` |
| PHP `php.ini`, MySQL service and Windows PATH | PC prerequisites | Configure these on the teammate's machine; they are not application files to copy from the owner |
| `node_modules` and `hazard-server/vendor` | No, intentionally | Install from the lockfiles; do not transfer these PC-specific folders |
| User accounts, submitted reports and uploaded evidence | No, intentionally | Fresh database starts empty; create synthetic test accounts/reports |
| Basista/Pangasinan boundaries, UI assets and manuals | Yes | Use directly; live basemap tiles still need network access |
| Android source, manifests, icons and Gradle wrapper JAR/scripts | Yes | Native source is complete; install the local build toolchain |
| `android/local.properties` | No, intentionally | Android Studio creates a machine-specific SDK path |
| APK/AAB and build/dist folders | No, intentionally | Build locally or obtain an approved test APK; a source download is not an installed app |
| Native Firebase `android/app/google-services.json` | No, intentionally | Only for configured native push: obtain the test Firebase Android config for `ph.municipal.bantay` |
| Firebase service-account JSON / R2 keys / OAuth client secrets | No, intentionally | Backend maintainer configures controlled test services; do not place server secrets in an APK, client env or repository |
| Release signing keystore/password | No, intentionally | Keep with the release maintainer; teammates can build debug apps without the owner's release key |
| Promo MP4s / speech-model cache | No, intentionally | Not needed to run/test the app; editable source, generated narration and captures are included |
| `.git` directory in a GitHub ZIP | No, normal ZIP behavior | Use `git clone` for updates/contributions/full publication checks; never request the owner's `.git` folder |

The private local storage directory for new evidence is created as needed. Authenticated photo access does not require an owner's public-storage symlink or copied uploads.

## Current optional items not configured/provided

The audit found no configured native HTTPS API origin, built debug APK, Android `local.properties`, native Firebase configuration, web/FCM push configuration, or Google/Facebook OAuth credentials in the checked local setup. These are not missing core source files. Email/password login, local private photos, reports, maps, review and database notifications can be tested without these integrations.

The local maintainer has a backend `.env` and installed dependencies. Those are deliberately excluded. A frontend `.env` is currently absent locally because browser defaults work; the included frontend example is sufficient for a teammate to create one.

## If the tester should use a shared website or phone-only APK

The owner must provide:

1. The **actual reachable HTTPS test website/API address** and confirmation that its backend/database are running. The repository URL is not that address.
2. Separate assigned citizen/admin test accounts as appropriate. Send test passwords through a private channel; do not put them in instructions or Git. Shared test data should be synthetic.
3. For native testing, the **matching debug APK** (or a successful private Actions artifact link) built for that HTTPS API. The current root workflow artifact name is `munialert-android-debug`.
4. If push/social login/R2 are in the test scope, have the backend maintainer configure the dedicated test services and give client builders only the required client test configuration. Native Firebase config is different from a server service-account private key.

No shared staging deployment or native APK has been created by this documentation update. GitHub Pages cannot run this Laravel/MySQL backend. A shared Hostinger deployment remains separate work under [the hosting guide](HOSTINGER_WEB_CLOUD.md).

## What not to send for an ordinary local test

Do not send production database exports, real incident photos/reporter records, the owner's `.env`/app key, server service-account keys, release-signing keys, browser cookies/tokens or shared administrator credentials. None is needed for an independent new development database. If shared historical test records are necessary later, prepare a deliberately anonymized/synthetic dataset or use controlled staging access instead of copying live municipal data.
