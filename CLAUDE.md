@AGENTS.md

# Gemcon City website — project instructions (read fully; overrides AGENTS.md where they differ)

Public real-estate website + admin for Gemcon City Limited (Bangladesh). Laravel 13 · PHP 8.4 · Inertia v3 · Vue 3 (TS, Composition API) · Tailwind 4 · shadcn-vue (reka-ui) · Fortify · Wayfinder · MySQL 8 · shared hosting (no Node, no Redis). English only, BDT only. Solo developer; work is done in small sub-phases tested by hand. **Frontend first** (Part A, phases 0–9) for the client's review, then backend and admin (Part B, DEC-29).

## Session commands (the user types these)

- **`start X.Y`** — 1) Read the status table at the top of `docs/PHASES.md` and ONLY card `### X.Y` (Grep `^### X.Y ` then Read that range). Every dependency must be ✅; if not, stop and say so. 2) Read only the docs listed in the card's **Read** line — and inside big files only the named sections (Grep the heading/ID, Read with offset/limit). 3) Check `docs/DECISIONS.md` (short) for overrides. 4) If the card says "owner approves …", list the packages with versions and wait for a yes before installing. 5) Implement. 6) Run the checks below. 7) Reply with: what was built (files), anything deviating from the docs, and the card's manual **Test** checklist expanded into concrete clickable steps (URLs, widths, DevTools settings). Set the card status to 🧪 (awaiting test).
- **`fix X.Y: <problem>`** — fix a bug found while testing X.Y. Keep status 🧪.
- **`done X.Y`** — set status ✅, add a ≤10-line **Done notes** block under the card (key files/classes, deviations, things later phases must know), append any new decision to `docs/DECISIONS.md`, then `git add -A && git commit -m "Phase X.Y: <title>"`.
- **`status`** — print the status table summary and the next startable sub-phase.

## Docs (the source of truth — never guess)

| File | Holds |
| --- | --- |
| `docs/PHASES.md` | Status table + one card per sub-phase (scope, reads, test steps, done notes) |
| `docs/DECISIONS.md` | Build decisions DEC-26… (wins over spec and design) |
| `docs/spec/decisions.md` | Spec decisions DEC-01…25, open items OPEN-01…04 |
| `docs/spec/overview.md` | Glossary (sft, katha, BDT, NRB, RAJUK…), ID scheme, goals BG, personas, KPIs, references, scope, FR conventions |
| `docs/spec/pages.md` | Sitemap, page inventory PG-01…26, reserved slugs |
| `docs/spec/hero.md` | HERO-001…043 (modes, fields, image, video, LCP, search widget) |
| `docs/spec/projects.md` · `search.md` · `locations.md` · `leads.md` · `news.md` · `social.md` · `careers.md` · `shortlist.md` | Module rules FR-PROJ/SRCH/LOC/LEAD/NEWS/SOC/CAR/SHORT + acceptance criteria AC-* + each module's tables DM-* |
| `docs/spec/page-builder.md` | CMS-000, CMS-PB-*, the 28 block types, DM-PAGE, DM-BLOCK |
| `docs/spec/cms.md` | Settings groups §8.2, menus CMS-NAV, SEO fields §8.5, publishing CMS-PUB, caching CMS-CACHE, DM-SET/MENU/REV/SEO/SLUGH |
| `docs/spec/media.md` | CMS-MED-*, DM-MASSET/MFOLD/MUSE |
| `docs/spec/seo.md` | SEO-REN (snapshots), SEO-TEC, SEO-ONP, DM-REDIR/404/SNAP |
| `docs/spec/structured-data.md` | SEO-SD-* + JSON-LD examples |
| `docs/spec/performance-a11y.md` | SEO-PERF, SEO-ANA, SEO-A11Y |
| `docs/spec/admin.md` | ADM-AUTH, permission matrix §10.2, ADM-DASH, ADM-SCR, ADM-TBL, ADM-IO, ADM-LOG, admin notifications, DM-USER, DM-VIEW |
| `docs/spec/crud.md` | **Admin CRUD rulebook (DEC-33):** screens CRUD-*, delete/Recycle bin DEL-*, toasts TOAST-*, validation VAL-*, guidance HELP-*, entity matrix (editor type, delete behaviour, duplicate, order, phase) |
| `docs/spec/database.md` | DM conventions, ERD, morph map, framework tables, soft deletes, entity → file index |
| `docs/spec/architecture.md` | Pinned stack §12.1, library choices, folder structure, ARC-001…017 |
| `docs/spec/nfr-ops.md` | NFR-*, QA-* (overridden by DEC-26), DEP-* |
| `docs/spec/phase-map.md` | Original delivery plan M0–M7 (reference only) |
| `docs/design/foundations.md` | Principles DP, colour + contrast TOK-C, type scale, spacing/grid/z-index, icons, imagery + `sizes` table, placeholders, **token CSS (§13)** |
| `docs/design/components.md` | CMP-* public components and CMP-BLK-* block components |
| `docs/design/layouts.md` | LAY-* page layouts (D/T/M) and seeded block orders |
| `docs/design/motion.md` | MOT tokens, rules MOT-R01…07, Lenis coexistence, effects, loading strategy |
| `docs/design/patterns.md` | UX-01…12, responsive §10, accessibility A11Y-D01…13 |
| `docs/design/admin.md` | CMP-ADM-* and admin dashboard design §12 |

