# 06 — Citizen and administrator user manual

## Updated controls and recovery — 12 November 2026

Dismiss dialogs using the X in their header. Footers contain actions only. Errors float above the page without reducing form space; dismiss a message or use Go to field to correct the server-reported field. Notifications preview at most two lines: select one to open its full authorized report. Opening an achievement card grows that card only.

Report location uses GPS or a map pin. Pan/tap/drag the map, or choose Place pin at map center, then confirm the location. Raw latitude/longitude inputs are hidden. Content management lets administrators add/edit offices and set each hazard's responsible office; reassign categories before archiving an office. New routing does not rewrite existing report history.

Forgot your password offers Send email reset link or Use recovery code. Email needs a verified deliverable account address and configured municipal mail. Save one-time recovery codes from Settings before losing access. A password reset signs out old sessions and keeps administrator authenticator 2FA required. Google/Facebook sign-in buttons are temporarily hidden.

A news post's Facebook/X/WhatsApp action opens prepared reviewed text. For Facebook, use Copy post & open Facebook, then paste the copied text into the composer. A live preview requires the deployed public HTTPS news URL; localhost cannot be reached by Facebook or other users. Private evidence and exact locations are excluded. See [workflow notes](REUSABLE_WORKFLOWS_2026-11-12.md).

**MuniAlert — Emergency & Public Hazard Reporting** is the shortened application name for **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking**.

## Before starting

Use the municipality's published HTTPS address. The web application supports phone and desktop browsers. A configured Android build can use the same API. A **Demo workspace** banner means the visible incidents are fictional; preview mode does not submit reports.

If someone is in immediate danger, contact local emergency services directly. A municipal report is not an emergency call. The application shows a call link only when a verified local number has been configured.

## Reading and accessibility

Open **Settings** from the header or navigation (under **More** on a phone), then **Reading and accessibility settings**. The user guide is also in Settings. No sign-in is required for these controls.

- Drag the **Text size** slider from 100% to 200%. The preview changes while dragging; release to apply the size to the app. Minus/plus and keyboard arrows adjust it in 5% steps.
- Enable **Bold text**, **High contrast** or **More reading space** for clearer text.
- Enable **Reduce motion** to stop decorative movement and show final totals immediately. The device's own reduced-motion preference is always respected.
- Enable **Keep messages visible** if you need more time. Success/information messages otherwise dismiss after eight seconds, pausing during focus or hover. Errors stay until corrected, cleared or dismissed. Use the close button on each message.
- Signed-in preferences save separately to your account and restore on other devices after sign-in. Guest preferences stay on this device. If account sync fails, your local changes remain and Settings offers **Retry saving preferences**. **Reset reading settings** restores defaults; browser zoom and phone text settings remain available.

Action messages use words and icons as well as color. Validation appears once in a persistent Please check toast. Go to field focuses an invalid field; correct it and retry. Required/type/length constraints and server validation remain enforced. Download feedback confirms file preparation, not that Android or the browser saved it. A photo-prepared message does not mean a report or resolution upload has finished. Messages also appear inside open dialogs without covering their footer actions.

Every password field has a Show/Hide control. It preserves what you typed and does not submit or save anything. Each starts masked, and visibility is not remembered. Registration-mode changes clear the password; backgrounding hides visible passwords. The controls are available in sign-in, registration, profile changes, provider linking, recovery and identity verification.

## Citizen: create an account

1. Select **Sign in**, then **Create an account**.
2. Enter your name, email and a password of at least 12 characters.
3. Submit. A successful login identifies your account in the sidebar.

Passwords are not displayed or returned by the API. Verified-local-email recovery is available only after the municipality enables real email delivery; otherwise use its approved support process. Never share an administrator login with citizens.

## Citizen: report a hazard

