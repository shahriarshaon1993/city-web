# Architecture — pinned stack, library choices, folders, conventions (REQUIREMENT §12)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 12. Architecture and implementation expectations (`ARC`)

### 12.1 Pinned stack (verified 25 Oct 2026)

npm versions were read from the npm registry on 25 Oct 2026. PHP versions were read from Packagist/package docs; packages marked "lock" were not verifiable from this environment and are pinned to the stated major, with the exact patch fixed by `composer.lock` at scaffold (M0) and recorded back into this table.

| Layer | Package | Pin | Notes / compatibility |
|---|---|---|---|
| Runtime | PHP | 8.4.x | Host currently runs 8.4.26; Laravel 13 supports 8.3–8.5 |
| Runtime | MySQL / MariaDB | MySQL 8.0+ or MariaDB 10.11+ | FULLTEXT InnoDB, JSON columns |
| Runtime | Node (build + snapshot runner only) | 22.x LTS | Inertia SSR requires Node ≥ 22; not needed on the host |
| Backend | `laravel/framework` | 13.38.0 | Latest stable on 20 Oct 2026 |
| Backend | `inertiajs/inertia-laravel` | 3.5.2 | Supports Laravel 11.35+/12/13, PHP ≥ 8.2 |
| Backend | `laravel/fortify` | 1.x (lock) | Headless auth + TOTP 2FA |
| Backend | `spatie/laravel-permission` | 7.x (lock) | Requires Laravel 12/13, PHP ≥ 8.3 |
| Backend | `spatie/laravel-medialibrary` | 11.23.10 | Laravel 12/13, PHP 8.2–8.5 |
| Backend | `spatie/laravel-sitemap` | 7.x (lock) | |
| Backend | `spatie/laravel-activitylog` | 4.x (lock) | |
| Backend | `spatie/laravel-backup` | 9.x (lock) | |
| Backend | `spatie/laravel-honeypot` | 4.x (lock) | |
| Backend | `spatie/laravel-query-builder` | 6.x (lock) | Table filters/sorts allowlists |
| Backend | `spatie/schema-org` | 3.x (lock) | JSON-LD builders |
| Backend | `spatie/simple-excel` | 3.x (lock) | Streaming CSV/XLSX import/export |
| Backend | `laravel/scout` | 11.x (lock) | `database` engine |
| Backend | `tightenco/ziggy` | 2.x (lock) | Named routes in Vue; JS side `ziggy-js` 2.6.5 |
| Backend | `stevebauman/purify` | 6.x (lock) | HTMLPurifier allowlist for rich text |
| Backend | `enshrined/svg-sanitize` | 0.x (lock) | SVG upload sanitising |
| Backend | `james-heinrich/getid3` | 1.9.x (lock) | Video duration/dimensions without ffmpeg |
| Frontend | `vue` | 3.5.44 | 3.6 is a release candidate (Vapor); excluded |
| Frontend | `@inertiajs/vue3` | 3.9.1 | |
| Frontend | `@inertiajs/vite` | 3.9.1 | SSR build entry detection |
| Frontend | `vite` | 8.3.5 | `laravel-vite-plugin` 3.2.0 peers `vite ^8` |
| Frontend | `laravel-vite-plugin` | 3.2.0 | Optional peer `fontaine ^0.8.0` → pin `fontaine` 0.8.x |
| Frontend | `@vitejs/plugin-vue` | 6.0.10 | |
| Frontend | `typescript` | 6.0.3 | TS 7.0.2 (native compiler) is latest, but `vue-tsc` relies on the TS JS API; DEC-16 |
| Frontend | `vue-tsc` | 3.3.13 | peers `typescript >=5` |
| Styling | `tailwindcss`, `@tailwindcss/vite` | 4.3.3 | CSS-first `@theme`; no `tailwind.config.js` |
| Styling | `tw-animate-css` | 1.4.0 | shadcn-vue v4 animation utilities |
| UI | `shadcn-vue` (CLI) | 2.8.2 | `new-york` style, Tailwind v4, OKLCH tokens; components copied into `resources/js/components/ui` |
| UI | `reka-ui` | 2.11.0 | Headless primitives used by shadcn-vue |
| UI | `class-variance-authority` 0.7.1, `tailwind-merge` 3.7.0, `clsx` 2.1.1 | — | shadcn-vue utilities |
| UI | `@lucide/vue` | 1.61.0 | `lucide-vue-next` is deprecated in favour of this package; shadcn-vue component imports rewritten accordingly |
| UI | `vue-sonner` | 2.0.9 | Toasts (shadcn-vue `Sonner`) |
| Tables | `@tanstack/vue-table` | 8.21.3 | v9.3.1 exists; DEC-11 |
| Motion | `gsap` | 3.15.0 | Core + ScrollTrigger + SplitText (all free) |
| Motion | `lenis` | 1.3.27 | Smooth scroll |
| Media UI | `photoswipe` 5.4.4, `embla-carousel-vue` 8.6.0 | — | Lightbox, carousels |
| Maps | `leaflet` 1.9.4, `leaflet.markercluster` 1.5.3 | — | OSM tiles |
| Editor | `@tiptap/vue-3` | 3.32.0 | Plus starter-kit, link, image, table, youtube extensions (same major) |
| DnD | `vue-draggable-plus` | 0.6.1 | Block list, menus, ordering |
| Utils | `@vueuse/core` | 15.0.0 | |
| Validation (client) | `zod` | 4.6.7 | Mirrors lead-form rules for instant feedback |
| Testing | `@playwright/test` 1.64.1, `@axe-core/playwright` 4.14.0, `vitest` 5.0.5, `@vue/test-utils` 2.5.1, Pest 3.x (lock) | — | |
| Lint | `eslint` 10.13.0, `@vue/eslint-config-typescript` 14.9.0, `prettier` 3.9.10, `prettier-plugin-tailwindcss` 0.8.1 | — | |

