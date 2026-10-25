# Components — foundations, header/nav, hero/search, listings/detail, block components (DESIGN §6.1–6.5)

> Verbatim from DESIGN.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 6. Component specifications

Format per component: **Anatomy** · **Variants / sizes** · **States** · **Responsive** · **Accessibility** · **Built on** (shadcn-vue component → Reka UI primitive). States not listed behave as the button/input defaults in CMP-BTN-01 / CMP-FORM-01. All components read semantic tokens (§2.3) so they work inside `.theme-dark` scopes.

### 6.1 Foundations

**CMP-BTN-01 Button**

| Aspect | Spec |
|---|---|
| Anatomy | Optional leading icon · label · optional trailing icon / spinner |
| Variants | `primary` (bg primary, fg primary-foreground); `accent` (bg brand-accent — the coral CTA, max one per viewport, DP-04); `secondary` (bg secondary, fg secondary-foreground); `outline` (1 px input border, transparent); `ghost`; `link` (underline on hover, offset 4 px); `glow` (primary + `--glow-primary`, MOT-GLOW); `on-media` (white/10% bg, white text, backdrop-blur 8 px, for hero) |
| Sizes | `sm` h-9 (36 px) px-3 text-sm; `md` h-11 (44 px) px-5 text-sm/600; `lg` h-13 (52 px) px-7 text-base/600; `icon` 44×44 (36×36 `icon-sm` admin only). Radius `--radius-md` |
| States | hover: primary → teal-700, accent → coral-700, outline → bg accent; active: translateY(1px), shadow none; focus-visible: 2 px ring `--ring` + 2 px offset (background colour); disabled: 50% opacity, `cursor-not-allowed`, no hover; loading: label kept (width locked), spinner replaces leading icon, `aria-busy="true"`, clicks ignored |
| Responsive | Full-width (`w-full`) inside forms and sheets < 640 px |
| A11y | Native `<button>`/`<a>`; icon-only requires `aria-label` + Tooltip; touch targets ≥ 44 px on public site |
| Built on | shadcn-vue `Button` (cva variants extended) |

**CMP-BADGE-01 Badge / status chip** — Anatomy: optional dot or icon · label. Variants: `lifecycle` (upcoming = info-50/700, ongoing = teal-50/700, ready = success-50/700, handed_over = stone-100/700), `booking` (booking_open = coral-50/coral-800, limited_units = warning-50/700, sold_out = stone-200/stone-800), `featured` (coral-600/white), `neutral`, admin `status` (draft = stone-100/700, scheduled = info-50/700, published = success-50/700, archived = stone-200/600, pending revision = warning-50/700). Sizes `sm` 22 px text-xs, `md` 26 px text-sm. On media: white/90% background. A11y: text always present (colour never the only signal). Built on `Badge`.

**CMP-FORM-01 Form controls**

| Aspect | Spec |
|---|---|
| Anatomy | Label (text-sm/500, required marker "*" with `aria-hidden` and "(required)" visually hidden) · control · helper text (text-xs muted) · error text (text-xs destructive with alert-circle icon) |
| Controls | Input (h-11 public / h-10 admin), Textarea (min 4 rows, auto-grow to 12), Select (Reka Select, not native — fixes **[Dash]** native selects), Combobox (searchable, grouped — areas by city), Phone input (country select defaulting to 🇧🇩 +88, formats `01XXX-XXXXXX` as typed), Checkbox, Radio group (as cards for lead step 1), Switch, Date picker (Popover + Calendar, display `DD MMM YYYY`), Date range (admin), File upload (dropzone with list), Slider (price/size range, dual thumb) |
| States | default border `--input`; hover border stone-600; focus: border primary + 3 px ring primary/25%; invalid: border destructive + ring destructive/20%, `aria-invalid="true"`; disabled: bg muted, 60% text; read-only: no border, bg muted |
| Validation UX | Validate on blur, then on change once a field has errored; on submit failure: error summary (`role="alert"`) above the form listing linked errors, focus moves to it; server errors mapped to fields from Inertia `errors` |
| Responsive | Single column < 768; two columns for short pairs (name/phone) ≥ 768 |
| A11y | `<label for>`; helper/error via `aria-describedby`; required via `aria-required`; groups in `<fieldset><legend>` |
| Built on | `Input`, `Textarea`, `Select`, `Combobox`, `Checkbox`, `RadioGroup`, `Switch`, `Calendar`, `Popover`, `Slider`, `Label`, `Field` |

