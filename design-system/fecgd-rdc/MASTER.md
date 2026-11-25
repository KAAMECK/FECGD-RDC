# FECGD-RDC visual system

Reviewed 4 November 2026. Existing PHP templates and CMS content remain authoritative.

## Direction

Institutional federation, grounded in the actual Congolese meetings and the three waste professions. Separate the introduction from the photograph for legibility. Use the official logo, not a generated replacement. No invented results, partners, or membership figures.

Palette, revised with the user's explicit freedom to choose colors: forest #124b3a, institutional blue #174c81, navy #173e5b, ink #193a30, muted #4e655d, sage #d1e5ca and light surface #eff5f1. White remains the main reading background. Retain Manrope headings and DM Sans body from the site, with Arial fallbacks; the administration uses Segoe UI/system fonts. Restrained section headings, short line lengths and clear primary actions. The official logo artwork remains unchanged.

Layout: 1240px maximum width, 48px desktop side margins, 18px on phones. Split desktop hero and stacked mobile hero. Direct navigation to actual published activity pages. Mixed editorial news grid; large first illustrated story, no invented placeholder photos for text-only articles. Consistent article gallery, forms, FAQ and footer.

## Shared identity — 4 November 2026

`cms/assets/identity.css` is the last visual layer for public PHP pages and the administration. It owns the shared palette and scoped `.site-ui` / `.admin-ui` rules. Public page introductions use navy panels with white text, a sage edge and balanced typography; the home hero keeps its real group photograph and a solid forest primary action. Photographs remain unaltered. Use sage for supporting actions and pale surfaces for facts, not indiscriminate bright lime accents. The forest footer groups navigation and official coordinates; the administrative sidebar echoes it, with a sage active marker and blue focus indication. Preserve semantic colors for request statuses.

Avoid adding new font downloads, JavaScript frameworks, decorative gradients or scroll-hidden text. Manrope weights and letter spacing carry the stronger hierarchy. Corners vary by role (7px controls, 12–16px panels); outline and white space carry structure. Only existing, subtle movement remains, and reduced motion is respected.

Skills applied: Web Design Pro, Frontend Design, UI UX Pro Max, Web Interface Guidelines, Accessibility and Webapp Testing. UI UX Pro Max proposed a Swiss/minimal institutional direction; adopted hierarchy, spacing and low motion, then adapted the generic palette to the green/blue official logo. No 21st component code was copied; CLI unavailable, existing project components reused. Vercel review reference: https://raw.githubusercontent.com/vercel-labs/web-interface-guidelines/main/command.md .

## Interactions and verification

Minimum 44px primary controls; keyboard focus in blue. Native validation retained, field errors connected with aria-describedby. Menu Escape returns focus only if the menu was open. Dialog Escape must keep its own focus restoration. Slow existing marquees pause on hover/focus and respect reduced motion. Only a short initial hero entrance is added; no scroll-hidden content.

Assets use content hashes in their URLs. New public assets are copied by cms/export.php and included by the safe public packager. Never package private database, requests, credentials or old backups.

Private inbox filters (27 October 2026): retain search as the main visible control, with advanced criteria inside a native details/summary disclosure, collapsed on entry and after submission. Show applied values and an active-criteria count outside the hidden controls. Keep stable defaults (inbox, all statuses, all messages, no dates, newest first) and an explicit reset. Advanced fields use three columns on wide screens, two at intermediate widths, one on phones; primary controls remain at least 44px tall. No external component or animation dependency added.

## Skills and sources

Client institutional presentation — 4 November 2026: rename navigation to “Qui sommes-nous ?” while retaining `/federation/`. Use the established photo, typography, forest/blue tokens and a restrained two-column introduction. Place verified identity facts beneath, followed by a native anchor index and readable sections. Collapse to one column on mobile, reserve photo space and offset anchors from the header. Empty official sections stay absent from public navigation and content; list them in the private administration instead. Official phone appears only once provided and validated, with tel: links and optional explicit WhatsApp. No invented dates, board members, promises or contact numbers. Original PHP/CSS only; 21st public catalogue consulted, no external component code copied or new animation dependency added. Skills: Web Design Pro, UI UX Pro Max, Accessibility and Webapp Testing. UX search: institutional page section navigation — active state, anchor navigation and unobscured headings.

- UI UX Pro Max, already installed: https://github.com/nextlevelbuilder/ui-ux-pro-max-skill — local recommendations used for layout, focus, responsive and motion review. Its generic suggested palette did not match the established identity and was not adopted.
- 21st UI Build and UI Review, already installed: https://github.com/21st-dev/skill — integration and QA workflow. Public catalogue consulted at https://21st.dev/community/components ; no component code downloaded, no proprietary component copied and no hosted generation used. CLI unavailable in this workspace.
- Frontend Design, newly installed globally: https://github.com/anthropics/skills/tree/main/skills/frontend-design — official SKILL.md and LICENSE.txt retrieved directly after the installer full-repository download stalled. Used for hierarchy, subject-specific imagery, restraint and visual critique.

GitHub stars indicate repository popularity, not per-skill active usage or an objective design rating. Implementation is original project-native PHP/CSS/JavaScript. No new frontend framework or animation dependency.


## Article and video presentation — 28 October 2026

Use the shared articles.css/articles.js layer and article-layout.php. Preserve existing green/blue tokens and PHP rendering. Desktop: restrained headline and media in two columns; text below in a reading column with a sticky utility rail. Mobile: text, media, then compact actions. Display the first native video once in the introduction, keep controls/preload=none/playsinline, never autoplay, provide visible loading/error/playback status, and pause on page hiding. The database content stays intact. Copy-link is progressive enhancement with an announced result; controls remain usable without JavaScript. Related links are the latest three other published items of the same kind, not a semantic recommendation engine. Contained posters/montages remain uncropped; photo viewing uses the existing native dialog.

Skills applied: Web Design Pro, UI UX Pro Max, Frontend Design, 21st UI Build/Review. UI UX query: video controls keyboard accessibility — focus and keyboard guidance applicable; skip link already present. CLI unavailable, so review performed through source inspection and browser QA.

Public reference consulted: https://21st.dev/@mohammadshehadeh/components/video-player — Mohammad Shehadeh / Hirael, MIT; reference for visible playback state and native-equivalent controls only. No component code copied, no React, motion or icon library added. Original implementation retains native HTML video controls. HTML media behavior checked against https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/video .

QA: nine page/viewport combinations (1440, 768, 390), real video playback and duration, pause/status, one video per article, related links, JavaScript errors, no-JS fallback. Copy-link confirmed in the user's browser. Review gallery images after decoding when taking full-page screenshots, to avoid mistaking lazy placeholders for missing images.