Compatibility constraints: shadcn-vue with Tailwind v4 uses `@theme inline` and `data-slot` attributes, and its `toast` is replaced by `sonner`; tokens therefore live in CSS (`resources/css/app.css`, DESIGN §13), not a JS config. Inertia 3 removed Axios; admin data fetches use Inertia's built-in client (`useHttp`) for JSON endpoints.

### 12.2 Library decisions (2–3 options each)

| Need | Options evaluated | Choice | Reason |
|---|---|---|---|
| Animation | GSAP 3 + ScrollTrigger; Motion One (`motion`); CSS scroll-driven animations only | GSAP | Free including SplitText and ScrollTrigger; precise timelines and cleanup (`gsap.context().revert()`) for Inertia navigation; CSS scroll-driven animations lack Safari support for all needed effects |
| Smooth scroll | Lenis; Locomotive Scroll v5 (wraps Lenis); none | Lenis | 4 KB, native scroll position (keeps sticky, anchors, ScrollTrigger working), easy `stop()/start()` for modals |
| Data table | TanStack Table v8; AG Grid Community; PrimeVue DataTable | TanStack v8 | Headless (matches shadcn-vue styling), server-side mode, column order/size/visibility state; AG Grid/PrimeVue bring their own design systems |
| Media handling | spatie/laravel-medialibrary; Glide on-the-fly; custom | medialibrary | Queued conversions, responsive images, GD support on shared host; on-the-fly Glide costs CPU per request on shared hosting |
| Permissions | spatie/laravel-permission; Laravel Gates only; Bouncer | spatie | Role/permission tables with UI-editable roles; Gates alone can't be edited by admins |
| Sitemap | spatie/laravel-sitemap; hand-rolled XML; Laravel SEO packages | spatie | Index + image extension support, static file output |
| SEO meta / JSON-LD | Own `SeoData` + spatie/schema-org; artesaos/seotools; ralphjsmit/laravel-seo | Own + schema-org | Head must render in Blade **and** Vue from one prop (SEO-REN-01); typed schema builders avoid invalid JSON-LD |
| Image optimisation | medialibrary conversions with GD (AVIF when `imageavif` exists, WebP otherwise); Cloudflare Polish (paid); external CDN transforms (paid) | GD conversions | Free and offline; AVIF auto-enabled when supported |
| Rich text | Tiptap 3; CKEditor 5; Quill 2 | Tiptap | MIT, Vue 3 native, schema control (block H1, heading order); CKEditor 5 is GPL/commercial |
| Search | Scout database engine (FULLTEXT); Meilisearch; Algolia | Scout database | Meilisearch needs a server process; Algolia is paid |
| Spam | Cloudflare Turnstile + honeypot; Google reCAPTCHA v3; hCaptcha | Turnstile + honeypot | Free, privacy-friendly, no puzzles for users |
| Maps | Leaflet + OSM; Google Maps JS API; MapLibre + vector tiles | Leaflet + OSM (+ keyless Google embed on demand) | Free, no key; Google JS API requires billing |
| Carousel | Embla; Swiper 12; Splide | Embla | 7 KB, headless, accessible patterns; Swiper ships heavy CSS |
| Lightbox | PhotoSwipe 5; GLightbox; custom Dialog | PhotoSwipe | Pinch-zoom, swipe, keyboard, focus management |
| Toasts | vue-sonner (shadcn-vue Sonner); Reka Toast; custom | vue-sonner | shadcn-vue v4 default |
| Import/export | spatie/simple-excel; maatwebsite/excel; League CSV | simple-excel | Streams rows (low memory on shared hosting) and handles XLSX |
| 2FA/auth | Fortify; Breeze/starter kit; custom | Fortify | Headless backend with TOTP and recovery codes; Inertia pages are ours |
| SSR on shared host | Snapshot pipeline (GitHub Actions); CSR + Blade head only; Blade-rendered duplicate public site | Snapshot pipeline + Blade head | Free, keeps a single Vue codebase, upgrade-compatible with live SSR (DEC-01) |

