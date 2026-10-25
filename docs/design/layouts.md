# Page-level layouts LAY-* (DESIGN §7)

> **Visual direction:** `direction.md` is the source of truth for the public site's look (DEC-49) and wins over this file where they differ — e.g. type weights, card, button, badge and block designs. Behaviour, accessibility and motion rules here still apply.

> Verbatim from DESIGN.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 7. Page-level layouts

Notation: `D` desktop (≥ 1280), `T` tablet (768–1279), `M` mobile (< 768). Column counts refer to the 12/8/4 grid (§4). Every page uses CMP-HEAD-01, CMP-FOOT-01 and (except home) CMP-BREAD-01; mobile adds CMP-BAR-01 on PG-01, 02, 03, 04, 06, 10, 11.

### LAY-HOME (PG-01)

```
D ┌──────────────────────────────────────────────────────────────┐
  │ HEADER (transparent)                                          │
  │ HERO 100svh: eyebrow / H1 display / sub / CTAs   [▶ pause]   │
  │ ┌ SEARCH WIDGET (hero variant, one row) ─────────────────────┐│
  └──────────────────────────────────────────────────────────────┘
  INTRO STATEMENT  [statement 7 cols | image mask reveal 5 cols]
  FEATURED PROJECTS  H2 + "View all" | carousel 3 per view
  SEGMENT TILES  4 tall tiles, hover-expand
  STATS COUNTERS  4–6 across on --surface-brand
  ONGOING PROJECTS  grid 3 cols (project_grid status=ongoing)
  PROGRESS HIGHLIGHT  1–3 progress summaries
  TESTIMONIALS  carousel 3 per view
  PARTNERS MARQUEE  full-bleed
  LATEST NEWS  3 cards
  CTA BAND (landowner)  gradient mesh
  FOOTER
```

T: hero search in two rows; featured carousel 2.2 per view; segment tiles 2×2; stats 3 across. M: hero full height with search trigger button; intro image below text; carousels 1.15 per view (peek); segment tiles stacked 16:9; stats 2×2; testimonials 1 per view; news stacked; sticky contact bar after hero.

### LAY-PROJ-LIST (PG-02, PG-03)

```
D  BREADCRUMB · H1 (e.g. "Ongoing Projects in Bangladesh") · intro (PG-03, ≤ 2 lines + "Read more")
   FILTER BAR (sticky) ─ keyword | Segment | Area | Status | Size | Beds | Price | More || 24 results | Sort | Grid/Map
   ACTIVE CHIPS
   GRID 3 cols × 4 rows (12)        |  or MAP VIEW: list 5 cols (horizontal cards) + map 7 cols sticky
   PAGINATION
   LOCATIONS LINKS (top areas for this segment/status)
   FAQ (PG-03 when set) · CTA BAND
```

T: grid 2 cols; map view = full-width map with list below toggled. M: "Filters (n)" + "Sort" sticky buttons; 1-col cards; map view full-screen sheet with bottom card carousel synced to markers.

### LAY-PROJ-DETAIL (PG-04)

```
D  BREADCRUMB
   GALLERY HERO (1 large 8 cols + 2×2 tiles 4 cols, "View all 24 photos")
   TITLE BLOCK 8 cols: badges · H1 name · tagline · area, city (link) · facts strip (size, beds, handover)
                  4 cols: ENQUIRY PANEL (sticky from here down) — price-from / Price on request, Enquire, Book visit, Brochure, WhatsApp, hotline
   SECTION TAB BAR (sticky): Overview · At a glance · Amenities · Plans · Gallery · Video · Progress · Location · Documents
   8 cols content (panel continues in 4 cols):
     OVERVIEW rich text + highlights (image_text-like alternating)
     AT A GLANCE (CMP-FACTS-01, 3 cols inside)
     AMENITIES icon grid (4 cols)
     UNIT TYPES & FLOOR PLANS (CMP-UNIT-01)
     GALLERY (category tabs + masonry)
     VIDEO / VIRTUAL TOUR (CMP-VIDEO-01)
     DESIGN TEAM (architect/engineer/contractor cards)
     PROGRESS (CMP-PROG-01)
     LOCATION: CMP-MAP-02 + NEARBY (CMP-NEAR-01)
     DOCUMENTS (brochure + docs; gated ones show lock icon)
     FAQ (project FAQs)
   SIMILAR PROJECTS (3 cards, full width)
   CTA BAND
```

T: enquiry panel moves below title block as a full-width card, then reappears as sticky bottom bar (CMP-BAR-01 variant with "Enquire" + "Book visit"); content 8/8 cols. M: gallery strip (swipe) with counter; title block; facts strip 2×2; tab bar horizontal scroll; sections stacked; inline enquiry form (CMP-FORM-02) before similar projects; sticky contact bar.

### LAY-LOC-LIST (PG-05)

D: H1 "Locations" + intro; per city: H2 city name + area tiles 4 cols (image 3:2, name, "{n} projects"). T: 3 cols. M: 2 cols, compact tiles (image 1:1).

### LAY-LOC-DETAIL (PG-06)

