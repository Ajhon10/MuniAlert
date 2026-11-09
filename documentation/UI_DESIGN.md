# MuniAlert interface direction

The requested reference is LUMA Store's system typography and liquid-glass
material. BANTAY remains a community safety tool: the map, report status and
reporting action take priority over decorative content.

- Type: LUMA's `-apple-system`, BlinkMacSystemFont, SF Pro, Segoe UI, Helvetica,
  Arial stack. No downloaded fonts. Headings use the same family, tighter tracking
  and clear weight; form controls are at least 16px on phones.
- Palette: ink `#202937`, muted slate `#566173`, mist `#f3f6fa`, safety teal
  `#176b59`, white `#ffffff`, alert amber `#805b17`. Existing severity and workflow
  colors remain semantic, paired with text.
- Material: LUMA's white rim, layered highlights, restrained shadows and backdrop
  blur on navigation and controls. Content surfaces stay nearly opaque for reading.
  Edge refraction is limited to navigation, with preference-aware fallbacks.
- Layout: persistent left workspace navigation on desktop; a five-action bottom
  dock on phones and tablets. The map and report list stay left-aligned and fluid.
  A report action is always reachable; More exposes the complete navigation.
- Desktop utilities share the page-heading row. Popups retain their title and
  actions while only their middle scrolls, including nested confirmations.
- Accessibility: 44px controls, safe-area spacing, keyboard focus, native dialogs,
  reduced motion/transparency, high contrast and no-blur fallbacks.

The UI/UX skill's two design-system searches returned a generic marketing hero
pattern. That pattern was unsuitable for municipal monitoring, so it was not
persisted or applied. Its contrast, semantics and responsive guidance was used;
the explicit LUMA font/material request overrides the suggested fonts and palette.

Review at 320, 375, 390, 768, 1024, 1366 and 1920px. Check actual report submission,
review, confirmation, community comments, account editing and inbox interactions,
including errors and loading, rather than judging screenshots alone.