### 12.3 Folder structure

```
app/
  Actions/                # single-purpose domain actions (PublishProject, AssignLead, ApplyRevision…)
  Enums/                  # Segment, Lifecycle, PublishStatus, LeadType, LeadStatus…
  Events/ Listeners/      # EntityPublished → BustCache, RegenerateSitemaps, RequestSnapshots, LogActivity
  Http/
    Controllers/Site/     # public controllers (thin, return Inertia::render)
    Controllers/Admin/    # admin controllers
    Controllers/Internal/ # snapshots upload, client errors
    Middleware/           # HandleInertiaRequests, NormalizeUrl, ApplyRedirects, SecurityHeaders, TrackNotFound
    Requests/Site|Admin/  # Form Requests (all validation)
    Resources/            # API Resources shaping Inertia props (ProjectCardResource, ProjectDetailResource…)
  Jobs/                   # GenerateConversions, DispatchSnapshotRender, SendWebhook, RunImport, BuildExport
  Models/                 # Eloquent models + traits HasSeo, HasRevisions, HasSlugHistory, UsesMedia, Publishable
  Notifications/
  Policies/
  Queries/                # QueryBuilder classes per admin table (allowlisted filters/sorts)
  Services/
    Seo/                  # SeoData, MetaResolver, JsonLd builders per page type
    Ssr/                  # SnapshotGateway, PropsHasher, SnapshotStore
    Blocks/               # BlockSchema per block type, BlockRenderer data loaders
    Search/               # FacetCounter, ProjectSearch
config/ database/{migrations,seeders,factories}/
resources/
  css/app.css             # Tailwind v4 + tokens (DESIGN §13)
  views/app.blade.php     # public root (server head)
  views/admin.blade.php   # admin root
  js/
    app.ts  ssr.ts  admin.ts
    pages/Site/…          # Inertia pages (Home.vue, Projects/Index.vue, Projects/Show.vue…)
    pages/Admin/…
    layouts/SiteLayout.vue  layouts/AdminLayout.vue
    components/ui/        # shadcn-vue generated (owned code)
    components/site/      # public components (CMP-*)
    components/blocks/    # one component per block type
    components/admin/     # admin components (DataTable, MediaPicker, BlockEditor…)
    composables/          # useFilters, useShortlist, useMotion, useConsent, useHeroVideo…
    motion/               # GSAP/Lenis setup, directives v-reveal, v-magnetic, v-tilt, v-parallax
    lib/                  # utils (cn, formatBdt, formatArea), analytics dataLayer helpers
    types/                # generated TS types from Resources (spatie/typescript-transformer, lock 2.x)
routes/web.php  routes/admin.php  routes/internal.php  routes/console.php
tests/Feature  tests/Unit  tests/Browser (Playwright)
.github/workflows/{ci.yml,deploy.yml,snapshots.yml}
```

