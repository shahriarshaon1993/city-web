# Build Phases

Status: ⬜ not started · 🔧 in progress · 🧪 built, awaiting manual test · ✅ done
Workflow and commands: see `CLAUDE.md`. A session reads this table + ONE card only — plus `docs/design/direction.md` in full for any card that renders public UI (DEC-49).

| ID   | Sub-phase                                                                  | Deps           | Status |
| ---- | -------------------------------------------------------------------------- | -------------- | ------ |
| 0.1  | Project setup, packages, public/admin entries                              | —              | ✅     |
| 0.2  | Design tokens & UI kit I (buttons, badges, controls, styleguide)           | 0.1            | ✅     |
| 0.3  | Images, formatters & props contract                                        | 0.2            | ✅     |
| 0.4  | Server-rendered head, shared props, URL normalisation, demo content        | 0.3            | ✅     |
| 1.1  | Site layout, header, mega menu, mobile menu                                | 0.4            | ✅     |
| 1.2  | Footer, breadcrumbs, mobile contact bar, navigation a11y, error pages      | 1.1            | ✅     |
| 2.1  | Hero (image, video, slider) & search widget                                | 1.2            | ✅     |
| 2.2  | Block renderer, project card, shortlist heart, home blocks I               | 2.1            | ✅     |
| 2.3  | Design refresh — premium direction (type, shape, card, demo photos)        | 2.2            | ✅     |
| 2.4  | Home blocks II (progress, testimonials, marquee, news, CTA)                | 2.3            | ✅     |
| 3.1  | Motion runtime, smooth scroll, reveals, counters                           | 2.4            | ✅     |
| 3.2  | Interactive effects & page transitions                                     | 3.1            | ✅     |
| 4.1  | Project listing & facet pages (filters, URL state, SEO rules)              | 3.1            | ✅     |
| 4.2  | Project detail I — gallery, title, section tabs, facts, amenities, plans   | 4.1            | ✅     |
| 4.3  | Project detail II — video, progress, location, documents, enquiry panel    | 4.2            | ✅     |
| 5.1  | Locations directory, area pages & interactive maps                         | 4.3            | ✅     |
| 6.1  | Content blocks I (page header, text, media, steps, timeline, pillars)      | 3.2            | ✅     |
| 6.2  | Content blocks II (team, FAQ, gallery, video, awards, locations, offices)  | 6.1, 5.1       | ✅     |
| 6.3  | Builder pages, legal pages, leadership, custom pages                       | 6.2            | ✅     |
| 7.1  | Form system & the seven lead forms                                         | 6.3, 4.3       | ✅     |
| 7.2  | Contact, thank-you, newsletter, consent banner, analytics layer            | 7.1            | ✅     |
| 8.1  | News list, archives & article                                              | 7.2            | ✅     |
| 8.2  | Careers, FAQ page, site search                                             | 8.1            | ✅     |
| 8.3  | Shortlist & compare                                                        | 8.2            | ✅     |
| 9.1  | Frontend hardening & client review                                         | 8.3, 3.2       | ✅     |
| 10.1 | MySQL, users, roles & permissions, Fortify hardening                       | 9.1            | ✅     |
| 10.2 | Admin shell, navigation, profile                                           | 10.1           | ✅     |
| 10.3 | Data table system (TanStack), users & roles, activity log                  | 10.2           | ⬜     |
| 10.4 | CRUD toolkit: editor layout, toasts, confirm & undo, Recycle bin           | 10.3           | ⬜     |
| 11.1 | Media upload, conversions & storage                                        | 10.4           | ⬜     |
| 11.2 | Media library UI, picker & focal point                                     | 11.1           | ⬜     |
| 12.1 | Settings                                                                   | 11.2           | ⬜     |
| 12.2 | Menus                                                                      | 12.1           | ⬜     |
| 12.3 | SEO base: redirects, 404 monitor, robots.txt                               | 12.1           | ⬜     |
| 13.1 | Schema & models: locations, projects, unit types, relations                | 12.3           | ⬜     |
| 13.2 | Locations & projects admin                                                 | 13.1           | ⬜     |
| 13.3 | Public projects & locations from the database                              | 13.2           | ⬜     |
| 14.1 | Draft, revisions, scheduling, preview, locking                             | 13.3           | ⬜     |
| 15.1 | Lead storage, spam protection, duplicates, assignment                      | 14.1           | ⬜     |
| 15.2 | Leads admin: table, board, detail drawer, subscribers                      | 15.1           | ⬜     |
| 15.3 | Notifications, autoresponder, webhook                                      | 15.2           | ⬜     |
| 16.1 | Pages & blocks: schema, block schemas, public render from DB               | 14.1, 12.2     | ⬜     |
| 16.2 | Page builder admin UI                                                      | 16.1           | ⬜     |
| 16.3 | News admin, rich text editor, RSS                                          | 16.1           | ⬜     |
| 16.4 | Testimonials, partners, FAQs, team, offices admin; remove demo content     | 16.2, 16.3     | ⬜     |
| 17.1 | Careers & applications                                                     | 16.4, 15.1     | ⬜     |
| 17.2 | Site search (Scout database engine)                                        | 16.4           | ⬜     |
| 17.3 | Import / export wizard, personal-data export & erase                       | 15.2, 16.4, 17.1 | ⬜     |
| 18.1 | Dashboard home                                                             | 17.1, 15.3     | ⬜     |
| 18.2 | Command palette, errors view, maintenance screen                           | 18.1           | ⬜     |
| 19.1 | Structured data (JSON-LD) & OG images                                      | 17.2           | ⬜     |
| 19.2 | Sitemaps & publish hooks                                                   | 19.1           | ⬜     |
| 19.3 | Snapshot pipeline (pre-rendered HTML on shared hosting)                    | 19.2           | ⬜     |
| 19.4 | Security headers, CSP, HTTP caching & CDN                                  | 19.3           | ⬜     |
| 20.1 | Backups, scheduler, deploy pipeline                                        | 19.4, 18.2     | ⬜     |
| 20.2 | Final QA & launch                                                          | 20.1           | ⬜     |

**Every card implicitly also requires:** CLAUDE.md golden rules; `design/foundations.md` tokens + `design/patterns.md` §11 (UI work); `spec/architecture.md` §12.4 + DECISIONS DEC-32 Action Pattern (backend work: every operation an Action in `app/Actions/{Domain}`, controllers/jobs/commands only call it); from 13.1 on every admin-managed model writes activity-log entries (spec/admin.md ADM-LOG-001). **Every admin CRUD card (10.4 on) also requires `spec/crud.md`** — its entity-matrix row for the entities built in that card, plus CRUD/DEL/TOAST/VAL/HELP rules, and the 10.4 toolkit (DEC-33). A card's Test always includes: create → edit → delete (Undo) → delete again → restore from the Recycle bin, each with its toast; submit invalid data → messages per VAL-02; the empty state; a disabled action explains itself. "§" = section heading inside the named file; FR-/CMP-/MOT-/SEO-… = IDs to Grep.
**Global "Off until later" (Part A, phases 0–9, DEC-29):** all content comes from `App\Support\Demo\DemoContent`; forms validate but store nothing until 15.1; no admin until 10.2; no snapshots (pages are CSR + server-rendered head) until 19.3; JSON-LD `[]` until 19.1; client-error endpoint until 18.2 (log once to the console instead).

---

## Part A — Public frontend (client review)

## Phase 0 — Foundation

### 0.1 — Project setup, packages, public/admin entries

**Deps:** — · **Read:** spec/architecture.md §12.1 (frontend rows), §12.3, ARC-008..010; spec/admin.md §10 intro (admin entry, no SSR); design/foundations.md §3.1; spec/performance-a11y.md SEO-PERF-004, SEO-PERF-007; DECISIONS DEC-26..29.
**Build:** owner confirms DEC-29 and approves packages. Check whether the starter's `laravel-vite-plugin` `fonts` helper self-hosts at build time. If yes, use it for Manrope (700/800) and Inter (400–600); otherwise use `@fontsource-variable/manrope` + `@fontsource-variable/inter` (Latin subset only). Optional `fontaine` 0.8.x (SEO-PERF-007). Split entries: `resources/js/app.ts` = public (pages under `pages/Site/*`), `resources/js/admin.ts` + `resources/views/admin.blade.php` = admin (starter auth/dashboard/settings pages move there unchanged until 10.2); `HandleInertiaRequests::rootView()` picks the root by route. Public root has no dark-mode script (DEC-28). `/` = `Site/Home` stub; remove `Welcome.vue`.
**Test:** `npm run build` and `npm run build:ssr` pass; `/` shows the stub in Manrope/Inter (DevTools → Computed font); `/login` still works on the admin root; view-source of `/` loads no admin chunk; no font request goes to a third-party host.

**Done notes (2026-10-25):**
- Entries: `resources/js/app.ts` (public + SSR entry, globs `pages/Site/**` only) and `resources/js/admin.ts` (all pages except `Site/**`, keeps theme + flash toasts). Roots: `views/app.blade.php` (no dark script) and `views/admin.blade.php`. No `ssr.ts`: `@inertiajs/vite` uses `app.ts`.
- `HandleInertiaRequests`: `ADMIN_ROUTES` name list → `rootView()` = `admin`; admin never SSR'd (`Inertia::disableSsr`); `version()` = manifest hash + `:app|:admin`, so a visit across roots gets a 409 and a full reload. 10.2: reduce the list to `admin.*` when the admin moves under `/admin`.
- Starter pages stay in `pages/auth`, `pages/settings` and `pages/Dashboard.vue` (resolved only by `admin.ts`) until 10.2.
- Fonts (DEC-35): `laravel-vite-plugin` 3.2.0 `fonts` helper, `bunny('Manrope', [700, 800])` + `bunny('Inter', [400, 500, 600])`, downloaded at build time into `/build/assets`, preload only Manrope 700 + Inter 400; `fontaine` 0.8.2 (dev) for fallbacks. CSS vars `--font-manrope`/`--font-inter` → `@theme` `--font-display`/`--font-sans` in `app.css`; 0.2 must use these, not "Manrope Variable".
- `/` = `Route::inertia('/', 'Site/Home', [title, lead])` stub; copy moves to `DemoContent` in 0.4. `Welcome.vue` deleted.
- `vite.config.ts` fmt ignores `docs/**`, `AGENTS.md`, `CLAUDE.md` (keep spec text verbatim, DEC-30).
- Public JS on `/`: `app` + shared Vue/Inertia chunk (~80 KB gzip) + `Home`. Public CSS still contains starter admin styles (~17 KB gzip, shared `app.css`).

### 0.2 — Design tokens & UI kit I

**Deps:** 0.1 · **Read:** design/foundations.md §1, §2 (all), §3.2, §4, §13 (token code); design/components.md CMP-BTN-01, CMP-BADGE-01, CMP-FORM-01 (look and states only), CMP-EMPTY-01, CMP-SKEL-01, CMP-TABS-01, CMP-ACC-01, CMP-TOAST-01; design/patterns.md A11Y-D01, A11Y-D04.
**Build:** `resources/css/app.css` replaced by DESIGN §13 (starter tokens removed). shadcn-vue components via CLI: button, badge, input, textarea, label, select, checkbox, radio-group, switch, tabs, accordion, skeleton, dialog, sheet, popover, tooltip, toggle-group, sonner. Revert any version bumps the CLI makes in `package.json`/lock. Button variants `primary/accent/secondary/outline/ghost/link/glow/on-media`, sizes `sm/md/lg/icon`, loading state. Badge `lifecycle/booking/featured/neutral`. `components/site/EmptyState`, skeleton shapes. Local-only `/styleguide` page (route registered only when `app()->isLocal()`): colour scales, semantic tokens in light and `.theme-dark`, type scale, buttons, badges, controls (all states), tabs, accordion, toast.
**Test:** `/styleguide` at 360/768/1440; colours match foundations §2.2 (coral CTA `#B8432C`, teal `#027685`); Tab through every control (2 px teal ring; coral ring inside `.theme-dark`); loading button keeps its width; disabled controls 50%; text never below 13 px.

**Done notes (2026-10-25):**
- Tokens: `resources/css/app.css` = foundations §13 + DEC-36 additions; fixed values in `@theme static` (all emitted), semantic in `@theme inline`. `cn()` (`lib/utils.ts`) knows the custom text sizes — add any new `text-*` token there too.
- `Button`: variants primary/accent/secondary/outline/ghost/link/glow/on-media/destructive, sizes sm/md(default)/lg/icon/icon-sm; `loading` prop; put a leading icon in `#icon` so the spinner can replace it. Hover uses `hover:not-disabled:`.
- `Badge`: `variant` (lifecycle/booking/status/featured/neutral) + `value` (enum), `size`, `on-media`, `dot`. Types `LifecycleValue`/`BookingValue`/`PublishStatusValue` exported from `ui/badge`.
- Text controls share `fieldControlClass` (+ `fieldReadOnlyClass` for real fields only — `:read-only` matches buttons) from `ui/input`. `Label required` renders "*" + sr-only "(required)". Checkbox/radio/switch have a 44 px hit area via `after:`.
- `TabsList variant="underline|pill"` (Reka `TabsIndicator`); `AccordionHeader` renders `h3` — pass `as` where the outline needs another level.
- Toasts: always `notify.*` from `lib/toast.ts` (durations); `<Toaster />` (ui/sonner) handles position/max 3. Errors lack `role="alert"` (vue-sonner limit). Public layout (1.1) must mount `<Toaster />` and `<TooltipProvider>`.
- `components/site/EmptyState.vue` (copy via props, `titleAs` default `p`), `SkeletonShape.vue` (5 shapes, `skeleton-shimmer` utility).
- `/styleguide` (local only, `Site/Styleguide`). Glow rotation (MOT-GLOW) left for 3.2. Admin starter buttons/inputs now 44 px until 10.2.
- shadcn CLI: bumps `package.json` ranges, adds a `cn` package and imports `cn` from `"cn"` — revert and fix to `@/lib/utils` after every `add`.

### 0.3 — Images, formatters & props contract

**Deps:** 0.2 · **Read:** design/foundations.md §5.2–5.4; spec/media.md CMS-MED-004, CMS-MED-005, CMS-MED-011; spec/hero.md HERO-010..014; spec/performance-a11y.md SEO-PERF-006; spec/architecture.md ARC-002, ARC-016, ARC-017; spec/overview.md §1.1 (BDT, sft, katha); DECISIONS DEC-31.
**Build:** owner provides photos in `storage/app/demo-src/{group}/` (client photos, or labelled royalty-free placeholders). `php artisan demo:images` (GD only): hero group → HERO-011 widths, others → CMS-MED-005 profile; AVIF only when `imageavif` exists; `og` 1200×630 focal crop; dominant colour from `thumb`; output `public/demo/…` + manifest. PHP `App\Support\Demo\DemoMedia` → `MediaImage` shape (alt, decorative, width, height, focal, dominant_color, sources avif/webp srcsets, fallback). `ResponsiveImage.vue`: `<picture>`, named `sizes` presets from §5.3, eager/lazy/`fetchpriority`, aspect box with dominant colour + hexagon placeholder, focal `object-position`, mobile-crop `<source media>`. `lib/format.ts` + PHP twin `App\Support\Format`: BDT full `৳ 1,45,00,000` and compact `৳ 1.45 crore` / `৳ 85 lakh`, `1,850 sft`, `5 katha`, en-dash ranges, `DD MMM YYYY` in Asia/Dhaka. Types in `resources/js/types/site.ts` (MediaImage, ProjectCard, Seo, SiteShared, MenuItem). Add demos to `/styleguide`.
**Test:** run `demo:images` → files per width/format exist, AVIF only if `php -r "var_dump(function_exists('imageavif'));"` is true; `/styleguide` image: Network shows AVIF (Chrome) and a smaller width at 390 px than at 1440 px; with "Slow 4G" the dominant colour shows before the image and nothing jumps; formatter samples match the strings above.

