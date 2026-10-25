# Design foundations — principles, colour, type, spacing, imagery, token code (DESIGN §1–5, §13)

> **Visual direction:** `direction.md` is the source of truth for the public site's look (DEC-49) and wins over this file where they differ — e.g. type weights, card, button, badge and block designs. Behaviour, accessibility and motion rules here still apply.

> Verbatim from DESIGN.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

> Visual, interaction and motion specification. Behaviour, data and acceptance criteria live in `REQUIREMENT.md`; this document references them by ID (`PG-*`, `FR-*`, `HERO-*`, `SEO-*`, `ADM-*`, `CMS-*`). Version 1.0.0 · 25 Oct 2026.

Sources cited inline: **[Logo]** `logo.jpeg`; **[Rancon]** rancondevelopments.com; **[bti]** btibd.com; **[Shanta]** shantaholdings.com; **[Polpharma]** polpharmabiologics.com; **[Dash]** `dashboard-1…6.png`.

---

## 1. Design principles and brand personality

**Personality: engineered, grounded, optimistic.** The logo is a hexagon built from teal and coral triangles with a bold, wide geometric wordmark **[Logo]** — precise construction (engineered), heavy confident letterforms (grounded), and a warm coral counterpoint to the teal (optimistic).

| ID | Principle | What it means in practice |
|---|---|---|
| DP-01 | Photography leads, chrome recedes | Large, full-bleed project imagery; UI uses warm neutrals so photos carry colour **[Rancon]** |
| DP-02 | Facts before adjectives | Every project surfaces size, bedrooms, handover, location, approvals in the first screen; stats are typographic, not decorative **[Rancon][bti]** |
| DP-03 | Built from triangles | The hexagon's 60° geometry appears as a subtle motif: triangular section dividers, the 60° clip in image masks, the map pattern, list bullets |
| DP-04 | Teal acts, coral invites | Teal = primary actions and navigation; coral = the single highest-value invitation per view (Enquire, Book a visit). Never more than one coral CTA per viewport |
| DP-05 | Motion explains, never blocks | Reveals follow reading order; nothing hides content from crawlers or reduced-motion users (REQUIREMENT SEO-PERF-012, SEO-A11Y-005) **[Polpharma]** |
| DP-06 | One clear next step | Each section ends with one action; lead capture is always within one tap on mobile (sticky contact bar) **[bti]** |
| DP-07 | Calm density | Generous vertical rhythm on public pages; compact, scannable density in admin **[Dash]** |

---

## 2. Color system

### 2.1 Brand colours extracted from the logo

Sampled from the 200×46 JPEG, saturated pixels only (median) **[Logo]**:

| Role | HEX | Use |
|---|---|---|
| Brand teal | `#027685` | Primary (becomes `teal-600`) |
| Brand coral | `#EC6B51` | Accent (becomes `coral-500`) |
| Mark grey | `#635C5B` | Warm neutral reference (neutral scale hue 40°) |

### 2.2 Scales (50–950)

The canonical definition of every token is the CSS in §13; the tables in §2 are the readable view of the same values.

Generated in OKLCH from each brand colour's hue, stepping lightness and tapering chroma at the ends; brand colours are kept exactly at their anchor step.

| Step | teal (primary) | coral (accent) | stone (neutral, warm) |
|---|---|---|---|
| 50 | `#EFF9FB` | `#FFF4F2` | `#FEF9F7` |
| 100 | `#DDF1F5` | `#FFE7E1` | `#F7F2F0` |
| 200 | `#BDE3EA` | `#FFCEC2` | `#EAE5E3` |
| 300 | `#91CCD7` | `#FEA794` | `#D9D3D0` |
| 400 | `#60AFBD` | `#EF7B63` | `#A6A09E` |
| 500 | `#3392A1` | **`#EC6B51`** (logo) | `#797371` |
| 600 | **`#027685`** (logo) | `#B8432C` (adjusted, see 2.5) | `#5C5755` |
| 700 | `#055A66` | `#963826` | `#46413F` |
| 800 | `#01464F` | `#782C1D` | `#2F2A28` |
| 900 | `#01343C` | `#5B2014` | `#1E1918` |
| 950 | `#001F24` | `#3A0F07` | `#100C0B` |

