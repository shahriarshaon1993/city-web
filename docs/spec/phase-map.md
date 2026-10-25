# Original delivery plan (REQUIREMENT §16) — reference for PHASES.md

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 16. Delivery plan

| Milestone | Scope | Definition of done |
|---|---|---|
| M0 Foundation (week 1) | Repo, CI, Laravel 13 + Inertia 3 + Vue 3.5 + Tailwind 4 + shadcn-vue scaffold, tokens from DESIGN §13, auth + 2FA, roles seed, admin layout shell, deploy to staging | CI green; staging reachable with basic auth; admin login with 2FA works; versions table §12.1 updated with lock values |
| M1 Media, settings, menus (week 2) | Media library, folders, picker, conversions; settings; menus; SEO base (SeoData, Blade head, robots, redirects, 404 log) | Upload → conversions → picker → usage tracking demo; settings change visible on site; redirect manager passes QA-FEAT tests |
| M2 Projects & locations (weeks 3–4) | Projects CRUD with all tabs, unit types, progress, nearby, documents, locations, facets, revisions/preview/scheduling; public PG-02…06 | Publish checklist enforced; PG-04 matches DESIGN LAY-PROJ-DETAIL at 3 breakpoints; AC-PROJ-*, AC-SRCH-*, AC-LOC-* pass |
| M3 Leads (week 5) | All forms, Turnstile, honeypot, duplicates, assignment, pipeline table + board, notifications, export, webhook | AC-LEAD-* pass; E2E enquiry flow green; GA4 events visible in DebugView |
| M4 Page builder & content (weeks 6–7) | 28 blocks, home/about/services/landowner/NRB/legal pages, news, testimonials, partners, FAQs, team, offices, careers | Every block renders and validates; QA-CMS-01 audit on these pages passes |
| M5 Motion & polish (week 8) | Motion system (DESIGN §8), page transitions, skeletons, toasts, shortlist/compare, dark admin | Reduced-motion and touch rules verified; no CLS regression; INP ≤ 200 ms in lab interaction tests |
| M6 SEO hardening (week 9) | Snapshot pipeline, JSON-LD all types, sitemaps, Lighthouse/axe/SEO CI gates, security headers + CSP | SEO-REN AC pass; QA-SEO-* pass; Lighthouse budgets met on staging; CSP report-only clean for 7 days |
| M7 UAT & launch (week 10) | Content entry support, UAT, training (2 sessions), launch checklist | QA-UAT-01 signed; DEP-LAUNCH-01 complete; 2-week hypercare starts |
