# Reading, action feedback and motion — 10 November 2026

The user requested toast feedback throughout BANTAY/MuniAlert, animated numbers and a more readable interface for a broad age range, including Android-style accessibility settings. The existing Vue/TypeScript and Capacitor client remains in place.

## Design decisions

Preserve the municipal interface: forest green `#175b44` for reading controls, burnt orange `#b9542d` for reporting, white `#ffffff` reading surfaces, dark green `#263e35` body text and muted green `#4f6259` secondary text. Keep the established system font. Use left-aligned labels and explanations, 16px default body text, relative text units, at least 48px shared action targets and visible keyboard focus. Summary counts are the main animated feature; short dialog fades and toast transitions explain changes without continuous decoration.

The review retained the established map/report hierarchy and glass navigation. It replaced the earlier small fixed text with relative sizes, introduced solid surfaces in high contrast mode, removed button movement and allowed large-text dialogs to use the full phone viewport. Toasts inside dialogs occupy their own bounded region so they do not cover the footer actions.

## Implemented behavior

- Reading controls are available without signing in, through the top bar and complete navigation. Text size: 100%, 115%, 130%, 150%, 175% or 200%. Independent options: bold text, high contrast, more reading space, reduced motion and keeping messages visible. Preferences contain display choices only and use guarded local storage; blocked storage leaves them usable for the current session.
- Relative font sizes reach the existing dashboard, guide, reports, forms, news and administrator components. Large text reflows major grids and phone dialogs. Browser zoom is preserved. The skip link hides by its actual height so enlarged text cannot cover top-bar actions.
- Shared success/error/information toasts cover sign-in/account errors, profile and CMS saves, report creation/update/confirmation/deletion, photo preparation/upload, GPS and location confirmation, news reactions/comments/sharing, push setup, notification read state, exports, explicit refresh and map display actions. Navigation and automatic successful background loads use their existing in-place state, avoiding a notification on every tap or polling interval. Inline form errors remain available.
- Toasts use icons and words as well as color, a polite live region, named dismissal buttons and contextual descriptions. They stay inside the active native modal, remain keyboard reachable and never request focus. Success/information messages dismiss after eight seconds of visible, unfocused/unhovered time. Errors and the keep-visible setting require dismissal. The stack is bounded to the latest four messages; older inline errors remain in their forms. Native form validation reports the first invalid field in a batch. Failed email sign-in returns focus to the email field for correction.
- Dashboard and period-summary numbers animate with locale formatting and tabular digits. Their final value is immediately present for assistive technology. New targets cancel earlier frames; unmount cancels pending work. Device or app reduced motion immediately uses final values and disables CSS motion, map easing and glass effects. High contrast also disables glass refraction.
- Export messages confirm that the file was prepared; they do not claim the operating system saved it. Photo selection messages distinguish preparation from server upload.

## Verification

All 51 isolated browser tests passed, including six new cases for saved/reset preferences, 200% text at 320/375/768/1920px, denied storage, operating-system reduced motion, animated count correctness, error/success feedback, error persistence, hover pause, modal placement/dismissal and enlarged-text landscape controls. Existing citizen/admin, map, guide, news, profile, CMS, session and social-return regressions passed. Vue type checking/production build, combined web package, Android asset sync, documentation consistency and Git whitespace checks also passed. This is not a fresh full release gate. See [the command record](evidence/accessibility-feedback-2026-11-10.json).

Screenshots use fictional demo records and synthetic browser-test content. No normal application database reset, GitHub push or deployment was requested. Physical Android/TalkBack, actual system font scaling, low-vision and older-user usability sessions, municipal acceptance and deployed behavior still require validation. These local checks are not a claim of universal accessibility or a complete WCAG audit.
