# MuniAlert narration

The user requested the narrator from KIKOS Order Planner. MuniAlert now uses the same **Kokoro `af_heart`** generic synthetic preset, `kokoro-js` **1.2.1**, Transformers.js **3.8.1**, and the **Kokoro-82M-v1.0-ONNX q8** CPU model. The installed voice binary and cached model match the reference project's hashes. The reference project was read only; all MuniAlert lines were newly synthesized.

Run `npm run narration` inside this folder, or `npm run narration:offline` after the model is cached. Speech runs locally; no paid voice API or personal voice cloning is used. The first uncached run downloads public model files. Model weights stay in the ignored parent `.cache/kokoro-tts/` directory and are excluded from source publication.

Linux CI installs the CPU authoring dependencies with `ONNXRUNTIME_NODE_INSTALL_CUDA=skip`. This disables only ONNX Runtime's optional CUDA download; the requested CPU inference, source checks and dependency audits remain enabled. Rendering/previewing the supplied clips needs no model download.

`scripts/narration.json` is the authored script. Generation stages all seven clips before replacing the old audio, checks each line against its scene/fade window, keeps speed between 1.02 and 1.16, and normalizes peaks without changing pitch. The shorter privacy/guide wording keeps the requested voice comfortable within the existing 60-second film. Current generated metadata and hashes are in `public/audio/narration-manifest.json`. `npm run check:narration` validates the actual WAV duration, script, preset binary and clip hashes; the workspace gate includes this check.

Sources: [Kokoro JavaScript publisher](https://github.com/hexgrad/kokoro/tree/main/kokoro.js), [ONNX model card](https://huggingface.co/onnx-community/Kokoro-82M-v1.0-ONNX). The package/model identify Apache-2.0; the [included license](public/licenses/Kokoro-Apache-2.0.txt) accompanies the generated narration assets.

The video-only dependency override pins Transformers.js's image helper to Sharp 0.35.5 because the older transitive version triggers current dependency advisories. The [publisher's advisory](https://github.com/advisories/GHSA-wq5f-xc86-pv6w) identifies 0.35.5 as patched. This does not change the Kokoro model or preset. Offline generation, source checks, rendering and audits validate the resulting authoring setup.
