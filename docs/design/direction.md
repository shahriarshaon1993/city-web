# Design direction — premium, 2026 (visual source of truth)

**Authority.** This file is the visual source of truth for the public site. It wins over every other file in `docs/design/` and over the look described in CMP-* entries (DEC-49). Behaviour, data and accessibility still come from `docs/spec/`. It condenses the owner-approved decisions DEC-44…48; if one of those is changed, change this file in the same session.
**Scope.** Public website only. The admin keeps `design/admin.md` (shadcn-vue defaults, compact density).
**Living reference.** `/` (home) and `/styleguide` show every rule below in code. When unsure, match what is already there — never the older wording in `foundations.md` / `components.md`.
**Read it** at the start of every card that renders public UI (CLAUDE.md `start X.Y`, step 2).

## 1. North star

Quiet, editorial, architectural — a developer that builds landmark buildings, not a listings portal. References for the *level* of polish: rancondevelopments.com, shanta holdings, btibd.com; the identity stays ours (teal + coral, hexagon/60° geometry).

- **Photography leads, chrome recedes.** Big, well-cropped photos; UI is white, greige and hairlines.
- **Typography does the work.** Light, large headings; small uppercase overlines for metadata; no bold display type.
- **Restraint.** Few colours, few borders, no shadows on content, one coral invitation per view.
- **Editorial rhythm.** Every section has its own layout; a page must never read as a stack of identical card grids.
- **Generous space.** Section spacing tokens, wide gutters, short line lengths.

## 2. Typography

| Token | Use | Weight |
|---|---|---|
| `text-display` | Hero headline | 300 |
| `text-h1`, `text-h2` | Page H1, block headings | 300 |
| `text-h3`, `text-h4` | Card titles, sub-sections, feature quotes (`font-light` for quotes) | 400 |
| `text-stat` | Big numbers (stats, progress %) | 300 |
| `text-price`, `text-fact` | Prices, facts | 500 |
| `text-overline` | Eyebrows, metadata, locations, footer column headings — always `uppercase`, 12 px, 0.16em | 500 |
| `text-lead`, `text-body-lg`, `text-body`, `text-sm` | Copy (Inter) | 400 / 500 |

- Manrope **300/400/500 only** (600+ is not loaded — never use `font-semibold`/`font-bold` on headings). Inter for body.
- Headings `text-balance`, paragraphs `text-pretty`; copy ≤ `max-w-prose`, statements ≤ 22ch.
- Metadata goes in an overline line with ` · ` separators (e.g. `CONSTRUCTION UPDATES · 18 OCT 2026`), the key part in `text-primary`. Not in chips.
- `text-overline` (12 px) is the only text under 13 px.

## 3. Colour and surfaces

- Surfaces: `--background` white · `--surface-subtle` greige `#f3f1ed` (`background: muted`) · `.theme-dark` teal-950 (`background: dark`/`gradient`). Text `--foreground` stone-900, secondary `--muted-foreground`.
- **Section rhythm:** alternate default and muted; dark is an accent (stats, CTA band, footer) — never two dark blocks next to each other; at most two dark blocks in a page body.
- **Teal** (`primary`) = links, hover colour, small accents, big numbers. **Coral** (`brand-accent`) = the single highest-value invitation per viewport (DP-04): the hero search button, the solid header's Enquire. Everything else is teal, light, outline or on-media. Coral is never small text.
- On photos: white text over `[data-overlay]` gradients/solid overlay (min 35 %); badges are one neutral white chip whose dot carries the status colour.
- Semantic tokens only; raw scale classes only inside badges/charts.

## 4. Shape, lines, depth

- Radius: images and cards `rounded-xl` (16 px) · buttons and inputs `rounded-md` (8 px; pills were rejected) · circles only for carousel arrows, avatars, the heart.
- **Hairlines, not boxes:** separate with `border-border` lines (block heading rule, list dividers `divide-y`, price row, quote top rule). Content cards have **no surface, no border, no shadow**.
- Shadows only on floating UI (menus, sheets, toasts, the focused marquee button).
- Decorative motif: the 60° triangle cluster (DP-03) — sparing, `aria-hidden`, at an edge.

## 5. Layout and rhythm

