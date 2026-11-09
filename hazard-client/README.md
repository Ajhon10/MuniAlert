# hazard-client

Vue 3, TypeScript, Vite, MapLibre, and Capacitor Android client for MuniAlert. It lives in the `hazard-client` folder of the private Ajhon10/MuniAlert workspace; the Laravel API is in the sibling `hazard-server` folder. Start with [team setup](../documentation/TEAM_TESTING.md) for one-clone installation.

## Local development

Requires Node 22.12+ and a running `hazard-server` on `http://127.0.0.1:8000`.

```sh
npm ci
cp .env.example .env
npm run dev
```

On PowerShell, use `Copy-Item .env.example .env`. Open `http://localhost:5173`. Vite proxies API, auth, and storage requests to the local server on port 8000. `?demo=1` shows fictional read-only records without the API.

The development command reuses an existing client from this workspace instead of failing when its port is occupied. From the parent BANTAY folder, prefer `npm run dev` to start/check both API and web together. A server from another project on that port is reported and left untouched.

Run `npm run typecheck` and `npm run build` to verify the client. For Android, configure a reachable HTTPS `VITE_API_BASE_URL` before `npm run android:sync`.

Citizens see hazard reporting, confirmed-report news, comments/reactions, emergency hotlines, and their profile/badges. Administrators see an operations workspace with report review and **Content management** for hotlines, default map center, and badge/reward descriptions. The header clock uses Philippine time. The app confirms report submission, discarding/deleting a report, logout, and publishing edits. Cash/gift details are display-only until the municipality fulfills them through a separate process.

Keep `.env`, Firebase configuration, signing keys, generated builds, and dependencies out of Git. Use the separate `hazard-server` setup guide for MySQL and administrator creation.

## Dedicated Android app

The `android/` project is an installable Capacitor app with its own app ID (`ph.municipal.bantay`), launcher icon, camera, location, and notification permissions. The native app uses the same citizen and administrator accounts as the web app. It is a separate installed application, not a browser shortcut.

Install Android Studio with its bundled JDK and Android SDK Platform 35. In `.env`, set `VITE_API_BASE_URL` to the **phone-reachable HTTPS origin** of `hazard-server` (for example, `https://api.your-domain.example`). Do not use `localhost` or `127.0.0.1`: on a phone, those refer to the phone itself. Keep the API available while testing. For native requests, the server's `FRONTEND_URLS` must allow `https://localhost`, which the example server configuration already includes.

```sh
npm ci
npm run android:sync
npm run android:open
```

In Android Studio, connect an Android phone with USB debugging enabled, select it, then click **Run** to build and install the debug app. Alternatively, `npm run android:run` builds and deploys from the command line once the SDK and device are ready. The app appears on the phone as **MuniAlert**. To make a shareable debug APK, use Android Studio's **Build APK(s)** action. A release APK/AAB needs a private signing key and deployment testing.

The native app's web assets are bundled during `android:sync`; `npm run dev` is for the browser workflow. Firebase push notifications need a configured Firebase project and `android/app/google-services.json`; the in-app Notifications view works without push delivery. No APK has been built or tested on a physical phone in this workspace yet.

Once this repository is on GitHub, its **Build Android debug APK** manual workflow can build an installable test APK without a local Android SDK. In the repository's **Actions** tab, run that workflow with the public HTTPS API origin. Download the `bantay-android-debug` artifact from the completed run. This workflow has not run yet; a debug APK is for testing, while a production release needs private signing and device validation.

## Responsive layouts and saved sign-in

Desktop uses fluid map/list panels; phones show report cards, stacked filters and larger touch controls. Web login survives browser closing through HttpOnly cookies. Android tokens are saved through native secure storage, not browser localStorage; normal app restarts retain the session. Defaults allow 90 days of inactivity. Explicit logout removes the saved credential; a password change revokes other devices. Sign in once after installing this update.

To test the running website without an APK, connect Android over USB debugging. In desktop Chrome, open chrome://inspect/#devices and forward device 5173 to localhost:5173 and 8000 to localhost:8000. Open http://localhost:5173 in phone Chrome. For an installed APK use the Android Studio workflow above with JDK 21 and SDK 35. Secure storage is pinned to 7.1.3 for the existing Capacitor 7 / Gradle 8.11.1 setup, with AGP 8.7.3. Physical-device validation remains required.

Android is the primary device. Reporting includes simple Filipino hints, explicit unknown injury observations and optional incident time/affected estimates. Original evidence and exact locations are private; public maps use approximate areas and separate reviewed text. Admins can upload resolution evidence and use **More ? Incident summary** for complete weekly/monthly records and CSV. Asset synchronization and browser emulation do not establish physical Android acceptance.
