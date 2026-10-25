# Non-functional, QA, deployment (REQUIREMENT §13–15) — §14 and DEP-CI-01 are overridden by DECISIONS DEC-26/27

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 13. Non-functional requirements (`NFR`)

| ID | Area | Requirement |
|---|---|---|
| NFR-PERF-01 | Performance | SEO-PERF-001…014 |
| NFR-PERF-02 | Admin | Admin table pages respond ≤ 800 ms p95 server time; editor save ≤ 1.5 s |
| NFR-SEC-01 | OWASP A01 access control | Policies on every admin action; IDOR tests per resource (QA-SEC-02) |
| NFR-SEC-02 | A02 crypto | HTTPS only; secrets in `.env` and encrypted settings; `APP_KEY` rotated only with re-encryption plan |
| NFR-SEC-03 | A03 injection | Eloquent/query builder only; no raw SQL with input; rich text sanitised by HTMLPurifier allowlist; Vue escapes by default, `v-html` only for sanitised server HTML |
| NFR-SEC-04 | A04/A05 config | `APP_DEBUG=false` in production (screenshot showed debug On in local — production deploy check fails if true); directory listing off; `.env` not web-accessible (document root = `public/`) |
| NFR-SEC-05 | A06 components | `composer audit` and `npm audit --omit=dev` in CI; fail on high/critical |
| NFR-SEC-06 | A07 auth | ADM-AUTH-001…007 |
| NFR-SEC-07 | A08 integrity | Snapshot uploads HMAC-signed (SEO-REN-05); webhook signed |
| NFR-SEC-08 | A09 logging | Activity log + Laravel logs (SEO-ANA-006) |
| NFR-SEC-09 | A10 SSRF | No server-side fetching of user-supplied URLs (video/tour URLs are validated by pattern only) |
| NFR-SEC-10 | Rate limiting | Forms (FR-LEAD-003); login (ADM-AUTH-001); `/search` 60/min/IP; `/internal/*` 120/min; global 300/min/IP |
| NFR-SEC-11 | CSRF | Laravel CSRF on all state-changing routes (Inertia sends `X-XSRF-TOKEN`); `/internal/snapshots` exempt but HMAC-protected |
| NFR-SEC-12 | Uploads | MIME sniffed by content (`finfo`), extension allowlist, size limits (CMS-MED-003), SVG sanitised, images re-encoded on conversion, private files never served inline, random storage names |
| NFR-SEC-13 | Headers | `Content-Security-Policy` with nonces for inline scripts (JSON-LD and Inertia page data use `type` attributes that are non-executable; GTM via nonce); allowlists: `script-src 'self' 'nonce-…' https://www.googletagmanager.com https://challenges.cloudflare.com`; `frame-src https://www.youtube-nocookie.com https://maps.google.com https://www.google.com https://challenges.cloudflare.com https://my.matterport.com https://kuula.co`; `img-src 'self' data: https://*.tile.openstreetmap.org https://i.ytimg.com https://www.googletagmanager.com`; `connect-src 'self' https://www.google-analytics.com https://*.analytics.google.com`; plus `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `X-Frame-Options: SAMEORIGIN`. CSP first ships in Report-Only for 7 days (reports to `/internal/csp-report`) |
| NFR-SCAL-01 | Scale | Designed for ≤ 300 projects, ≤ 2,000 posts, ≤ 100,000 leads, ≤ 20,000 media assets, ≤ 50,000 page views/day on shared hosting behind Cloudflare; beyond that, F1 VPS |
| NFR-AVL-01 | Availability | 99.5% monthly (host SLA dependent); maintenance mode serves a branded 503 page with `Retry-After` |
| NFR-BAK-01 | Backups | `spatie/laravel-backup` nightly 03:00: database dump + `storage/app` (excluding conversions, regenerable); retain 7 daily, 4 weekly, 3 monthly; stored on the host **and** copied off-site for free: the GitHub Actions workflow `backup-pull.yml` (daily 03:30) pulls the newest archive over SFTP and stores it as an encrypted (GPG, passphrase in GitHub secrets) workflow artifact with 30-day retention; database-only archives are expected to stay well under the free plan's 500 MB artifact storage, so media archives are pulled weekly only. Restore drill before launch (QA-OPS-01) |
| NFR-LOG-01 | Logging | `daily` channel, 14-day retention; level `warning` in production; snapshot misses and SSR events counted, not logged per request |
| NFR-PRIV-01 | Privacy (Bangladesh + GDPR-aligned for NRB in EU/UK) | Consent banner with granular categories; no analytics/marketing cookies before consent; consent stored 12 months |
| NFR-PRIV-02 | Data minimisation | Lead forms collect only listed fields |
| NFR-PRIV-03 | Rights | Admin can export a person's data (all leads/applications/subscriptions by phone or email) and erase it (hard delete + activity entry without PII) |
| NFR-PRIV-04 | Retention | Lead IPs hashed (SHA-256 with app key salt) after 90 days; leads soft-deleted are purged after 365 days; CVs deleted 12 months after the application's last update |
| NFR-BRW-01 | Browser support | Public and admin: last 2 versions of Chrome, Edge, Firefox, Safari (macOS + iOS 16.4+), Samsung Internet; Android Chrome on Android 10+. Tailwind v4 requires Safari 16.4+, Chrome 111+, Firefox 128+; older browsers receive readable, unstyled-degraded content (no blocking) |
| NFR-DEV-01 | Devices | Tested widths: 360, 390, 414 (phones), 768, 834, 1024 (tablets), 1280, 1440, 1920 (desktops) |
| NFR-I18N-01 | Locale | `en`, timezone Asia/Dhaka; strings in `lang/en` for F2 |

---

## 14. Testing and QA (`QA`)

| ID | Type | Scope / tool | Gate |
|---|---|---|---|
| QA-UNIT-01 | Unit | Pest: enums, formatters (BDT, sft), phone normaliser, props hasher, block schemas, meta resolvers | CI must pass |
| QA-FEAT-01 | Feature | Pest + Laravel HTTP tests: every public route (status, Inertia component, props shape, robots/canonical), filters → redirects/noindex rules (SEO-TEC-020…024), forms (validation, spam, duplicates, assignment), publishing flow, revisions, policies per role (matrix §10.2), import/export, snapshot gateway (match/miss) | CI must pass; ≥ 80% line coverage on `app/Actions`, `app/Services` |
| QA-SEC-02 | Security | Pest tests per resource for IDOR (Sales Executive cannot read another's lead), CSRF, upload rejection (polyglot files, SVG with script), rate limits | CI |
| QA-E2E-01 | Browser | Playwright (Chromium, WebKit, Firefox; mobile emulation Pixel 7, iPhone 14): search → detail → enquiry → thank-you; site visit; brochure gating; shortlist/compare; admin login + 2FA; create & publish project; page builder reorder + preview; lead status change via board and keyboard | CI on PR (Chromium), nightly all browsers |
| QA-A11Y-01 | Accessibility | `@axe-core/playwright` on every page type (light + dark admin): 0 serious/critical; keyboard-only E2E for header, mega menu, filters, gallery, forms, modals; manual screen reader pass before launch | CI + pre-launch |
| QA-SEO-01 | SEO checklist | Every page type: 1 H1; heading order; title 30–60 chars; description 70–160; canonical correct; robots correct; OG tags + image 1200×630; JSON-LD valid; images have alt; no broken internal links (crawler in CI over seeded site, `spatie/crawler` lock); sitemap only contains 200 indexable canonical URLs; robots.txt correct per environment | CI (automated parts) + pre-launch manual |
| QA-SEO-03 | SSR safety | CI builds the SSR bundle and renders one fixture page of every page type with Node; fails on any `window/document` access during render or hydration mismatch warning | CI |
| QA-SEO-05 | Heading audit | Playwright script asserts heading levels per page type | CI |
| QA-SEO-06 | Structured data | §9.4 validation steps | CI + pre-launch |
| QA-PERF-01 | Performance budget | Lighthouse CI (`@lhci/cli`) on PG-01, 02, 04, 06, 14 against staging: thresholds SEO-PERF-002; bundle size check (`size-limit`) for SEO-PERF-004/005 | PR blocks on budget regressions > 5% |
| QA-CMS-01 | Content audit | Checklist walk-through: every visible element on every page traced to an admin field | Pre-launch sign-off by client |
| QA-OPS-01 | Ops | Backup restore drill on staging; snapshot pipeline end-to-end; scheduler/queue cron verification | Pre-launch |
| QA-UAT-01 | UAT | Client editors and sales team run scripted tasks (create project, publish, handle 5 leads, export) | Sign-off for M7 |

---

## 15. Deployment and environments (`DEP`)

| ID | Item | Requirement |
|---|---|---|
| DEP-ENV-01 | Local | Laravel Herd or Sail (Docker) with MySQL 8, Node 22; `composer run dev` runs server, queue, Vite (Inertia v3 SSR works in Vite dev without a separate process) |
| DEP-ENV-02 | Staging | Subdomain on the same shared hosting account (`staging.{domain}`), separate database, HTTP basic auth, `noindex` forced (SEO-TEC-010), snapshot pipeline pointed at staging |
| DEP-ENV-03 | Production | Shared hosting, PHP 8.4, document root → `public/` (symlink `public_html` → `{app}/public`), Cloudflare proxied |
| DEP-CI-01 | CI (GitHub Actions, free) | On PR: install, lint (ESLint, Prettier, Pint), type-check (`vue-tsc`), Pest, build (client + SSR), SSR fixture render, Playwright (Chromium), Lighthouse CI on preview build, `composer audit`, `npm audit` |
| DEP-CD-01 | Deploy | On tag `v*` to `main`: build assets + `composer install --no-dev --optimize-autoloader` in CI; upload release over SSH/rsync to `releases/{timestamp}`; run `php artisan migrate --force`, `optimize`, `storage:link` (first time); switch `current` symlink atomically; `php artisan queue:restart`; trigger full snapshot render; purge Cloudflare cache. Keep 5 releases; `rollback` workflow re-points the symlink. Requires SSH access on the hosting plan (OPEN-02) |
| DEP-CRON-01 | Cron | Single host cron entry: `* * * * * cd {app}/current && php artisan schedule:run >> /dev/null 2>&1` (queue processing runs inside the scheduler, ARC-012) |
| DEP-SSR-01 | SSR | No Node on host; snapshot workflow per SEO-REN-02…10; secrets in GitHub: `SNAPSHOT_HMAC_SECRET`, `SNAPSHOT_TOKEN`, `SITE_URL` |
| DEP-PHP-01 | PHP settings | `upload_max_filesize=64M`, `post_max_size=70M`, `memory_limit=512M`, `max_execution_time=120`, extensions: gd (with webp, avif if available), intl, mbstring, pdo_mysql, fileinfo, zip, exif, bcmath |
| DEP-VAR-01 | Environment variables | `APP_ENV`, `APP_KEY`, `APP_URL`, `APP_DEBUG=false`, `DB_*`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=public`, `MAIL_*` (host SMTP), `INERTIA_SSR_ENABLED=false`, `SSR_MODE=snapshot`, `SNAPSHOT_HMAC_SECRET`, `SNAPSHOT_TOKEN`, `GITHUB_DISPATCH_TOKEN` (fine-grained PAT, `contents:write` on the repo only), `GITHUB_REPO`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET`, `CLOUDFLARE_ZONE_ID`, `CLOUDFLARE_API_TOKEN` (cache purge only), `BACKUP_*` |
| DEP-LAUNCH-01 | Launch checklist | DNS to Cloudflare; SSL full (strict); SPF/DKIM/DMARC for mail; redirects from any old site URLs imported; GSC + Bing verified; sitemap submitted; GA4/GTM consent tested; UptimeRobot on; backups verified; `APP_DEBUG=false`; robots allow; snapshot coverage ≥ 95%; Lighthouse budgets met |