**CMP-FORM-02 Multi-step lead form** (FR-LEAD project_inquiry) — Step indicator "Step 1 of 2" with 2-segment progress bar; Step 1 = three radio-card groups (timeline, purpose, contact time) **[bti]**; Step 2 = contact fields + consent + Turnstile (managed, rendered only on step 2); buttons Back (outline) / Continue (primary) / Send enquiry (accent). Step change moves focus to the step heading and announces via `aria-live`. Skipping step 1 is allowed ("Skip" link) so visitors in a hurry are not blocked.

**CMP-MODAL-01 Dialog, Sheet, Drawer** — Dialog: centred, max-w 560 (forms) / 720 (compare picker), radius xl, shadow xl, overlay stone-950/60 with 4 px backdrop blur; Sheet: side (right, 480 px, admin detail) and bottom (mobile filters, search; max-h 90svh, drag handle, swipe-down to close). States: open animates MOT-MODAL. A11y: focus trap, `Esc` closes, focus returns to trigger, `aria-labelledby` title, `inert` on background; Lenis stopped while open (§8.3). Built on `Dialog`, `Sheet`, `Drawer` (vaul-vue via shadcn-vue).

**CMP-TOAST-01 Toast** — Anatomy: icon (status) · title · description (optional) · action (optional) · close. Variants success, info, warning, error. Position bottom-right desktop (24 px inset), top-center mobile (below header). Durations 4 s success/info, 6 s warning, errors persist until dismissed. Max 3 visible, stack with 8 px offset. A11y: polite live region; errors `role="alert"`; pause on hover/focus. Built on `Sonner` (vue-sonner). Motion MOT-TOAST.

**CMP-TABS-01 Tabs** — Underline style (2 px primary indicator sliding, MOT-TAB) for content tabs; pill style (segment filter on PG-12). Keyboard: arrow keys move, Home/End, automatic activation for ≤ 6 tabs. Built on `Tabs`.

**CMP-TABS-02 Section tab bar (project detail)** — Sticky below header (top = header height), horizontally scrollable on mobile with edge fade masks; links are anchors (`#overview` …) that smooth-scroll (Lenis offset = header + bar height) and update the active item through IntersectionObserver (rootMargin `-40% 0px -55% 0px`) **[Rancon]**. Hidden tabs for empty sections. A11y: `<nav aria-label="Project sections">` with `aria-current="true"` on the active link (not ARIA tabs, since these are page anchors).

**CMP-ACC-01 Accordion** — FAQ rows with question (text-h4) and chevron rotating 180° (MOT-ACC); single-open by default, multiple on PG-18. Height animates via Reka's `--reka-accordion-content-height`. Built on `Accordion`.

**CMP-BREAD-01 Breadcrumbs** — Home › Section › Page; separator chevron 14 px; last item `aria-current="page"` (plain text); truncates middle items to "…" menu on < 640 px. Rendered on every page except home (SEO-ONP-005). Built on `Breadcrumb`.

**CMP-PAGI-01 Pagination** — Prev / numbered (first, last, current ±1, ellipses) / Next; real `<a href="?page=n">` (FR-SRCH-007); 44 px targets; current `aria-current="page"`; mobile shows "Page 3 of 9" with Prev/Next only. Built on `Pagination`.

**CMP-EMPTY-01 Empty state** — 72 px muted circle with 40 px icon, title (text-h4), one-sentence body, one action **[Dash]** Enquiries empty state (kept). Variants: no results (filters), no content yet (admin), error (with retry).