Secondary colour = the stone neutral scale used as the supporting surface family (warm, matching the logo's grey), with teal-50/100 tints as secondary brand surfaces.

**Semantic scales**

| Step | success | warning | error | info |
|---|---|---|---|---|
| 50 | `#EFFAF1` | `#FFF5E9` | `#FFF4F2` | `#F1F8FE` |
| 100 | `#DDF4E1` | `#FDEAD0` | `#FEE7E4` | `#DFEFFE` |
| 200 | `#BEE7C5` | `#F7D5A6` | `#FECDC7` | `#BCE0FF` |
| 300 | `#93D39F` | `#EBB66A` | `#FEA69C` | `#8AC8FC` |
| 400 | `#62B777` | `#D59219` | `#FE6C61` | `#56A9EB` |
| 500 | `#359A54` | `#AF7603` | `#E2403A` | `#2389D1` |
| 600 | `#0A7E3A` | `#8D5E05` | `#C2181D` | `#036EAE` |
| 700 | `#03612A` | `#6C4701` | `#99020E` | `#025486` |
| 800 | `#004B1E` | `#543600` | `#780108` | `#024068` |
| 900 | `#023816` | `#402801` | `#5D0005` | `#013050` |
| 950 | `#00220A` | `#271700` | `#3A0002` | `#001C32` |

### 2.3 Semantic tokens (shadcn-vue variables)

Public site uses **light** tokens globally and **dark** tokens inside dark-surface sections (`.theme-dark` scope: footer, dark CTA bands, hero text area, sticky mobile bar). The public site does not offer a full dark-mode toggle in v1. The admin supports light, dark and system (`ADM-SCR-20`).

| Token | Light | Dark | Notes |
|---|---|---|---|
| `--background` | `#FFFFFF` | `#001F24` (teal-950) | |
| `--foreground` | `#1E1918` (stone-900) | `#FEF9F7` (stone-50) | |
| `--card` | `#FFFFFF` | `#092C31` | dark card = teal hue, L 0.27 |
| `--card-foreground` | `#1E1918` | `#FEF9F7` | |
| `--popover` | `#FFFFFF` | `#092C31` | |
| `--popover-foreground` | `#1E1918` | `#FEF9F7` | |
| `--primary` | `#027685` (teal-600) | `#91CCD7` (teal-300) | |
| `--primary-foreground` | `#FFFFFF` | `#001F24` | |
| `--secondary` | `#EFF9FB` (teal-50) | `#14363C` | |
| `--secondary-foreground` | `#01464F` (teal-800) | `#FEF9F7` | |
| `--muted` | `#F7F2F0` (stone-100) | `#14363C` | |
| `--muted-foreground` | `#5C5755` (stone-600) | `#A6A09E` (stone-400) | |
| `--accent` | `#F7F2F0` (stone-100) | `#14363C` | shadcn hover/selected surface (not the brand accent) |
| `--accent-foreground` | `#1E1918` | `#FEF9F7` | |
| `--brand-accent` | `#B8432C` (coral-600) | `#EF7B63` (coral-400) | coral CTA fill |
| `--brand-accent-foreground` | `#FFFFFF` | `#001F24` | |
| `--brand-accent-decor` | `#EC6B51` (coral-500) | `#EC6B51` | decoration and large display only |
| `--destructive` | `#C2181D` (error-600) | `#FE6C61` (error-400) | |
| `--destructive-foreground` | `#FFFFFF` | `#001F24` | |
| `--success` / `--success-foreground` | `#0A7E3A` / `#FFFFFF` | `#62B777` / `#001F24` | |
| `--warning` / `--warning-foreground` | `#8D5E05` / `#FFFFFF` | `#D59219` / `#001F24` | |
| `--info` / `--info-foreground` | `#036EAE` / `#FFFFFF` | `#56A9EB` / `#001F24` | |
| `--border` | `#EAE5E3` (stone-200) | `#314D52` | decorative dividers (no contrast requirement) |
| `--input` | `#797371` (stone-500) | `#6C858A` | form control boundary, ≥ 3:1 (adjusted, 2.5) |
| `--ring` | `#027685` | `#EF7B63` | focus ring |
| `--surface-subtle` | `#FEF9F7` (stone-50) | `#001F24` | alternating section bg, admin canvas |
| `--surface-brand` | `#EFF9FB` (teal-50) | `#092C31` | brand-tinted section bg |
| `--chart-1` | `#027685` | `#60AFBD` | |
| `--chart-2` | `#EC6B51` | `#EF7B63` | |
| `--chart-3` | `#01464F` | `#BDE3EA` | |
| `--chart-4` | `#AF7603` | `#D59219` | |
| `--chart-5` | `#797371` | `#A6A09E` | |
| `--sidebar` | `#001F24` | `#001F24` | admin sidebar is dark in both modes **[Dash]** |
| `--sidebar-foreground` | `#EAE5E3` | `#EAE5E3` | |
| `--sidebar-primary` | `#EF7B63` | `#EF7B63` | active indicator bar, logo tile |
| `--sidebar-primary-foreground` | `#001F24` | `#001F24` | |
| `--sidebar-accent` | `#14363C` | `#14363C` | active/hover item background |
| `--sidebar-accent-foreground` | `#FEF9F7` | `#FEF9F7` | |
| `--sidebar-border` | `#314D52` | `#314D52` | |
| `--sidebar-ring` | `#EF7B63` | `#EF7B63` | |
| `--sidebar-muted` | `#91CCD7` | `#91CCD7` | group labels |

### 2.4 Gradients

| Token | Definition | Use |
|---|---|---|
| `--gradient-brand` | `linear-gradient(135deg, #01464F 0%, #055A66 45%, #027685 100%)` | Brand CTA bands, OG fallback images |
| `--gradient-dusk` | `linear-gradient(160deg, #001F24 0%, #01343C 55%, #055A66 100%)` | Dark sections, footer |
| `--gradient-sunrise` | `linear-gradient(135deg, #B8432C 0%, #EC6B51 60%, #FEA794 100%)` | Decorative accents only (no text on it below 24 px) |
| `--gradient-hero-bottom` | `linear-gradient(to top, rgb(16 12 11 / var(--overlay)) 0%, rgb(16 12 11 / calc(var(--overlay) * .55)) 45%, rgb(16 12 11 / 0) 100%)` | Hero overlay `gradient-bottom` (HERO §6.2) |
| `--gradient-hero-left` | `linear-gradient(to right, rgb(16 12 11 / var(--overlay)) 0%, rgb(16 12 11 / calc(var(--overlay) * .4)) 60%, rgb(16 12 11 / 0) 100%)` | Hero overlay `gradient-left` |
| `--gradient-mesh` | `radial-gradient(60% 50% at 15% 20%, rgb(2 118 133 / .55), transparent 70%), radial-gradient(45% 40% at 85% 30%, rgb(236 107 81 / .35), transparent 70%), radial-gradient(55% 50% at 50% 100%, rgb(51 146 161 / .40), transparent 70%), #001F24` | Animated gradient background (MOT-GRAD) |
| `--glow-primary` | `0 0 0 1px rgb(2 118 133 / .35), 0 8px 24px -6px rgb(2 118 133 / .55)` | Glowing primary button (MOT-GLOW) |
| `--glow-accent` | `0 0 0 1px rgb(236 107 81 / .35), 0 10px 28px -8px rgb(236 107 81 / .60)` | Glowing coral CTA |

### 2.5 Contrast table (WCAG 2.2 AA)

Ratios computed with the WCAG relative-luminance formula. Pass thresholds: normal text 4.5:1, large text (≥ 24 px, or ≥ 18.66 px bold) 3:1, UI component boundaries and focus indicators 3:1.

| ID | Pairing (foreground / background) | Ratio | Result |
|---|---|---|---|
| TOK-C01 | stone-900 `#1E1918` / white | 17.39 | Pass — body text |
| TOK-C02 | stone-600 `#5C5755` / white | 7.12 | Pass — muted text |
| TOK-C03 | stone-600 / stone-50 `#FEF9F7` | 6.82 | Pass |
| TOK-C04 | stone-600 / stone-100 `#F7F2F0` | 6.41 | Pass |
| TOK-C05 | stone-600 / teal-50 `#EFF9FB` | 6.65 | Pass |
| TOK-C06 | teal-600 `#027685` / white (links, text) | 5.34 | Pass |
| TOK-C07 | white / teal-600 (primary button) | 5.34 | Pass |
| TOK-C08 | white / teal-700 `#055A66` (primary hover) | 7.89 | Pass |
| TOK-C09 | teal-700 / stone-50 | 7.56 | Pass |
| TOK-C10 | teal-700 / teal-50 | 7.37 | Pass — secondary button |
| TOK-C11 | **coral-500 `#EC6B51` / white** | **3.09** | **Fail for normal text.** Adjustment: coral-500 restricted to decoration and text ≥ 24 px; coral CTA fill uses coral-600 `#B8432C` |
| TOK-C12 | white / coral-600 `#B8432C` (accent button) | 5.42 | Pass |
| TOK-C13 | coral-700 `#963826` / coral-50 | 6.74 | Pass — accent badge |
| TOK-C14 | coral-800 `#782C1D` / coral-50 | 8.95 | Pass |
| TOK-C15 | coral-500 / teal-950 `#001F24` | 5.56 | Pass — accent text on dark |
| TOK-C16 | coral-400 `#EF7B63` / stone-950 | 7.12 | Pass |
| TOK-C17 | teal-950 / coral-400 (dark accent button) | 6.29 | Pass |
| TOK-C18 | stone-50 / teal-950 (dark bg) | 16.46 | Pass |
| TOK-C19 | stone-400 `#A6A09E` / teal-950 | 6.67 | Pass — dark muted |
| TOK-C20 | stone-400 / dark card `#092C31` | 5.75 | Pass |
| TOK-C21 | stone-400 / dark muted `#14363C` | 5.02 | Pass |
| TOK-C22 | teal-300 `#91CCD7` / teal-950 | 9.68 | Pass — dark links |
| TOK-C23 | teal-300 / dark card | 8.35 | Pass |
| TOK-C24 | coral-400 / dark card | 5.43 | Pass |
| TOK-C25 | stone-200 `#EAE5E3` / teal-900 `#01343C` | 10.79 | Pass |
| TOK-C26 | white / stone-950 (hero text on full overlay) | 19.45 | Pass |
| TOK-C27 | white / stone-600 (worst-case mid-tone photo under 35% overlay, approximated) | 7.12 | Pass; HERO overlay minimum 35% (REQUIREMENT §6.2) plus text-shadow `0 1px 2px rgb(0 0 0 / .35)` as backup |
| TOK-C28 | success-700 / success-50 | 7.14 | Pass |
| TOK-C29 | warning-700 / warning-50 | 7.67 | Pass |
| TOK-C30 | error-600 / white; white / error-600 | 6.10 | Pass |
| TOK-C31 | info-700 / info-50 | 7.49 | Pass |
| TOK-C32 | error-400 `#FE6C61` / teal-950 | 6.17 | Pass |
| TOK-C33 | **stone-400 input border / white** | **2.58** | **Fail (1.4.11).** Adjustment: `--input` = stone-500 `#797371` → 4.66 Pass |
| TOK-C34 | **dark input border `#556E73` / dark card** | **2.73** | **Fail.** Adjustment: `--input` dark = `#6C858A` → 3.78 on card, 4.39 on bg, 3.30 on muted: Pass |
| TOK-C35 | teal-600 focus ring / white | 5.34 | Pass |
| TOK-C36 | coral-400 focus ring / teal-950 | 6.29 | Pass |
| TOK-C37 | stone-200 `#EAE5E3` border / white | 1.25 | Decorative only — never the sole indicator of a control |
| TOK-C38 | teal-600 / coral-500 | 1.73 | Never pair as text/background |
| TOK-C39 | white / success-600 `#0A7E3A`; white / warning-600 `#8D5E05`; white / info-600 `#036EAE` | 5.17 / 5.62 / 5.46 | Pass — solid status badges |
| TOK-C40 | teal-950 / success-400, warning-400, info-400, error-400 (dark-mode fills) | 7.01 / 6.51 / 6.77 / 6.17 | Pass |
| TOK-C41 | stone-200 `#EAE5E3` / dark muted `#14363C` (admin sidebar item text on active bg) | 10.36 | Pass |
| TOK-C42 | coral-400 indicator / `#14363C` (active bar, non-text) | 4.74 | Pass (≥ 3:1) |
| TOK-C43 | teal-600 / stone-50 (links on subtle sections) | 5.11 | Pass |
| TOK-C44 | stone-500 input border / stone-50 | 4.46 | Pass (≥ 3:1) |
| TOK-C45 | white / teal-500 `#3392A1` | 3.64 | Large text and icons only; excluded from gradients carrying text |

### 2.6 Usage rules

| Do | Don't |
|---|---|
| Use teal-600 for primary buttons, links, active states | Use coral-500 for body text or small labels on white (TOK-C11) |
| Use one coral (coral-600) CTA per viewport for the top conversion action | Put two coral buttons side by side |
| Use stone neutrals for text and surfaces; keep photos the main colour | Tint photos with brand colours (except hero overlay, which is neutral stone-950) |
| Use `--gradient-brand`/`--gradient-dusk` behind white text (every stop passes with white; the lightest stop, teal-600, gives 5.34:1) | Add lighter teal stops (teal-500 gives only 3.64:1 with white) to these gradients |
| Use semantic colours only for status meaning | Use success green as a decorative brand colour |
| Pair badges as `-50` background + `-700/-800` text | Use `-500` text on `-100` backgrounds |

### 2.7 Tailwind v4 theme mapping

Tokens are declared once as CSS custom properties (§13) and exposed to Tailwind with `@theme inline`, which is the shadcn-vue Tailwind v4 pattern (REQUIREMENT §12.1). Utilities: `bg-primary`, `text-muted-foreground`, `bg-brand-accent`, `border-input`, `ring-ring`, `bg-teal-600`, `text-coral-700`, `bg-stone-50`. Raw scale utilities are allowed only in components that need a specific step (badges, charts); everything else uses semantic tokens so dark sections work by scope.

---

## 3. Typography

### 3.1 Families

| Role | Family | Why |
|---|---|---|
| Display / headings / numbers | **Manrope Variable** (200–800) | The logo wordmark is a bold, wide geometric sans with squared, engineered terminals **[Logo]**; Manrope's geometric-grotesque forms at 700–800 echo it, it has tabular figures (`tnum`) for prices, areas and stats, and its wide counters stay legible at display sizes |
| Body / UI | **Inter Variable** (100–900) | Optimised for screens at 14–18 px, excellent for dense admin tables and forms, neutral enough to let Manrope carry the brand |
| Code / slugs (admin) | `ui-monospace, "SF Mono", "Cascadia Code", Menlo, monospace` | System stack; used for slugs like **[Dash]** `/marine-engineering` chips |

Delivery: self-hosted via `@fontsource-variable/manrope` and `@fontsource-variable/inter` 5.3.0, Latin subset WOFF2 only, `font-display: swap`, preloads per REQUIREMENT SEO-PERF-007, fallback metrics via `fontaine`. Self-hosting (vs Google Fonts CDN) avoids a third-party connection, works under strict CSP, and keeps consent-free. Future Bangla (F2): Noto Sans Bengali Variable added as a second family in the stack.

### 3.2 Type scale (fluid, `clamp()`; 1 rem = 16 px; mobile min at 360 px, desktop max at 1440 px)

| Token | Size (`clamp`) | Mobile → desktop | Weight | Line height | Letter spacing | Family | Use |
|---|---|---|---|---|---|---|---|
| `text-display` | `clamp(2.5rem, 1.75rem + 3.33vw, 5rem)` | 40 → 80 px | 800 | 1.02 | -0.03em | Manrope | Hero headline |
| `text-h1` | `clamp(2.125rem, 1.625rem + 2.22vw, 3.5rem)` | 34 → 56 | 800 | 1.08 | -0.025em | Manrope | Page H1 |
| `text-h2` | `clamp(1.75rem, 1.4rem + 1.56vw, 2.75rem)` | 28 → 44 | 700 | 1.12 | -0.02em | Manrope | Section headings |
| `text-h3` | `clamp(1.375rem, 1.225rem + 0.67vw, 1.875rem)` | 22 → 30 | 700 | 1.2 | -0.015em | Manrope | Sub-sections, card titles (large) |
| `text-h4` | `clamp(1.125rem, 1.05rem + 0.33vw, 1.375rem)` | 18 → 22 | 700 | 1.3 | -0.01em | Manrope | Card titles |
| `text-lead` | `clamp(1.125rem, 1.05rem + 0.33vw, 1.375rem)` | 18 → 22 | 400 | 1.55 | 0 | Inter | Intro paragraphs |
| `text-body` | `1rem` | 16 | 400 | 1.65 | 0 | Inter | Body |
| `text-body-lg` | `1.125rem` | 18 | 400 | 1.65 | 0 | Inter | Article body ≥ 768 px |
| `text-sm` | `0.875rem` | 14 | 400/500 | 1.5 | 0 | Inter | UI, captions, table cells |
| `text-xs` | `0.8125rem` | 13 | 500 | 1.45 | 0.005em | Inter | Meta, helper text (never below 13 px) |
| `text-overline` | `0.75rem` | 12 | 600 | 1.3 | 0.14em, uppercase | Inter | Eyebrows (≥ 4.5:1 required as small text) |
| `text-stat` | `clamp(2.25rem, 1.75rem + 2.22vw, 3.75rem)` | 36 → 60 | 800 | 1 | -0.03em, `tnum` | Manrope | Counters |
| `text-price` | `clamp(1.25rem, 1.15rem + 0.44vw, 1.5rem)` | 20 → 24 | 700 | 1.2 | -0.01em, `tnum` | Manrope | Prices |
| `text-fact` | `1.125rem` | 18 | 700 | 1.3 | 0, `tnum` | Manrope | At-a-glance values |

Admin uses the same families with a fixed (non-fluid) scale: page title 28/36 700 Manrope; card title 18/26 600 Manrope; body 14/22 Inter; table 14/20 Inter (13/18 compact); label 13/18 500; KPI value 36/40 800 Manrope `tnum` **[Dash]** KPI cards.

Number formatting styles: prices `৳ 1.45 crore` (compact) and `৳ 1,45,00,000` (full, compare table); areas `1,850 sft`; land `5 katha`; ranges with en dash `1,650–2,100 sft`.

Rules: max line length 68ch for prose (`max-w-prose` = 68ch); no font weights below 400 for text under 24 px; never all-caps for sentences (only overlines and short labels ≤ 3 words).

---

## 4. Spacing, grid and layout

| Token group | Values |
|---|---|
| Spacing scale | Tailwind 4 default (`--spacing: 0.25rem`): 1 = 4 px … 32 = 128 px. Component padding uses 2/3/4/6/8; section gaps 12/16/24 |
| Containers | `--container-content: 80rem` (1280 px), `--container-wide: 90rem` (1440 px, galleries and maps), `--container-narrow: 48rem` (768 px, articles/forms), prose 68ch |
| Gutters (inline padding) | 16 px (< 640), 24 px (640–1023), 32 px (1024–1279), 40 px (≥ 1280) |
| Breakpoints | `sm` 640, `md` 768, `lg` 1024, `xl` 1280, `2xl` 1536 (Tailwind defaults); container queries (`@container`) for cards and blocks |
| Grid | 4 columns < 768 (gap 16), 8 columns 768–1023 (gap 24), 12 columns ≥ 1024 (gap 24; 32 ≥ 1280) |
| Section rhythm | `--section-sm: clamp(2.5rem, 2rem + 2.2vw, 4rem)`; `--section-md: clamp(3.5rem, 2.6rem + 4vw, 6rem)`; `--section-lg: clamp(4.5rem, 3.2rem + 5.8vw, 8rem)` (block `spacing` sm/md/lg, CMS-PB-001). Heading → content gap 32–48 px |
| Radius | `--radius-xs` 4 px (chips), `--radius-sm` 6 px (inputs, small buttons), `--radius-md` 8 px (buttons), `--radius-lg` 12 px (cards), `--radius-xl` 16 px (modals, media), `--radius-2xl` 24 px (feature panels), `--radius-full` 9999 px (pills, avatars). shadcn `--radius` = 0.5rem. Restrained radii keep the engineered character **[Logo]** |
| Shadows | `--shadow-xs: 0 1px 2px rgb(16 12 11 / .06)`; `--shadow-sm: 0 1px 3px rgb(16 12 11 / .08), 0 1px 2px rgb(16 12 11 / .04)`; `--shadow-md: 0 6px 16px -4px rgb(16 12 11 / .10), 0 2px 4px rgb(16 12 11 / .05)`; `--shadow-lg: 0 16px 32px -8px rgb(16 12 11 / .14), 0 4px 8px rgb(16 12 11 / .05)`; `--shadow-xl: 0 28px 56px -12px rgb(16 12 11 / .22)`; glows §2.4 |
| Elevation map | 0 page; 1 cards (`xs`, hover `md`); 2 sticky header (`sm` after scroll), dropdowns (`md`); 3 drawers, sheets (`lg`); 4 modals (`xl`); toasts (`lg`) |
| Z-index | `--z-base 0`, `--z-raised 10` (card hover), `--z-sticky 100` (tab bar, filter bar), `--z-header 200`, `--z-compare-bar 250`, `--z-mobile-bar 260`, `--z-dropdown 300`, `--z-overlay 400`, `--z-drawer 410`, `--z-modal 500`, `--z-lightbox 600`, `--z-toast 700`, `--z-skip-link 800` |

---

## 5. Iconography and imagery

### 5.1 Icons

`@lucide/vue` (REQUIREMENT §12.1), 1.75 px stroke (2 px at ≤ 16 px). Sizes: 16 (inline, table actions), 20 (buttons, inputs), 24 (nav, feature cards), 32 (amenity tiles), 40 (empty states, inside 72 px circle). Icons inherit `currentColor`. Decorative icons `aria-hidden="true"`; icon-only buttons always have `aria-label` and a tooltip (fixes **[Dash]** unlabeled star/eye/toggle). Amenity icons chosen from an allowlist (≈ 60 names) mapped in admin (DM-AMEN.icon). Custom SVG set (6 icons, drawn on the logo's 60° grid): hexagon mark, katha, road width, facing/compass, RAJUK seal, handover key.

### 5.2 Photography art direction

- Architectural exteriors at golden or blue hour; verticals corrected; sky retained; no heavy HDR **[Rancon]**.
- Interiors with daylight, warm-neutral styling matching the stone palette; people sparingly and natural.
- Construction progress photos: wide, dated, consistent vantage point per project for comparison.
- No stock photos of generic skylines from other countries; no watermarks; renders labelled "Artist's impression" in captions (admin field `credit`).
- Team portraits: plain stone-100 background, 4:5, shoulders up, consistent lighting.

### 5.3 Aspect ratios and `sizes` per context

| Context | Ratio | `sizes` attribute | Loading |
|---|---|---|---|
| Hero (desktop) | fill container (`object-fit: cover`, focal point) | `100vw` | eager, `fetchpriority=high` |
| Hero mobile crop | 4:5 | `100vw` | eager |
| Project card | 4:3 | `(min-width:1280px) 400px, (min-width:768px) 45vw, 92vw` | lazy |
| Project detail gallery hero | 16:9 (desktop), 4:3 (mobile) | `(min-width:1440px) 1440px, 100vw` | eager first, lazy rest |
| Gallery thumbnails | 1:1 | `160px` | lazy |
| Floor plan | intrinsic (contain on stone-50) | `(min-width:1024px) 640px, 92vw` | lazy |
| News card | 16:10 | `(min-width:1280px) 400px, (min-width:768px) 45vw, 92vw` | lazy |
| Article cover | 16:9 | `(min-width:1024px) 960px, 100vw` | eager |
| Segment tile | 3:4 (desktop) / 16:9 (mobile) | `(min-width:1024px) 25vw, 92vw` | lazy |
| Team portrait | 4:5 | `(min-width:1024px) 280px, 45vw` | lazy |
| Partner logo | intrinsic, max-height 48 px | `160px` | lazy |
| OG image | 1.91:1 (1200×630) | — | — |

### 5.4 Placeholders and skeletons

- Image placeholder: `--muted` background with a centred 32 px hexagon outline at 20% opacity; for media with known dominant colour (computed at conversion: average colour of the `thumb`), that colour fills the box before load (no blur-up JS).
- Skeleton: `--muted` blocks with radius matching the element; shimmer = 1.4 s linear gradient sweep (`MOT-SKELETON`), disabled under reduced motion (static blocks).
- Map placeholder (CMP-MAP-02): stone-50 with a 60° triangular grid pattern (SVG, 2% teal), pin icon, area name and "Show interactive map" button.

## 13. Design tokens appendix

Copy-paste into `resources/css/app.css` (Tailwind v4.3 + shadcn-vue). Values match §2–§4 exactly.

```css
@import "tailwindcss";
@import "tw-animate-css";

@custom-variant dark (&:where(.dark, .dark *, .theme-dark, .theme-dark *));

:root {
  /* Brand scales */
  --teal-50: #EFF9FB; --teal-100: #DDF1F5; --teal-200: #BDE3EA; --teal-300: #91CCD7; --teal-400: #60AFBD;
  --teal-500: #3392A1; --teal-600: #027685; --teal-700: #055A66; --teal-800: #01464F; --teal-900: #01343C; --teal-950: #001F24;
  --coral-50: #FFF4F2; --coral-100: #FFE7E1; --coral-200: #FFCEC2; --coral-300: #FEA794; --coral-400: #EF7B63;
  --coral-500: #EC6B51; --coral-600: #B8432C; --coral-700: #963826; --coral-800: #782C1D; --coral-900: #5B2014; --coral-950: #3A0F07;
  --stone-50: #FEF9F7; --stone-100: #F7F2F0; --stone-200: #EAE5E3; --stone-300: #D9D3D0; --stone-400: #A6A09E;
  --stone-500: #797371; --stone-600: #5C5755; --stone-700: #46413F; --stone-800: #2F2A28; --stone-900: #1E1918; --stone-950: #100C0B;
  --success-50: #EFFAF1; --success-100: #DDF4E1; --success-200: #BEE7C5; --success-300: #93D39F; --success-400: #62B777;
  --success-500: #359A54; --success-600: #0A7E3A; --success-700: #03612A; --success-800: #004B1E; --success-900: #023816; --success-950: #00220A;
  --warning-50: #FFF5E9; --warning-100: #FDEAD0; --warning-200: #F7D5A6; --warning-300: #EBB66A; --warning-400: #D59219;
  --warning-500: #AF7603; --warning-600: #8D5E05; --warning-700: #6C4701; --warning-800: #543600; --warning-900: #402801; --warning-950: #271700;
  --error-50: #FFF4F2; --error-100: #FEE7E4; --error-200: #FECDC7; --error-300: #FEA69C; --error-400: #FE6C61;
  --error-500: #E2403A; --error-600: #C2181D; --error-700: #99020E; --error-800: #780108; --error-900: #5D0005; --error-950: #3A0002;
  --info-50: #F1F8FE; --info-100: #DFEFFE; --info-200: #BCE0FF; --info-300: #8AC8FC; --info-400: #56A9EB;
  --info-500: #2389D1; --info-600: #036EAE; --info-700: #025486; --info-800: #024068; --info-900: #013050; --info-950: #001C32;
  --dark-card: #092C31; --dark-muted: #14363C; --dark-border: #314D52; --dark-input: #6C858A;

  /* Semantic — light */
  --background: #FFFFFF; --foreground: var(--stone-900);
  --card: #FFFFFF; --card-foreground: var(--stone-900);
  --popover: #FFFFFF; --popover-foreground: var(--stone-900);
  --primary: var(--teal-600); --primary-foreground: #FFFFFF;
  --secondary: var(--teal-50); --secondary-foreground: var(--teal-800);
  --muted: var(--stone-100); --muted-foreground: var(--stone-600);
  --accent: var(--stone-100); --accent-foreground: var(--stone-900);
  --brand-accent: var(--coral-600); --brand-accent-foreground: #FFFFFF; --brand-accent-decor: var(--coral-500);
  --destructive: var(--error-600); --destructive-foreground: #FFFFFF;
  --success: var(--success-600); --success-foreground: #FFFFFF;
  --warning: var(--warning-600); --warning-foreground: #FFFFFF;
  --info: var(--info-600); --info-foreground: #FFFFFF;
  --border: var(--stone-200); --input: var(--stone-500); --ring: var(--teal-600);
  --surface-subtle: var(--stone-50); --surface-brand: var(--teal-50);
  --chart-1: var(--teal-600); --chart-2: var(--coral-500); --chart-3: var(--teal-800); --chart-4: var(--warning-500); --chart-5: var(--stone-500);
  --sidebar: var(--teal-950); --sidebar-foreground: var(--stone-200);
  --sidebar-primary: var(--coral-400); --sidebar-primary-foreground: var(--teal-950);
  --sidebar-accent: var(--dark-muted); --sidebar-accent-foreground: var(--stone-50);
  --sidebar-border: var(--dark-border); --sidebar-ring: var(--coral-400); --sidebar-muted: var(--teal-300);

  /* Gradients and glows */
  --gradient-brand: linear-gradient(135deg, #01464F 0%, #055A66 45%, #027685 100%);
  --gradient-dusk: linear-gradient(160deg, #001F24 0%, #01343C 55%, #055A66 100%);
  --gradient-sunrise: linear-gradient(135deg, #B8432C 0%, #EC6B51 60%, #FEA794 100%);
  --overlay: 0.45;
  --gradient-hero-bottom: linear-gradient(to top, rgb(16 12 11 / var(--overlay)) 0%, rgb(16 12 11 / calc(var(--overlay) * .55)) 45%, rgb(16 12 11 / 0) 100%);
  --gradient-hero-left: linear-gradient(to right, rgb(16 12 11 / var(--overlay)) 0%, rgb(16 12 11 / calc(var(--overlay) * .4)) 60%, rgb(16 12 11 / 0) 100%);
  --gradient-mesh: radial-gradient(60% 50% at 15% 20%, rgb(2 118 133 / .55), transparent 70%), radial-gradient(45% 40% at 85% 30%, rgb(236 107 81 / .35), transparent 70%), radial-gradient(55% 50% at 50% 100%, rgb(51 146 161 / .40), transparent 70%), #001F24;
  --glow-primary: 0 0 0 1px rgb(2 118 133 / .35), 0 8px 24px -6px rgb(2 118 133 / .55);
  --glow-accent: 0 0 0 1px rgb(236 107 81 / .35), 0 10px 28px -8px rgb(236 107 81 / .60);

  /* Layers (radius, shadows and easings live in @theme below) */
  --radius: 0.5rem;
  --z-base: 0; --z-raised: 10; --z-sticky: 100; --z-header: 200; --z-compare-bar: 250; --z-mobile-bar: 260; --z-dropdown: 300;
  --z-overlay: 400; --z-drawer: 410; --z-modal: 500; --z-lightbox: 600; --z-toast: 700; --z-skip-link: 800;

  /* Layout */
  --container-content: 80rem; --container-wide: 90rem; --container-narrow: 48rem;
  --section-sm: clamp(2.5rem, 2rem + 2.2vw, 4rem);
  --section-md: clamp(3.5rem, 2.6rem + 4vw, 6rem);
  --section-lg: clamp(4.5rem, 3.2rem + 5.8vw, 8rem);

  /* Motion */
  --dur-instant: 100ms; --dur-fast: 180ms; --dur-base: 280ms; --dur-slow: 480ms; --dur-reveal: 900ms; --dur-mask: 1200ms;
}

/* Static theme values: Tailwind emits these as :root variables AND generates utilities (rounded-lg, shadow-md, ease-out-expo) */
@theme {
  --radius-xs: 4px; --radius-sm: 6px; --radius-md: 8px; --radius-lg: 12px; --radius-xl: 16px; --radius-2xl: 24px;
  --shadow-xs: 0 1px 2px rgb(16 12 11 / .06);
  --shadow-sm: 0 1px 3px rgb(16 12 11 / .08), 0 1px 2px rgb(16 12 11 / .04);
  --shadow-md: 0 6px 16px -4px rgb(16 12 11 / .10), 0 2px 4px rgb(16 12 11 / .05);
  --shadow-lg: 0 16px 32px -8px rgb(16 12 11 / .14), 0 4px 8px rgb(16 12 11 / .05);
  --shadow-xl: 0 28px 56px -12px rgb(16 12 11 / .22);
  --ease-standard: cubic-bezier(0.2, 0, 0, 1); --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
  --ease-in-out: cubic-bezier(0.65, 0, 0.35, 1); --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Semantic — dark (admin .dark, and public .theme-dark sections) */
.dark, .theme-dark {
  --background: var(--teal-950); --foreground: var(--stone-50);
  --card: var(--dark-card); --card-foreground: var(--stone-50);
  --popover: var(--dark-card); --popover-foreground: var(--stone-50);
  --primary: var(--teal-300); --primary-foreground: var(--teal-950);
  --secondary: var(--dark-muted); --secondary-foreground: var(--stone-50);
  --muted: var(--dark-muted); --muted-foreground: var(--stone-400);
  --accent: var(--dark-muted); --accent-foreground: var(--stone-50);
  --brand-accent: var(--coral-400); --brand-accent-foreground: var(--teal-950);
  --destructive: var(--error-400); --destructive-foreground: var(--teal-950);
  --success: var(--success-400); --success-foreground: var(--teal-950);
  --warning: var(--warning-400); --warning-foreground: var(--teal-950);
  --info: var(--info-400); --info-foreground: var(--teal-950);
  --border: var(--dark-border); --input: var(--dark-input); --ring: var(--coral-400);
  --surface-subtle: var(--teal-950); --surface-brand: var(--dark-card);
  --chart-1: var(--teal-400); --chart-2: var(--coral-400); --chart-3: var(--teal-200); --chart-4: var(--warning-400); --chart-5: var(--stone-400);
}

@theme inline {
  --color-background: var(--background); --color-foreground: var(--foreground);
  --color-card: var(--card); --color-card-foreground: var(--card-foreground);
  --color-popover: var(--popover); --color-popover-foreground: var(--popover-foreground);
  --color-primary: var(--primary); --color-primary-foreground: var(--primary-foreground);
  --color-secondary: var(--secondary); --color-secondary-foreground: var(--secondary-foreground);
  --color-muted: var(--muted); --color-muted-foreground: var(--muted-foreground);
  --color-accent: var(--accent); --color-accent-foreground: var(--accent-foreground);
  --color-brand-accent: var(--brand-accent); --color-brand-accent-foreground: var(--brand-accent-foreground);
  --color-brand-accent-decor: var(--brand-accent-decor);
  --color-destructive: var(--destructive); --color-destructive-foreground: var(--destructive-foreground);
  --color-success: var(--success); --color-success-foreground: var(--success-foreground);
  --color-warning: var(--warning); --color-warning-foreground: var(--warning-foreground);
  --color-info: var(--info); --color-info-foreground: var(--info-foreground);
  --color-border: var(--border); --color-input: var(--input); --color-ring: var(--ring);
  --color-surface-subtle: var(--surface-subtle); --color-surface-brand: var(--surface-brand);
  --color-chart-1: var(--chart-1); --color-chart-2: var(--chart-2); --color-chart-3: var(--chart-3); --color-chart-4: var(--chart-4); --color-chart-5: var(--chart-5);
  --color-sidebar: var(--sidebar); --color-sidebar-foreground: var(--sidebar-foreground);
  --color-sidebar-primary: var(--sidebar-primary); --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
  --color-sidebar-accent: var(--sidebar-accent); --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
  --color-sidebar-border: var(--sidebar-border); --color-sidebar-ring: var(--sidebar-ring);

  --color-teal-50: var(--teal-50); --color-teal-100: var(--teal-100); --color-teal-200: var(--teal-200); --color-teal-300: var(--teal-300);
  --color-teal-400: var(--teal-400); --color-teal-500: var(--teal-500); --color-teal-600: var(--teal-600); --color-teal-700: var(--teal-700);
  --color-teal-800: var(--teal-800); --color-teal-900: var(--teal-900); --color-teal-950: var(--teal-950);
  --color-coral-50: var(--coral-50); --color-coral-100: var(--coral-100); --color-coral-200: var(--coral-200); --color-coral-300: var(--coral-300);
  --color-coral-400: var(--coral-400); --color-coral-500: var(--coral-500); --color-coral-600: var(--coral-600); --color-coral-700: var(--coral-700);
  --color-coral-800: var(--coral-800); --color-coral-900: var(--coral-900); --color-coral-950: var(--coral-950);
  --color-stone-50: var(--stone-50); --color-stone-100: var(--stone-100); --color-stone-200: var(--stone-200); --color-stone-300: var(--stone-300);
  --color-stone-400: var(--stone-400); --color-stone-500: var(--stone-500); --color-stone-600: var(--stone-600); --color-stone-700: var(--stone-700);
  --color-stone-800: var(--stone-800); --color-stone-900: var(--stone-900); --color-stone-950: var(--stone-950);

  --font-display: "Manrope Variable", "Manrope Fallback", ui-sans-serif, system-ui, sans-serif;
  --font-sans: "Inter Variable", "Inter Fallback", ui-sans-serif, system-ui, sans-serif;
  --font-mono: ui-monospace, "SF Mono", "Cascadia Code", Menlo, monospace;

  --text-display: clamp(2.5rem, 1.75rem + 3.33vw, 5rem);       --text-display--line-height: 1.02; --text-display--letter-spacing: -0.03em; --text-display--font-weight: 800;
  --text-h1: clamp(2.125rem, 1.625rem + 2.22vw, 3.5rem);        --text-h1--line-height: 1.08; --text-h1--letter-spacing: -0.025em; --text-h1--font-weight: 800;
  --text-h2: clamp(1.75rem, 1.4rem + 1.56vw, 2.75rem);          --text-h2--line-height: 1.12; --text-h2--letter-spacing: -0.02em; --text-h2--font-weight: 700;
  --text-h3: clamp(1.375rem, 1.225rem + 0.67vw, 1.875rem);      --text-h3--line-height: 1.2; --text-h3--letter-spacing: -0.015em; --text-h3--font-weight: 700;
  --text-h4: clamp(1.125rem, 1.05rem + 0.33vw, 1.375rem);       --text-h4--line-height: 1.3; --text-h4--letter-spacing: -0.01em; --text-h4--font-weight: 700;
  --text-lead: clamp(1.125rem, 1.05rem + 0.33vw, 1.375rem);     --text-lead--line-height: 1.55;
  --text-body: 1rem;      --text-body--line-height: 1.65;
  --text-body-lg: 1.125rem; --text-body-lg--line-height: 1.65;
  --text-sm: 0.875rem;    --text-sm--line-height: 1.5;
  --text-xs: 0.8125rem;   --text-xs--line-height: 1.45;
  --text-overline: 0.75rem; --text-overline--line-height: 1.3; --text-overline--letter-spacing: 0.14em; --text-overline--font-weight: 600;
  --text-stat: clamp(2.25rem, 1.75rem + 2.22vw, 3.75rem);      --text-stat--line-height: 1; --text-stat--letter-spacing: -0.03em; --text-stat--font-weight: 800;
  --text-price: clamp(1.25rem, 1.15rem + 0.44vw, 1.5rem);       --text-price--line-height: 1.2; --text-price--font-weight: 700;
  --text-fact: 1.125rem;  --text-fact--line-height: 1.3; --text-fact--font-weight: 700;

}

@layer base {
  * { @apply border-border; }
  html { -webkit-text-size-adjust: 100%; }
  body { @apply bg-background text-foreground font-sans antialiased; font-feature-settings: "cv11", "ss01"; }
  h1, h2, h3, h4, .font-display { font-family: var(--font-display); }
  .tabular { font-variant-numeric: tabular-nums; }
  :focus-visible { outline: 2px solid var(--ring); outline-offset: 2px; }
  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; scroll-behavior: auto !important; }
  }
}
```

Note: semantic colours use `@theme inline` (values resolve per scope, so `.dark`/`.theme-dark` switch them); fixed values (radius, shadows, easings) use plain `@theme`, which both emits the variables and generates utilities, so each is defined once. The `.theme-dark` scope reuses the `.dark` tokens so public dark sections and admin dark mode stay identical.
