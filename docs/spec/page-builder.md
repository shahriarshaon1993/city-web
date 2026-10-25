# CMS rule & page builder (REQUIREMENT §8 intro, §8.1, DM-PAGE, DM-BLOCK)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 8. Dynamic content management (`CMS`)

Rule CMS-000: every visible string, image, link and section on public pages is editable in the admin, except the structural chrome listed in CMS-000a. Acceptance: a content audit (QA-CMS-01) of every page in §5.2 finds no hard-coded copy outside CMS-000a.

CMS-000a (fixed in code, translatable later through `lang/en/*.php`): form field labels and validation messages, filter labels, button labels inside components ("Load more", "Close"), ARIA labels, error page technical text.

### 8.1 Page builder

| ID | Requirement |
|---|---|
| CMS-PB-001 | Builder pages (PG-01, 07, 09, 10, 11, 15 intro, 23, 24, 25) are an ordered list of blocks stored in DM-BLOCK. Each block has `type`, `data` (JSON validated per type), `is_visible` (toggle), `anchor_id` (optional, `^[a-z][a-z0-9-]{1,40}$`, unique per page), `background` (`default`, `muted`, `brand`, `dark`, `gradient`), `spacing` (`none`, `sm`, `md`, `lg`), `animate` (bool, default true). |
| CMS-PB-002 | Admin: block list (left), block form (right drawer), live preview (iframe, desktop/tablet/mobile widths 1280/834/390) refreshing 600 ms after the last edit. Drag-and-drop reorder (vue-draggable-plus) with keyboard alternative (Move up / Move down buttons, `Alt+↑/↓`). Duplicate, hide, delete (with undo toast 8 s). |
| CMS-PB-003 | Block data is validated server-side by a per-type `BlockSchema` class (Laravel validation rules) on save; invalid blocks are saved as draft-only and flagged red; publishing is blocked until fixed. |
| CMS-PB-004 | The first visible block's heading is the page H1 (hero `headline` or `page_header.title`); all other block headings render as H2 with internal H3s. A page without an H1-capable first block is blocked from publishing. |
| CMS-PB-005 | Max 30 blocks per page. |

**Block catalogue (28 types).** Component specs in `DESIGN.md` §6 (`CMP-BLK-*`).

| # | Type | Fields (required *) | Validation |
|---|---|---|---|
| 1 | `hero` | See §6.2–6.4 | §6 |
| 2 | `page_header` | title*, intro, background image, breadcrumb (bool) | title ≤ 80 |
| 3 | `intro_statement` | eyebrow, heading*, body (rich, ≤ 120 words), CTA, side image | — |
| 4 | `featured_projects` | heading*, source* (`featured`, `manual`, `segment`, `status`), manual IDs (≤ 12), segment, status, limit (3–12), layout (`carousel`, `grid`) | manual source needs ≥ 1 ID |
| 5 | `project_grid` | heading, preset filters (segment/status/area), limit (3–24), show "View all" link | — |
| 6 | `segment_tiles` | heading, tiles (2–4): segment*, image*, label, description | images ≥ 1200 px |
| 7 | `stats_counters` | heading, items (2–6): value* (number), suffix (≤ 4, e.g. "+", "M sft"), label* (≤ 40), decimals (0–1) | value ≤ 10^9 |
| 8 | `progress_highlight` | heading*, projects (1–3, must have progress updates) | — |
| 9 | `testimonials` | heading, source (`all`, `nrb`, `project`, `manual`), IDs, layout (`carousel`, `grid`), limit (3–12) | — |
| 10 | `partners_marquee` | heading, category filter, speed (`slow` 60 s, `normal` 40 s per loop), direction (`left`, `right`) | ≥ 4 partners |
| 11 | `latest_news` | heading, category (optional), limit (3, 6) | — |
| 12 | `cta_band` | heading*, text, primary CTA*, secondary CTA, style (`brand`, `gradient`, `image`), image | heading ≤ 70 |
| 13 | `rich_text` | body* (rich), width (`narrow` 68ch, `wide`) | — |
| 14 | `image_text` | heading*, body*, image*, image side (`left`, `right`), CTA, image reveal (`mask`, `none`) | — |
| 15 | `feature_cards` | heading, items (2–12): icon (Lucide name from allowlist) or image, title*, text, link; columns (2, 3, 4); tilt (bool) | — |
| 16 | `process_steps` | heading*, steps (2–8): title*, text* | — |
| 17 | `timeline` | heading*, items (2–30): year*, title*, text, image | year 1950–2100 |
| 18 | `tabbed_pillars` | heading, tabs (2–5): label*, title*, text*, image | — |
| 19 | `team_grid` | heading, group* (`leadership`, `management`, `team`) | — |
| 20 | `faq` | heading, source (`category`, `manual`), category, IDs | ≥ 1 FAQ |
| 21 | `gallery` | heading, images (2–40 from media), layout (`grid`, `masonry`) | alt required |
| 22 | `video` | heading, source* (`youtube`, `upload`), URL or media, poster*, captions file (WebVTT), caption | upload MP4 ≤ 50 MB; captions required for uploads with speech |
| 23 | `awards` | heading, items (1–24): image*, title*, issuer, year | — |
| 24 | `locations_grid` | heading, city filter, limit | — |
| 25 | `offices` | heading, office IDs (empty = all) | — |
| 26 | `form` | heading*, intro, form type* (`contact`, `callback`, `landowner`, `nrb`), side content (rich) | — |
| 27 | `quote` | quote* (≤ 400), person*, role, photo | — |
| 28 | `parallax_band` | image*, heading, text, height (`md` 60vh, `lg` 80vh), parallax (bool) | image ≥ 1920 px |

Plus `job_openings` is rendered by PG-15's template (not a block) to keep careers data in FR-CAR.

## Data model (REQUIREMENT §11.2)

**DM-PAGE `pages`** (SD): title varchar(120), slug varchar(80) unique (reserved list, §5.2), template varchar(12) (`builder`, `legal`), is_system bool (home/about/services/landowner/nrb/careers/privacy/terms — cannot be deleted or re-slugged), publish_status, published_at, pending_revision_id, lock_version, last_reviewed_at (legal pages show "Last updated").

**DM-BLOCK `page_blocks`**: page_id FK (cascade), type varchar(30), data json, is_visible bool, anchor_id varchar(42) null, background varchar(10), spacing varchar(4), animate bool, sort_order int. Index (page_id, sort_order). Media inside `data` also recorded in media_usages (field `blocks.{block_id}.{path}`).