**CMP-SKEL-01 Skeleton** — §5.4; shapes provided for project card, news card, table row, KPI card, media tile.

### 6.2 Header and navigation

**CMP-HEAD-01 Site header**

| Aspect | Spec |
|---|---|
| Anatomy | Logo (left; light/dark variants) · primary nav (centre/left, ≤ 7 items) · utilities (right): phone hotline (≥ 1280), shortlist heart with count, "Enquire" accent button · mobile: logo · shortlist · menu button |
| Variants | `transparent` (over hero: no bg, white text, logo-light, bottom gradient stone-950/35% → 0 for legibility); `solid` (bg background/92% + `backdrop-blur-md`, border-bottom border, shadow-sm); `hidden` (scrolled down) |
| Behaviour | Starts `transparent` on pages whose first block is a hero (setting `transparent_on_hero`), otherwise `solid`. Switches to `solid` after 24 px scroll. Hides on scroll down > 120 px (translateY(-100%), 280 ms) and reappears on any scroll up (≥ 8 px delta) — "headroom" pattern; always shown when focus is inside it or a menu is open |
| Sizes | Height 80 px desktop, 64 px < 1024 |
| States | nav link hover: underline grows from left (MOT-UNDERLINE); active section: 2 px coral-500 underline (decorative, label also bold); focus ring per CMP-BTN-01 |
| A11y | `<header>` with `<nav aria-label="Main">`; skip link precedes it; mega menu triggers are buttons with `aria-expanded` |
| Built on | `NavigationMenu` |

**CMP-NAV-02 Mega menu** — Opens on hover (150 ms intent delay) or click/Enter; panel full container width under header, radius-b xl, shadow lg, up to 4 columns (CMS-NAV-003): `auto:segments` (icon + label + one-line description), `auto:locations` (top 8 areas with counts), `auto:featured_projects` (3 mini cards 4:3), custom link list. Footer row: "View all projects →". Closes on Esc, outside click, or pointer leaving for 300 ms. Keyboard: Tab enters panel, arrow keys move within columns. Motion MOT-MEGA. Built on `NavigationMenu` content/viewport.

**CMP-NAV-03 Mobile menu** — Full-screen sheet from right (100vw, max 420 on tablet), dark scope (`--gradient-dusk`), large links (text-h3) with accordion for children, bottom area: hotline, WhatsApp, Enquire button, social icons. Opens with MOT-MENU (links stagger). Focus trapped; Esc closes; body scroll locked (Lenis stopped).

**CMP-BAR-01 Sticky mobile contact bar** **[bti]** — < 1024 px only, fixed bottom, safe-area padding, 64 px: Call (`tel:`), WhatsApp, Enquire (accent). Shown after the hero leaves the viewport, hidden while a form field is focused (keyboard open) and when the footer is visible. `aria-label="Quick contact"`. z `--z-mobile-bar`. On PG-04 the Enquire button opens the enquiry sheet with project prefilled.

**CMP-FOOT-01 Footer** — Dark scope (`--gradient-dusk`), container content. Rows: (1) logo + about text + newsletter form (email + subscribe); (2) 4 menu columns (`footer_1…4`) + offices (address, phone, email); (3) top 8 locations links (SEO-ONP-005); (4) legal menu · © {year} · "A Gemcon Group company" (link) · "Reduce motion" toggle · social icons. Mobile: columns become accordions (except contact). A11y: `<footer>` with `<nav aria-label="Footer">` per column group.

### 6.3 Hero and search

**CMP-HERO-01 Hero — image** (REQUIREMENT §6)

