# Fixed modals, shared desktop heading and refreshed MP4s

9 November 2026, Philippine time. The user requested fixed popup headers and footers, a scrolling body, removal of the remaining empty desktop header row and refreshed promotional videos.

## Interface changes

Sign-in/registration, hazard reporting, report details and confirmations now share a native dialog with three regions. The header and footer do not scroll; the middle is an independently scrolling, keyboard-focusable region. The outer dialog clips overflow so photo selection or focus changes cannot scroll its chrome away. Submit buttons remain associated with their forms and preserve browser validation. Confirmation cancellation restores the form underneath; a shared scroll lock handles nested dialogs. The highlighted tour uses the same region pattern.

Desktop utilities now sit beside the page heading instead of reserving a separate row. Compact screens keep their existing opaque sticky header and bottom More navigation. The viewport requests content resizing for an Android onscreen keyboard; this is not a physical-device acceptance result.

Visual evidence: [desktop heading](evidence/modal-header-desktop-2026-11-09.png) and [fixed phone reporting actions](evidence/modal-report-fixed-actions-2026-11-09.png).

## Refreshed promotion

Both MP4s were re-rendered with nine current synthetic UI captures. Locations are in Basista; the opening identifies its 13 barangays, and the reporting scene now transitions to a fire-filter/boundary map view. Reporting and tracking show the fixed action footers. The video remains a demonstration and does not claim live dispatch or a released Android app.

| Export | Dimensions | Video/audio | Duration | Size | SHA-256 |
| --- | --- | --- | --- | --- | --- |
| Portrait | 1080 × 1920 | H.264 / AAC, 30 fps | 60.011 s | 22,374,505 bytes | `88a0115512b3fb68a899fe102fdd85b298d98c7d80165f6143a1645a4b3e208b` |
| Landscape | 1920 × 1080 | H.264 / AAC, 30 fps | 60.011 s | 17,044,771 bytes | `8b6560bd348dcacdc51f09e000856e4bc12bd834e5063d3cbdfd148ed0721de3` |

Both contain 1,800 frames. Decoded audio peak is -2.68 dBFS and mean level is -21.68 dBFS. Metadata/audio checks pass, both files differ from the earlier exports, and Chromium played and sought each file to the new map transition. Eight samples per format were reviewed across all seven scenes and the added map view. Studio loaded portrait, landscape and the editable Guide scene without page errors.

Local files are `promo-video/out/MuniAlert-portrait.mp4` and `promo-video/out/MuniAlert-landscape.mp4`. Editable preview: `http://localhost:3030/MuniAlertPortrait` or `/MuniAlertLandscape`. The private source repository excludes generated MP4s; use its Remotion render commands to recreate them.

## Verification and publication

All **14 local checks passed**, including **65 backend tests / 502 assertions, 45 browser tests and five launcher tests**. Dependency audits reported zero npm findings and no Composer advisories. Browser coverage includes photo focus, fixed regions, nested confirmation return/discard, native validation, short landscape/resized viewports, enlarged tour text, heading placement and the municipal review lifecycle. Both manual PDFs and the technical appendix were updated.

[Dated evidence](evidence/modals-header-video-2026-11-09.json) separates local verification, encoded video review and external acceptance. The pre-push hook repeats the full gate on the committed state. Final private GitHub commit/privacy/CI checks are recorded separately in the ignored local receipt `.cache/modal-video-publication-result.json`.

Physical Android keyboard/camera/GPS behavior, signed APKs, cloud/hosting delivery and municipal acceptance remain pending. No hosting deployment or external promotional posting was performed.
