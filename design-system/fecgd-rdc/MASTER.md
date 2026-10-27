# FECGD-RDC visual system

Reviewed 21 October 2026. Existing PHP templates and CMS content remain authoritative.

## Direction

Institutional federation, grounded in the actual Congolese meetings and the three waste professions. Separate the introduction from the photograph for legibility. Use the official logo, not a generated replacement. No invented results, partners, or membership figures.

Palette: forest #123e2e, official blue accent #073c9e, ink #182e24, muted #52665b, light green surface #f2f6f1, existing lime accent #d8ef87. Retain Manrope headings and DM Sans body from the site, with Arial fallbacks. Restrained section headings, short line lengths and clear primary actions.

Layout: 1240px maximum width, 48px desktop side margins, 18px on phones. Split desktop hero and stacked mobile hero. Direct navigation to actual published activity pages. Mixed editorial news grid; large first illustrated story, no invented placeholder photos for text-only articles. Consistent article gallery, forms, FAQ and footer.

## Interactions and verification

Minimum 44px primary controls; keyboard focus in blue. Native validation retained, field errors connected with aria-describedby. Menu Escape returns focus only if the menu was open. Dialog Escape must keep its own focus restoration. Slow existing marquees pause on hover/focus and respect reduced motion. Only a short initial hero entrance is added; no scroll-hidden content.

Assets use content hashes in their URLs. New public assets are copied by cms/export.php and included by the safe public packager. Never package private database, requests, credentials or old backups.

## Skills and sources

- UI UX Pro Max, already installed: https://github.com/nextlevelbuilder/ui-ux-pro-max-skill — local recommendations used for layout, focus, responsive and motion review. Its generic suggested palette did not match the established identity and was not adopted.
- 21st UI Build and UI Review, already installed: https://github.com/21st-dev/skill — integration and QA workflow. Public catalogue consulted at https://21st.dev/community/components ; no component code downloaded, no proprietary component copied and no hosted generation used. CLI unavailable in this workspace.
- Frontend Design, newly installed globally: https://github.com/anthropics/skills/tree/main/skills/frontend-design — official SKILL.md and LICENSE.txt retrieved directly after the installer full-repository download stalled. Used for hierarchy, subject-specific imagery, restraint and visual critique.

GitHub stars indicate repository popularity, not per-skill active usage or an objective design rating. Implementation is original project-native PHP/CSS/JavaScript. No new frontend framework or animation dependency.