| Aspect | Spec |
|---|---|
| Anatomy | Media layer (`<picture>`, focal point) · overlay (§2.4 hero gradients, `--overlay` from admin 0.35–0.80) · content (eyebrow overline, H1 `text-display`, subheadline `text-lead`, CTAs) · search widget (CMP-SEARCH-01) · scroll cue (12 px chevron, bounce MOT-CUE, hidden under reduced motion) |
| Variants | height `full` (100svh, min 560), `large` (80svh), `medium` (60svh); align left (content max 720 px, bottom-left, 15% from bottom) or center; overlay solid/gradient-bottom/gradient-left |
| States | Text visible at first paint (HERO-032); reveal MOT-REVEAL-HERO animates clip-path only |
| Responsive | < 768: mobile crop source (4:5) if provided; H1 max 4 lines; CTAs stack full width; search collapses to button (HERO-043) |
| A11y | H1 only when hero is first block; decorative image `alt=""` else alt; text-shadow backup (TOK-C27) |
| Built on | Custom component; `Button` |

**CMP-HERO-02 Hero — video** — As CMP-HERO-01 with the poster as the `<picture>`; `<video>` injected per HERO-025/026, fades in (MOT-HERO-VIDEO). Control: 44×44 pause/play button bottom-right (on-media variant), `aria-label` "Pause background video"/"Play background video", `aria-pressed`. No captions needed (no audio, decorative, `aria-hidden` video).

**CMP-HERO-03 Hero — slider** — 2–5 slides (images only); crossfade 700 ms (`MOT-SLIDE`), Ken Burns scale 1.0 → 1.06 over the interval (off on reduced motion); controls: prev/next 44 px, dots with "Slide n of N", pause/play always visible when autoplay is on. Only slide 1 is eager/preloaded; others lazy. Built on Embla. A11y: `aria-roledescription="carousel"`, slides `role="group" aria-label="n of N"`, autoplay pauses on hover/focus.

**CMP-SEARCH-01 Search widget**

| Aspect | Spec |
|---|---|
| Anatomy | Segmented control (All / Residential / Commercial / Land) · Location combobox · Status select · Size select · Bedrooms select · "Search projects" accent button |
| Variants | `hero` (floating card: bg background/95%, radius xl, shadow xl, p-3, fields inline with 1 px dividers, 72 px tall on ≥ 1024); `inline` (on PG-02 top, no shadow); `compact` (mobile trigger button opening a bottom sheet) |
| States | Fields show selected value or placeholder; button loading during navigation |
| Responsive | ≥ 1024 one row; 768–1023 two rows (2 + 3 fields); < 768 trigger button "Search projects" (full width, h-13) with a summary of current filters |
| A11y | Real `<form method="get">` (HERO-042); each field labelled (visually hidden labels in `hero` variant, visible in sheet) |
| Built on | `ToggleGroup`, `Combobox`, `Select`, `Button`, `Sheet` |

### 6.4 Listings and detail

**CMP-CARD-01 Project card**