**Done notes (2026-10-25):**
- Images: `php artisan demo:images [--placeholders] [--force]` → Actions `app/Actions/Demo/{GenerateDemoImages,ConvertDemoImage,GenerateDemoPlaceholders}`. Sources `storage/app/demo-src/{group}/` + per-group `meta.json` (alt, focal [x,y], caption, credit, decorative); `hero` group = HERO-011 widths, `{name}-mobile` = HERO-013 crop. Output `public/demo/{group}/{slug}-{hash}/` + `public/demo/manifest.json`; incremental by content hash (DEC-38).
- Placeholders (owner, no client photos yet): groups hero(3 + mobile), projects 8, interiors 4, locations 6, news 6, team 6, progress 4 — keys like `projects/projects-03`. Neither folder is in git: every checkout/server runs `demo:images --placeholders` (~3.5 min, AVIF).
- `DemoMedia::image($key)` → `MediaImage` (+ `caption`, `credit`, `sources.jpeg` hero-only srcset); `ogImage($key)`, `keys($group)`. 11.1 must output this exact shape (DEC-31).
- `components/site/ResponsiveImage.vue`: props `image` (null = placeholder), `preset` (`lib/images.ts`, §5.3 verbatim), `mobile`, `priority` (the one LCP image), `loading`, `imgClass`; root is a block `span` — size/radius via `class`. Hero preset = `fill`: parent sets the size.
- Formatters `lib/format.ts` ⇄ `App\Support\Format` (`formatBdt/bdt`, `…Compact`, `…Range`, `Sft`, `SftRange`, `Katha`, `Date`, `Month`), no Intl number formatting; change both together (DEC-37). `/styleguide` table compares PHP vs TS.
- Types `resources/js/types/site.ts` (MediaImage, LcpImage, ProjectCard, Seo, MenuItem, MegaColumn, SiteInfo, SiteShared) — 0.4 wires `SiteShared` into `sharedPageProps` and `LcpImage` into the head preload. `ProjectCard.price_from_bdt` is null when hidden (AC-PROJ-01).
- `/styleguide` now served by `Site\StyleguideController` (local only).

### 0.4 — Server-rendered head, shared props, URL normalisation, demo content

**Deps:** 0.3 · **Read:** spec/seo.md SEO-REN-01, SEO-REN-07 (admin no SSR), SEO-REN-08, SEO-TEC-002, SEO-TEC-004, SEO-TEC-005 (`?page=1`), SEO-TEC-010 (non-production part), SEO-ONP-001, SEO-ONP-002, SEO-ONP-006; spec/hero.md HERO-030/031; spec/architecture.md ARC-001, ARC-003; spec/cms.md §8.2 (SEO, General, Contact, Header groups — key names only).
**Build:** `App\Support\Seo\SeoData` (title via template, description, robots, canonical, OG/Twitter, `jsonLd[]`, `lcpImage`). The public Blade head renders it with `inertia` attributes, plus one LCP preload (two with a mobile crop) and font preloads; `SeoHead.vue` mirrors it client-side. Non-production: meta robots + `X-Robots-Tag: noindex, nofollow`. `App\Support\Demo\DemoContent` (site, contact, social, menus, settings-like values) → shared props `site`, `menus`, `ui.motion` (`Inertia::once`). `NormalizeUrl` middleware (trailing slash, uppercase, `index.php`, duplicate slashes, `?page=1`) — permanent code. `ClientOnly.vue`. Thin `Site\HomeController`.
**Test:** view-source `/` → one `<title>`, description, canonical, OG tags, one image preload; Inertia-navigate to a stub page and back → head tags replaced, not duplicated (Elements panel); `/About/` → 301 `/about`; `/?page=1` → 301 `/`; local response has `X-Robots-Tag`.

**Done notes (2026-10-25):**
- `App\Support\Seo\SeoData`: `page($title, $desc)` (title template), `home()`, `raw()`; fluent `noindex()`, `canonical()`, `image()`, `article()`, `lcp($image, $mobile, $sizes)`, `jsonLd()`; `->toArray($request)` = the `seo` page prop (`Seo` in types/site.ts, incl. `preload[]`). Every public controller passes `'seo'`; without it the shared fallback is noindex,follow with the site name (DEC-39).
- Head: `app.blade.php` renders the tags inside `<x-inertia::head>` with `data-inertia` keys; `components/site/SeoHead.vue` (in `layouts/SiteLayout.vue`) renders the same `head-key`s, so SSR and client visits replace, not duplicate. Add a tag in both places. LCP preloads sit outside `<x-inertia::head>`, unmanaged. `app.ts` title callback is identity (title is final server-side).
- `layouts/SiteLayout.vue` exists now (default layout via `createInertiaApp({ layout })`) with only `<SeoHead />` — 1.1 extends it.
- Shared props (public only, `HandleInertiaRequests::sharePublic`): `site`, `menus`, `ui.motion` as once-props, `seo` fallback, `ui.consent` = null (7.2). Admin keeps `name`/`auth`/`sidebarOpen`; public pages get no `auth`. Vue: `usePage<SiteShared>()`.
- `DemoContent`: `settings($group)` (§8.2 key names; `seo.home_title/home_description` added), `site()` (+ `header`, `footer` with `{year}` filled), `motion()`, `menus()` (all 7 keys, root-relative URLs; header mega columns segments/status/locations — 1.1 adds `featured_projects`), `page($key)`. SeoData reads settings from it until 12.1.
- Global middleware: `AddNoindexHeader` (outer, so 301s get the header too) then `NormalizeUrl` (GET/HEAD only). Routes: `home` (`Site\HomeController`), `about` (`Site\PageController::about` → `Site/Page` stub until 6.3).
- Placeholder logos `public/images/brand/logo-{light,dark,mark}.svg`; contact/social = spec DEC-17 placeholders. Manual Wayfinder regen needs `--with-form`.
- Open: facet URL slugs `mixed-use`/`handed-over` vs enum `mixed_use`/`handed_over` — owner to confirm before 4.1 (comment in `DemoContent::menus()`).

## Phase 1 — Site shell

### 1.1 — Site layout, header, mega menu, mobile menu

**Deps:** 0.4 · **Read:** design/components.md CMP-HEAD-01, CMP-NAV-02, CMP-NAV-03, CMP-MODAL-01; design/foundations.md §4 (containers, gutters, z-index); design/patterns.md A11Y-D02, A11Y-D03, A11Y-D05, §10; spec/cms.md CMS-NAV-001..004 (menu shape only); spec/architecture.md ARC-010.
**Build:** `layouts/SiteLayout.vue` (persistent), skip link, `<main id="content" tabindex="-1">`. `SiteHeader`: transparent/solid/hidden (headroom), `transparent_on_hero`, hotline ≥ 1280, shortlist heart count slot, Enquire accent button. Mega menu (NavigationMenu) with `auto:segments`, `auto:locations`, `auto:featured_projects` and link columns from demo menus. Mobile menu sheet in the dark scope: accordion children, hotline/WhatsApp/Enquire, social. Motion here is CSS only (GSAP timeline arrives in 3.2).
**Test:** mega menu opens on hover (intent delay) and click/Enter, arrows move, Esc and outside click close; header transparent over a dummy hero, solid after 24 px, hides scrolling down > 120 px and returns on scroll up; at 390 px the menu traps focus, Esc closes, body does not scroll; the first Tab shows the skip link.

**Done notes (2026-10-25):**
- `layouts/SiteLayout.vue` owns `TooltipProvider`, `SkipLink`, `SiteHeader`, `<main id="content" tabindex="-1">` and `<Toaster />`; pages must not render `<main>` (or a second `<header>`). Main gets `pt-16 lg:pt-20` unless the header is over a hero. 1.2 adds the footer, mobile bar and route focus here.
- Transparent header = page prop `starts_with_hero: true` (type `SitePageProps`) + `site.header.transparent_on_hero`; only `Site\HomeController` sets it so far. Part B derives it from the first block.
- `components/site/header/`: `SiteHeader` (headroom via `composables/useHeadroom.ts`; pinned while focus is inside or a menu is open; `sticky=false` → absolute, no hiding), `DesktopNav` (Reka NavigationMenu, no viewport; list wrapper forced static so the mega panel spans the container; extra 150 ms close timer = 300 ms), `MegaPanel`, `MobileMenu` (Reka Dialog + Accordion, closes on `page.url` change and at ≥ 1024), `ShortlistLink` (`count` hard-wired to 0 — 2.2 connects the store; URL `/shortlist` until 8.3 adds the Wayfinder route).
- Menu/mega motion is CSS in `app.css` (`.mega-content`, `.mega-column`, `.mobile-menu`, `.mobile-menu-link` with `--i`), only under `prefers-reduced-motion: no-preference`; 3.1/3.2 must also gate it on the motion setting/footer toggle and swap in GSAP.
- `MenuItem.count` (auto:locations) and `icon` (allowlist `lib/icons.ts`); `components/site/SocialIcon.vue` (facebook/instagram/linkedin/youtube/x/whatsapp) — reuse in the 1.2 footer.
- `DemoContent::projectCards($featuredOnly)` — 8 demo cards (2 with hidden price → `price_from_bdt` null); reuse for 2.2 and 4.x.
- DEC-40: header ≤ 6 items (Contact in `mobile_extra`), `mega-card` preset, `formatPhone`/`Format::phone`, `logo_light` = white logo for dark surfaces.
- Overlays use the z tokens (`z-(--z-header|overlay|drawer|dropdown)`); shadcn tooltips/sheets default to `z-50` — pass the token class.

### 1.2 — Footer, breadcrumbs, mobile contact bar, navigation a11y, error pages

**Deps:** 1.1 · **Read:** design/components.md CMP-FOOT-01, CMP-BAR-01, CMP-BREAD-01; design/layouts.md LAY-ERROR; design/patterns.md A11Y-D06, UX-11, UX-12; design/motion.md MOT-PAGE (progress bar + dim only); spec/performance-a11y.md SEO-A11Y-003; spec/seo.md SEO-TEC-007; spec/pages.md PG-26.
**Build:** footer (gradient-dusk, about text, newsletter UI (submits in 7.2), 4 menu columns, offices, top 8 locations, legal menu, © `{year}`, parent-company link, social, "Reduce motion" toggle slot (wired in 3.1); columns become accordions on mobile). `Breadcrumbs` from a page prop. Sticky mobile bar: < 1024 only, appears after the hero, hides while an input has focus and while the footer is visible. Route-change focus + polite announcer. Inertia progress coral-500 after 150 ms; `.is-navigating` dim. Error pages 404/410/419/429/500/503 through Inertia's exception handler (404 = search box + 3 popular projects; 500/503 logo-only header). Toaster mounted.
**Test:** `/does-not-exist` → branded page with HTTP 404 (Network tab); breadcrumb on a stub page, last item not a link; at 390 px the bar appears after scrolling past the hero and hides when a field is focused; after a navigation the live region contains the new title and focus is on main.

**Done notes (2026-10-25):**
- Layout order: `SiteHeader`, `main#content`, `SiteFooter` (`#site-footer`), `MobileContactBar`, live region, `Toaster` (offset lifted above the bar at 640–1023 px). `composables/useNavigationFeedback.ts`: focus to main + "Navigated to {seo.title}" on route changes only (skips `preserveState`/`only` visits); `.is-navigating` dim after 150 ms (CSS in `app.css`); progress `var(--coral-500)`, delay 150 in `app.ts`. 3.2 adds the fade-in.
- Footer: `components/site/footer/{SiteFooter,FooterColumn}`; new once shared prop `footer` (`FooterInfo`: column headings = menu names, offices, top 8 areas) from `DemoContent::footer()`; `site.footer.newsletter_title/text`. Newsletter submit is a placeholder toast until 7.2; `#motion-toggle` slot is empty until 3.1. No-JS: `.footer-collapsible` noscript rule in `app.blade.php`.
- `components/site/Breadcrumbs.vue` takes the `breadcrumbs` page prop (`App\Support\Seo\Breadcrumbs::to($current, $parents)`); pages render it (not the layout). 19.1 reuses the trail for JSON-LD.
- `MobileContactBar`: mark the hero element with `data-hero` (2.1 HeroBlock must keep it); no hero → visible at once. 4.3 makes Enquire open the enquiry sheet on PG-04.
- Errors: `App\Support\Errors\ErrorPage` via `Inertia::handleExceptionsUsing` (bootstrap/app.php) → `Site/Error` (`ErrorPageProps`); 500/503 use `layouts/MinimalLayout.vue` (only `site` + `seo` props). Debug 500 keeps the exception page; previews at local `/styleguide/errors/{status}`. Admin/JSON untouched until 18.2 (`HandleInertiaRequests::isAdminRequest` is now public static).
- `lib/contact.ts` (`whatsappUrl(e164, text?)`, `socialLinks()`) — use for every WhatsApp/social link. 404 popular projects use an inline card: swap to `ProjectCard` mini in 2.2.
- Stub route `about.leadership` (`/about/leadership`) until 6.3. DEC-41 records the new settings keys.

## Phase 2 — Home page

### 2.1 — Hero (image, video, slider) & search widget

**Deps:** 1.2 · **Read:** spec/hero.md (all); design/components.md CMP-HERO-01/02/03, CMP-SEARCH-01, CMP-MODAL-01 (bottom sheet); design/motion.md MOT-HERO-VIDEO, MOT-SLIDE, MOT-CUE; design/foundations.md §2.4 (hero gradients), TOK-C27; spec/search.md FR-SRCH-002, FR-SRCH-008.
**Build:** owner approves `embla-carousel-vue` 8.6. `components/blocks/HeroBlock.vue`: single image/video, slider (2–5 images, autoplay off by default, pause/play always visible, pause on hover/focus/hidden tab); overlay styles with the 35 % minimum; heights reserved (CLS 0); H1 only as the first block. `useHeroVideo`: HERO-025 conditions, HERO-026 sequence, IntersectionObserver pause, sessionStorage pause state, error → poster stays. Search widget: real `<form method="get" action="/projects">`, segment toggle, grouped area combobox, status, size bands, bedrooms; single facet → clean URL (HERO-041); < 768 trigger + bottom sheet. Home renders the hero from the demo `home` block list; LCP preload from it.
**Test:** production build + Lighthouse mobile on `/`: LCP element is the hero image (or poster) and there is exactly one preload; DevTools emulation: reduced motion, Save-Data, Slow 3G or width < 1024 → no `<video>` request; video pause survives a reload in the same tab; slider keyboard + pause button; with JavaScript disabled the search submits to `/projects?…` (404 until 4.1); one facet → `/locations/{area}` style URL.

