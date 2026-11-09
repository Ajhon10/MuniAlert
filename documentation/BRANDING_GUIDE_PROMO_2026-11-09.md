# MuniAlert identity, guidance and promotional-video handover

Updated 9 November 2026, Philippine time. Implementation began 8 November. These are local implementation and verification results; deployment, physical Android testing and municipal acceptance remain separate.

## Application identity

The short application name is **MuniAlert**, with subtitle **Emergency & Public Hazard Reporting**. The user-selected capstone title is **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking**.

The web title, dashboard branding, authentication messages, Android display label and current capstone documents use the new identity. The full title appears in the guide's **About this capstone**, its footer and the video closing scene. `hazard-client/src/config/identity.json` holds the client identity. Android package ID, callback scheme, credential-storage keys and the `bantay_session` cookie remain stable for compatibility. Existing `SESSION_COOKIE` overrides remain valid. The workspace folder stays named BANTAY.

## Feature guide and user manual

Open **Guide & user manual** in navigation, **More** on a phone, or the header help button. Local review URL: <http://localhost:5173/?view=guide>.

- Feature cards explain photo reports, confirmed locations, private evidence, response updates and administrator records, linking directly to the relevant topic.
- The six-step highlighted tour covers the overview, reporting/metrics, filters, map, reports and account controls. Back, Next, Skip, close, Escape and replay are supported; the final step opens the manual.
- Citizen/admin topic selection, search, collapsible instructions and no-results recovery cover actual implemented behavior. Public access to administrator instructions grants no administrator permissions.
- The bundled instructions remain readable if API requests fail after the app loads. Offline reporting, draft persistence and offline web installation are not claimed.
- The browser's **Print / save PDF** action expands the currently displayed instructions. Clear search first to print the selected audience's complete manual. Native Android uses the readable in-app manual.

Instructions explain safe photography, GPS/manual location confirmation, duplicate checks, unknown injury observations, private evidence, reviewed community summaries, tracking, resolution photos, period records and troubleshooting. They distinguish a saved report from an emergency call and explain that assignments do not automatically dispatch an outside agency. Current [user manual](06-user-manual.md), [citizen manual PDF](MUNIALERT-CITIZEN-MANUAL.pdf), [administrator manual PDF](MUNIALERT-ADMIN-MANUAL.pdf) and [printable appendix](CAPSTONE-TECHNICAL-DOCUMENTATION.pdf) are included. Both short PDFs were exported directly from the bundled in-app instructions.

## Promotional deliverables

| Deliverable | Format | File |
| --- | --- | --- |
| Phone/social portrait | 1080 × 1920, 30 fps, 60 s | [Portrait MP4](../promo-video/out/MuniAlert-portrait.mp4) |
| Presentation landscape | 1920 × 1080, 30 fps, 60 s | [Landscape MP4](../promo-video/out/MuniAlert-landscape.mp4) |
| Portrait poster | 1080 × 1920 PNG | [Poster](../promo-video/out/MuniAlert-poster.png) |
| Editable source | React/TypeScript, seven connected Remotion scenes | [Video project](../promo-video/README.md) |

Editable local previews: [portrait](http://localhost:3030/MuniAlertPortrait), [landscape](http://localhost:3030/MuniAlertLandscape), and [guide scene](http://localhost:3030/Guide). From `promo-video`, use `npm.cmd run dev` to restart Studio. No external publication or hosting is required.

The video introduces reporting, confirmed incident locations, privacy, response progress, weekly/monthly records and the guide. Locally generated narration and original synthesized music accompany clear on-screen text. A **Demonstration data** label identifies synthetic examples. Actual Vue screens were captured through read-only synthetic API fixtures; API writes were blocked. No real incident records, credentials, victim photos or personal contact information were used. See [asset provenance](../promo-video/public/screens/manifest.json) and [video architecture](../promo-video/ARCHITECTURE.md).

The application remains Vue/TypeScript/Capacitor plus Laravel/MySQL. React and Remotion are confined to video authoring, as requested.

## Local verification

The final app gate and screenshots are recorded in [dated evidence](evidence/branding-guide-promo-2026-11-09.json). The gate covers Composer validation, PHP formatting, five launcher tests, Vue type checking, production and single-origin builds, backend/browser tests, Android asset synchronization, dependency audits and documentation consistency. Guide browser coverage includes 320–1440 px reflow, full title, manual search/audience changes, API-unavailable reading, complete tour controls and signup/provider-return navigation to the guide.

All **12 checks passed** on the final application state: **61 backend tests / 487 assertions, 36 browser tests and five launcher tests**, zero npm findings and no Composer advisories. Locked platform requirements passed on PHP 8.5.1. Both repositories passed whitespace checks. Lenovo ownership was restored on **76,868 files with zero failures**; sampled access permissions were unchanged. A separate running-app guide check passed at 320px with simulated 200% text enlargement. [Phone guide screenshot](evidence/munialert-guide-390-2026-11-09.png) and [highlighted tour screenshot](evidence/munialert-tour-2026-11-09.png) use demonstration content.

Video lint/type checking and dependency audit passed with matched Remotion 4.0.541 packages. Both completed H.264/AAC exports contain 1,800 video frames, approximately 60.011 seconds of container duration including AAC padding, stereo audio, and a measured peak of -2.68 dBFS. Seven representative frames from each layout were visually reviewed. Narration fits within every scene. Studio loaded both formats and an individual editable scene without browser errors. Both final MP4s played in Chromium and sought to the closing scene without media errors. [Export checks](../promo-video/out/verification.json), [audio timing](../promo-video/out/audio-timing.json), [playback checks](../promo-video/out/playback-verification.json) and [preview checks](../promo-video/out/preview-verification.json) preserve the results.

Remotion's sparse-frame sequence review encountered a Windows audio-mixing `EINVAL`; standalone still reviews and full continuous MP4 rendering succeeded. Output verification decodes PCM directly because Remotion's matched FFmpeg does not include the optional `volumedetect` filter. Neither issue required changing application behavior or suppressing checks.

## Acceptance remaining outside this work

No signed APK/AAB, physical Android camera/GPS/accessibility acceptance, live OAuth/R2/Firebase/Hostinger validation, production workload test or municipal UAT is claimed. No GitHub push, app deployment or video publication occurred. The original interview/screenshot assessment and its corrected scope remain available in the [8 November review](INTERVIEW_ALIGNMENT_2026-11-08.md).
