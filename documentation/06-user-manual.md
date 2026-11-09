# 06 — Citizen and administrator user manual

**MuniAlert — Emergency & Public Hazard Reporting** is the shortened application name for **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking**.

## Before starting

Use the municipality's published HTTPS address. The web application supports phone and desktop browsers. A configured Android build can use the same API. A **Demo workspace** banner means the visible incidents are fictional; preview mode does not submit reports.

If someone is in immediate danger, contact local emergency services directly. A municipal report is not an emergency call. The application shows a call link only when a verified local number has been configured.

## Citizen: create an account

1. Select **Sign in**, then **Create an account**.
2. Enter your name, email and a password of at least 12 characters.
3. Submit. A successful login identifies your account in the sidebar.

Passwords are not displayed or returned by the API. This version has no self-service password-reset or email-verification flow; use the municipality's approved support process for account problems. Never share an administrator login with citizens.

## Citizen: report a hazard

1. Tap **Report a hazard**. Sign in if prompted.
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
- Open **Notifications** for updates. **Mark all read** changes only your inbox.
- **Enable push** requests notification permission when Firebase is configured. If configuration or permission is missing, the in-app inbox still works.
- Confirm another active report when you observed the same hazard. Repeated taps do not add multiple confirmations.
- Use **Sign out** when finished, especially on a shared device. Android retains a protected device credential across normal application restarts; clearing app data, revocation or expiry requires sign-in again.

## Citizen: news, profile and achievements

Open **Verified news** to read separately reviewed community summaries. Citizens can react, comment, and share using the Share button, Facebook, X, or a copied link. Comments show your account name. Publication requires a confirmed report and an administrator's reviewed public title and summary; verification alone does not publish a report. Rejected and unverified reports are excluded.

Open **My profile** to change your name, email, or password. Password changes require the current password. Bronze, silver, gold and platinum badges are earned at 50, 100, 1,000 and 10,000 of your reports currently in confirmed states. Cash and gift descriptions are municipal offers; the app does not pay or deliver them automatically.

For a report still **Submitted** or **Under verification**, open it and choose **Delete unverified report**. Confirm the deletion. It disappears from listings; an audit record remains. A reporter cannot delete a verified report.

## Administrator: review and respond

1. Sign in using an administrator account provisioned by the maintainer.
2. Use the map, list, search, category and status filters to locate reports. Marker position uses the verified location when available.
3. Open a report and review its description, photos, reporter information and timeline.
4. Compare selected coordinates with phone GPS, accuracy, source and distance. A large difference is a review flag, not an automatic rejection.
5. Move the verified pin if necessary. This does not alter the citizen's original location evidence.
6. Move **Submitted → Under verification → Verified**; document the reason when rejecting an unverified/verified report.
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

Verification alone does not publish news. In a confirmed report, select **Publish a separate community summary**, write a privacy-reviewed public title and summary, then save the response. Remove the check and save to unpublish. Avoid names, injury details, precise addresses and other identifying information. Private evidence is never attached to the public summary.

Use **Incident summary** in the admin navigation (on a phone, **More → Incident summary**). Set dates/category and weekly or monthly grouping, then update the summary. **Export summary** covers the complete filtered period; **Export page** remains limited to the displayed report page. Dates use Philippine time. Records without a supplied incident time use their submission time and are counted explicitly as a fallback. Counts describe reports, including unverified reports, not confirmed casualties or unique incidents.

## In-app feature guide and searchable manual

Open **Guide & user manual** from the navigation, the help button, or **More** on a phone. The page is also linked by `?view=guide`. Choose **Citizen guide** or **Administrator guide**, then open a topic or search for GPS, photos, privacy, updates or summary exports. A no-match message lets you clear the search and recover. The manual text is bundled with the application and can be read even if API requests fail after the app loads; this does not imply offline submission or offline web installation.

Choose **Start feature tour** for a highlighted walkthrough of the overview, reporting/metrics, filters, map, report list and account area. Use Back/Next, Skip tour, the close button or Escape. The final step returns to the manual. The tour reads the interface and does not submit or modify a report. On the web, **Print / save PDF** expands the currently displayed topics for printing; clear the search first to print the selected audience’s complete instructions. Standalone exports are available as the [citizen manual PDF](MUNIALERT-CITIZEN-MANUAL.pdf) and [administrator manual PDF](MUNIALERT-ADMIN-MANUAL.pdf).


## Map styles, boundaries and hazard types

On phones/tablets, **More** in the bottom dock opens the full navigation. The header has no second menu button. Its guide/account actions remain available while reading, with a solid background. Desktop utilities sit beside the page heading without a separate empty header row.

Beside the map, choose **Clear**, **Streets** or **Dark**. Open **Boundaries** to switch the Basista barangays, Basista/nearby municipalities or Pangasinan province outlines. Labels appear as you zoom in. These are reference boundaries, not proof of legal jurisdiction. Mode and boundary choices are remembered on this device.

Use **Show hazard type** to see only Fire, Road accident or another category. This stays synchronized with the report category filter; status and search still apply. **Show all hazard types** clears only the category choice. **Fit reports** fits the currently loaded page; **Basista area** returns to the covered area. Map counts and page controls identify partial pages rather than implying every record is shown at once. If map content fails, use **Retry map**, another mode, or the report list.

Administrators should follow [the false-report review guide](FRAUD_PREVENTION.md). Sign-in, rate limits and evidence review reduce abuse but do not prove a report genuine. Verified contact enrollment, account suspension and automated image/fraud detection are not implemented. Do not delay urgent assistance while completing documentary review.

## Reading and completing popups

Sign-in, reporting, report details and confirmations keep their title/close button at the top and actions at the bottom. Scroll the middle to reach the remaining fields or history. **Submit hazard report**, **Save response update**, confirmation choices and **Close** remain reachable without scrolling to the end. Required fields still need completion before a form can be submitted. A confirmation's **Go back** returns to the form underneath. The feature tour follows the same layout: its explanation scrolls while the title and Back/Next controls remain visible.
