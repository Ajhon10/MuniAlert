export type ManualAudience = 'citizen' | 'admin';
export type ManualSection = { id: string; audience: ManualAudience | 'all'; title: string; summary: string; steps: string[]; note?: string; keywords?: string };

export const manualSections: ManualSection[] = [
  { id: 'getting-started', audience: 'all', title: 'Sign in and find your way around', summary: 'Your account, navigation and the preview workspace.', steps: [
    'Use Sign in or the account button. Create a citizen account with your name, email and password. Google or Facebook sign-in is available only when enabled by the municipality.',
    'On a phone, use Home, Map, Report, Updates and More. More opens the complete navigation, including this guide. Administrator navigation shows Review and Manage instead of citizen reporting.',
    'In a popup, scroll the middle to read all fields or details. The title, close button and bottom actions stay visible. Use Go back to cancel a confirmation without losing the form underneath.',
    'A Demo workspace banner means the records are fictional. Exit preview before sending a real report. A report is accepted only when the system returns a reference number.',
    'Stay signed in on your own device. Use Sign out on shared devices; clearing app data, revocation or session expiry requires a new sign-in.',
  ], keywords: 'account login register navigation menu android demo preview google facebook' },
  { id: 'reporting', audience: 'citizen', title: 'Send a report with photos', summary: 'A category, safe evidence and a clear description.', steps: [
    'If anyone is in immediate danger, call an approved emergency hotline first. Do not wait to finish this form or take photographs.',
    'Tap Report a hazard and choose the incident category, such as Road accident, Flooding, Fire or Fallen tree.',
    'Take or add one to three safe incident photos. The app prepares each photo to fit the upload limits. Avoid faces, visible injuries, personal documents and other identifying details.',
    'Choose and confirm the incident location, then choose one of Basista’s 13 covered barangays and enter the street or landmark. The location section below explains GPS and manual entry.',
    'Enter a clear title and description. Supply incident time only if known. Choose whether injuries are observed, or Not sure / Hindi tiyak. Enter people affected only as an optional estimate; leave unknown values blank.',
    'Review any similar reports, then submit and confirm. Wait for a reference number. Open My reports to follow the saved record.',
  ], note: 'Submitting an app report does not guarantee emergency dispatch. Stay safe and contact emergency services directly when needed.', keywords: 'submit camera picture image photo accident sunog baha puno report ulat' },
  { id: 'map-display', audience: 'all', title: 'Make the map clearer', summary: 'Map styles, Basista boundaries and hazard-type filters.', steps: [
    'Choose Clear for fewer details, Streets for more roads and landmarks, or Dark for a darker background. The selected map style and boundary switches are remembered on this device.',
    'Open Boundaries and choose Barangays, Cities / municipalities or Provinces. The local layer names the 13 covered Basista barangays. City outlines provide Basista and nearby context; the province outline is Pangasinan. These published reference boundaries do not establish official jurisdiction.',
    'Use Show hazard type beside the map to show only Fire, Road accident, Flooding or another category. The selector shares the report filter. Show all hazard types clears this category choice; existing status/search filters still apply.',
    'Basista area returns to the service-area view. Fit reports brings the current report page into view. A report count and, when needed, map-page controls show which matching records are loaded.',
    'Zoom in to read small area labels. Pins, original GPS and confirmed incident locations stay in place when changing map style. A map/network error offers Retry map or Use Streets; it does not confirm a report submission.',
  ], keywords: 'map clear streets dark style mode boundaries barangay city province basista fire accident filter layers' },
  { id: 'location', audience: 'citizen', title: 'Confirm the incident location', summary: 'Use phone GPS, adjust the pin or enter a location manually.', steps: [
    'Choose Use current location and allow location access if you want to use your phone GPS. Check the accuracy shown; your current position may be different from the incident location.',
    'Choose Adjust pin when the incident is elsewhere. Tap the map or move the pin, then check the barangay and nearby landmarks.',
    'If GPS is denied or unavailable, choose Choose location on map. Place the incident pin, or enter the selected latitude and longitude if you know them.',
    'Tap Confirm location. Changing the pin clears that confirmation, so confirm again before submitting. Original phone GPS, the selected incident pin and later verified location are kept separately.',
  ], note: 'A public map marker is an approximate area. Use the private report details for a precise incident location.', keywords: 'gps permission denied coordinates latitude longitude mapa lokasyon accuracy manual pin' },
  { id: 'duplicates', audience: 'citizen', title: 'Check a similar nearby report', summary: 'Help the municipality avoid duplicate incident records.', steps: [
    'When a recent similar hazard is nearby, the form shows a suggestion before submission.',
    'If it is the same incident, choose Same hazard to record your confirmation. This does not submit another incident report.',
    'If it is a separate incident, select This is a different hazard; submit a new report, then continue.',
  ], keywords: 'same duplicate nearby confirmation similar' },
  { id: 'tracking', audience: 'citizen', title: 'Track updates and resolution', summary: 'Your reference, timeline, notifications and resolution photos.', steps: [
    'Open My reports and select your report. Its timeline shows municipal status changes and response notes.',
    'The usual stages are Submitted, Under verification, Verified, Assigned, Responding, Resolved and Closed. Rejected reports include a municipal note. A saved report or a verified status does not itself guarantee dispatch.',
    'Incident photos and resolution photos are shown as separate private groups. Response notes explain action taken; only the reporter and authorized administrators can view private details.',
    'Open Updates or Notifications for your inbox. Enable push if offered; delivery depends on permission, connectivity and the municipality enabling the service. The inbox remains the place to check saved updates.',
    'Only your own unverified report can be deleted from the interface. Deletion removes its listing; retention and records handling remain the municipality’s responsibility.',
  ], keywords: 'status timeline history notifications inbox resolution resolved closed deleted tracking' },
  { id: 'privacy', audience: 'all', title: 'Understand what stays private', summary: 'Original evidence and exact incident details are protected.', steps: [
    'Original report titles and descriptions, precise landmarks and coordinates, injury observations, reporter identity, incident photos and resolution photos are private to the reporter and authorized municipal administrators.',
    'Community maps use approximate areas. Public news uses a separate title and summary reviewed by an administrator. Verifying a report does not automatically publish its original description or images.',
    'Comments on public news show the commenter’s account name and text. Do not post victim names, injury details, contact information or other private records in comments.',
    'There is no automatic face blurring or automatic removal of personal information. Avoid collecting unnecessary identifying details, even in private evidence.',
  ], keywords: 'privacy private public photos identity name gps evidence consent comment news' },
  { id: 'review', audience: 'admin', title: 'Review, verify and coordinate a response', summary: 'Inspect private evidence, priority, location and team assignment.', steps: [
    'Open Review reports and choose a record. Inspect the incident category, time if supplied, private observations, photos, barangay and landmark.',
    'Compare the original GPS, selected pin, accuracy and distance. A large difference is a review prompt, not proof that a report is false. Set independent verified coordinates without replacing original evidence.',
    'Move from Submitted to Under verification, then Verified or Rejected, using a clear response note. Verification requires a verified location.',
    'Select the responsible department and enter an assigned responder or team before moving to Assigned. Move to Responding as the documented response progresses, then Resolved and Closed.',
    'Record action taken and coordination with other offices in response notes. Notes are private to the reporter and administrators. Closed and rejected records cannot be reopened in the current workflow.',
  ], note: 'Emergency calls and field response must not wait for documentary verification. Assignment in the app does not automatically contact an outside agency.', keywords: 'verification admin priority department police rhu rescue assignment responder severity' },
  { id: 'suspicious-reports', audience: 'admin', title: 'Review a suspicious report', summary: 'Reduce false reports through evidence and documented review.', steps: [
    'Keep a new report Under verification while checking the description, category, photos, time and location evidence. A photo or GPS reading alone cannot prove authenticity.',
    'Check nearby reports and response records. Repeated photos or large GPS differences are prompts to review, not automatic proof of fraud. Manual pins can be legitimate.',
    'Use the municipality’s approved contact or field-confirmation process. Verify only when supported by the evidence standard; record a specific note when rejecting an editable unverified or verified report.',
    'Do not publish raw evidence or unreviewed claims as news. Public map entries can still be awaiting verification, so check each record’s status.',
    'Sign-in, submission limits, duplicate suggestions and audit records are implemented. Verified-email/phone enrollment, account suspension and automatic image/fraud detection are not implemented. Urgent assistance must not wait for the documentary process.',
  ], keywords: 'fraud false fake spam suspicious abuse verification duplicate evidence reject' },
  { id: 'resolution', audience: 'admin', title: 'Add resolution photos', summary: 'Document action taken with a separate evidence group.', steps: [
    'Open an Assigned, Responding or Resolved report and find Add resolution photos.',
    'Select up to three resolution photos in total for that report. These are separate from the three incident photos. Wait for preparation to finish, then choose Upload resolution photos.',
    'Check that the saved photos appear under Resolution photos. Add action taken in the response note when updating the report.',
  ], note: 'Resolution photos stay private. Do not delay assistance or require unsafe photography to complete a field response.', keywords: 'resolution after evidence upload photo action taken remarks' },
  { id: 'publication', audience: 'admin', title: 'Publish a reviewed community update', summary: 'Create a public summary without exposing original evidence.', steps: [
    'Open a report in a confirmed status: Verified, Assigned, Responding, Resolved or Closed. Publication edits must be completed before a terminal record is closed.',
    'Select Publish a separate community summary. Write a public title and summary that leave out names, injury details, precise addresses and identifying information.',
    'Save and confirm the response update. Only the separate reviewed text is eligible for public news; original descriptions and photos remain private.',
    'To unpublish an editable confirmed report, remove the publication check and save. Rejection also clears publication text. Review public comments separately.',
  ], keywords: 'publish news community summary unpublish privacy moderation' },
  { id: 'analytics', audience: 'admin', title: 'Summarize weekly or monthly records', summary: 'Date filters, category filters and complete summary exports.', steps: [
    'Open Incident summary. On a phone, open More to find it.',
    'Choose From date and To date, a weekly or monthly grouping, and an optional incident category. Select Update summary. A range may cover up to 366 calendar days.',
    'Read dates in Philippine time. Weeks start Monday; the first or last period may cover only part of a week or month.',
    'Incident time is used when supplied. Otherwise, submission time is used and the fallback count is shown. Totals include unverified reports and exclude rejected or deleted records.',
    'Choose Export summary for every matching record in the selected period. Export page on the report list covers only the displayed result page.',
  ], note: 'Counts describe reports, not confirmed casualties or necessarily unique incidents. A change in counts does not establish why accidents occurred.', keywords: 'weekly monthly summary analytics csv export statistics dates count period records' },
  { id: 'settings', audience: 'admin', title: 'Manage approved municipal information', summary: 'Hotlines, map starting location and badge descriptions.', steps: [
    'Open Content management or Manage. Set the starting map location and zoom for your municipality.',
    'Enter only locally approved emergency service names and phone numbers. Verify the numbers with the municipality before publishing them.',
    'Save and confirm changes. Badge and reward descriptions are informational; payment or gift fulfillment is handled separately by the municipality.',
  ], keywords: 'cms settings manage hotline phone emergency map badge reward' },
  { id: 'troubleshooting', audience: 'all', title: 'Fix common reporting problems', summary: 'Connection, GPS, photo, map and account recovery steps.', steps: [
    'No connection: keep the form open and retry when connected. Do not assume a report was accepted without a reference number. Unsent forms are not automatically saved or submitted in the background.',
    'GPS denied or inaccurate: use a manual pin or known coordinates and confirm the incident location. If map tiles do not load, use available coordinate fields and an accurate landmark.',
    'Photo error: choose JPEG, PNG or WebP, wait for preparation and try a smaller image if needed. You can attach up to three incident photos, each prepared to at most 1 MB and 1920 pixels.',
    'Session expired: sign in again. Disabled Google or Facebook buttons mean the municipality has not enabled that provider; use an available sign-in method.',
    'No push alert: check Notifications and your phone permissions. Push is optional and its availability depends on the municipality enabling it.',
  ], keywords: 'offline retry error failed connection network denied permission gps photo login push help' },
];

export const guideFeatures = [
  { id: 'reporting', icon: 'camera', title: 'Photo-based reports', description: 'Share a category, safe photos and clear incident details.', audience: 'citizen' },
  { id: 'location', icon: 'location', title: 'Confirmed locations', description: 'Use GPS or a manual pin, then confirm where it happened.', audience: 'citizen' },
  { id: 'tracking', icon: 'timeline', title: 'Updates you can follow', description: 'See response notes, status history and resolution photos.', audience: 'citizen' },
  { id: 'privacy', icon: 'shield', title: 'Private original evidence', description: 'Precise details and photos are limited to authorized viewers.', audience: 'all' },
  { id: 'review', icon: 'review', title: 'Organized report review', description: 'Verify location, set priority and assign a responsible team.', audience: 'admin' },
  { id: 'resolution', icon: 'camera', title: 'Resolution evidence', description: 'Keep action-taken photos separate from incident photos.', audience: 'admin' },
  { id: 'analytics', icon: 'chart', title: 'Weekly and monthly records', description: 'Filter periods and export a complete record summary.', audience: 'admin' },
  { id: 'publication', icon: 'news', title: 'Reviewed community news', description: 'Publish a separate summary without exposing original evidence.', audience: 'admin' },
] as const;
