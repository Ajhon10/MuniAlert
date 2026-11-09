# MuniAlert promotional video

An editable 60-second Remotion promotion for **MuniAlert — Emergency & Public Hazard Reporting**.

Full capstone title: **Municipal Emergency and Public Hazard Reporting System with Photo and Location-Based Incident Tracking**.

The user explicitly requested both MP4 exports and an editable preview. This authoring project is separate from the Vue/Laravel application and does not change its stack.

## Deliverables

- `out/MuniAlert-portrait.mp4` — 1080 × 1920, 30 fps, 60 seconds, H.264 with audio.
- `out/MuniAlert-landscape.mp4` — 1920 × 1080, 30 fps, 60 seconds, H.264 with audio.
- `out/MuniAlert-poster.png` — portrait poster.
- Remotion Studio: `http://localhost:3030/MuniAlertPortrait` or `/MuniAlertLandscape`.
- Seven editable connected scenes under **Editable-scenes**; each scene has editable headline/supporting text and its own timeline.

## Run or edit

Use Node compatible with the package engine and the included lockfile:

```sh
npm ci
npm run dev
```

Studio starts without opening a system browser on port 3030. Open its printed URL. Edit the compositions or open individual scenes in Studio; source changes are reflected in the preview.

```sh
npm run lint
npm run review
npm run render
npm run poster
npm run verify:output
```

`render:portrait` and `render:landscape` export one format. `review` uses standalone still rendering to inspect eight representative frames in each format, covering all seven scenes plus the Basista map transition. Sparse-frame sequence rendering with per-frame audio mixing produced an `EINVAL` error during review on this workstation; still rendering avoids that review path. Complete continuous MP4 rendering, including narration/music, is checked separately. Use Remotion's matched browser by default; `MUNIALERT_BROWSER_EXECUTABLE` is an optional override.

On this managed workstation, Remotion's configuration loader needs the normal Lenovo user context because sandbox profile traversal is denied. Normal Lenovo terminals can use the commands above. No Git ownership exception is required.

## Story and source material

| Scene | Length before transition overlap | Message |
| --- | --- | --- |
| Opening | 7 s | Introduce MuniAlert |
| Reporting | 11 s | Safe photos, details and confirmed incident location |
| Privacy | 8 s | Original evidence stays private; public summaries are separate |
| Tracking | 11 s | Review, assignment, response notes and resolution |
| Records | 9 s | Weekly/monthly summaries with clear count limitations |
| Guide | 10 s | Highlighted tour and searchable user manual |
| Closing | 7 s | Full capstone title and emergency-call reminder |

Six half-second fades overlap the scenes, producing exactly 60 seconds.

`scripts/capture-ui.mjs` captures the actual Vue interface with **synthetic API fixtures**, blocks API writes and records asset hashes in `public/screens/manifest.json`. Fictional names use `example.test`; no credentials, real victims or production incident records enter the assets. The video carries a **Demonstration data** label. These are interface demonstrations, not evidence of deployment, device validation or response performance.

Narration uses the same local **Kokoro `af_heart`** preset as KIKOS Order Planner, as requested by the user. `npm run narration` generates all seven clips; `npm run narration:offline` reuses the ignored local model cache. See [narration sources and reproduction](NARRATION.md). `scripts/generate-music.py` creates the original synthesized instrumental; no sampled or third-party recordings are used. `npm run check:narration` validates the preset/clip hashes and actual WAV durations against their scene windows.

Regenerating screenshots requires the parent BANTAY workspace's Playwright installation/browser and the application running on port 5173. Previewing and rendering the already captured video do not require the app server, an API, a database or cloud credentials.

## Dependencies and verification

The project uses React/TypeScript and matching Remotion 4.0.541 packages, as required for the requested Remotion authoring workflow. The scaffold's development-tool audit finding was resolved with a reviewed `minimatch` 11.0.1 override; lint/type checks and audit verify compatibility. No audit check is disabled.

`verify:output` decodes the exported audio and checks duration, frame count, dimensions, codecs, audio levels, narration provenance and file hashes in `out/verification.json`. The matched FFmpeg omits `volumedetect`; direct PCM measurement avoids relying on that unavailable filter. Final exports contain 1,800 video frames each and approximately 60.011 seconds of container duration (AAC padding). Measured levels belong to the current export's verification record.

See [the project handover](../documentation/BRANDING_GUIDE_PROMO_2026-11-09.md) for final results. No promotion was posted externally, and no app deployment or signed Android release is implied.

Rendered MP4s and review outputs in `out/` stay local and are excluded from the source repository. After cloning, use the render commands above to recreate both downloadable formats. The checked-in synthetic screens, narration, music and Remotion source support the editable preview without access to municipal data.

The 9 November follow-up refreshed all nine UI captures and both MP4s with Basista locations, a fire-filter/boundary map view and fixed dialog headers/footers. The opening identifies Basista's 13 barangays. `verify:output` checks capture hashes alongside the encoded outputs; see [the current handover](../documentation/MODALS_HEADER_VIDEO_2026-11-09.md) for results and limitations.