```
D  AREA HERO (60svh image, H1 "Flats & Commercial Spaces in Bashundhara R/A", city chip)
   INTRO 8 cols (rich text) | AREA FACTS 4 cols (card list)
   SEGMENT PILLS (links to /locations/{area}/{segment} when they exist)
   PROJECTS IN AREA grid 3 cols (+ pagination if > 12)
   MAP OF AREA (CMP-MAP-01, 420 px)
   NEARBY AREAS (chips/tiles)
   FAQ · CTA BAND
```

T: intro full width then facts 2 cols. M: hero 50svh; facts as 2-col grid; map behind "Show map" button.

### LAY-ABOUT (PG-07) / LAY-SERVICES (PG-09) / LAY-LANDOWNER (PG-10) / LAY-NRB (PG-11) / LAY-CUSTOM (PG-25)

Builder pages: blocks stack in admin order using each block's layout (§6.5). Default seeded order:

| Page | Seeded blocks |
|---|---|
| About | page_header (image) → intro_statement → tabbed_pillars (mission, vision, values) → timeline → stats_counters → image_text (Gemcon Group parent) → awards → partners_marquee → cta_band |
| Services | page_header → feature_cards (5 services) → process_steps → image_text ×2 → faq → cta_band |
| Landowner | hero (medium) → feature_cards (benefits) → process_steps (5 steps) → rich_text (terms overview) → project_grid (status = handed_over, 3) → faq (landowner) → form (landowner) |
| NRB | hero (medium) → feature_cards (why buy) → process_steps (remote buying) → rich_text (payment/legal) → testimonials (nrb) → faq (nrb) → form (nrb) |

Block layouts at T/M follow §6.5 component responsiveness; forms: 7/5 split D, stacked T/M with side content above the form.

### LAY-TEAM (PG-08)

D: page_header; Leadership: 3-col large portraits with bio expand; Management: 4 cols; Team groups: 5 cols mini. T: 3/3/4. M: 1 col leadership (horizontal card), 2 cols others.

### LAY-NEWS-LIST (PG-12, PG-13)

D: H1 + category description (PG-13); featured post horizontal card (12 cols); category pills (tabs-pill, links); grid 3 cols × 3; pagination. T: 2 cols. M: 1 col; pills scroll horizontally.

### LAY-NEWS-DETAIL (PG-14)

D: narrow container (768): breadcrumb, category chip, H1, meta row (date · read time · author), cover 16:9 (wide container 1040), body prose (68ch), tags, share row, related projects (2 mini cards) in body end, author card; then full container: related posts 3 cols; newsletter CTA band. T/M: same single column; cover full-bleed on M.

### LAY-CAREERS (PG-15) / LAY-CAREER-DETAIL (PG-16)

PG-15 D: builder intro blocks; "Open roles" H2 + filters (department, location, type) + list rows (title, department, location, type, deadline, chevron) — rows are links; empty state with CV drop form. M: rows become stacked cards.
PG-16 D: 8 cols content (H1, meta chips, description sections) + 4 cols sticky summary card (deadline countdown text, "Apply now" scrolls to form); apply form full width below (2-col fields). M: summary card above content; sticky "Apply" button at bottom.

### LAY-CONTACT (PG-17)

D: page_header; 7/5 split: contact form | contact card (hotline large, WhatsApp, email, hours); offices row (cards with CMP-MAP-02 each); social row. T: stacked, offices 2 cols. M: hotline + WhatsApp buttons first (full width), then form, then offices.

### LAY-FAQ (PG-18)

D: H1 + search within FAQs (client-side filter); category tabs (vertical list 3 cols left, accordions 9 cols right). T/M: category select/pills on top, accordions full width; CTA "Still have questions?" band.

### LAY-SEARCH (PG-19)

Query input large (autofocus only when arriving with empty `q`), result groups (Projects as horizontal cards, News as compact rows, Pages as rows) each with "See all in projects" links; empty state with suggestions (popular areas). Single column all breakpoints, narrow container.

### LAY-SHORTLIST (PG-20) / LAY-COMPARE (PG-21)

PG-20: H1 "Your shortlist ({n})", note "Saved on this device only"; grid 3/2/1 cols with remove buttons; actions "Compare selected" and "Enquire about all". Empty state with CTA to projects.
PG-21: CMP-COMPARE-01 table; sticky header cards; M: horizontal scroll with first column pinned (120 px).

### LAY-THANKS (PG-22)

Centred narrow card: success icon (success-600 in 72 px circle), H1 (per form, editable), message, "What happens next" 3 steps, buttons (WhatsApp us, Back to project / Explore projects); similar projects row below (project forms).

### LAY-LEGAL (PG-23, PG-24)

Narrow container: H1, "Last updated {date}", table of contents (sticky left on D ≥ 1280, collapsible on T/M) generated from H2s, prose body.

### LAY-ERROR (PG-26)

Full-height centred: large status number (Manrope 800, `text-display`, coral-500 decorative), H1 message (editable), search box (404) + 3 popular projects (404), Home button; 503 shows maintenance message and expected return time. Minimal header (logo only) for 500/503 to avoid failing dependencies.