| Aspect | Spec |
|---|---|
| Anatomy | Media (4:3, radius lg, image zoom on hover MOT-CARD-IMG) with badges top-left (lifecycle, booking) and heart top-right · body: area/city overline · name (H3 `text-h4`, the card's only link target with stretched-link overlay) · facts row (icons + values: size range sft, beds, baths, land katha) in a `<dl>` · footer: price-from (`text-price`, only if shown) or "Price on request" muted · compare checkbox (PG-02/03/06 only) |
| Variants | `default` (vertical); `horizontal` (list/map view, media 40%); `mini` (mega menu, similar projects: no facts row); `featured` (2-col span on ≥ 1280 in grids, larger title) |
| Sizes | min width 280 px (container query switches facts to 2×2 under 340 px) |
| States | hover (pointer: fine): lift translateY(-4px), shadow md, image scale 1.04, optional tilt (MOT-TILT, `featured_projects`/`feature_cards` with tilt enabled); focus-visible on title link: ring around the whole card (`:focus-within`); loading: CMP-SKEL-01; shortlisted heart filled coral-500 with 1.6 px stroke (non-text) |
| Responsive | 1 col < 640, 2 cols 640–1023, 3 cols ≥ 1024, 4 cols on `wide` containers ≥ 1536 |
| A11y | Single link in card (no nested interactive elements inside the link); heart and compare are separate buttons with labels "Add {name} to shortlist" / "Compare {name}"; facts use `<dl>` not headings (fixes **[bti]** H6 misuse) |
| Built on | `Card`, `Badge`, `Checkbox`, `Toggle` |

**CMP-FILT-01 Filters panel**

| Aspect | Spec |
|---|---|
| Anatomy | Desktop: horizontal filter bar sticky under header (h-16): keyword input, Segment, Area (multi combobox), Status, Size (popover with dual slider + presets), Bedrooms (1–5+ toggle group), Price (popover dual slider, only when FR-SRCH-005), "More" (booking state, featured) · right: result count, sort select, Grid/Map toggle. Active filters row below: removable chips + "Clear all" |
| Mobile | "Filters (n)" button + sort button sticky under header; Filters opens bottom sheet with stacked sections (accordion), sticky footer "Show {count} projects" (count updates live) + "Clear" |
| States | Pending request: result grid dims to 60% with skeleton overlay after 300 ms (CMP-SKEL); option with 0 results disabled + "(0)"; error: inline alert with retry |
| A11y | Each control labelled; result count in `aria-live="polite"` ("24 projects found"); chips are buttons "Remove filter: Uttara" |
| Built on | `Popover`, `Slider`, `ToggleGroup`, `Combobox`, `Select`, `Sheet`, `Badge` |

**CMP-GAL-01 Gallery and lightbox**

| Aspect | Spec |
|---|---|
| Anatomy | Detail hero gallery: 1 large (16:9) + 4 tiles grid (desktop) with "View all {n} photos" button on the last tile **[Rancon]**; category tabs in lightbox (Exterior, Interior, Amenity, Site, Night) |
| Mobile | Swipeable Embla strip (1 slide, 4:3) with counter "1 / 24" |
| Lightbox | PhotoSwipe 5: full-screen dark (stone-950/96%), caption + credit at bottom, zoom (pinch, double-tap, wheel), arrows, counter, close; keyboard ←/→/Esc; preloads ±1 |
| States | Tile hover: scale 1.03 + dim overlay 15%; loading: placeholder §5.4 |
| A11y | Tiles are buttons "Open photo 3 of 24: {alt}"; lightbox `role="dialog"` with focus trap; captions read by screen readers |
| Built on | Custom + PhotoSwipe + Embla; `Dialog` not used (PhotoSwipe manages its own) |

**CMP-FACTS-01 At a glance** — Responsive grid of fact items (icon 24 · label text-xs muted · value `text-fact`): 2 cols < 640, 3 cols 640–1023, 4 cols ≥ 1024; RAJUK number with seal icon and copy button; custom facts appended. Semantic `<dl>`. **[Rancon]**

**CMP-UNIT-01 Unit types and floor plans** — Tabs per unit type (CMP-TABS-01) or select on < 640; panel: floor plan image (contain, stone-50 bg, click → lightbox) beside a spec list (size, beds, baths, balconies, facing, floors, availability badge, price if shown) and "Enquire about Type A" (prefills unit). Availability `sold_out` greys the tab label and adds "(sold out)".

**CMP-PROG-01 Construction progress** **[Rancon]** — Big percentage (`text-stat`, counts up MOT-COUNTER), 4-stage segmented bar (Foundation, Structure, Finishing, Complete; filled segments teal-600, current pulses subtly MOT-PULSE), "Updated {Month YYYY}" and note; below: timeline of updates (date · % · note · photo strip opening lightbox). `role="progressbar"` with `aria-valuenow`.

**CMP-NEAR-01 Nearby places** — List of rows: category icon · name · "{n} min {drive/walk}"; 2 columns ≥ 768.

**CMP-MAP-01 Interactive map** — Leaflet with OSM tiles, custom hexagon-shaped markers (teal-600 fill, white border; active coral-500), clusters (teal circles with count), popups = CMP-CARD-01 `mini`. Height: 560 px (listing map view), 420 px (area page). Controls: zoom ± (44 px), "Reset view". Scroll-wheel zoom disabled until map is clicked/focused (prevents scroll trap with Lenis; container has `data-lenis-prevent` only while active). A11y: a visually-hidden list of the same projects precedes the map ("Projects on this map"), markers are focusable buttons with labels.

**CMP-MAP-02 Map facade** — §5.4 pattern box (16:9, radius lg) with address, "Show interactive map" (outline button → loads keyless Google embed iframe with `title="Map of {project}"`) and "Open in Google Maps ↗" link (FR-LOC-007).

**CMP-ENQ-01 Enquiry panel** — Desktop (≥ 1024): sticky right column card (top = header + tab bar + 24 px) on PG-04 with project name, price-from or "Price on request", primary actions: "Enquire" (accent, opens CMP-FORM-02 inline in the card), "Book a site visit" (outline, dialog), "Download brochure" (ghost, gated dialog), WhatsApp link (with WhatsApp green icon only; text stays foreground colour), hotline. Mobile: replaced by CMP-BAR-01 + inline enquiry section near page end.

**CMP-PERSON-01 Person card** (replaces "agent card": no public agents, REQUIREMENT DEC-06) — Used for leadership/team (PG-08) and the "Your sales contact" mini card in the enquiry panel (shows hotline, not an individual). Anatomy: portrait 4:5 (grayscale → colour on hover, MOT-PORTRAIT) · name (H3) · role · LinkedIn icon link. Variant `mini` (64 px avatar, name, role).

**CMP-TEST-01 Testimonial** — Quote mark (decorative coral-500 48 px, `aria-hidden`), quote text (text-lead, max 4 lines in carousel with "Read more" expanding), person (photo 48 px circle, name, designation/project link). Carousel variant (Embla, 1 / 2 / 3 per view by breakpoint, arrows + dots, no autoplay) and grid variant. Real text, never images (fixes **[bti]**). `<figure><blockquote><figcaption>`.

**CMP-STAT-01 Stats / counters** — Items: value (`text-stat`, `tnum`, final value in HTML) + suffix + label (text-sm muted); 2 cols < 768, 3–6 across ≥ 1024 with vertical dividers; counts up once in view (MOT-COUNTER). Screen readers get the final value only (animated number container `aria-hidden`, a visually hidden final value sibling).

**CMP-CTA-01 CTA band** — Full-bleed band: `brand` (`--gradient-brand`), `gradient` (`--gradient-mesh`, animated MOT-GRAD), `image` (photo + stone-950/55% overlay). Content: H2, one sentence, primary CTA (on dark: `accent` dark tokens) + secondary (outline white). Min height 320 px; centred ≤ 768 px. Decorative triangle cluster (DP-03) at the right edge ≥ 1024, `aria-hidden`.

**CMP-MARQ-01 Partner marquee** **[Polpharma]** — Two copies of the logo row in a track translating -50% (MOT-MARQUEE); logos grayscale 100% + opacity 70% → colour/100% on hover; edge fade masks 80 px. Pause on hover and focus-within; pause/play button (visually hidden until focused, then visible top-right). Second copy `aria-hidden="true"` + `inert`.

**CMP-NEWS-01 News card** — Cover 16:10 · category chip · title (H3 `text-h4`, 3-line clamp) · excerpt (2-line clamp) · date (`DD MMM YYYY`) · read time. Featured variant: horizontal, cover 60%.

**CMP-VIDEO-01 Video facade** — Poster (16:9) + 72 px circular play button (on-media) + duration chip; click loads `youtube-nocookie.com` iframe with `autoplay=1` (or native `<video controls>` for uploads with captions track if provided). `title` on iframe.

**CMP-COMPARE-01 Compare bar and table** — Floating bar bottom-centre (above CMP-BAR-01 on mobile): up to 3 mini thumbnails with remove buttons + "Compare (n)" primary; slides up MOT-SLIDE-UP. Table (PG-21): sticky first column (attribute names) and sticky header row with project mini cards; differing values highlighted with teal-50 background; horizontal scroll on mobile with first column pinned.

**CMP-COOKIE-01 Consent banner** — Bottom sheet card (max-w 560, left-aligned desktop; full width mobile), text + "Accept all" (primary) / "Reject non-essential" (outline, equal prominence) / "Preferences" (link → dialog with category switches). Never blocks content; no consent wall. Focus not stolen on load; reachable as first landmark after skip link.

**CMP-SHARE-01 Share row (articles)** — Copy link (toast "Link copied"), Facebook, LinkedIn, WhatsApp share URLs (no SDKs).

### 6.5 Page-builder block components (`CMP-BLK-*`)

Each block type in REQUIREMENT §8.1 maps to one Vue component composed of the components above:

| Block | Component | Composition |
|---|---|---|
| hero | CMP-BLK-HERO | CMP-HERO-01/02/03 + CMP-SEARCH-01 |
| page_header | CMP-BLK-PAGEHEAD | H1, intro (`text-lead`), breadcrumb, optional bg image with overlay (min-h 320 px) |
| intro_statement | CMP-BLK-INTRO | Large statement (text-h2 weight 600, max 22ch per line) with split-line reveal (MOT-SPLIT) + side image mask reveal |
| featured_projects | CMP-BLK-FEATURED | CMP-CARD-01 carousel (Embla, 1.15 / 2.2 / 3 per view) or grid; header with "View all" link |
| project_grid | CMP-BLK-GRID | CMP-CARD-01 grid |
| segment_tiles | CMP-BLK-SEGMENTS | 2–4 tall image tiles (3:4) with label and arrow; hover expands flex-grow 1 → 1.4 (desktop) |
| stats_counters | CMP-BLK-STATS | CMP-STAT-01 |
| progress_highlight | CMP-BLK-PROGRESS | 1–3 CMP-PROG-01 summaries with photo |
| testimonials | CMP-BLK-TESTIMONIALS | CMP-TEST-01 |
| partners_marquee | CMP-BLK-MARQUEE | CMP-MARQ-01 |
| latest_news | CMP-BLK-NEWS | CMP-NEWS-01 ×3/6 |
| cta_band | CMP-BLK-CTA | CMP-CTA-01 |
| rich_text | CMP-BLK-RICH | Prose styles (§3), narrow/wide |
| image_text | CMP-BLK-IMGTXT | 2-col 6/6 (≥ 1024), image mask reveal (MOT-MASK) |
| feature_cards | CMP-BLK-FEATURES | Icon cards (radius lg, border, p-6), optional MOT-TILT |
| process_steps | CMP-BLK-STEPS | Numbered steps "01–08" in Manrope 800 coral-500 (decorative, ≥ 24 px) with connecting line drawing on scroll (MOT-LINE) **[Polpharma]** |
| timeline | CMP-BLK-TIMELINE | Vertical (mobile) / alternating (desktop) milestones with year chips |
| tabbed_pillars | CMP-BLK-PILLARS | CMP-TABS-01 with image crossfade **[Polpharma]** |
| team_grid | CMP-BLK-TEAM | CMP-PERSON-01 grid (2 / 3 / 4 cols) |
| faq | CMP-BLK-FAQ | CMP-ACC-01 |
| gallery | CMP-BLK-GALLERY | Grid or masonry (CSS columns) + PhotoSwipe |
| video | CMP-BLK-VIDEO | CMP-VIDEO-01 |
| awards | CMP-BLK-AWARDS | Logo/image + title + issuer + year cards, 2–6 cols |
| locations_grid | CMP-BLK-LOCATIONS | Area tiles (image 3:2, name, project count) |
| offices | CMP-BLK-OFFICES | Office cards (address, phones, email, hours, CMP-MAP-02) |
| form | CMP-BLK-FORM | Form (CMP-FORM-01/02) with side rich content; 7/5 split |
| quote | CMP-BLK-QUOTE | Large quote (text-h2 600), photo, name, role |
| parallax_band | CMP-BLK-PARALLAX | Full-bleed image with MOT-PARALLAX and optional heading |