1. Tap **Report a hazard** and answer whether someone needs immediate assistance. **Yes** opens searchable approved agency hotlines; **Show closest agencies** optionally uses your location for contacts with saved coordinates. Call if possible, or use **Continue to report online** if you cannot call. Internet is required and dispatch is not guaranteed. **No** continues normally. Sign in if prompted.
2. Choose the hazard category.
3. Take or select one to three photos from a safe position. The client resizes and compresses them before upload. Review the thumbnails; remove an incorrect image with its close button.
4. Choose **Use current location** or **Choose location on map**.
5. With GPS, inspect the reported accuracy and green hazard pin. Use **Adjust pin** if needed. The blue dot retains the original phone location.
6. With manual selection, tap the map or enter known coordinates. A manually pinned report does not require granting GPS permission.
7. Select **Confirm location**. Changing the point invalidates the previous confirmation.
8. Choose one of the 13 Basista barangays, then enter the street/landmark, title, description and apparent severity.
9. Submit. If similar recent hazards appear, use **Same hazard** when appropriate. Otherwise explicitly choose to submit a different hazard and submit again. Review the submission confirmation before sending. Closing the form asks whether to discard it.
10. Wait for a reference number. A network failure or preview message means a real submission has not been confirmed.

Your original description, exact incident location, observations, photos, account identity, device GPS and response history are private to you and authorized municipal administrators. Public views use a separate reviewed summary and approximate area. Avoid including faces, unrelated people, contact details or victim identities even in private evidence. Do not delay calling for emergency help to capture a photo.

## Citizen: follow the response

- Open **My reports**, then select a report to see status notes and timestamps.
- Open the **bell** or mobile **Updates** for your notification dialog. Select an item to open its report in **My reports** and mark only that item read. Notifications has no sidebar page. **Mark all read** changes only your inbox.
- **Enable push** requests notification permission when Firebase is configured. If configuration or permission is missing, the in-app inbox still works.
- Confirm another active report when you observed the same hazard. Repeated taps do not add multiple confirmations.
- Use **Sign out** when finished, especially on a shared device. Android retains a protected device credential across normal application restarts; clearing app data, revocation or expiry requires sign-in again.

## Citizen: news, profile and achievements

Open **Verified news** to read separately reviewed community summaries. Citizens can react, comment, and share using the Share button, Facebook, X, WhatsApp, or a copied link. Comments show your account name. Publication requires recorded factual acceptance and a separate administrator publication of a reviewed public title and summary; verification alone does not publish a report. Rejected and unverified reports are excluded.

Open **My profile** to change your name, email, or password. Password changes require the current password. Bronze, silver, gold and platinum badges are earned at 50, 100, 1,000 and 10,000 of your reports currently in confirmed states. Cash and gift descriptions are municipal offers; the app does not pay or deliver them automatically.

For a report still **Submitted** or **Under verification**, open it and choose **Delete unverified report**. Confirm the deletion. It disappears from listings; an audit record remains. A reporter cannot delete a verified report.

## Administrator: review and respond

1. Sign in using an administrator account provisioned by the maintainer.
2. Use the map, list, search, category and status filters to locate reports. Marker position uses the verified location when available.
3. Open a report and review its description, photos, reporter information and timeline.
4. Compare selected coordinates with phone GPS, accuracy, source and distance. A large difference is a review flag, not an automatic rejection.
5. Move the verified pin if necessary. This does not alter the citizen's original location evidence.
6. Review each required new incident photo under **Private image authenticity review**. Inspect the evidence and independent corroboration, choose a decision, enter notes and confirm inspection. **Not assessed** means no local detector result; no score proves an image or incident genuine/false. Accept supporting evidence only after your review, then use **Fact-check incident**: choose a method, record internal verification notes, confirm that the incident happened and select **Verify and accept incident**. Record a clear response reason for rejection.
7. Choose the responsible department and response team, then mark **Assigned**.
8. Mark **Responding** when the team starts the response, **Resolved** when the hazard has been addressed, and **Closed** after final review.
9. Add a meaningful note for every update. The reporter receives a private notification and the system records an audit entry.

Use **Content management** to set the initial map center and zoom, add approved emergency hotlines, and edit badge descriptions and cash/gift information. Confirm each publication. Hotline changes become visible to citizens immediately. Test the numbers with the municipality before publishing them.

Administrators see operations navigation and cannot submit citizen reports. All roles can edit their profile and see their badges. Philippine time appears in the desktop utilities or the compact screen's content heading.

