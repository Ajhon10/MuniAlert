# Test MuniAlert on an Android phone during development

Updated 9 November 2026. Start with the phone's Chrome browser over USB. Test the installed Capacitor app separately for native camera, secure storage and push. Browser emulation and a GitHub push do not establish physical Android acceptance.

## 1. Clone and start the private workspace

Follow [team setup](TEAM_TESTING.md) to clone [Ajhon10/MuniAlert](https://github.com/Ajhon10/MuniAlert), install Node/PHP/Composer dependencies, configure a new development MySQL database and provision your own test accounts. The owner will add members as GitHub collaborators. Use synthetic incidents during testing.

From the workspace root in PowerShell:

```powershell
npm.cmd run dev
```

The launcher selects `C:\php85\php.exe`, starts or reuses the matching API on 8000 and Vite on 5173, and checks their connection. Open `http://localhost:5173` on the PC first. Keep both services running. A port conflict from another workspace must be resolved deliberately; do not start duplicate servers.

## 2. Connect Android Chrome over USB — recommended first test

1. Update Chrome on both PC and phone. Enable **Developer options → USB debugging** on the phone.
2. Connect a data-capable USB cable, unlock the phone and approve its **Allow USB debugging** prompt. Select a USB data/file-transfer mode if the phone is not detected.
3. In desktop Chrome, open `chrome://inspect/#devices` and enable **Discover USB devices**. The phone should appear. Windows may need the phone manufacturer's USB driver.
4. Select **Port forwarding**. Add device port **5173**, destination **localhost:5173**, then enable forwarding. For the packaged Laravel website or direct API debugging, optionally forward **8000 → localhost:8000** too. Ordinary browser API and private-photo requests already use the Vite proxy through 5173.
5. In Chrome **on the phone**, open `http://localhost:5173`. This phone-local URL is forwarded to the PC through the cable. The PC's displayed LAN URL is not the recommended GPS test path.
6. Allow Chrome's location permission and Android's location service when testing GPS. Use the app's manual pin/coordinate flow if GPS is denied or inaccurate. Confirm the incident location explicitly.
7. In desktop `chrome://inspect`, select **Inspect** under the phone's MuniAlert tab to inspect console/network errors. Client edits should update the phone through Vite while the cable and forwarding remain active.

Chrome's [official USB/local-server guide](https://developer.chrome.com/docs/devtools/remote-debugging/local-server) explains forwarding. Localhost is a secure context for browser APIs. Plain HTTP on a Wi-Fi/LAN IP may show layout but is not a reliable secure-context GPS test. For wireless/remote tests, use an approved HTTPS staging site.

If Android platform-tools are already installed, the CLI alternative is:

```powershell
adb devices
adb reverse tcp:5173 tcp:5173
# Optional: packaged website at http://localhost:8000/app/
adb reverse tcp:8000 tcp:8000
```

The device must show `device`, not `unauthorized`. When finished, remove only the mappings you created with `adb reverse --remove tcp:5173` and, if used, `adb reverse --remove tcp:8000`.

## 3. Phone test checklist

| Area | Try | Expected result |
| --- | --- | --- |
| Navigation | Phone portrait/landscape, open More, choose the guide, close the drawer | No header hamburger; bottom More remains the menu; text/actions fit |
| Map | Clear/Streets/Dark, three boundary levels, Fire/Road accident filters, Basista area | Correct filtered markers, legible reference boundaries, retained map preferences |
| Account | Sign up/sign in, close/reopen Chrome, sign out, change password from one device | Valid account persists; logout and revocation work; another citizen's private history is inaccessible |
| Evidence | Safe synthetic camera/gallery images, three-photo limit, cancellation and denied permission | Photos are prepared; errors are readable; no real victim details are collected |
| Location | GPS allowed/denied, poor accuracy, manual pin, move pin after confirmation, switch map style | Original GPS stays separate; edits require reconfirmation; a style change preserves the chosen point |
| Coverage | Choose a Basista barangay; try an outside-reference pin | Exactly 13 barangay choices; a reference-area warning helps review |
| Submission | Disconnect/reconnect, submit once, review reference number and history | No false success during failure; real acceptance has a reference number |
| Review | Administrator verifies/assigns/responds, adds private resolution evidence and reviewed news | Workflow/permissions hold; public summaries do not expose raw evidence |
| Accessibility | Larger Android font/display sizes, zoom, TalkBack and keyboard open/close | Labels and focused controls remain readable and reachable |

For each case record the device/Android/Chrome version, browser or installed-app target, test account role, network, app commit, steps, expected/actual result and a synthetic-data screenshot. Failed cases need a retest after the fix. Never reset a populated application database to clean up testing.

## 4. Installed Capacitor app

The current source uses Capacitor 7, Android SDK Platform/target 35, minimum API 23, JDK 21 and Gradle 8.11.1 with AGP 8.7.3. Match the checked-in files and verify toolchain requirements if upgrading. Read [Capacitor's Android setup](https://capacitorjs.com/docs/v7/android).

1. Install Android Studio, JDK 21, Android SDK Platform 35, build-tools 35.0.0 and platform-tools. Accept SDK licenses and configure `android/local.properties` locally; do not commit a workstation-specific SDK path.
2. Supply a phone-reachable **HTTPS** Laravel API origin. Set the public `VITE_API_BASE_URL` in ignored `hazard-client/.env`, without `/api` or credentials. Allow the native `https://localhost` origin in backend `FRONTEND_URLS`. Do not disable TLS validation or change release traffic to cleartext.
3. From the workspace root:

   ```powershell
   npm.cmd run android:sync -w hazard-client
   npm.cmd run android:open -w hazard-client
   ```

4. Select the USB-connected phone in Android Studio and choose **Run** to install **MuniAlert**. Rebuild/sync and reinstall after changes; `npm run dev` alone does not update bundled native assets.
5. Test native camera/GPS permissions, force-stop/restart/reboot login persistence, logout, private evidence loading and denied-permission recovery. Secure storage uses the pinned Capacitor secure-storage 7.1.3 dependency; browser tests do not certify Android Keystore behavior.
6. Firebase foreground/background push requires separate project configuration, credentials and a running delivery process. In-app saved notifications work without Firebase. Keep `google-services.json`, service-account files and signing keys outside Git.

The private repository also provides a manual **Build Android debug APK** workflow. Supply the approved HTTPS API origin in Actions, then download the `munialert-android-debug` artifact after success. A debug APK and its configured backend must both be reachable/useful to the tester. A signed release APK/AAB is a separate deliverable.

## 5. Troubleshooting and remote members

| Problem | Check |
| --- | --- |
| Phone absent | Unlock/approve USB debugging, data cable, USB mode, Windows driver and Discover USB devices |
| Phone localhost refuses connection | Keep PC launcher running; check the exact forwarded device port; use the same hostname consistently |
| GPS unavailable | Use phone localhost forwarding or HTTPS; enable OS/Chrome location permissions; use manual location as fallback |
| Edits do not appear | Inspect the phone's network tab, reload; installed apps need a new build/sync rather than Vite alone |
| Sign-in/cookies fail | Use the documented localhost hostname, check API/proxy and configured stateful origins; do not share accounts |
| Native app cannot reach API | Use a real phone-reachable HTTPS origin with a valid certificate; PC localhost/LAN assumptions do not create a remote backend |
| Push unavailable | Firebase/device/worker configuration and permission need separate verification |

GitHub stores the private source; it does not host Laravel/MySQL. Remote members should run their own clone with USB forwarding or use an explicitly deployed HTTPS staging environment. No staging deployment or physical-device result is claimed by these instructions.

Normal web sessions use HttpOnly cookies. Native sessions use a Keystore-backed stored credential with rolling expiry. Clearing cookies/app data, uninstalling, revocation or expiry requires sign-in again. Do not mistake a remembered account for offline submission support.
