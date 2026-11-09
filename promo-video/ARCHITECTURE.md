# Promotional authoring decisions

The user requested Remotion, then explicitly selected **MP4 plus editable preview**. React and TypeScript are used solely for that video artifact, with Remotion 4.0.541 and separate lockfile dependencies. The application remains a separate Vue/TypeScript/Capacitor client and Laravel/MySQL API. No database, backend framework, hosting service or external publishing account is needed for the video.

The seven scene components have individual JSX nodes and connected composition registrations, inline editable defaults, deterministic frame-based animation and one-second premounting. Both video formats reuse the same scene implementations with adaptive layout. A separate poster freezes the opening scene at an intentional visible frame.

Screens and audio are local assets. Preview/rendering never reads app credentials, private environment files or real evidence. Screenshot generation uses synthetic route fixtures and rejects API mutations; it does not populate or reset the development database. Asset provenance and output verification are recorded alongside the project.

Visuals use the app's existing shield motif and green/cream palette. Narration and on-screen copy describe implemented behavior, identify demonstration data and distinguish reporting from emergency calls. There are no fabricated response-time, Play Store availability, clinical, workload, cloud-delivery or municipal-acceptance claims.

Delivery is local MP4 files plus Studio source/preview. Publication, cloud hosting, alternate languages, music replacement and physical-device acceptance are separate future choices; none blocks the requested local deliverables.