Closed and rejected records cannot be reopened in this version. The application blocks skipping workflow stages. Responder assignment identifies a team/person by name; it is not a live dispatch or responder-tracking subsystem.

## Filters, exports and maps

Private search matches the original title, reference, barangay or address. Public search matches reviewed public titles, reference and barangay only; it cannot reveal private text through search. Category and status filters affect the current list query. Use page controls to view additional records. **Export page** downloads the displayed result page as CSV; it is not a full database backup. Spreadsheet formula-like fields are escaped.

Base-map roads and labels come from the configured tile provider. Supplied barangay boundaries are an optional overlay. Do not assume a default map center establishes the municipality's jurisdiction.

## Troubleshooting

| Symptom | Action |
| --- | --- |
| GPS denied/unavailable | Allow location on HTTPS, or manually choose and confirm the hazard pin |
| GPS accuracy is poor | Inspect landmarks and adjust the selected pin; preserve the displayed accuracy |
| Map unavailable | Use coordinate entry where offered and contact support; check WebGL and tile connectivity |
| Photo rejected | Select a valid JPEG/PNG/WebP; wait for compression; use a smaller image if necessary |
| Server unavailable | Check connectivity, retry, and do not assume the report was submitted |
| Duplicate suggestion | Confirm the matching report or explicitly submit a different hazard |
| Expired session | Reload and sign in again |
| Push not configured | Continue using Notifications; a maintainer must supply Firebase configuration |
| No real incidents in demo | Exit preview and connect to the configured municipal API |

Screenshots in the documentation evidence folder are generated from clearly labeled demo data. Replace them with approved deployment screenshots only after removing private information.

## Returning to MuniAlert

Sign in once on your personal browser or Android app. Normal closing/reopening retains the account; the default inactivity window is 90 days. Use Sign out on shared devices. Clearing cookies/app data, uninstalling, a revoked credential or prolonged inactivity requires sign-in again. A password change keeps this device signed in and signs other devices out. Existing accounts should sign in once after this update. See [Android testing](ANDROID_TESTING.md) for phone access.

## Sign up or sign in with Google or Facebook

Choose **Continue with Google** or **Continue with Facebook** on the sign-in/create-account form. Allow the provider to share your name and email, then return to MuniAlert. Your first successful sign-in creates a citizen account. On Android, complete this in the system browser; MuniAlert resumes when the browser returns to the app. If you were about to report a hazard, the report form opens after sign-in. Cancel sign-in in MuniAlert if you closed the browser.

Unavailable buttons mean the municipality has not enabled that provider; use email. If the provider does not share an email, allow email access or register using email. If your email already belongs to an account, sign in using its existing method. For an email/password account, open My profile and confirm your MuniAlert password under Connected sign-in accounts before connecting a provider. Social-only accounts continue using their original provider; manage that provider's password through its own account settings.

## Incident observations, resolution and period records

In **A few details**, enter incident time only if known, choose whether injuries are observed (or **Not sure / Hindi tiyak**) and optionally estimate the number of people affected. Unknown is different from zero or “none observed.” Do not include victim identity or clinical diagnoses. These observations remain private. Call emergency services before trying to gather evidence when someone is in danger.

Administrators can use **Add resolution photos** on assigned/responding/resolved reports. Select up to three photos, wait for compression and choose **Upload resolution photos**. These remain separate from the three incident photos. The reporter sees both labeled evidence groups in their private report; public news contains no raw evidence. Add action taken in the private response update. Do not make unsafe photography a prerequisite for field response.

Verification alone does not publish news. In a fact-checked report, use **Create community news post**, write the public title/summary and review the incident-detail preview and submitter credit. Confirm the privacy review, then choose **Publish news post**. Use **Unpublish news post** to withdraw it. Keep contact details, victim identities, injury details and precise addresses out of the narrative. Private evidence is never attached to the public post.

Use **Incident summary** in the admin navigation (on a phone, **More → Incident summary**). Set dates/category and weekly or monthly grouping, then update the summary. **Export summary** covers the complete filtered period; **Export page** remains limited to the displayed report page. Dates use Philippine time. Records without a supplied incident time use their submission time and are counted explicitly as a fallback. Counts describe reports, including unverified reports, not confirmed casualties or unique incidents.

