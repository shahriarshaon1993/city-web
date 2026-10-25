# Performance, analytics, accessibility (REQUIREMENT §9.5–9.7)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 9.5 Performance and Core Web Vitals (`SEO-PERF`)

| ID | Requirement | Target / rule |
|---|---|---|
| SEO-PERF-001 | Field CWV (p75 mobile, CrUX/GSC) | LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1 |
| SEO-PERF-002 | Lab (Lighthouse 12 mobile, simulated Slow 4G, on PG-01, 02, 04, 06, 14 with snapshot present) | Performance ≥ 90, Accessibility ≥ 95, Best Practices ≥ 95, SEO = 100; LCP ≤ 2.5 s; TBT ≤ 200 ms; CLS ≤ 0.05 |
| SEO-PERF-003 | Lab on CSR fallback (no snapshot) | Performance ≥ 75 (documented degradation, SEO-REN-06) |
| SEO-PERF-004 | JS budget (public, gzip) | Initial route ≤ 130 KB (Vue + Inertia + app shell + page chunk); GSAP + ScrollTrigger + Lenis ≤ 45 KB loaded after first paint (SEO-PERF-012); admin bundle never loaded on public routes |
| SEO-PERF-005 | CSS budget | ≤ 45 KB gzip total public CSS (Tailwind v4 tree-shaken) |
| SEO-PERF-006 | Images | AVIF/WebP via `<picture>` (CMS-MED-005); every `<img>` has `width`/`height` or an aspect-ratio box; below-the-fold `loading="lazy" decoding="async"`; above-the-fold one `fetchpriority="high"` image only; `sizes` attribute exact per component (DESIGN §5 table) |
| SEO-PERF-007 | Fonts | Self-hosted WOFF2 variable fonts (Manrope, Inter), Latin subset only, `font-display: swap`, preload only Manrope 700 latin and Inter 400 latin; metric-adjusted fallback faces via `fontaine` (Vite plugin) to keep CLS ≈ 0 |
| SEO-PERF-008 | Code splitting | One chunk per Inertia page (`import.meta.glob` lazy), shared vendor chunk; Leaflet, PhotoSwipe, Embla, Tiptap loaded only by components that need them |
| SEO-PERF-009 | Critical CSS | Not inlined in v1 (single CSS file ≤ 45 KB with `preload`); revisit if LCP target missed. Reason: inlining duplicates bytes on every HTML response and fights CDN caching |
| SEO-PERF-010 | Caching headers | Hashed assets (`/build/*`, `/storage/media/*`) `public, max-age=31536000, immutable`; HTML per CMS-CACHE-004; Brotli/gzip by Cloudflare |
| SEO-PERF-011 | CDN | Cloudflare free plan in front of the host: DNS proxied, HTTP/3, Brotli, Early Hints on, Rocket Loader **off** (breaks hydration), Auto Minify off (Vite already minifies) |
| SEO-PERF-012 | Animation loading | Motion runtime (GSAP core + ScrollTrigger + SplitText + Lenis) is dynamically imported after `requestIdleCallback` (timeout 1500 ms) following first paint, only when motion is enabled and `prefers-reduced-motion: no-preference`. Content is fully visible without it (progressive enhancement: elements are visible by default; the reveal "from" state is applied by JS just before animating only for elements below the fold). |
| SEO-PERF-013 | Third parties | GTM loads after consent (Consent Mode v2 defaults to denied) and after `load`; Turnstile script loads only when a form enters the viewport; YouTube via facade (thumbnail + play button; iframe from `youtube-nocookie.com` on click) |
| SEO-PERF-014 | Server | TTFB ≤ 600 ms p75 for cached HTML (Cloudflare edge), ≤ 1,200 ms uncached on shared host; DB queries per public page ≤ 25 (asserted in tests) |

### 9.6 Analytics and monitoring (`SEO-ANA`)

| ID | Requirement |
|---|---|
| SEO-ANA-001 | GTM container (preferred) or GA4 tag ID configured in Settings → Analytics; nothing hard-coded. |
| SEO-ANA-002 | Google Consent Mode v2: default `analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization` = `denied`; updated from the cookie banner choice. |
| SEO-ANA-003 | Inertia navigations push a virtual `page_view` to `dataLayer` (`page_location`, `page_title`) on `router.on('navigate')`; GA4 enhanced-measurement history events disabled to avoid double counting. |
| SEO-ANA-004 | Events: `generate_lead` (lead_type, project_slug, form_location), `brochure_download` (project_slug), `whatsapp_click` (project_slug, location), `phone_click`, `site_visit_request`, `search` (search_term, filters count), `filter_apply` (filter names), `shortlist_add`, `compare_open`, `video_play` (project_slug), `gallery_open`, `map_open`. All pushed to `dataLayer` with consistent snake_case names. |
| SEO-ANA-005 | GSC verification via meta token in Settings (or DNS TXT); Bing Webmaster token likewise. Sitemap index submitted at launch (DEP checklist). |
| SEO-ANA-006 | Error monitoring (free): Laravel log channel `daily` (14 days) + an admin "Errors" view of the last 200 exceptions grouped by message; client JS errors posted to `/internal/client-errors` (rate-limited 10/min/IP) and shown in the same view. |
| SEO-ANA-007 | Uptime: free UptimeRobot monitor (5-minute interval) on `/up` (Laravel health route) and `/` configured at launch; alert email to the client's IT address. |

### 9.7 Accessibility (`SEO-A11Y`)

| ID | Requirement |
|---|---|
| SEO-A11Y-001 | WCAG 2.2 AA conformance for public site and admin. |
| SEO-A11Y-002 | Contrast per DESIGN TOK contrast table; no text below 4.5:1 (3:1 for ≥ 24 px or ≥ 18.66 px bold). |
| SEO-A11Y-003 | Keyboard: every interactive element reachable and operable; visible focus (DESIGN §11); skip link "Skip to content" first in DOM; focus moved to the page H1 container (`tabindex="-1"`) after each Inertia navigation and announced via an `aria-live="polite"` region ("Navigated to {title}"). |
| SEO-A11Y-004 | Target size ≥ 24×24 CSS px (WCAG 2.5.8); primary touch targets 44×44. |
| SEO-A11Y-005 | Motion: `prefers-reduced-motion: reduce` disables smooth scroll, parallax, marquee movement, tilt, magnetic effects, counters (show final values) and video autoplay. A site-level "Reduce motion" toggle in the footer stores the choice in `localStorage`. |
| SEO-A11Y-006 | Forms: labels bound to inputs, errors linked with `aria-describedby`, error summary focused on submit failure, no placeholder-only labels. |
| SEO-A11Y-007 | Automated: axe-core run in E2E on every page type with zero serious/critical violations (QA-A11Y-01); manual NVDA + Chrome and VoiceOver + Safari pass on PG-01, 02, 04, 17 before launch. |