**Done notes (2026-10-25):**
- Packages: `embla-carousel-vue` 8.6.0 + `embla-carousel-fade` 8.6.0 (exact). Autoplay and bottom-sheet swipe are hand-written (DEC-43).
- Blocks: `types/blocks.ts` (`Block<Type, Data>` DM-BLOCK envelope, `HeroBlockData`, `PageBlock` union — 2.2 extends it). `DemoContent::homeBlocks($variant)`; Home passes `blocks` + `lead`; 2.2 swaps the `v-for` in `pages/Site/Home.vue` for the block renderer (pass `first` = index 0).
- Hero: `components/blocks/HeroBlock.vue` → `hero/HeroSingle` (`useHeroVideo`) or `hero/HeroSlider` (`useHeroSlider`), both on `hero/HeroShell` (overlay, text, CTAs, search, cue, `data-hero`; slots `media`/`controls`). `SeoData::lcpFromBlocks($blocks)` derives the preload — reuse for every block page.
- 3.1 must: add MOT-REVEAL-HERO (clip-path only), gate `.hero-cue`/`.hero-kenburns` (app.css) and the video on the footer motion toggle, stop Lenis while `BottomSheet` is open.
- Search: `components/site/search/ProjectSearch.vue` (`hero` variant; `initial` prop for 4.1's `inline`), native-first fields (DEC-42), `lib/search.ts` (`projectSearchUrl`, `projectSearchSummary`), options from `DemoContent::projectSearch()` (`types/search.ts`; settings group `search.size_bands`). 4.1 must normalise the no-JS `size=min-max` + empty params (301) and accept `area[]`.
- `components/site/BottomSheet.vue` (title, `trigger`/default/`footer` slots, popovers inside need `z-(--z-modal)`) — reuse for 4.1 filters.
- Demo video: `storage/app/demo-src/video/{name}.mp4|webm` → `DemoMedia::video('video/{name}')`, manifest `videos` key; 11.1 must output the `MediaVideo` shape.
- `POST /internal/client-errors` (route `client-errors.store`, CSRF-exempt, `throttle:client-errors` 10/min/IP) → `Actions/Monitoring/LogClientError` (log only; 18.2 Errors view reads it).
- Local previews `/styleguide/hero/{image|video|slider|centered}`.

### 2.2 — Block renderer, project card, shortlist heart, home blocks I

**Deps:** 2.1 · **Read:** spec/page-builder.md CMS-PB-001, CMS-PB-004, block rows 3–7; design/components.md CMP-CARD-01, CMP-STAT-01, §6.5 rows intro_statement, featured_projects, project_grid, segment_tiles, stats_counters; design/layouts.md LAY-HOME; spec/projects.md project types/statuses table, FR-PROJ-009, AC-PROJ-01, AC-PROJ-02; spec/shortlist.md FR-SHORT-001, FR-SHORT-005.
**Build:** `BlockRenderer` (type → lazy component; `background`, `spacing`, `anchor_id`, `animate`, `is_visible`; first visible block owns the H1). `ProjectCard` variants default/horizontal/mini/featured (one stretched link, heart as a separate button, facts in `<dl>`, price only when shown). `useShortlist` (localStorage `gc.shortlist`, max 20, hidden when storage throws; header count). Blocks intro_statement, featured_projects (Embla carousel 1.15/2.2/3 or grid), project_grid, segment_tiles, stats_counters (final values in HTML; counting comes in 3.1).
**Test:** home vs LAY-HOME at 360/768/1280/1536; one Tab stop per card link + one per heart; a demo project with `show_price=false` → no price anywhere in view-source; heart survives a reload and updates the header count; in a browser with site storage blocked the hearts disappear and the console has no error.

**Done notes (2026-10-25):**
- `components/blocks/BlockRenderer.vue` (type → component map; hero eager for LCP, other types `defineAsyncComponent`; skips `is_visible: false`; `first` = index 0 of the visible list). New block types: add to `PageBlock` in `types/blocks.ts` and to the map. Pages render `<BlockRenderer :blocks>` (Home does).
- `BlockSection.vue` = CMS-PB-001 envelope (background/spacing tokens per DEC-44, `id` = anchor, `data-block`, `data-animate`); slot props `headingTag` (h1 on the first block, else h2) + `headingId`; `labelled=false` when the block has no heading. `BlockHeader.vue` (heading + "View all" + `#actions`), `BlockLinkButton.vue` (BlockLink → `<Link>`/`<a>`).
- Blocks: `IntroStatementBlock` (`data-split` heading, `data-mask` image for 3.1), `FeaturedProjectsBlock` (Embla, arrows only; scroll-snap strip until Embla starts), `ProjectGridBlock` + shared `ProjectGrid`, `SegmentTilesBlock` (fixed desktop row height, flex-grow hover), `StatsCountersBlock` (`data-counter`/`data-value`/`data-decimals`, aria-hidden number + sr-only twin, `min-width` in ch — 3.1 animates these).
- `components/site/ProjectCard.vue` variants default/horizontal/mini/featured (parent sets `xl:col-span-2` for featured); `headingAs` prop; hover CSS `.project-card` / `.media-zoom` in `app.css` (3.1/3.2 must also gate it on the motion toggle). Compare checkbox → 8.3, tilt → 3.2.
- `composables/useShortlist.ts`: module-level store, filled in `onMounted` only (SSR/hydration see empty + `available=false`), `storage` event sync, full list → info toast. `ShortlistHeart.vue` renders only when `available`. 8.3 (PG-20) must reuse it and drop unknown IDs (FR-SHORT-002).
- `DemoContent::homeBlocks()` = 6 blocks; `projectCards($featuredOnly, $segment, $lifecycle, $limit)`; 6 demo projects featured (mega menu still takes 3). Stats figures are placeholders. 404 popular projects use the `mini` card.
- Open: unlayered `.theme-dark { background-color }` in `app.css` beats utilities — check the hero text wrapper (2.1) for a teal box; don't rely on `bg-transparent` over `.theme-dark`.

### 2.3 — Design refresh — premium direction

**Deps:** 2.2 · **Read:** DECISIONS DEC-46 (the whole scope); design/foundations.md §3.2, §5.3; design/components.md CMP-CARD-01, CMP-BTN-01, CMP-BADGE-01.
**Build:** owner reviewed the home page against the three references and asked for the same level of polish with our own identity. Tokens (light Manrope weights, greige surface), 8 px buttons + `light` variant, neutral on-media badges, CMP-CARD-01 rebuilt borderless with a 4:5 photo, block heading hairline, stats on dark, hero CTAs/overlay, header CTA over the hero, footer overlines, `.theme-dark` background into the base layer, stock demo photos via `demo:images`. New home sections (split image/dark panel, closing CTA band) are not added here: the CTA band is 2.4 (`cta_band`), the split panel is `media_text` in 6.1.
**Test:** `/` at 1440, 1024, 768 and 390 px next to rancondevelopments.com and btibd.com: headings light (300), no teal box behind the hero text, hero text readable on the photo, one coral button per viewport (search on the hero; Enquire turns coral once the header goes solid), project cards without border, photos 4:5, the hairline price rows level across a row (also with "Price on request"), facts read "1,650–2,100 sft · 3–4 beds" with a space before the unit; stats on dark with no wrapped suffix; `/styleguide` card variants (default, featured, horizontal, mini) and skeleton; `/404` popular projects (mini); keyboard Tab through a card = name link then heart, focus ring around the whole card; DevTools reduced motion → no image zoom.

**Done notes (2026-10-26):**
- Everything is DEC-46 (wins over foundations §3/§5.3/§13 and CMP-BTN/BADGE/CARD/BLK-STATS). Tokens in `app.css`; Manrope 300/400/500 in `vite.config.ts` (preload 300) — don't use Manrope 600+ (not loaded).
- Buttons `rounded-md` 8 px (pill rejected by the owner), new `light` variant; outline = `foreground/30`. Carousel arrows stay `rounded-full`. Footer Subscribe is `h-12` to match the email field.
- `ProjectCard` is borderless: 4:5 `project-card` preset, name + segment overline, location, `ProjectCardFacts` = one dotted `<dl>` line (`value&nbsp;unit`), `ProjectCardPrice` = hairline row with `min-h-12`. Hover CSS = `.media-zoom` + `.card-arrow-icon` only. Skeleton matches.
- On-media badges: neutral white chip; `Badge` colours the dot by tone (`dotTones`).
- `[data-overlay]` re-declares `--gradient-hero-bottom|left` so `--overlay` works; any new element using those gradients with its own opacity must carry `data-overlay`. `.theme-dark` bg/colour now in `@layer base` (utilities win).
- `BlockHeader` sits on a hairline; header Enquire is `light` while transparent over the hero.
- Stats: reserved `min-width` is on the number + suffix box; 3.1 animates only the inner `[data-counter]`.
- Demo photos: Unsplash (credits in each `storage/app/demo-src/{group}/meta.json`, team still placeholder). Git-ignored — 9.1 must copy `storage/app/demo-src` to the review server, then `php artisan demo:images`.

### 2.4 — Home blocks II

**Deps:** 2.3 · **Read:** spec/page-builder.md block rows 8–12; design/components.md CMP-PROG-01 (summary use), CMP-TEST-01, CMP-MARQ-01, CMP-NEWS-01, CMP-CTA-01, §6.5 rows progress_highlight, testimonials, partners_marquee, latest_news, cta_band; design/motion.md MOT-MARQUEE, MOT-GRAD, MOT-PULSE; spec/social.md FR-SOC-001, FR-SOC-002, AC-SOC-01; spec/news.md FR-NEWS-004.
**Build:** progress_highlight, testimonials (carousel/grid, `<figure><blockquote>`), partners_marquee (CSS, duplicate track `aria-hidden` + `inert`, pause on hover/focus-within, pause button), latest_news + `NewsCard` (no read time on home), cta_band (brand/gradient/image; animated mesh paused off-screen).
**Test:** full home review against LAY-HOME at all widths; marquee pauses on hover and when tabbing into it; the accessibility tree lists each partner once; with reduced motion the marquee is a static grid and the gradient static.

**Done notes (2026-10-26):**
- Blocks `ProgressHighlightBlock` (hairline rows of `site/ProgressSummary`), `TestimonialsBlock` (`site/TestimonialCard` variants `feature`/`card`), `PartnersMarqueeBlock`, `LatestNewsBlock` (lead + compact rows; `site/NewsCard` variants default/lead/compact/featured — 8.1 reuses it, pass `showReadTime` on PG-14), `CtaBandBlock` (centred, owner kept it). Look = DEC-48; visual rules now live in `design/direction.md` (DEC-49).
- `useCarousel` (Embla + arrows state, `selected`/`count`) + `CarouselArrows` — featured projects was refactored onto them; reuse for any new carousel. `useOffscreen` sets `data-offscreen` to pause CSS loops (marquee, `.cta-mesh`, `.progress-pulse`).
- `BlockSection` gained `width="full"` and `sectionClass`. New presets `progress-card`, `avatar`, `cta-band` (DEC-47), `news-lead`, `news-thumb` (DEC-48).
- SVG media (DEC-47): `sources.webp === ''` → `ResponsiveImage` renders a plain `<img>`; `demo:images` copies `*.svg`, `--placeholders` writes 10 fictional partner wordmarks. 11.1 must emit the same shape for SVG uploads.
- `DemoContent`: public `newsCards()`, `testimonials($nrbOnly)`, `partners()`; demo progress/testimonials/posts are placeholders.
- 3.1 must also stop the marquee, mesh and pulse under the footer "Reduce motion" toggle (today only `prefers-reduced-motion`), and count up `[data-counter]` in ProgressSummary too.

## Phase 3 — Motion system

### 3.1 — Motion runtime, smooth scroll, reveals, counters

**Deps:** 2.4 · **Read:** design/motion.md §8.1–8.3, §8.5, MOT-SMOOTH, MOT-REVEAL, MOT-REVEAL-HERO, MOT-SPLIT, MOT-STAGGER, MOT-MASK, MOT-COUNTER, MOT-SKELETON; spec/performance-a11y.md SEO-PERF-004, SEO-PERF-012, SEO-A11Y-005; spec/cms.md §8.2 Motion group.
**Build:** owner approves `gsap` 3.15 and `lenis` 1.3.27. `motion/index.ts` as a dynamic chunk, loaded after first paint via `requestIdleCallback` (1500 ms) only when motion is enabled, no reduced motion, and the footer toggle is off. Lenis in SiteLayout (GSAP ticker; anchor offset; `stop()/start()` hooked into the dialogs, sheets and menus built so far). Directives `v-reveal`, `v-split`, `v-mask`; counter helper (stats, later progress) — all no-ops until loaded. `gsap.context` per page with revert on unmount; `ScrollTrigger.refresh()` after navigate. Footer "Reduce motion" toggle (localStorage). Applied to shell + home.
**Test:** desktop home: reveals play once, nothing above the fold starts hidden (Performance panel screenshots); reduced-motion emulation → the motion chunk is never requested and everything is visible; toggle persists after reload; navigate away and back 10× → `ScrollTrigger.getAll().length` stays stable; anchor links land below the header; motion chunk ≤ 45 KB gzip (build output).

**Done notes (2026-10-26):**
- Packages: `gsap` 3.15.0 + `lenis` 1.3.27 (exact). Motion chunk = 53.9 KB gzip, over the 45 KB budget (libraries alone ≈ 55 KB) — accepted, DEC-50.
- `motion/bridge.ts` (always loaded): `register/unregister`, `startMotion/stopMotion`, `usePauseSmoothScroll(() => open)` — every new dialog/sheet/lightbox/menu must call it and put `data-lenis-prevent` on its scroll area. Lazy chunk = `motion/index.ts` + `effects.ts` + `smooth-scroll.ts`; never import gsap/lenis statically elsewhere (3.2's effects go into the lazy chunk too).
- Directives (global, `withApp`): `v-reveal` (`="delayMs"`, `.stagger` = children batch, `.hero` = `[data-hero-line]` + `[data-hero-ctas]`), `v-split`, `v-mask`, `v-counter="index"` on a `[data-counter]` span. Each binding = own `gsap.context`; later tweens go through `within()`. Already-passed elements are never touched (MOT-R02). `v-split` targets must hold static text.
- Client-visit mounts attach two frames later (after Inertia's scroll reset); hero attaches at once. Hero reveal plays on client visits only (DEC-50).
- Motion off = OS setting, footer `MotionToggle` (`gc.reduce-motion`) or Settings → animations → `data-reduce-motion` on `<html>` (server + inline script in `app.blade.php`; 19.4 must give that script a CSP nonce). `motion-safe:`/`motion-reduce:` are redefined in `app.css`; custom CSS motion blocks use `@media (…) { :root:not([data-reduce-motion]) { … } }` (top-level `@variant` breaks). JS checks use `isMotionReduced()` / `useReducedMotion()` from `motion/preference.ts`, never `matchMedia` alone.
- Anchor offset = `html { scroll-padding-top }` (`--header-height` + `--tabbar-height` + 1rem): 4.2's sticky tabs set `--tabbar-height`. Console hook `__gcMotion.scrollTriggers()`.

### 3.2 — Interactive effects & page transitions

**Deps:** 3.1 · **Read:** design/motion.md MOT-R04..R07, MOT-PARALLAX, MOT-MAGNETIC, MOT-TILT, MOT-GLOW, MOT-MENU, MOT-PAGE, MOT-CARD-IMG, MOT-UNDERLINE, MOT-LINE, MOT-PORTRAIT, MOT-ACC, MOT-TAB; design/patterns.md UX-01.
**Build:** `v-parallax`, `v-magnetic`, `v-tilt` (pointer-fine only), glow rotation, mobile-menu GSAP timeline, page enter transition (client visits only), `MOT-LINE` helper for process steps (used in 6.1); audit CSS hover effects for `(hover: hover)`.
**Test:** touch emulation → no magnetic/tilt, parallax halved; Performance recording of a home scroll with 4× CPU slowdown stays smooth (no long tasks from motion setup); the first page load never animates; page transitions keep focus/announcement from 1.2.

**Done notes (2026-10-26):**
- Lazy chunk adds `motion/interactive.ts` (`v-parallax`, `v-magnetic`, `v-tilt`, `v-line`), `motion/menu.ts` (MOT-MENU), `motion/measure.ts` (`compact`/`finePointer`/`passed`). Bridge attaches every entry in idle slices (≤ 8 ms, MOT-R06), on first load and client visits; hero `.hero` reveals still attach at once.
- `v-parallax="N"` (% of frame, ≤ 10, halved on touch/small): needs a `[data-parallax-layer]` child that overflows the frame by N % top and bottom from first paint (`-inset-y-[N%]`). Hero = 8 (`HeroShell`). 6.1 page_header / parallax_band use the same contract.
- `v-magnetic` (6 px, `[data-magnetic-label]` inline-flex span moves 1.3×): hero CTAs + CTA band. `v-tilt` (`[data-tilt-depth]`, `[data-tilt-glare]`) and the `glow` ring (`.glow-ring`) are styleguide-only (DEC-51). `v-line` on the steps wrapper, `[data-line-path]` = SVG shape or an HTML rule (`data-line-axis="x"`).
- Mobile menu: `DialogContent force-mount v-if` under `<Transition :css="false">` → `animateMenu()` from the bridge (instant without runtime); groups carry `data-menu-link`. New sheets that want a GSAP timeline follow the same pattern.
- MOT-PAGE enter = Web Animations on `#content` in `useNavigationFeedback` (only on visits that announce). `.link-line` span = MOT-UNDERLINE for text links (footer now; inline links in 6.1). Custom `:hover` CSS lives in `@media (hover: hover)`.
- Motion chunk 55.31 KB gzip, 0.31 KB over DEC-50's 55 KB — open, see DEC-51.

## Phase 4 — Projects

### 4.1 — Project listing & facet pages

**Deps:** 3.1 · **Read:** spec/search.md (all); spec/pages.md PG-02, PG-03; spec/seo.md SEO-TEC-005, SEO-TEC-020..024; design/components.md CMP-FILT-01, CMP-PAGI-01, CMP-EMPTY-01; design/layouts.md LAY-PROJ-LIST; design/patterns.md UX-07, UX-08; design/motion.md MOT-SKELETON.
**Build:** routes `site.projects.index`, `/projects/type/{segment}`, `/projects/status/{status}`. Filter normaliser (drop unknown params, cast/clamp, swap min > max, ≤ 5 areas, `q` 80 chars, alphabetical order), single-facet 301, noindex/canonical rules — permanent code. `App\Support\Demo\DemoProjectSearch` (filters, sorts, facet counts, pagination over demo projects; 13.3's Eloquent search returns the same shape). Filter bar + mobile sheet, chips, sort, Grid/Map toggle (map arrives in 5.1), partial reload `only: projects, facets, meta`, debounce 350 ms `replace` for typing, `push` for selections, aria-live count, dim + skeleton after 300 ms, real `<a href>` pagination, page beyond last → 404, empty state with newest 3, location links, facet intro/FAQ, CTA.
**Test:** AC-SRCH-01..04 by hand; `/projects?segment=commercial` → 301 `/projects/type/commercial`; view-source `/projects?beds=3&segment=residential` → `noindex,follow` + canonical `/projects/type/residential`; Back undoes a selection, typing adds no history entries; `?page=99` → 404; mobile sheet "Show n projects" updates live.

**Done notes (2026-10-26):**
- `App\Support\Search\ProjectFilters` (permanent): `fromQuery($query, $areaSlugs)` normalises; `url()` = the one URL (landing path for one segment/status/area, else alphabetical query, `area[]` literal); `matches($path, $rawQuery)` compares decoded → controller 301s otherwise; `isIndexable()`/`canonicalPath()` = SEO-TEC-021. `lib/listing.ts` `listingUrl()` is its client twin — change both. Facet slugs hyphenated (`ProjectFilters::urlSlug/enumValue`), DEC-52.
- Area landing = `Route::has('site.locations.show')`: 5.1 must name the area route exactly that; single areas then 301 to it automatically (also fix the hero search, which already links `/locations/{area}`).
- `Site\ProjectListingController` → `Site/Projects`; props `projects`/`facets`/`meta` (DemoProjectSearch shape, `types/listing.ts`) + `listing` (H1, intro, FAQ, breadcrumbs, location links), `newest`, `copy`, `cta`. 13.3 replaces `DemoProjectSearch` with a Query class returning the same arrays; 5.1's PG-06 reuses the page components with an area facet.
- `useProjectListing` (provide/inject `useListing()`): `update()` push, `type()` 350 ms replace, partial reload `only: projects, facets, meta, listing, seo`; focus restore via `data-filter-key` (keep keys unique per instance: `idPrefix`). `useNavigationFeedback` no longer dims `main` for `only` visits.
- Demo data: `App\Support\Demo\DemoProjects` (26 rows, IDs 1–8 unchanged, areas with cities, computed counts, `card()`); DemoContent delegates (`projectListing()`, `projectListingCta()`).
- `SeoData`: an explicit `canonical()` now survives `noindex()` (DEC-52). `.sticky-under-header` + `data-header-hidden` on `<html>` (SiteHeader) — 4.2 section tabs use the same class. `ProjectCard`/`ProjectCardMedia` `priority` prop; listing preloads the first card.
- Map view = list + placeholder panel (`copy.map_*`); 5.1 swaps the panel for the map.

### 4.2 — Project detail I — gallery, title, section tabs, facts, amenities, plans

**Deps:** 4.1 · **Read:** spec/projects.md FR-PROJ-001..003, FR-PROJ-009, AC-PROJ-01/02, edge cases; spec/pages.md PG-04; design/layouts.md LAY-PROJ-DETAIL; design/components.md CMP-GAL-01, CMP-TABS-02, CMP-FACTS-01, CMP-UNIT-01; design/foundations.md §5.3 (gallery, floor plan rows); spec/seo.md SEO-ONP-001/002 (project templates), SEO-ONP-005.
**Build:** owner approves `photoswipe` 5.4. Route `site.projects.show` + `ProjectDetail` type. Gallery hero (desktop 1 + 4 tiles, "View all n photos"; mobile Embla strip + counter); PhotoSwipe lightbox with category tabs and captions. Title block (badges, H1, tagline, area link, facts strip). Sticky section tab bar (IntersectionObserver, empty sections hidden, Lenis offset). Overview + highlights. At a glance `<dl>` (RAJUK copy button, custom facts). Amenities grid (Lucide allowlist). Unit types: tabs, or a select < 640; floor plan → lightbox; price rules; sold out styling. Meta title/description templates.
**Test:** lightbox ←/→/Esc, pinch on a phone, focus returns to the tile; the tab bar follows the scroll; a land demo project has no Plans tab; only `show_price` units show a price (view-source); breadcrumb Home › Projects › name.

**Done notes (2026-10-26):**
- `photoswipe` 5.4.4 (exact), loaded on first open only (`composables/useLightbox.ts`: category jump buttons, caption + credit, Lenis paused, focus returned). Reuse it for 4.3's progress photo strips.
- `Site\ProjectController@show` → `Site/Project`; props `project` (`types/project.ts` ProjectDetail) + `copy`. Data from `App\Support\Demo\DemoProjectDetail` (derived from the 26 DemoProjects rows; `DemoProjects::card()` now typed `ProjectCardData`). 13.3 swaps in ProjectDetailResource with the same shape and adds AC-PROJ-06 (410/redirect) and slug history.
- Price rule (DEC-54): unit `price_bdt` only when project **and** unit show prices; `price_from_bdt` = min shown unit price. `App\Support\Seo\ProjectMeta` fills the SEO-ONP-001/002 templates (settings `seo.project_*_template`).
- Components in `components/site/project/`; `ProjectSection` = anchor section (id + H2). Sections/tabs hide when empty (land → no Plans). 4.3 adds its sections there and the enquiry panel in columns 9–12 of the content grid in `pages/Site/Project.vue` (currently empty); the tab bar already shows name + price once stuck.
- `SectionTabs` sets the anchor offset through `html:has(.section-tabs) { --tabbar-height }`. New presets `gallery-tile`, `gallery-masonry`, `project-highlight`; amenity icon allowlist `lib/amenity-icons.ts` (13.2 admin uses it).
- Demo floor plans: `GenerateDemoFloorPlans` (SVG, `demo:images --placeholders`, group `plans`). "Enquire about Type X" links to `{header cta}?project=…&unit=…` until 7.1 opens the form.
- `৳` renders via the local-only `'Taka Sign'` @font-face (app.css), first in both font stacks — site-wide.

### 4.3 — Project detail II — video, progress, location, documents, enquiry panel

**Deps:** 4.2 · **Read:** spec/projects.md FR-PROJ-004..007, AC-PROJ-07, edge cases (handed-over heading); spec/locations.md FR-LOC-007; spec/leads.md FR-LEAD-013; design/components.md CMP-VIDEO-01, CMP-PROG-01, CMP-NEAR-01, CMP-MAP-02, CMP-ENQ-01, CMP-BAR-01 (PG-04 variant), CMP-PERSON-01 (mini), CMP-ACC-01; design/layouts.md LAY-PROJ-DETAIL (T/M).
**Build:** video facade (`youtube-nocookie` iframe on click, native `<video>` with captions track), virtual-tour link; progress (stat, 4-stage bar, `role="progressbar"`, timeline with photo strips → lightbox); map facade → keyless Google embed on click + "Open in Google Maps"; nearby list; design team; documents (gated lock icon); project FAQ; similar projects (3); enquiry panel (sticky desktop, inline card on tablet, sticky bar on mobile) with dialog shells for Enquire / Site visit / Brochure (forms filled in 7.1); WhatsApp prefilled link.
**Test:** no iframe or map request until clicked (Network); progress bar read correctly by the accessibility tree; the panel stays sticky and stops before similar projects; WhatsApp URL text is encoded and names the project; the handed-over demo project shows the resale heading.

**Done notes (2026-10-26):**
- Props: `ProjectDetail` gained `video`, `virtual_tour_url`, `progress` (newest first), `coordinates`, `address`, `nearby`, `design_team`, `documents` (gated = `url: null`, AC-PROJ-05), `faq`, `similar` (AC-PROJ-07), `enquiry` (heading incl. handed-over edge case, WhatsApp E.164 + prefilled text); page prop `cta`; `copy` holds texts + `progress_stages`. Demo data: `App\Support\Demo\DemoProjectSections` (13.3 maps relations to the same shape).
- Components in `components/site/project/` (ProjectVideo, ProjectProgress, NearbyPlaces, DesignTeam, ProjectDocuments, ProjectFaq, EnquiryPanel, EnquiryDialog); site-level `MapFacade.vue` + `lib/maps.ts` (reuse for 6.2 offices) and `ProgressBar.vue` (home ProgressSummary uses it).
- **7.1:** put CMP-FORM-02 into `EnquiryDialog`'s default slot per `kind` (`enquire` → project_inquiry, `visit` → site_visit, `brochure` → brochure_download); no-JS links are `/contact?project={slug}&type=…`. Unit "Enquire about Type X" still links (4.2).
- `composables/useContactBar.ts`: a page overrides the mobile bar (WhatsApp text, Enquire handler) from onMounted. ProjectGallery carries `data-hero`, so the bar appears after the gallery.
- **7.2:** fire GA4 `whatsapp_click` on the PG-04 WhatsApp links (panel, dialog, mobile bar); nothing is tracked yet.
- `demo:images --placeholders` writes placeholder PDFs (`documents/`) and `video/project.vtt`; drop `video/project.mp4` for Lakeview's upload demo. YouTube placeholder `aqz-KE-bpKQ` and the Matterport tour URL are demo-only.
- New preset `video-poster`; `.sticky-under-tabs` (app.css) for the panel; `--color-whatsapp` (icon only); `formatFileSize` ⇄ `Format::fileSize`.

## Phase 5 — Locations & maps

### 5.1 — Locations directory, area pages & interactive maps

**Deps:** 4.3 · **Read:** spec/locations.md (all); spec/pages.md PG-05, PG-06; spec/search.md FR-SRCH-006; design/layouts.md LAY-LOC-LIST, LAY-LOC-DETAIL, LAY-PROJ-LIST (map view); design/components.md CMP-MAP-01; design/motion.md §8.3 (maps).
**Build:** owner approves `leaflet` 1.9.4 + `leaflet.markercluster` 1.5.3. Routes `/locations`, `/locations/{area}`, `/locations/{area}/{segment}` (exists only with ≥ 2 projects in that segment; indexability per FR-LOC-003/004). Area hero, intro, facts, segment pills, project grid + pagination, nearby areas, FAQ, CTA. `ProjectMap` (loads on viewport or toggle, hexagon markers, clusters, mini-card popups, visually hidden project list, wheel zoom only after click, `data-lenis-prevent` while active, reset view). Map view on `/projects?view=map` (list + map desktop, full-screen sheet mobile).
**Test:** AC-LOC-01/02 on demo data; scroll the page over the map without zooming it; Tab reaches markers; mobile map view opens and closes; no tile request before the map is visible.

**Done notes (2026-10-27):**
- `leaflet` 1.9.4 + `leaflet.markercluster` 1.5.3 (exact), `@types/leaflet` 1.9.22 + `@types/leaflet.markercluster` 1.5.6 (dev). `lib/leaflet.ts` loads them + `resources/css/map.css` on first map only (sets `window.L` first: the cluster plugin is UMD). **19.4:** CSP must allow `tile.openstreetmap.org` (img-src).
- `Site\LocationController` → `Site/Locations` (`directory`) and `Site/Location` (`area`, `map`, `copy` + the listing's `projects`/`facets`/`meta`, so `ListingPagination`/`useProjectListing` work there). Area pages accept only `page`; anything else 301s to `/projects?…`. Area × segment 404 below 2 projects. 13.3 swaps `DemoLocations` / `DemoProjectSearch::mapProjects()` / `segmentCounts()` for the Location model + Query (same shapes).
- `ProjectFilters` (DEC-58): area landing only for direct requests with default sort + grid view (`withoutAreaLanding()` on `X-Inertia`), `onLanding($path)` for area × segment URLs; `lib/listing.ts` never lands on an area. `Route::has` guard removed.
- `App\Support\Seo\LocationMeta`: settings `seo.location_title_template` / `location_segment_title_template`, description = first 155 chars of the intro, FR-LOC-003/004 rules. 19.2's `locations.xml` must reuse `areaIndexable()` + "own segment intro".
- `components/site/map/`: `ProjectMap` (props `projects`, `label`, `centre`/`zoom` or fit bounds, `eager`, `popups`, `v-model:active`), `MapSheet` (full-screen + synced card strip), `MapProjectCard`. Listing prop `map` (markers, only in map view; in `RELOAD_ONLY`). Reuse for 6.2 locations block.
- `Breadcrumbs` `tone="media"`; new preset `area-tile`; `DemoProjectSections` area centres moved to `DemoLocations::centre()`. Demo: Purbachal (Narayanganj, 0 projects), row 27 Gemcon Birch Residency (Uttara); six areas have ≥ 150-word intros, Uttara residential has its own intro.

## Phase 6 — Content pages

### 6.1 — Content blocks I

**Deps:** 3.2 · **Read:** spec/page-builder.md block rows 2, 3, 13–18, 27, 28; design/components.md §6.5 rows page_header, rich_text, image_text, feature_cards, process_steps, timeline, tabbed_pillars, quote, parallax_band; design/foundations.md §3.2 (prose rules); design/motion.md MOT-MASK, MOT-LINE, MOT-PARALLAX, MOT-TILT.
**Build:** page_header, rich_text (prose styles, narrow/wide), image_text, feature_cards (icon allowlist or image, 2/3/4 cols, tilt), process_steps (numbered, line draw), timeline, tabbed_pillars, quote, parallax_band. Local `/styleguide/blocks` page renders every block with demo data.
**Test:** `/styleguide/blocks` at 360/768/1440; heading levels H2 → H3 only (HeadingsMap-style check in DevTools); keyboard on tabs; reduced motion → no parallax/line animation, content complete.

**Done notes (2026-11-03):**
- Blocks in `components/blocks/`: `PageHeaderBlock` (photo = area-hero pattern, `data-hero`, parallax 8, LCP via `SeoData::lcpFromBlocks`; plain = 7/5 on a hairline), `RichTextBlock` (`.rich-text` in app.css), `ImageTextBlock`, `FeatureCardsBlock` (`lib/feature-icons.ts` allowlist), `ProcessStepsBlock` (`v-line`), `TimelineBlock` (hairline rows, not alternating), `TabbedPillarsBlock` (force-mounted panels, opacity crossfade), `QuoteBlock`, `ParallaxBandBlock`. All lazy in `BlockRenderer`; types in `types/blocks.ts`.
- Demo data: `App\Support\Demo\DemoBlocks::styleguide()`; local page `/styleguide/blocks` (`?header=plain`) via `StyleguideController::blocks` → `Site/StyleguideBlocks`. 6.3 builds its seeded pages from the same block shapes.
- `v-parallax="0"` / `v-tilt="0"` switch the effect off (block toggles). New presets: `media-text`, `feature-image`, `timeline-photo`, `pillar-image`.
- A block without a heading renders item titles as H2 (feature_cards, tabbed_pillars); rich_text, quote and a heading-less parallax_band are unlabelled sections.
- Motion chunk 55.32 KB gzip (0.01 KB over DEC-51's 55.31 KB; budget decision still due in 9.1).

### 6.2 — Content blocks II

**Deps:** 6.1, 5.1 · **Read:** spec/page-builder.md block rows 19–26; design/components.md §6.5 rows team_grid, faq, gallery, video, awards, locations_grid, offices, form; CMP-PERSON-01, CMP-ACC-01, CMP-GAL-01, CMP-VIDEO-01, CMP-MAP-02; spec/social.md FR-SOC-003..006.
**Build:** team_grid, faq (accordion), gallery (grid/masonry + PhotoSwipe), video, awards, locations_grid, offices (cards + map facade), form block shell (form inside comes in 7.1); all added to `/styleguide/blocks`.
**Test:** gallery lightbox from the block; offices map loads only on click; FAQ single-open; portraits grayscale → colour on hover only with a fine pointer.

**Done notes (2026-11-03):**
- Blocks in `components/blocks/`: `TeamGridBlock` (`.portrait-tone` grey → colour, fine pointer + motion only), `FaqBlock` (reuses `ProjectFaq`), `GalleryBlock` (`useLightbox`, presets `gallery-block` / `gallery-block-masonry`), `VideoBlock` (reuses `ProjectVideo`, no tour), `AwardsBlock` (closed hairline grid, `partner-logo` preset), `LocationsGridBlock` (lead tile + 3-across, preset `area-feature`), `OfficesBlock` (`MapFacade` + `lib/offices.ts` hours), `FormBlock` (shell). All lazy in `BlockRenderer`; types in `types/blocks.ts`.
- `components/site/location/AreaTile.vue` is now shared by `/locations` and the locations_grid block.
- **7.1:** mount CMP-FORM-01 inside `FormBlock`'s `[data-form]` panel (type in `form_type`); until then it links to `data.fallback` (`/contact`).
- Demo data: `DemoTeam::members('leadership'|'management'|'team')` (6.3 leadership page reuses it); `DemoBlocks::peopleAndPlaces()` = blocks 14–23 on `/styleguide/blocks`. `DemoProjectSections::DEMO_YOUTUBE_*` are public.
- Faq block is single-open only; the multiple-open variant for PG-18 is 8.2's.
- Motion chunk unchanged at 55.32 KB gzip.

### 6.3 — Builder pages, legal pages, leadership, custom pages

**Deps:** 6.2 · **Read:** spec/pages.md PG-07..11, PG-23..25, reserved slugs; design/layouts.md LAY-ABOUT/SERVICES/LANDOWNER/NRB/CUSTOM (seeded blocks table), LAY-TEAM, LAY-LEGAL; spec/social.md FR-SOC-004.
**Build:** routes `/about`, `/about/leadership`, `/services`, `/landowner`, `/nrb`, `/privacy-policy`, `/terms`, catch-all `/{page-slug}` that excludes the reserved list. Demo pages in the seeded block order; legal template (H1, last updated, TOC from H2s, sticky ≥ 1280); leadership page (leadership / management / team groups).
**Test:** each page matches the seeded-blocks table; legal TOC collapses on mobile; a demo custom slug renders, `/admin`-like reserved slugs are never caught; one H1 per page.

**Done notes (2026-11-03):**
- `Site\PageController`: `about`, `leadership`, `services`, `landowner`, `nrb`, `show($slug)` (PG-25) render `Site/Page` (`blocks`, `starts_with_hero` = first block is a hero or a photo `page_header`, LCP via `lcpFromBlocks`; `index=false` → `noindex`); `privacy`/`terms` render `Site/Legal`. Content: `App\Support\Demo\DemoPages` (seeded orders of LAY-ABOUT/SERVICES/LANDOWNER/NRB, leadership, legal, custom `sustainability` + `buyer-guide` noindex). 16.1 replaces it with the `pages` table; same prop shapes.
- Catch-all `Route::get('{slug}')` is last in `routes/web.php`; `App\Support\Pages\ReservedSlugs` (spec list + app paths) builds its constraint — 16.2's slug rule must reuse `ReservedSlugs::isReserved()`.
- `App\Support\Pages\LegalToc::build()` adds ids to H2s and returns `toc`; `Legal.vue` + `components/site/legal/LegalToc.vue` (sticky `sticky-under-tabs` column ≥ 1280, native `<details>` below). Draft legal copy: the client must supply the real text.
- Landowner/NRB: anchors `#enquire` (form block, `anchor_id`) and `#how-it-works`; the form block is still the 6.2 shell until 7.1 (`form_type` landowner/nrb).
- Shared demo data: `DemoBlocks::block()` is public, `timelineItems()`, `awardItems()`; `DemoContent::statItems()`; `DemoContent::page()` removed. No new packages, no new `sizes` presets.

## Phase 7 — Forms UI

### 7.1 — Form system & the seven lead forms

**Deps:** 6.3, 4.3 · **Read:** design/components.md CMP-FORM-01, CMP-FORM-02, CMP-MODAL-01; design/patterns.md UX-03, UX-04, A11Y-D10; spec/crud.md VAL-01..03, VAL-06, VAL-08, TOAST-07; spec/leads.md lead types table, FR-LEAD-001, FR-LEAD-002, FR-LEAD-005, FR-LEAD-014; spec/performance-a11y.md SEO-A11Y-006; spec/careers.md FR-CAR-002 (field rules reused by 8.2).
**Build:** owner decides on `zod` 4 (client mirror; optional). `FormField` (label, required marker, helper, error, `aria-describedby`), `ErrorSummary` with focus, `PhoneInput` (+88 default, formatting, country), `DatePicker` (`DD MMM YYYY`, min/max), `FileUpload` (dropzone list, limits), radio-card group. Forms: project_inquiry (2 steps + Skip), site_visit, brochure, callback, contact, landowner, nrb. UTM capture into sessionStorage → hidden fields. Limits shown before errors (VAL-03), messages per VAL-02 in `lang/en/validation.php`, input kept on error (VAL-06). One Form Request per type with the exact rules + VAL-01 defaults (permanent) + `site.forms.store` that validates and redirects to `/thank-you/{form}` without storing (storage and spam in 15.1); brochure validates and returns the demo PDF link inline.
**Test:** each form submitted empty → summary focused, every field error linked; `01712-345678` accepted, `0171` rejected; site-visit date outside tomorrow…+60 days rejected; a 4th landowner file refused; step change moves focus and is announced; mobile "Enquire" from the sticky bar opens the sheet with the project prefilled.

**Done notes (2026-11-03):**
- Server: `App\Support\Forms\{FormType,FormOptions,Countries,PhoneNumber}`, `App\Rules\PhoneNumber`, one Form Request per type in `app/Http/Requests/Site/Forms` (base `LeadFormRequest`), `App\Actions\Leads\SubmitLead` (normalises the phone, no storage until 15.1), `FormController@store` (`POST /forms/{form}`, `site.forms.store`; request class resolved from the enum), `ThankYouController` (stub PG-22, 7.2 builds it), messages in `lang/en/validation.php`. Check script: `docs/checks/7.1-forms.php`.
- Client: `composables/useLeadForm.ts` (blur-then-change, summary focus, UTM + `source_url` + `form_location` added at submit; fields read the form via `useLeadFormContext()`), `lib/forms/{rules,phone,attribution}.ts`, `components/forms/*`, `components/site/forms/*` (seven forms, `CallbackDialog` mounted in `SiteLayout`, opened by `useCallbackDialog`). `FormDialog` is the shared lead-form dialog shell; `EnquiryDialog` uses it.
- `forms` shared prop (choices, countries, limits, `visit_window`, consent per type, `callback_dialog`); consent and thank-you copy live in `DemoContent` settings group `forms`.
- Brochure answers with `Inertia::flash('download', …)` + `back()`. Form block lost its `fallback` link. `/styleguide/forms` shows all seven forms. Regenerate Wayfinder with `--with-form`.
- 7.2 must: build the real thank-you page (`generate_lead` with `lead_type`, `project`, `form_location`), the contact page using `ContactForm` (`location="contact_page"`), and consent-aware analytics. 8.2 reuses `FileUpload`, `PhoneInput`, `FormField` for the apply form.

### 7.2 — Contact, thank-you, newsletter, consent banner, analytics layer

**Deps:** 7.1 · **Read:** spec/pages.md PG-17, PG-22; design/layouts.md LAY-CONTACT, LAY-THANKS; design/components.md CMP-COOKIE-01, CMP-TOAST-01; spec/performance-a11y.md SEO-ANA-001..004, SEO-PERF-013; spec/cms.md §8.2 (Legal & cookies, Forms, Analytics groups); spec/social.md FR-SOC-005; spec/nfr-ops.md NFR-PRIV-01.
**Build:** contact page (form, contact card, offices, social); thank-you page per form (copy from demo settings, `generate_lead` dataLayer push, next steps, WhatsApp, related projects); newsletter inline success (footer + article); consent banner + preferences dialog (equal-weight buttons, Consent Mode v2 defaults denied, version re-prompt); `lib/analytics.ts` (dataLayer events of SEO-ANA-004, virtual `page_view` on navigate); GTM/GA4 loader only after consent + `load` and only when an ID is configured.
**Test:** contact submit → thank-you, console `dataLayer` shows `generate_lead` with the right fields; banner choice remembered, reopened from the footer link; no googletagmanager request without consent or ID; newsletter success replaces the form.

**Done notes (2026-11-03):**
- Server: `ContactController` (`/contact`, `?project=&type=` prefill), `ThankYouController` (steps, related projects, `lead` from a one-request session flash set by `FormController`; carries a ULID `reference` from `SubmitLead`), `Actions/Newsletter/SubscribeToNewsletter` + `NewsletterRequest` + `NewsletterController` (`POST /newsletter`, stores nothing until 15.1), `Support/Consent/ConsentCookie`. Check script: `docs/checks/7.2-contact-thankyou.php`.
- Consent: cookie `gc_consent` (JSON `{version, analytics, marketing}`, 12 months, excluded from encryption) written by the browser, read by the server into `ui.consent`; an older `consent_version` counts as no consent. Copy lives in `DemoContent` groups `legal` and `contact_page`; `ui.cookies` / `ui.analytics` are once-props.
- Analytics: `lib/analytics.ts` (SEO-ANA-004 events, Consent Mode v2 default denied, GTM or bare GA4 loaded only with an ID + granted consent + window `load`), `composables/useAnalytics.ts` (in `SiteLayout`: first + per-visit `page_view`, delegated `tel:` / wa.me click tracking). IDs from `.env` `ANALYTICS_GTM_ID` / `ANALYTICS_GA4_ID` (`config/services.php`) until 12.1.
- Client: `consent/{ConsentBanner,ConsentPreferences}.vue`, `useConsent`, `NewsletterForm.vue` (footer; 8.1 reuses it with `location="article"`), `contact/ContactDetails.vue`, `pages/Site/{Contact,ThankYou}.vue`. Footer has a "Cookie settings" button; the mobile contact bar hides while the banner is open.
- Events wired at the source: `generate_lead`, `site_visit_request`, `brochure_download`, `map_open`, `video_play`, `gallery_open`, `search` (hero widget), `filter_apply`. **Not yet wired:** `shortlist_add` (9.x) and `compare_open` (8.3).
- Later phases must know: GTM/GA4 must not send their own automatic page view (the site pushes one per visit); 12.1 moves the IDs into settings; 15.1 stores newsletter and leads; `newsletter` is a reserved slug. Visual patterns: DEC-65.

## Phase 8 — Remaining public pages

### 8.1 — News list, archives & article

**Deps:** 7.2 · **Read:** spec/news.md FR-NEWS-001..007, AC-NEWS-01 (404 part); spec/pages.md PG-12..14; design/layouts.md LAY-NEWS-LIST, LAY-NEWS-DETAIL; design/components.md CMP-NEWS-01, CMP-SHARE-01, CMP-TABS-01 (pill); spec/seo.md SEO-TEC-004, SEO-TEC-005.
**Build:** `/news`, `/news/category/{slug}`, `/news/tag/{slug}` (noindex with < 3 posts), `/news/{slug}`: featured post, category pills, grid 9/page, article prose, cover, meta row with read time, tags, share row (no SDKs), related projects, related posts, newsletter CTA. Future-dated demo post → 404.
**Test:** pagination canonical `/news?page=2` is self; tag page with 2 posts is `noindex,follow`; share "Copy link" toast; article at 390 px has a full-bleed cover.

**Done notes (2026-11-03):**
- `NewsController` (index/category/tag/show) + `App\Support\Demo\DemoNews` (13 published posts, 1 scheduled → 404, 5 categories, tags, bodies as block lists → HTML, read time from the body, `related()` per FR-NEWS-007, `share()` URLs). `DemoContent::newsCards()` now delegates to it; 16.3 swaps it for the Post model/Resources (same shapes, `types/news.ts`).
- `App\Support\Pagination\PageLinks` + `site/PaginationNav.vue` (emits `navigate`) are the shared `?page=n` links; `ListingPagination` is now a thin wrapper. Reuse for careers/search (8.2).
- Pages `Site/News` (PG-12/13) and `Site/NewsArticle` (PG-14); `site/news/NewsCategoryNav` (underline links), `NewsGrid` (2 large · 3 standard · 4 compact tiers), `site/ShareRow` (CMP-SHARE-01; Copy link after mount only). `NewsCard` gained `priority`; its `featured` variant uses `news-lead`.
- Featured post = newest published with the flag, shown on `/news` page 1 only and never repeated in the grid. Tag page indexable from 3 posts (`DemoNews::TAG_INDEX_MIN`); an empty category is noindex. Locally every page is `noindex,nofollow` (SEO-TEC-010); an unindexable page simply has no canonical.
- `.rich-text` gained `h4`, `figure`, `figcaption`. Wayfinder must be regenerated with `--with-form` (`php artisan wayfinder:generate --with-form`), else admin pages fail `types:check`.
- Not done (later): `/news/feed.xml` (16.3), signed preview ribbon (14.1). Visual patterns: DEC-66.

### 8.2 — Careers, FAQ page, site search

**Deps:** 8.1 · **Read:** spec/careers.md FR-CAR-001..004, AC-CAR-01; spec/pages.md PG-15, PG-16, PG-18, PG-19; design/layouts.md LAY-CAREERS, LAY-CAREER-DETAIL, LAY-FAQ, LAY-SEARCH; spec/social.md FR-SOC-003; spec/search.md FR-SRCH-010 (result grouping only); spec/seo.md SEO-TEC-024.
**Build:** `/careers` (intro blocks, filters, role rows, empty state with CV drop), `/careers/{slug}` (summary card, apply form validated like 7.1, closed → "Applications closed", 410 30 days after close), `/faq` (category tabs, client-side filter, multi-open accordions), `/search?q=` (grouped demo results, empty state, `noindex`).
**Test:** a 6 MB CV → "CV must be 5 MB or smaller"; closed demo job older than 30 days → 410; FAQ filter narrows the list as you type; search with 1 character shows the minimum-length message.

**Done notes (2026-11-03):**
- `CareerController` (index/show/apply) + `DemoCareers` (6 open roles, deadlines relative to today; `quantity-surveyor` closed < 30 days, `customer-relations-manager` → 410, `project-manager` draft → 404). Closing = deadline + 1 day 00:05 Dhaka (FR-CAR-003). 17.1 swaps in the JobPost model (same shapes, `types/careers.ts`) and stores applications + CV (private disk) in `Actions/Careers/SubmitJobApplication`.
- `POST /careers/apply` (throttle 6/min) takes the apply form and the CV drop (`job` empty) via `JobApplicationRequest`; success = inline confirmation + toast, no thank-you page, no `generate_lead`. Size is checked before type (AC-CAR-01). Local preview of the CV drop: `/careers?preview=empty`.
- Person/consent/tracking rules moved to `Requests/Site/Concerns/ContactFieldRules` (used by `LeadFormRequest` too). `useLeadForm` takes `url`; `FileUpload` has single-file mode (`max` 1), `extensions` fallback and custom messages; contact-bar override takes `label` (PG-16 "Apply now").
- `FaqController` + `DemoFaqs` (21 FAQs, FR-SOC-003 categories); 16.4 replaces it with the FAQ model. Answers use Reka `unmount-on-hide=false` (in HTML, `hidden=until-found`).
- `SearchController` + `DemoSiteSearch` (all words must match, title hits first, 10 per group, min 2 chars); 17.2 swaps in Scout with the same group shape (`types/site-search.ts`). FAQs are not searched.
- Visual patterns and rules: DEC-67, DEC-68.

### 8.3 — Shortlist & compare

**Deps:** 8.2 · **Read:** spec/shortlist.md (all); spec/pages.md PG-20, PG-21; design/layouts.md LAY-SHORTLIST, LAY-COMPARE; design/components.md CMP-COMPARE-01, CMP-CARD-01 (compare checkbox).
**Build:** `/shortlist?ids=` (Inertia visit, unknown ids dropped from storage, "Saved on this device only", Enquire about all → project_inquiry with a multi-select); compare checkbox on cards (max 3, toast on the 4th), floating compare bar above the mobile bar, `/compare?ids=` table (sticky first column and header, differing values highlighted, price only when shown).
**Test:** AC-SHORT-01; remove a project from the shortlist page and reload; compare table scrolls sideways on 390 px with the first column pinned; both pages are `noindex,nofollow`.

**Done notes (2026-11-03):**
- `ShortlistController` (index/compare) + `DemoShortlist` (ids parsing, published rows in request order, FR-SHORT-003 compare rows formatted with `Format`, `differs` flag, amenity ✓/– rows). 13.3 swaps in Project queries/Resources with the same shapes (`types/shortlist.ts`).
- Stores: `useShortlist` (localStorage, gained `add/remove/keepOnly/ready`, fires `shortlist_add`) and `useCompare` (sessionStorage `gc.compare`, memory fallback, max 3, AC-SHORT-01 toast). Both fill in onMounted only (SSR-safe).
- `lib/shortlist.ts` builds `?ids=3,1` links (header heart, bar). PG-20 visits itself with the stored IDs unless the URL already holds the same set, drops unknown IDs, and handles removals/Undo with `router.replace` (no request). PG-21: URL IDs win and become the selection.
- `ProjectCard` `compare` prop → `CompareToggle` in the price row (replaces the arrow); on listing grid/map, area page, shortlist. `CompareBar` lives in `SiteLayout` (hidden on PG-20/21 and while the cookie card is open; toasts lift above it).
- "Enquire about all": `ProjectInquiryForm` multi mode (`projects` prop → `CheckboxList`, no unit type); `ProjectInquiryRequest` takes `projects[]` (a `projects` key excludes `project`/`unit_type`); flash + thank-you carry `project_slugs`, one `generate_lead` each. 15.1 must create one lead per project in `SubmitLead`.
- PG-04 got the FR-SHORT-001 heart (title block, `tone="plain"`). Table sticky rules: `.compare-scroll`/`.compare-head` in app.css. Rules and visuals: DEC-69, DEC-70.

## Phase 9 — Frontend sign-off

### 9.1 — Frontend hardening & client review

**Deps:** 8.3, 3.2 · **Read:** design/patterns.md §10, §11; spec/performance-a11y.md SEO-PERF-001..008, SEO-A11Y-001..007; spec/seo.md SEO-ONP-003, SEO-ONP-004; spec/nfr-ops.md NFR-BRW-01, NFR-DEV-01, DEP-ENV-02; spec/decisions.md OPEN-02.
**Build:** sweep at the 9 widths of NFR-DEV-01; heading audit per page type; alt audit; keyboard-only pass; Lighthouse on PG-01/02/04/06/14 (production build); bundle budgets measured from build output (JS ≤ 130 KB initial, CSS ≤ 45 KB gzip); `npm run build:ssr` + a local SSR render of one page per type (SEO-REN-08 check). Review build for the client on staging (basic auth, manual upload if no SSH, OPEN-02). Client feedback recorded as DEC entries; fixes as `fix 9.1`.
**Test:** Lighthouse ≥ SEO-PERF-003 values (no snapshots yet), Accessibility ≥ 95, SEO 100; axe DevTools: 0 serious/critical on every page type; client sign-off.

**Done notes (2026-11-04):**
- Audits (static crawl of 297 URLs, 25 page types × 9 widths in headless Chrome): headings, alt text, accessible names and one `fetchpriority` per page are clean; no overflow, console error or failed request left. Lighthouse and axe stay manual (owner runs them).
- Initial JS cut from 189 to 165 KB gzip by lazy-loading `CallbackDialog`, `ConsentPreferences` and `Toaster` in `SiteLayout` and `vue-sonner` inside `lib/toast.ts` (`notify.*` now returns `void`). CSS ≈ 33 KB. Budgets: DEC-71.
- Hydration: `ShortlistHeart` / `CompareToggle` gate on `useMounted()` (lazy blocks hydrate after the layout filled the stores); `Shortlist.vue` noscript has `data-allow-mismatch`.
- Focus: gallery tiles and the video poster use `focus-visible:outline-solid` (Tailwind 4: `outline-none` kills `outline-2`). `ConsentBanner` publishes its height as `--bottom-obstruction`; `html` `scroll-padding-bottom` reads it (WCAG 2.4.11).
- `/faq`: grid wrapper `grid-cols-1` so the topics scroller no longer widens the page at 390 px.
- `INERTIA_SSR_ENABLED` (config/inertia.php, `.env.example`) feeds `Inertia::disableSsr()` in `HandleInertiaRequests`. Shared hosting: `false`. Staging `.env`: `APP_ENV=staging`, file session/cache; upload `public/demo` too (DEC-46).
- Open for later: footer CLS up to 0.2 on `/shortlist` and `/compare` at 768–1024 px; 20 px checkboxes (label extends the hit area); dev-only `v-magnetic` warning; text over photos needs an axe/visual check.

---

## Part B — Backend & admin

## Phase 10 — Admin foundation

### 10.1 — MySQL, users, roles & permissions, Fortify hardening

**Deps:** 9.1 · **Read:** spec/admin.md §10.1, §10.2, DM-USER; spec/database.md (all); spec/architecture.md ARC-005, ARC-006, ARC-011; spec/nfr-ops.md NFR-SEC-06, NFR-SEC-10; spec/cms.md CMS-CACHE-001; spec/decisions.md DEC-19.
**Build:** owner approves `spatie/laravel-permission` 7 + `spatie/laravel-activitylog` 4. MySQL utf8mb4 (`.env.example`); users per DM-USER (soft deletes, `is_active`, `theme`, `table_density`, `last_login_at`); permission seeder + 6 roles per the matrix; `Gate::before` Super Admin; `Relation::enforceMorphMap`. Fortify: registration off, login throttle 5/min email+IP then 15-min lockout, 2FA required for Super Admin/Admin, password policy, idle 120 min, deactivated users blocked; invite flow (72 h link); auth events logged once per session.
**Test:** `migrate:fresh --seed` on MySQL; log in as each role; an Admin without 2FA is sent to setup; 6 wrong passwords → lockout; `/register` → 404; invite mail (log mailer) → set password → login.

**Done notes (2026-11-04):**
- Packages: `spatie/laravel-permission` 7.4.2, `spatie/laravel-activitylog` 4.12.3; `symfony/mime`, `phpunit`, `pest` patch-updated first (Composer 2.9 `block-insecure`). See DEC-72.
- Deviations (DEC-72): no 2FA (ADM-AUTH-002 dropped, no `two_factor_*` columns), no "Remember me", password min 8 (not 12; mixed case + number + `uncompromised()` kept).
- Roles/permissions: `Support/Auth/PermissionMatrix` + `SystemRole`, seeded by `Actions/Users/SyncSystemRoles`; `Gate::before` Super Admin and `Relation::enforceMorphMap(['user'])` in `AppServiceProvider` — each phase adds its models to the morph map. Row-level limits (assigned leads, unused media, Admin cannot assign Super Admin) belong to the Policies of the screens that need them (10.3 on).
- Login: `Support/Auth/LoginThrottle` (5/min → 15 min lock), `Actions/Auth/AuthenticateUser` (Fortify callback), `EnsureUserIsActive` (web group), `Listeners/RecordAuthEvents` → `RecordAuthEvent`/`RecordLogin` (activity log `auth`, login once per session). `login_failed` rows have no causer (email in properties).
- Invite: `Actions/Users/InviteUser` + `AcceptInvitation`, signed `/invitation/{user}` (72 h, single use via unverified email), `php artisan user:invite`; 10.3 adds the UI and the "resend" (`InviteUser::sendInvitation`).
- Seeder: outside production one user per role, `{role-slug}@gemcon.test` / `password`. Starter routes stay un-prefixed until 10.2 (`ADMIN_ROUTES` + `invitation.*`).
- Check script: `docs/checks/10.1-auth.php` (`php artisan tinker docs/checks/10.1-auth.php`). Starter tests in `tests/` still assume registration/2FA and were not run (DEC-26).

### 10.2 — Admin shell, navigation, profile

**Deps:** 10.1 · **Read:** design/admin.md CMP-ADM-SHELL, CMP-ADM-SIDEBAR, CMP-ADM-TOPBAR, CMP-ADM-PAGEHEAD, §12.1, §12.7; spec/admin.md §10.2 (UI hiding), ADM-SCR-20; spec/architecture.md ARC-007 (with DEC-28).
**Build:** AdminLayout on the admin root from 0.1: dark sidebar in both modes, groups, count badges (null until their phases), collapse state cookie, Sheet < 1024; top bar (breadcrumbs, search trigger placeholder, View site, bell placeholder, theme toggle light/dark/system applied before paint, avatar menu); offline banner on admin forms (UX-06); profile page (password, 2FA, sessions, theme, density); placeholder page for each sidebar item, filtered by permission; `X-Robots-Tag: noindex` on `/admin`; starter dashboard/settings pages replaced.
**Test:** each role sees only its items (compare with the matrix); a forbidden URL → 403; dark mode has no light flash on reload; collapse is remembered; sign out other sessions works.

**Done notes (2026-11-04):**
- Admin lives under `/admin` (DEC-73): Fortify `prefix` = `admin` (`/admin/login`), `routes/admin.php`, `routes/settings.php` and the starter settings/dashboard pages are gone; `ADMIN_ROUTES` = `admin.*` + Fortify/invitation names, and any `/admin/*` path counts as admin (403/404 too).
- `Support/Admin/AdminNavigation` + `NavItem` = single registry (sidebar, `admin.can:a|b` route middleware, placeholder pages). A phase that builds a real screen registers its routes and sets `placeholder: false`; `count` is null until then. Dashboard `/admin` = `admin.dashboard` (`admin/Dashboard.vue`, lists reachable sections until 18.1).
- Shared admin props: `auth {user, permissions}`, `navigation`, `sidebarOpen`; pages send `breadcrumbs` (`AdminCrumb[]`). Client: `useCan()`, `AdminLayout`, `components/admin/*` (sidebar, topbar, `AdminPageHeader` = the page's only h1, `AdminSection`, `AdminRadioCards`). Sidebar constants 280/72 px, mobile Sheet below 1024 (`ui/sidebar` edited). Sidebar hover = `--dark-card` + white text.
- Profile (`admin/Profile.vue`): `UpdateProfile`, `ChangePassword`, `SignOutOtherSessions` (deletes `sessions` rows), `UpdatePreferences` (theme, density; `forceFill`). Theme: `users.theme` wins over the `appearance` cookie (`HandleAppearance`), `data-appearance` on `<html>`; `useAppearance` no longer uses localStorage.
- Matrix tweak: Content Editor lost `seo.view` (sees Redirects/404s only); Errors needs `maintenance.caches|manage`. A role without `dashboard.view` gets 403 at `/admin` after login (10.3 custom roles must include it).
- Open: 403/404/419 inside the shell use Laravel's default pages until 10.4 (TOAST-04); starter tests in `tests/` still assume old routes (DEC-26). Check script `docs/checks/10.2-admin-shell.php`.

### 10.3 — Data table system, users & roles, activity log

**Deps:** 10.2 · **Read:** spec/admin.md §10.5 (ADM-TBL-001..016), DM-VIEW, ADM-SCR-16, ADM-SCR-17, §10.7; design/admin.md CMP-ADM-TABLE, CMP-ADM-CONFIRM, CMP-ADM-STATUS, §12.3; design/motion.md MOT-SLIDE-UP; spec/crud.md CRUD-02, entity rows Users/Roles/Saved views/Activity log; spec/decisions.md DEC-11; DECISIONS DEC-34.
**Build:** owner approves `@tanstack/vue-table` (8.21.3 per DEC-11, or v9 after the DEC-34 re-check), `spatie/laravel-query-builder` 6, `spatie/simple-excel` 3. One `components/admin/DataTable` on TanStack in server-side mode, rendered with shadcn-vue `Table` primitives, reused by every admin list. Server list query (allowlisted filters/sorts, `per_page`, ≤ 3 sorts); `DataTable` component (toolbar, typed filters, chips, visibility/order/resize, selection + "select all matching", bulk bar, saved views, URL state, density, CSV/XLSX export, the empty/loading/error states). First consumers: Users & roles (invite, deactivate with lead-reassign count, reset 2FA, roles with permission checkboxes) and Activity log.
**Test:** sort/filter/search, copy the URL into a new tab → same view; Back restores state; save and reload a view; resize a column with the keyboard; bulk deactivate asks with the count; export opens in Excel.

### 10.4 — CRUD toolkit: editor layout, toasts, confirm & undo, Recycle bin

**Deps:** 10.3 · **Read:** spec/crud.md (all); design/admin.md CMP-ADM-FORM, CMP-ADM-CONFIRM, CMP-ADM-CHECKLIST (component only), CMP-ADM-PAGEHEAD; design/components.md CMP-TOAST-01, CMP-FORM-01, CMP-EMPTY-01; design/patterns.md UX-03, UX-04, UX-06, UX-09, UX-10; spec/admin.md §10.2 (delete/restore permissions), ADM-AUTH-007; spec/nfr-ops.md NFR-PRIV-04.
**Build:** server: `Trashable` contract + trait (bin label, type, thumbnail, usage guard, restore conflict checks, retention days) and a bin-type registry; Actions `MoveToRecycleBin`, `RestoreFromRecycleBin`, `DeleteForever`, `PurgeRecycleBin` (scheduled nightly from now on); a toast flash helper (TOAST-01) and fixed error toasts for 403/404/419/429/500 (TOAST-04) via Inertia's exception handling; a slug-availability endpoint (VAL-04); `lang/en/validation.php` attributes and messages (VAL-02). Client: `lib/notify.ts`; `useConfirm` + `ConfirmDialog` (named object, consequence, optional redirect picker, type-to-confirm); undo toasts; `AdminEditor` layout (sections, sticky rail/bar, Last saved, dirty marker, Ctrl/⌘+S, unsaved guard); `FieldHelp`, `CharCounter`, `SlugField`, error badges for tabs and repeater cards (VAL-07); `ListPageHeader` with status tabs and the "Recycle bin (n)" link; disabled-with-reason button (HELP-08); `?` shortcuts dialog (HELP-09). Recycle bin screen (DEL-04) under System. First consumer: users (delete → bin with the open-lead guard, restore, delete forever).
**Test:** delete a user → toast with Undo → Undo restores it; delete again → it appears in the Recycle bin with its auto-delete date → Restore → toast with Open; Delete forever asks you to type the name; backdate a bin row in tinker and run `php artisan schedule:test` (purge) → it is gone; edit your profile, press Ctrl+S → saved toast, "Last saved" updates; make a change and click a sidebar link → unsaved warning; delete your own session row in the DB, then save → session-expired toast and the form still holds your text; `?` opens the shortcuts dialog.

## Phase 11 — Media library

### 11.1 — Media upload, conversions & storage

**Deps:** 10.4 · **Read:** spec/media.md (all); spec/hero.md HERO-010, HERO-011, HERO-020..023; spec/nfr-ops.md NFR-SEC-12, DEP-PHP-01; spec/architecture.md ARC-011, ARC-014; DECISIONS DEC-31.
**Build:** owner approves `spatie/laravel-medialibrary` 11, `enshrined/svg-sanitize`, `james-heinrich/getid3`. `media_assets`, `media_folders`, `media_usages` + spatie `media`; MIME by content, size limits, > 40 MP rejected, SVG sanitised, video limits read from the header; queued conversions (`media` queue): widths/formats per CMS-MED-005 and HERO-011, AVIF when supported, `og` focal crop, dominant colour; content-hashed names + immutable cache headers; media resource that outputs exactly the `MediaImage` shape of 0.3.
**Test:** upload a 6000×4000 JPEG → conversions after `queue:work`; AVIF only if supported; a 45 MP image is rejected; an SVG with `<script>` is cleaned; a 7 MB hero MP4 is rejected with the limit named; the resource JSON keys equal the demo shape.

### 11.2 — Media library UI, picker & focal point

**Deps:** 11.1 · **Read:** spec/media.md CMS-MED-002, CMS-MED-004, CMS-MED-007..010; design/admin.md CMP-ADM-MEDIA, CMP-ADM-PICKER, CMP-ADM-FOCAL, §12.6; spec/crud.md rows Media assets / Media folders, DEL-03, VAL-03.
**Build:** library screen (folder tree ≤ 3 levels, dropzone with per-file progress that rejects wrong type/size/pixels before uploading, grid/list (list = the TanStack DataTable), filters incl. missing alt, detail drawer with metadata, usages, replace everywhere, delete → Recycle bin 30 days); picker dialog (single/multiple, upload tab) reused by every media field; focal-point picker with ratio previews.
**Test:** create/rename/move/delete folders (delete only empty); deleting a used asset is blocked and "Replace everywhere" works; moving the focal point changes the previews; missing-alt tiles show the warning corner.

## Phase 12 — Settings, menus, SEO base

### 12.1 — Settings

**Deps:** 11.2 · **Read:** spec/cms.md §8.2, CMS-CACHE-002, DM-SET; spec/admin.md §10.2 (settings rows), ADM-SCR-15, ADM-LOG-003; design/admin.md CMP-ADM-FORM; spec/crud.md CRUD-04, VAL-01..03, HELP-03.
**Build:** settings table + registry (groups, keys, defaults, secrets encrypted, versioned cache); Settings screen with a tab per group and the permission split; shared props `site`, `ui` and per-form copy now come from settings (DemoContent loses those parts).
**Test:** change the hotline → header/footer show it after save; secret values are logged as "changed" only; an Admin cannot open the Analytics/Scripts tabs; a bad GTM ID is rejected.

### 12.2 — Menus

**Deps:** 12.1 · **Read:** spec/cms.md §8.3, DM-MENU; design/admin.md CMP-ADM-TREE; design/components.md CMP-NAV-02; spec/crud.md row Menus / items, CRUD-06.
**Build:** owner approves `vue-draggable-plus` 0.6.1. Menus + items; entity links stored by ID and resolved at render time; mega columns; tree editor with keyboard indent/outdent/move; `menus` shared prop from the DB (cached) replaces the demo menus.
**Test:** reorder and nest items → the site header follows; a link to an unpublished entity is hidden publicly and flagged in the admin; footer columns follow their menus.

### 12.3 — SEO base: redirects, 404 monitor, robots.txt

**Deps:** 12.1 · **Read:** spec/seo.md SEO-TEC-002, SEO-TEC-003, SEO-TEC-007..010, DM-REDIR, DM-404; spec/admin.md ADM-SCR-14 (Redirects, 404 monitor, Robots tabs); spec/crud.md rows Redirects / 404 log rows.
**Build:** `ApplyRedirects` before routing (exact/prefix, 301/302/410, hit counts), loop/chain detection, 2,000-rule cap, CSV import/export; redirects table + dialog editor, soft deletes → Recycle bin (DEC-33); `TrackNotFound` (ignore regex, "Create redirect", 90-day purge); dynamic robots.txt from settings (always `Disallow: /` off production).
**Test:** `/old` → `/projects` 301 and the hit count rises; creating A→B when B→A exists is refused; a 404 appears in the monitor and turns into a redirect; local robots.txt shows `Disallow: /`.

## Phase 13 — Locations & projects backend

### 13.1 — Schema & models: locations, projects, unit types, relations

**Deps:** 12.3 · **Read:** spec/projects.md (fields summary, validation rules, data model); spec/locations.md (data model); spec/cms.md DM-SEO, DM-SLUGH, DM-REV; spec/media.md DM-MUSE; spec/database.md (all); spec/architecture.md ARC-002, ARC-006.
**Build:** migrations/models/enums (Segment, Lifecycle, BookingState, PublishStatus, Availability, ProgressStage, NearbyCategory…); soft deletes + `Trashable` on locations, projects and amenities (DEC-33); traits HasSeo, HasSlugHistory, UsesMedia, Publishable; denormalised size/bed/bath ranges and `price_from_bdt` computed on save; seeders that recreate the demo projects and locations as real rows with their photos imported into the media library.
**Test:** `migrate:fresh --seed` → demo projects in the DB; change a unit size in tinker → project ranges update; a slug change writes slug history; a hidden unit price is excluded from `price_from_bdt`.

### 13.2 — Locations & projects admin

**Deps:** 13.1 · **Read:** spec/admin.md ADM-SCR-01..05; spec/projects.md FR-PROJ-008..010, validation rules, AC-PROJ-03; spec/locations.md FR-LOC-002, FR-LOC-003; design/admin.md CMP-ADM-FORM, CMP-ADM-CHECKLIST, CMP-ADM-SEO, CMP-ADM-SORTLIST, CMP-ADM-TREE, §12.4; spec/crud.md rows Cities / areas, Facet pages, Amenities, Projects, CRUD-05, DEL-02, DEL-03, DEL-05, VAL-04, VAL-07.
**Build:** projects table + editor tabs (Basics … SEO; Revisions tab in 14.1), repeaters as collapsible cards with summaries and error counts, publish checklist with Fix links (draft/publish now, schedule in 14.1), duplicate project, delete with the redirect option, featured order list, amenities (dialog, icon picker, used-by guard), locations tree + area editor with indexability badge and reason (delete guarded by projects/child areas), facet pages editor; Form Requests with the exact rules + VAL-01 defaults.
**Test:** create a project fully in the UI → Publish blocked until the checklist passes; a wrong size in unit type 3 → that card opens with "1 error" and the Unit types tab shows a count; duplicate a project → "Copy of …" draft with a free slug; delete a published project with a redirect to another → the old URL 301s, then restore it from the bin → it comes back as Draft; try to delete an area that has projects → refused with the project list; drag the featured order → home follows after 13.3; an area with a short intro shows its noindex reason.

### 13.3 — Public projects & locations from the database

**Deps:** 13.2 · **Read:** spec/search.md FR-SRCH-004, FR-SRCH-011; spec/projects.md AC-PROJ-06, AC-PROJ-07; spec/cms.md CMS-CACHE-002, CMS-CACHE-003; spec/architecture.md ARC-001, ARC-002; spec/performance-a11y.md SEO-PERF-014.
**Build:** Eloquent `ProjectSearch` (FULLTEXT + short-query LIKE fallback, facet counts) returning the 4.1 shape; API Resources for card/detail/location matching the TS types; similar projects auto rule; soft-deleted project → 410 for 90 days or 301 to its redirect; versioned cache keys; demo projects/locations removed from DemoContent.
**Test:** repeat the 4.1–5.1 checklists on DB data; ≤ 25 queries per page (query log); a hidden price never appears in view-source or the page props; a deleted project → 410.

## Phase 14 — Publishing workflow

### 14.1 — Draft, revisions, scheduling, preview, locking

**Deps:** 13.3 · **Read:** spec/cms.md §8.6 (CMS-PUB-001..006 + workflow), DM-REV; spec/projects.md AC-PROJ-04; spec/admin.md §10.8; design/admin.md CMP-ADM-REV, CMP-ADM-FORM; spec/architecture.md ARC-012.
**Build:** pending revision on editing a published entity, Publish changes / Discard, schedule (Asia/Dhaka) + scheduler every minute running the queue worker, keep 30 revisions + field diff + restore, signed 60-min preview with ribbon + `noindex` + no analytics, `lock_version` conflict dialog, unsaved-changes guard + autosave of the pending revision every 60 s (UX-10, design/patterns.md), `EntityPublished` event (cache bust now; sitemap/snapshot listeners join in 19.x). Applied to projects and locations; later entities reuse it.
**Test:** edit a published project → the live page is unchanged until "Publish changes"; schedule 2 minutes ahead with `php artisan schedule:work` → it goes live; the preview link works in a private window; two tabs editing → the second save shows the conflict.

## Phase 15 — Leads

### 15.1 — Lead storage, spam protection, duplicates, assignment

**Deps:** 14.1 · **Read:** spec/leads.md FR-LEAD-001..007, FR-LEAD-011, FR-LEAD-014, AC-LEAD-01..04, data model; spec/projects.md FR-PROJ-004, AC-PROJ-05 (gated brochure: lead + 10-min signed download URL); spec/careers.md DM-APP (private files); spec/nfr-ops.md NFR-PRIV-01..04, NFR-SEC-10; spec/cms.md §8.2 Forms group.
**Build:** owner approves `spatie/laravel-honeypot` 4. Leads + lead_activities + newsletter_subscribers; the 7.1 Form Requests now store; Turnstile (script loads when the form is in view; 3 s timeout → `needs_review`); honeypot + minimum time (fake success); rate limits with 429 + Retry-After; content rules → `spam`; E.164 normalisation; 24 h duplicate touch; assignment rule; private uploads served as attachments with `nosniff`; consent text stored; newsletter single/double opt-in.
**Test:** AC-PROJ-05 (brochure download link expires after 10 minutes); AC-LEAD-01..04; honeypot filled → normal thank-you, no row; the 6th submission in 10 minutes → 429 page; the same phone + project twice → one lead with a touch.

### 15.2 — Leads admin: table, board, detail drawer, subscribers

**Deps:** 15.1 · **Read:** spec/leads.md FR-LEAD-006, FR-LEAD-009, FR-LEAD-010, AC-LEAD-05; spec/admin.md ADM-SCR-11, ADM-SCR-12, leads/subscribers permission rows; design/admin.md CMP-ADM-KANBAN, CMP-ADM-LEAD, §12.3 (Leads row); spec/crud.md rows Leads / Subscribers, TOAST-02, TOAST-06; DECISIONS DEC-33 (manual lead).
**Build:** leads table with seeded saved views, kanban board (drag + keyboard "Move to…", Undo toast), won/lost reason dialog, detail drawer (Details, Timeline, Notes, Follow-up), call/WhatsApp links, export with phones as text, Viewer masking, Sales Executive sees only own leads; "New lead" dialog for phone/walk-in enquiries (type `manual`, same duplicate + assignment rules); delete → Recycle bin (Admin); subscribers screen (export, unsubscribe, delete → bin, Erase = hard).
**Test:** a Sales Executive opening another's lead URL → 403; dragging to Won asks for a reason, dragging elsewhere shows Undo; add a lead by hand with a phone that already enquired today → a touch on the existing lead, not a new one; export keeps `+880…`; Viewer sees masked phones.

### 15.3 — Notifications, autoresponder, webhook

**Deps:** 15.2 · **Read:** spec/leads.md FR-LEAD-008, FR-LEAD-012, DM-WEBH; spec/admin.md §10.8, ADM-SCR-21; design/admin.md CMP-ADM-NOTIF; spec/cms.md §8.2 (Notifications, Webhook groups).
**Build:** database notifications (new lead, assigned to you, follow-up due 09:00), bell count polled every 60 s while visible, toast on arrival, inbox page; optional SMTP emails (off by default) and autoresponder; signed webhook (HMAC) with 3 retries and a 30-day delivery log.
**Test:** submit a lead → bell count within 60 s; enable email with the log mailer → mail in the log; webhook to a local test route with a valid signature; an endpoint returning 500 is retried.

## Phase 16 — Page builder & content

### 16.1 — Pages & blocks: schema, block schemas, public render from DB

**Deps:** 14.1, 12.2 · **Read:** spec/page-builder.md (all); spec/pages.md reserved slugs, PG-25; spec/cms.md §8.6 (reuse); design/layouts.md seeded-blocks table; spec/crud.md row Pages.
**Build:** pages (soft deletes, `is_system` guard) + page_blocks; one `BlockSchema` per block type (28) with Laravel rules; invalid blocks saved as draft-only; seeders create the system pages in the 6.3 order; public builder pages render from the DB through the same `BlockRenderer`; media inside block data recorded as usages; demo pages removed.
**Test:** every builder page looks the same as in Part A; a block with invalid data in tinker → publish refused; reserved slug refused.

### 16.2 — Page builder admin UI

**Deps:** 16.1 · **Read:** spec/page-builder.md CMS-PB-002..005; spec/admin.md ADM-SCR-06; design/admin.md CMP-ADM-BUILDER, §12.5; spec/crud.md row Pages, CRUD-05, CRUD-06, VAL-07.
**Build:** pages list (status tabs, duplicate custom pages, delete → bin, system pages locked with a tooltip); block list (drag + Alt+↑/↓), block form drawer per type, live preview iframe (1280/834/390, 600 ms refresh, hover highlight, click to select), add-block gallery, duplicate/hide/delete with 8 s undo, invalid blocks outlined with error count, 30-block cap.
**Test:** reorder home blocks → preview updates within a second; undo a delete; a page whose first block has no H1 cannot be published.

### 16.3 — News admin, rich text editor, RSS

**Deps:** 16.1 · **Read:** spec/news.md (all); spec/admin.md ADM-SCR-08; design/admin.md CMP-ADM-RTE; spec/nfr-ops.md NFR-SEC-03; spec/crud.md rows Posts / Post categories / Tags.
**Build:** owner approves `@tiptap/vue-3` 3.x (+ starter-kit, link, image, table, youtube) and `stevebauman/purify` 6. Posts (duplicate, delete → bin), categories (seeded; dialog; soft deletes; delete guard with "Move posts to…"), tags (rename, merge, hard delete with post count); Tiptap editor with the heading rules, library images inheriting alt, word count/read time; HTML purified on save; public news from the DB; RSS `/news/feed.xml`.
**Test:** AC-NEWS-01/02; H4 directly after H2 is blocked; pasted `<script>` is removed; the RSS feed opens in a reader.

### 16.4 — Testimonials, partners, FAQs, team, offices admin; remove demo content

**Deps:** 16.2, 16.3 · **Read:** spec/social.md (all); spec/admin.md ADM-SCR-09; design/admin.md CMP-ADM-SORTLIST; spec/crud.md rows Testimonials / Partners / FAQs / Team members / Offices; DECISIONS DEC-29, DEC-31, DEC-33.
**Build:** CRUD with drag order and publish toggles for testimonials, partners, FAQs (attach to projects/locations, `show_on`), team members, offices — all soft-deleted into the Recycle bin, offices guarded by job posts; editor type per the entity matrix; public blocks read the DB; `DemoContent`, `DemoProjectSearch`, `DemoMedia` and `demo:images` deleted.
**Test:** reorder partners → marquee order follows; unpublish a testimonial → it disappears; `grep -r Demo app/` finds nothing; every public page still renders.

## Phase 17 — Careers, search, import/export

### 17.1 — Careers & applications

**Deps:** 16.4, 15.1 · **Read:** spec/careers.md (all); spec/admin.md ADM-SCR-10, careers permission rows; spec/architecture.md ARC-012 (00:05 job); spec/crud.md rows Job posts / Job applications, DEL-07.
**Build:** job posts + applications (both soft-deleted, applications auto-deleted after 365 days with their CV files); jobs table + editor (duplicate); applications table + drawer (status, notes, authorised CV download); auto-close at 00:05; public careers from the DB.
**Test:** AC-CAR-01; a job past its deadline closes after running the scheduler; the CV downloads only when signed in with permission.

### 17.2 — Site search (Scout database engine)

**Deps:** 16.4 · **Read:** spec/search.md FR-SRCH-010; spec/pages.md PG-19; spec/nfr-ops.md NFR-SEC-10 (`/search` limit).
**Build:** owner approves `laravel/scout` 11. FULLTEXT indexes, searchable projects/posts/pages, grouped results (max 10 per group), rate limit 60/min/IP; the demo search is removed.
**Test:** search a project name, an area, an article word; a 1-character query shows the minimum message; the 61st request in a minute → 429.

### 17.3 — Import / export wizard, personal-data export & erase

**Deps:** 15.2, 16.4, 17.1 · **Read:** spec/admin.md §10.6, ADM-TBL-012; spec/architecture.md ARC-011 (exports queue); spec/nfr-ops.md NFR-PRIV-03.
**Build:** upload → column mapping → preview 20 rows → queued import → report + error CSV, for leads, subscribers, redirects, locations, FAQs; exports > 5,000 rows queued with a signed 24 h link + notification; every export logged. Personal data: find everything for a phone or email (leads, applications, subscriptions) → export it, or erase it (hard delete + activity entry without PII).
**Test:** import a sample Facebook-leads CSV with one bad phone row → report shows created/failed and the error CSV; an export over 5,000 rows arrives as a notification link; erase a test person → their leads, application and subscription are gone and the activity log names no PII.

## Phase 18 — Dashboard & system screens

### 18.1 — Dashboard home

**Deps:** 17.1, 15.3 · **Read:** spec/admin.md §10.3; design/admin.md CMP-ADM-KPI, CMP-ADM-QUICK, CMP-ADM-CHART, §12.2; spec/crud.md HELP-05.
**Build:** owner approves the chart package (shadcn-vue Chart / Unovis). KPI cards with deltas and "—" zero states, quick actions, leads trend, pipeline, leads by project, lead sources, my follow-ups, recent activity, SEO health, unread enquiries — each widget per permission; "Getting started" checklist card (HELP-05) for Admin+.
**Test:** a fresh database shows "—" with helper text, not 0, and the Getting started card with every step linking to its screen; completing a step ticks it; a Sales Executive sees only own KPIs; each card links to the filtered list.

### 18.2 — Command palette, errors view, maintenance screen

**Deps:** 18.1 · **Read:** spec/admin.md ADM-SCR-18, ADM-SCR-19, ADM-SCR-22; spec/performance-a11y.md SEO-ANA-006; spec/cms.md CMS-CACHE-005; design/admin.md CMP-ADM-CMDK, CMP-ADM-INFO; spec/hero.md HERO-028.
**Build:** command palette (`/`, Ctrl/⌘+K; projects, leads by name/phone, posts, pages, settings, actions; screen synonyms per HELP-07); `/internal/client-errors` (10/min/IP) + errors view grouped by message (server + client); maintenance screen (caches, app info, maintenance mode with message and bypass secret); the hero video error now reports to the endpoint.
**Test:** `/` opens the palette, a lead found by phone; a thrown client error appears in the errors view; maintenance mode → branded 503 with Retry-After, bypass secret works.

## Phase 19 — SEO hardening

### 19.1 — Structured data (JSON-LD) & OG images

**Deps:** 17.2 · **Read:** spec/structured-data.md (all); spec/seo.md SEO-ONP-006..008; spec/cms.md §8.5.
**Build:** owner approves `spatie/schema-org` 3. One `@graph` builder per page type (Organization, WebSite, RealEstateAgent, BreadcrumbList, RealEstateListing + residence types + Offer only with a visible price, CollectionPage/ItemList, BlogPosting/NewsArticle, FAQPage, Person list, JobPosting, VideoObject); GD-generated fallback OG image at publish.
**Test:** every page type through Google Rich Results Test and validator.schema.org → 0 errors; a project with hidden prices has no `Offer`; a post without a cover gets a branded OG image.

### 19.2 — Sitemaps & publish hooks

**Deps:** 19.1 · **Read:** spec/seo.md SEO-TEC-011, SEO-TEC-012; spec/cms.md §8.6 (on-transition steps); spec/locations.md FR-LOC-003; spec/architecture.md ARC-012.
**Build:** owner approves `spatie/laravel-sitemap` 7. Sitemap index + 6 sitemaps as static files with images, only indexable canonical URLs; regenerated on publish (debounced 2 min) and nightly 02:00; SEO screen "Sitemaps" tab (last generated, URL counts, regenerate — ADM-SCR-14).
**Test:** publish a project → it appears in `projects.xml` after the debounce; a noindex area is absent; archived content leaves immediately.

### 19.3 — Snapshot pipeline (pre-rendered HTML on shared hosting)

**Deps:** 19.2 · **Read:** spec/seo.md SEO-REN-02..10, DM-SNAP; spec/nfr-ops.md DEP-SSR-01; spec/decisions.md DEC-01, OPEN-04; DECISIONS DEC-27.
**Build:** `ssr_snapshots`; `SnapshotGateway` (props hash, volatile keys excluded); HMAC upload endpoint (limits, 401 on a bad signature); GitHub Actions renderer workflow (Node 22, concurrency 8) triggered by repository_dispatch, nightly and after deploy; miss counter + "Render now"; coverage health check.
**Test:** AC-REN-01..03 (curl with a bingbot user agent shows the H1 and facts; after a rename, no stale body is served); coverage ≥ 95 % on staging.

### 19.4 — Security headers, CSP, HTTP caching & CDN

**Deps:** 19.3 · **Read:** spec/nfr-ops.md NFR-SEC-01..13; spec/cms.md CMS-CACHE-004, CMS-CACHE-005; spec/performance-a11y.md SEO-PERF-010, SEO-PERF-011; spec/seo.md SEO-TEC-006.
**Build:** `SecurityHeaders` middleware with nonce CSP (report-only first, `/internal/csp-report`), the other headers, public HTML cache headers only for anonymous requests, admin/forms `no-store`, optional Cloudflare purge on publish, HTTPS + HSTS trial value.
**Test:** securityheaders.com-style check on staging; no CSP violations on any page type in report-only mode; a logged-in admin never receives a cacheable HTML response.

## Phase 20 — Launch

### 20.1 — Backups, scheduler, deploy pipeline

**Deps:** 19.4, 18.2 · **Read:** spec/nfr-ops.md NFR-BAK-01, NFR-LOG-01, DEP-ENV-01..03, DEP-CD-01, DEP-CRON-01, DEP-PHP-01, DEP-VAR-01; spec/architecture.md ARC-012, ARC-013; DECISIONS DEC-27; spec/decisions.md OPEN-02.
**Build:** owner approves `spatie/laravel-backup` 9. Full schedule of ARC-012; backups + off-site pull workflow; deploy workflow without a test step (or the zip fallback of OPEN-02); prune jobs (404 logs, webhooks, snapshots, activity log, IP hashing, soft-delete purge).
**Test:** restore drill of a backup on staging; a deploy to staging switches releases and a rollback works; `schedule:list` matches ARC-012.

### 20.2 — Final QA & launch

**Deps:** 20.1 · **Read:** spec/nfr-ops.md DEP-LAUNCH-01, QA-CMS-01, QA-UAT-01; spec/performance-a11y.md SEO-ANA-005, SEO-ANA-007, SEO-PERF-002; spec/decisions.md OPEN-01..04.
**Build:** content audit (every visible element traced to an admin field), UAT with the client's editors and sales team, training notes, launch checklist (DNS, SSL, mail records, GSC/Bing, sitemap submit, consent test, uptime monitor, `APP_DEBUG=false`, snapshot coverage, Lighthouse budgets).
**Test:** every DEP-LAUNCH-01 item ticked; QA-UAT-01 signed by the client.