## In-app feature guide and searchable manual

Open **Settings → Open guide and user manual** (on a phone, use **More → Settings**). The page is also linked by `?view=guide`. Choose **Citizen guide** or **Administrator guide**, then open a topic or search for GPS, photos, privacy, updates or summary exports. A no-match message lets you clear the search and recover. Use **Back to settings** to return. The manual text is bundled with the application and can be read even if API requests fail after the app loads; this does not imply offline submission or offline web installation.

Choose **Start feature tour** for a highlighted walkthrough of the overview, reporting/metrics, filters, map, report list and account area. Use Back/Next, Skip tour, the close button or Escape. The final step returns to the manual. The tour reads the interface and does not submit or modify a report. On the web, **Print / save PDF** expands the currently displayed topics for printing; clear the search first to print the selected audience’s complete instructions. Standalone exports are available as the [citizen manual PDF](MUNIALERT-CITIZEN-MANUAL.pdf) and [administrator manual PDF](MUNIALERT-ADMIN-MANUAL.pdf).


## Map styles, boundaries and hazard types

On phones/tablets, **More** in the bottom dock opens the full navigation. The header has no second menu button. Its Settings/account actions remain available while reading, with a solid background. Desktop utilities sit beside the page heading without a separate empty header row.

Beside the map, choose **Clear**, **Streets** or **Dark**. Open **Boundaries** to switch the Basista barangays, Basista/nearby municipalities or Pangasinan province outlines. Labels appear as you zoom in. These are reference boundaries, not proof of legal jurisdiction. Mode and boundary choices are remembered on this device.

Use **Show hazard type** to see only Fire, Road accident or another category. This stays synchronized with the report category filter; status and search still apply. **Show all hazard types** clears only the category choice. **Fit reports** fits the currently loaded page; **Basista area** returns to the covered area. Map counts and page controls identify partial pages rather than implying every record is shown at once. If map content fails, use **Retry map**, another mode, or the report list.

Administrators should follow [the false-report review guide](FRAUD_PREVENTION.md). Sign-in, rate limits and evidence review reduce abuse but do not prove a report genuine. Local-email verification/recovery and operator suspension are implemented; actual enrollment policy/delivery still need approval. Phone verification is not implemented. Local image screening requires a configured/evaluated classifier and explicit administrator image review; no detector is currently configured. Do not delay urgent assistance while completing documentary review.

## Reading and completing popups

Sign-in, reporting, report details and confirmations keep their title/close button at the top and actions at the bottom. Scroll the middle to reach the remaining fields or history. **Submit hazard report**, **Save response update**, confirmation choices and **Close** remain reachable without scrolling to the end. Required fields still need completion before a form can be submitted. A confirmation's **Go back** returns to the form underneath. The feature tour follows the same layout: its explanation scrolls while the title and Back/Next controls remain visible.

## Administrator: authenticator and recovery codes

Before initial setup, the authorized municipal operator verifies your identity and opens a short enrollment window. Sign in with your existing password or connected provider, scan the private QR code with your authenticator (or enter the setup key), then enter a fresh code. Save the ten recovery codes privately, outside the phone. Each code works once. Never share the setup key, QR code, password or recovery codes in a report/comment.

Later sign-ins need the six-digit authenticator code or a saved recovery code. Paste a complete code into the first cell or type the six digits; keyboard arrows move between cells. Choose **Use a recovery code** for a backup code. If a code was just used, wait for a fresh code. Expired setup needs a new sign-in and, if the operator window expired, fresh operator approval. **Settings → Account security & email** shows unused-code count and lets you replace codes after verification. Sensitive administrative changes may ask for your password/code again; your pending edit is retained. Cancelling verification does not save it.

