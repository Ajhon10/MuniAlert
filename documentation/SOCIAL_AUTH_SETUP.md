# Google and Facebook account creation and sign-in

Implemented for responsive web and the Capacitor Android app using Laravel Socialite. First sign-in creates a citizen account. Subsequent sign-ins use the provider's stable identifier. The existing Vue/Laravel source split and single-origin Hostinger package are preserved.

## Configure provider applications

Set the following **only in `hazard-server/.env`**, outside the web document root. Never use `VITE_` variables for client secrets, commit them, or send them in chat. Empty credentials leave the relevant button unavailable; email registration/login continues working.

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://bantay.example/auth/social/google/callback
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=https://bantay.example/auth/social/facebook/callback
```

Replace `bantay.example` with the actual HTTPS domain. `APP_URL`, `FRONTEND_URLS`, stateful domains, trusted proxy HTTPS handling and secure session cookies must match the deployed origin. Keep session storage shared between redirect and callback requests. The application must be deployed at the origin root, as the current web package expects.

For Google, create an OAuth **Web application** client in the Google Cloud/Google Auth console, configure the consent screen/audience and approved test users, and register the exact Google callback URI above. BANTAY requests identity, email and profile scopes only; no Drive, contacts or offline access. The server also uses Google's S256 PKCE support. For local browser development use one consistent host, for example `http://localhost:5173/auth/social/google/callback`, with BANTAY on localhost:5173 and the API proxy running. Register that exact URI and set `GOOGLE_REDIRECT_URI` to it. Loopback HTTP callbacks are accepted by BANTAY only in local/testing environments. Do not alternate localhost and 127.0.0.1 during a flow because their cookies differ.

For Facebook, create/configure a Meta app with Facebook Login, enable the web OAuth flow, register the exact Facebook callback URI, and request the email permission alongside the basic public profile. Configure the app's website/domain, privacy policy, data deletion instructions and audience/review requirements in the current Meta dashboard. Use an HTTPS development domain for reliable Facebook testing. Until the app has the required audience/access approval, test only with authorized app roles/test users. BANTAY cannot create a new account when Facebook does not return an email; it explains this and offers email registration. Meta's documentation returned HTTP 429 during this implementation review, so verify current dashboard-specific publication/review requirements directly before release.

After configuration, run with the workstation PHP 8.5 runtime:

```powershell
cd hazard-server
& 'C:\php85\php.exe' artisan migrate --force
& 'C:\php85\php.exe' artisan config:clear
```

On Hostinger use the plan's supported PHP binary and deployment config-cache procedure instead of the workstation path. The scheduler's existing `schedule:run` cron also deletes OAuth attempts more than one day past expiration. No persistent OAuth worker is needed.

## Android

Build with `VITE_API_BASE_URL` set to the phone-reachable **HTTPS** Laravel origin, without `/api`. Run the normal frontend build and Capacitor sync. OAuth opens a system browser/custom tab instead of an embedded provider login page. Both providers redirect to Laravel's HTTPS callback; Laravel returns to the fixed `ph.municipal.bantay://auth/callback` app link registered in the Android manifest. Do **not** register that custom scheme as the Google/Facebook OAuth callback.

The app stores a random verifier in native secure storage before opening the browser. The deep link contains a 90-second, single-use opaque code, not a Sanctum token or personal profile. The API requires the matching S256 verifier before issuing the mobile token. Cold launches restore the verifier, exchange the code and resume the intended report/profile screen. Five invalid proofs exhaust a code; replay and expired exchanges fail. Cancel from the BANTAY sign-in screen if the browser was closed without completing sign-in. Rebuild/resync an existing installed app to receive the App/Browser plugins and manifest filter.

## Existing accounts and security

Provider email is never sufficient to merge accounts. For an existing email/password account, sign in normally, open **My profile**, and confirm the current Bantay password before connecting Google or Facebook. A provider identity can belong to only one Bantay account, with one identity per provider on an account. An identity already connected elsewhere is rejected. Password change, logout or native credential revocation invalidates pending link proof. Social-only accounts retain provider sign-in and do not expose an unusable password-change form. Adding a password, disconnecting providers, or cross-provider recovery for social-only accounts is outside this change; do not delete their final sign-in method manually.

Provider access/refresh tokens and remote avatars are not retained. Only the provider and subject identifier are stored. New social accounts have `password_enabled=false` and an unexposed random password hash; native/email password login rejects disabled passwords. Web uses the existing HttpOnly session/remember cookies. Android stores its eventual session only in secure native storage. State remains session-bound in Socialite; errors omit provider tokens and authorization codes. Return origins are allowlisted; the Android return URI is fixed.

## Verification and release acceptance

Automated tests mock provider identity responses, not a person's Google/Facebook credentials. They cover real state rejection/redirect construction, creation and repeat login, email collision protection, linking proof, account ownership, web cookies, Android PKCE exchange/expiry/replay/lockout, and browser UI return handling. The Android browser test simulates the OS bridge; it is not a physical Keystore/deep-link result.

Before release, configure real provider apps and test each on the HTTPS deployment and a physical Android build: new citizen signup, repeat login, consent cancellation, denied/missing email, existing account linking, logout/reopen, killed-app callback and expired retry. Confirm deployment cookies, origin checks, current provider review/branding requirements and the municipality's privacy/deletion process. No real-provider, device, Hostinger or municipal acceptance is implied by local mocks passing.

References: [Laravel Socialite](https://laravel.com/docs/12.x/socialite), [Google server OAuth](https://developers.google.com/identity/protocols/oauth2/web-server), [Google OAuth policies](https://developers.google.com/identity/protocols/oauth2/policies), [Google button branding](https://developers.google.com/identity/branding-guidelines), [Facebook web login](https://developers.facebook.com/docs/facebook-login/web/), [Capacitor Browser](https://capacitorjs.com/docs/v7/apis/browser), [Capacitor App](https://capacitorjs.com/docs/v7/apis/app), [RFC 8252](https://www.rfc-editor.org/rfc/rfc8252.html).