- Containers `max-w-content` (80 rem) with `px-4 sm:px-6 lg:px-8 xl:px-10`; 12-column grid; gaps 24/32 px (`gap-6`/`xl:gap-8`), 48 px between rows of cards.
- Vertical spacing only via block `spacing` → `--section-sm|md|lg`; hero and statement sections use `lg`.
- Left-aligned by default. Centre only short things: the CTA band (owner's choice), captions, empty states.
- **Vary adjacent layouts.** Never more than two uniform card rows in a row. Pick from the pattern catalogue:

| Pattern | Used by |
|---|---|
| Split 7 / 5 (statement + image) | intro_statement, media_text (6.1) |
| Carousel with peek (1.15 / 2.2 / 3) | featured_projects |
| Editorial card grid (3 cols) | project_grid, listings |
| Tall tiles with hover expand | segment_tiles |
| Typographic stats on dark, dividers | stats_counters |
| Hairline rows (photo · text · data) | progress_highlight |
| One large feature + counter | testimonials carousel |
| Lead story + compact list | latest_news |
| Edge-to-edge strip with caption | partners_marquee |
| Centred dark band | cta_band |

## 6. Component patterns

- **Block heading row** (`BlockHeader`): light H2 on a bottom hairline; right side "View all" in foreground with a sliding arrow, carousel arrows (outline circles) and counters (`01 / 06`). Logo strips use a centred overline caption instead.
- **Buttons:** `primary` teal (default action) · `accent` coral (the one invitation) · `outline` (`foreground/30` border) · `light` (white, on photos/dark) · `on-media` (glass, secondary on photos/dark) · `link`. Sizes `md` 44 px, `lg` 52 px for heroes and bands.
- **Project card** (CMP-CARD-01): 4:5 photo `rounded-xl` → name `text-h4` + segment overline → location overline with pin → one dotted facts line (`1,650–2,100 sft · 3–4 beds`) → hairline price row (`min-h-12`) with a plain arrow. Hover = slow image zoom + arrow nudge. `featured` = text over the photo.
- **Every card:** one link (the title, stretched over the card), extra controls (heart, compare) as separate buttons above it, focus ring around the whole card (`has-[a:focus-visible]:outline…`), `media-card`/`project-card` class for the hover zoom.
- **Image ratios:** project 4:5 · news 16:10 · progress 4:3 · segment 3:4 / 16:9 · intro 4:5 · team 4:5. Always `ResponsiveImage` + a preset.
- **Badges** only for status on photos (lifecycle, booking). Categories, dates, locations are overlines.
- **Stats:** big light numbers (`text-stat` 300, teal) with a small suffix and a muted label below; typographic, never icons in circles.
- **Carousels:** prev/next arrows in the heading row, **no dots, no autoplay** (hero slider excepted), native scroll-snap strip before hydration, mobile peek.
- **Quotes:** coral quote mark, the quote as large light text, person with 48 px photo or initials behind a hairline.

## 7. Motion feel

Subtle and slow, never bouncy on content. Hover: image `scale(1.05)` over 800 ms, arrow nudge 2 px, colour to teal (`--dur-fast`). No card lift, no hover shadows. Loops (marquee, mesh, pulse) pause off-screen. Transform/opacity only; everything visible without JS; reduced motion removes it (MOT-R01…03).

## 8. The outdated style — do not reintroduce

Bold/extra-bold headings (Manrope 600–800) · pill buttons · cards with borders, backgrounds or drop shadows · lift-on-hover · coloured category chips · several coral buttons in one view · carousel dots · three-icons-in-circles feature rows · identical 3-column grids back to back · everything centred · heavy gradients on light sections · coloured section backgrounds other than white / greige / dark teal · uppercase headings · text shadows except over photos · stock "SaaS template" hero layouts.

## 9. Older docs this file supersedes

| Older text | Now |
|---|---|
| foundations §3.1–3.2: Manrope 700/800 headings | §2 above (300/400/500) |
| foundations §5.3: project card 4:3 | 4:5 (`project-card` preset) |
| foundations §2.3 surfaces (stone-50) | greige `--surface-subtle` |
| CMP-BTN-01 radius, no `light` variant | 8 px, `light` added |
| CMP-BADGE-01 coloured badges on photos | neutral white chip, coloured dot |
| CMP-CARD-01 bordered card, 4:3, labelled spec sheet | §6 project card |
| CMP-BLK-STATS on `--surface-brand` | on dark, light numbers |
| CMP-TEST-01 1/2/3 per view, dots | one large quote, counter, arrows |
| CMP-NEWS-01 category chip, date at the bottom | overline meta; lead + compact list on home |
| CMP-MARQ-01 display heading | centred overline caption, hairlines |
| CMP-CTA-01 coral primary | `light` + `on-media` |
| CMP-HERO CTAs teal/coral | `light` + `on-media`; search is the coral CTA |

## 10. Working rules for every UI card

1. Read this file, then the card's Read list. Where a CMP-* look conflicts, build to this file and say so in the reply.
2. Before reporting, check at 1440 / 1024 / 768 / 390 px against §8 and the home page: weights, radii, hairlines, one coral CTA, layout variety, overline metadata.
3. A new visual pattern needs the owner's approval; once approved, add it here (catalogue §5 or §6) and log a DEC.