**Section map** — the docs still cite the original files (DEC-30); resolve them like this:
REQUIREMENT §1–4 + §7 intro → `spec/overview.md` · §5 → `pages.md` · §6 → `hero.md` · §7.1 → `projects.md` · §7.2 → `search.md` · §7.3 → `locations.md` · §7.4 → `leads.md` · §7.5 → `news.md` · §7.6 → `social.md` · §7.7 → `careers.md` · §7.8–7.9 → `shortlist.md` · §8 intro + §8.1 → `page-builder.md` · §8.2, 8.3, 8.5–8.7 → `cms.md` · §8.4 → `media.md` · §9.1–9.3 → `seo.md` · §9.4 → `structured-data.md` · §9.5–9.7 → `performance-a11y.md` · §10 → `admin.md` · §11 → `database.md` (entities in the module files) · §12 → `architecture.md` · §13–15 → `nfr-ops.md` · §16 → `phase-map.md` · §17–18 → `decisions.md`.
DESIGN §1–5 + §13 → `design/foundations.md` · §6.1–6.5 → `components.md` · §6.6 + §12 → `admin.md` · §7 → `layouts.md` · §8 → `motion.md` · §9–11 → `patterns.md`.
Behaviour conflict → spec wins; look/interaction → design wins. Missing or contradictory info → **ask the user**, then record the answer in DECISIONS.md. Never silently pick. `docs/REQUIREMENT.md` and `docs/DESIGN.md` are never read again once deleted (DEC-30).

## Golden rules (most-violated)

1. **Props-first** (DEC-29, ARC-002): pages render typed props shaped like API Resources — from `App\Support\Demo\DemoContent` in Part A, from Resources in Part B. No content or copy hard-coded in Vue except the CMS-000a list (field labels, validation messages, button/ARIA labels).
2. **SSR-safe** (SEO-REN-08): no `window`, `document`, `localStorage`, `matchMedia` in setup/render — only in `onMounted` or inside `<ClientOnly>`. `npm run build:ssr` must keep passing.
3. **Images only through `ResponsiveImage`** with the exact `sizes` preset (design/foundations.md §5.3): width/height always, lazy below the fold, exactly one `fetchpriority="high"` image per page (SEO-PERF-006, HERO-012).
4. **Content is visible without JS and without motion** (MOT-R02, HERO-032): animate only `transform`/`opacity`/`clip-path`/`filter` (MOT-R01); reduced motion / the footer toggle / settings → no motion chunk (MOT-R03).
5. **Semantic tokens only** (design §2.7): raw scale classes only in badges/charts; at most one coral CTA per viewport (DP-04); coral-500 never for small text (TOK-C11).
6. **One H1 per page, headings never skip, facts and stats are not headings** (SEO-ONP-003, CMS-PB-004).
7. **Action Pattern** (DEC-32): every write/business operation is one `final` class in `app/Actions/{Domain}/{VerbNoun}.php` with a single typed `handle()` and constructor injection. Controllers, jobs, listeners, commands and Fortify callbacks only authorise → validate → call the Action → respond. Reads use Query classes, API Resources and `app/Services`.
8. **Server is the validation truth:** a Form Request per form (ARC-004) with the VAL-01 defaults where the spec is silent; client checks mirror it and show limits before errors happen (VAL-03); input is never lost (VAL-06). Every admin route has a Policy and the UI hides what is not permitted (ARC-005).
9. **Admin CRUD follows `spec/crud.md`** (DEC-33): one TanStack `DataTable` per list (DEC-34), Delete = Recycle bin with Undo (DEL-*), usage guards instead of orphans, exactly one toast per mutation (TOAST-*), every screen explains itself (HELP-*). Check the entity matrix before building any admin screen.
10. **Hidden prices never leave the server** — not in HTML, JSON-LD or page props (AC-PROJ-01).
11. **Real links for crawl paths:** Wayfinder for URLs (DEC-28), `<a href>` for pagination and single-facet filters, `<button>` for multi-facet ones (SEO-TEC-023, FR-SRCH-007).
12. **Accessibility is part of done:** keyboard operable, visible focus, labels, targets ≥ 44 px on public pages, live announcements on navigation (SEO-A11Y-*, A11Y-D*).
13. **No paid APIs** (BG-06); third parties load late and behind consent (SEO-PERF-013).
14. Cite rule IDs (FR-…, CMP-…, MOT-…) in comments where the logic lives. Keep comments sparse.

## Checks before reporting a sub-phase (no test suite — DEC-26)

- PHP changed: `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --memory-limit=1G` (fix new errors).
- Frontend changed: `npm run types:check`, `npm run check`, `npm run build` and `npm run build:ssr`.
- Use Boost `search-docs` before using version-specific Laravel/Inertia/Fortify/Wayfinder/Tailwind APIs; `database-schema` instead of reading migrations.
- Do NOT write Pest, PHPUnit, Vitest or Playwright tests (DEC-26, overrides AGENTS.md "Test Enforcement"). Do not delete the existing starter-kit tests without the owner's approval.
- Optional for backend cards: a tinker check script `docs/checks/{X.Y}-{name}.php` that runs inside a rolled-back transaction and prints "expect …" lines.