### 12.4 Conventions

| ID | Convention |
|---|---|
| ARC-001 | Controllers are thin: authorise → validate (Form Request) → call Action/Query → return `Inertia::render('Site/Projects/Show', [...Resources])`. |
| ARC-002 | Props are shaped by API Resources only; models are never passed raw to Inertia (prevents leaking hidden fields such as unit prices when `show_price=false`). |
| ARC-003 | Shared props (`HandleInertiaRequests::share`): `site` (name, logos, contact, social — cached), `menus` (header, footer, legal — cached), `seo` (per page, from controller), `flash`, `errors`, `auth.user` (admin only), `ui.motion` (settings), `ui.consent`, `ziggy`. Large shared data uses `Inertia::once()` so it is not resent on every visit. |
| ARC-004 | Validation exclusively in Form Requests; client-side checks mirror rules for UX (zod) but never replace them. |
| ARC-005 | Every admin route uses a Policy; `Gate::before` grants Super Admin. |
| ARC-006 | `Relation::enforceMorphMap()` with aliases (§11.1). |
| ARC-007 | Route names: `site.projects.index`, `site.projects.show`, `site.locations.show`, `site.forms.store` (`{type}`), `admin.projects.index|create|store|edit|update|destroy|publish|preview`, `admin.leads.board`, `internal.snapshots.store`. URLs generated via Ziggy `route()` in Vue. |
| ARC-008 | Vue: `<script setup lang="ts">`, Composition API, strict TS; props typed from generated types; no Pinia (state lives in Inertia props, URL and small composables); `useRemember` for in-page UI state that should survive back/forward. |
| ARC-009 | Components: `components/ui` = shadcn-vue primitives (owned, themed via tokens); `components/site` and `components/admin` compose them; blocks are 1:1 with block types and receive validated `data` props. |
| ARC-010 | Layouts: persistent layouts (`defineOptions({ layout: SiteLayout })`) so header/footer state and Lenis instance survive navigation. |
| ARC-011 | Queues: `database` driver; queues `default`, `media` (conversions), `notifications`, `snapshots`, `exports`. Jobs idempotent, `tries=3`, backoff `[60, 300, 900]`. |
| ARC-012 | Scheduler (`routes/console.php`): every minute → publish due items, run queue worker (`queue:work --stop-when-empty --max-time=50 --queue=default,notifications,media,snapshots,exports`, `withoutOverlapping`); 00:05 → close expired jobs; 02:00 → sitemaps; 02:15 → prune (404 logs, webhooks, snapshots, activity log, soft-deleted purge, IP hashing); 02:30 → nightly snapshot dispatch; 03:00 → backup (NFR-BAK); 09:00 → follow-up reminders; hourly → snapshot coverage check. |
| ARC-013 | Caching per CMS-CACHE-*; `php artisan optimize` on deploy. |
| ARC-014 | Storage: `local` (private: CVs, landowner docs, exports) and `public` (media) disks on the host; S3-compatible disk (Cloudflare R2 free tier 10 GB) supported by config switch without code change (future). |
| ARC-015 | Mail: SMTP of the hosting account (free); queued mailables; `MAIL_FROM` = `noreply@{domain}` with SPF/DKIM records set at DNS (DEP checklist). |
| ARC-016 | Dates stored UTC; displayed Asia/Dhaka; format `DD MMM YYYY` (e.g. 25 Oct 2026), times 24 h in admin, 12 h in public copy. |
| ARC-017 | Numbers: BDT formatted with South-Asian grouping (`৳ 1,45,00,000`) and compact form (`৳ 1.45 crore`, `৳ 85 lakh`); areas `1,850 sft`; land `5 katha`. |