If municipal email delivery is enabled, verify your local email from **Settings → Account security & email**. Enable **Report update emails** there if you want emails with the reference and status of your own reports; this is off by default. Private notes, original evidence, exact locations and reporter contact details are not emailed. Sample addresses cannot receive mail. **Forgot your password?** requests a link only for an eligible verified account. Links work once for 30 minutes. A password reset signs out other credentials and retains administrator MFA. Provider-only accounts use their provider's recovery process. If the phone and recovery codes are lost, contact approved municipal support for independently verified operator recovery. Do not create another administrator or bypass verification.

## Dashboard background, reading and saved login

Dashboard replaces the Overview/Operations navigation label. Administrators open Content management → Dashboard background to preview an approved JPG/PNG/WebP up to 1 MB and 1920 pixels per side, then confirm publication. Everyone sees the background on the dashboard; use an approved image without personal information or incident evidence. Discard preview cancels the draft; Remove background restores the default. High contrast hides decoration on that device.

Reading & accessibility now uses a text-size icon and aligned label above the sidebar community card. Page entry fades/headings and count animations run unless system/app Reduce motion is enabled. Closing/reopening retains signed-in access on the device while cookies/app storage remain. Explicit Sign out revokes that device; password resets, suspension and other security changes can also revoke access. Restored administrators still verify identity for sensitive changes. The in-app guide and both printable manuals contain these instructions.

## Private reports, separate publication and hazard collections

Members use **My reports** for every undeleted submission across all eight statuses, including pending, accepted and rejected records. Clear status/search/category filters to see the full collection. **Dashboard**, **Hazard map** and nearby suggestions contain only incidents fact-checked and accepted by the municipality. The redundant community **Verified hazards** menu is removed; old report-list links open the hazard map. Administrators retain **Review reports**. Other members cannot see your unreviewed submissions. Public counters do not show the private queue.

Administrators open **Review reports**, inspect the original evidence and location, then complete **Fact-check incident** and confirm **Accept incident**. Internal verification notes are for authorized administrators. Use the independent **Create community news post** form for a public title/summary, check the public preview and privacy confirmation, then select **Publish news post** and confirm. Acceptance alone does not create a news post. **Unpublish news post** removes the feed/share page; prior external copies may remain. Accepted closed reports can also have their separate news post managed.

Verified news shows the reviewed narrative, submitter credit, category, barangay, priority, incident time when known, reporting/verification dates, responsible office and reference number. New posts include the submitter's display name in the preview; administrators can uncheck that option for safety. Existing anonymous posts stay anonymous until explicitly reviewed. The display name and incident time are publication snapshots. Contact information, full account identity, exact addresses, original evidence and internal review notes remain private. Feed and shared HTML use the same public fields.

In **My profile → Hazard achievement collection**, each of ten categories has milestones at 1, 5 and 15 accepted own reports. Progress is private; confirmation taps, pending/rejected/deleted/other-user reports do not contribute. Expand **Explore three milestones** to read creative earned/upcoming badges. Overall municipal reward definitions remain separate; no automatic payment is promised. Report from a safe distance.

## Sounds, nearby agencies and floating help

Administrators tap **Enable sound alerts**, then use **Settings → Hazard sound alerts** to choose per-category chime, pulse or urgent patterns, set volume, test and mute. A browser restart may need another enable tap. Old unread reports do not ring during initial synchronization. Sounds require an open app and browser/device audio permission; they are attention cues, not dispatch guarantees.

Urgent assistance requests are private reporter flags. Open the report from the bell, then **Contact agencies & responders**. Approved numbers and **Copy responder brief** remain available before factual/image checks. The brief includes reference/category/area and selected or verified incident coordinates; review it before sharing with an authorized agency. It excludes private photos, identity and notes. Nothing is automatically forwarded.

In Content management, add approved agency numbers with service/area/address and optional paired coordinates. Closest means straight-line distance to the saved agency location. No contacts are currently published in the localhost database; publish approved contacts before public use. Do not use fictional test numbers as real hotlines.

The bottom-right **Help tour** button launches the highlighted walkthrough above the phone dock. The searchable manual remains in Settings. Closing/Escape returns keyboard focus to the launcher. See [implementation and service readiness](EMERGENCY_NOTIFICATIONS_IMAGE_REVIEW_2026-11-11.md).
