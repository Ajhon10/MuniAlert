# KIKOS narrator and flush mobile header

9 November 2026, Philippine time. The user requested KIKOS Order Planner's narrator and removal of the empty strip above the compact header.

## Narrator replacement

The reference project uses the generic **Kokoro `af_heart`** preset with `kokoro-js` 1.2.1, Transformers.js 3.8.1 and the q8 `onnx-community/Kokoro-82M-v1.0-ONNX` CPU model. The installed voice binary and model hashes match the reference project. No KIKOS application/customer data or existing narration was copied; the reference project was read only. Seven new MuniAlert clips replace the Windows voice. Privacy/guide wording was shortened to fit without rushing.

The generator, authored script, provenance and license are documented in [narration reproduction](../promo-video/NARRATION.md). Large model files stay in ignored cache. The source gate checks every WAV's bytes, text, preset and duration. A reviewed Sharp 0.35.5 override resolves the new transitive audit findings; CI omits optional CUDA downloads for the CPU workflow while retaining all tests/audits.

## Mobile spacing

The former compact header forced a 10px top margin and sticky offset. Both are removed. The header attaches to the viewport's top edge and contains genuine device safe-area padding within its surface. Desktop utilities retain their existing page-heading placement.

The browser checks assert `y = 0` and full viewport width at 320, 390, 709, 768 and 1023 pixels, plus scrolled-guide readability and desktop regression behavior. [Phone screenshot](evidence/mobile-header-flush-2026-11-09.png) uses fictional preview records; physical Android inset/keyboard behavior remains a separate acceptance task.

## Latest exports and verification

| Export | Dimensions | Duration | Bytes | SHA-256 |
| --- | --- | --- | --- | --- |
| Portrait | 1080 × 1920 | 60.011 s | 22,394,130 | `49aa1ba4889c58d9c696d00c980359393fdfeb002206b40e4c9335ed78104472` |
| Landscape | 1920 × 1080 | 60.011 s | 17,123,984 | `cf8318d1a6bea0a33341bc3224df571be226e80fce5e94013d6cd902c9999624` |

Both contain 1,800 H.264 frames at 30 fps with AAC audio. Decoded peak is -2.64 dBFS and mean level is -22.08 dBFS. Both files differ from the preceding exports. A decoded-signal comparison confirms that all seven scenes contain the new WAV narration at their intended start times; correlation exceeds 0.97 despite background music. Chromium playback/seeking and editable Studio checks pass. Eight samples per format were reviewed, and the poster was refreshed.

Local MP4s remain in ignored `promo-video/out`, while the private repository contains their editable source and generated narration/captures. Preview: `http://localhost:3030/MuniAlertPortrait` or `/MuniAlertLandscape`.

All **14 local checks passed**: **65 backend tests / 502 assertions, 45 browser tests, five launcher tests**, builds, synchronization, documentation/source checks, narration integrity and clean dependency audits. [Dated evidence](evidence/narrator-mobile-2026-11-09.json) records results. The pre-push hook repeats the full gate; final private commit/CI verification is recorded in the ignored local receipt `.cache/narrator-header-publication-result.json`.

These are source, browser and encoded-media results. No hosting deployment, external video posting, signed Android release or physical-device acceptance was performed.
