# Overview — glossary, IDs, goals, personas, references, scope (REQUIREMENT §1–4, §7 intro)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

> Single source of truth for scope, behaviour, data and delivery. Visual and interaction specifications live in `DESIGN.md`; this document references them by ID (`CMP-*`, `LAY-*`, `MOT-*`, `TOK-*`).

---

## 1. Document control

| Field | Value |
|---|---|
| Document | REQUIREMENT.md |
| Version | 1.0.0 |
| Date | 25 Oct 2026 |
| Status | Approved for build (Phase 1 answers received 25 Oct 2026) |
| Owner | Gemcon City Limited (client) |
| Author | Product / architecture lead |
| Companion | `DESIGN.md` v1.0.0 |
| Change rule | Any change to an ID's meaning bumps the minor version and is logged in §17 |

### 1.1 Glossary

| Term | Meaning in this project |
|---|---|
| Project | A development (building, complex, plot scheme) sold by Gemcon City. The primary listing entity. Also called "property" on the public site. |
| Unit type | A configuration inside a project (e.g. "Type A, 1,850 sft, 3 bed"). No per-unit inventory is tracked. |
| Segment | Project category: Residential, Commercial, Mixed-use, Land/Plot. |
| Status | Project lifecycle: Upcoming, Ongoing (booking open), Ready, Handed over. |
| Location | Hierarchical place: City → Area (e.g. Dhaka → Bashundhara R/A). Drives location landing pages. |
| Lead | Any enquiry captured by a form (project enquiry, site visit, brochure, callback, contact, landowner, NRB). |
| sft | Square feet. Primary area unit. |
| katha | Land unit used in Bangladesh. 1 katha = 720 sft (Dhaka convention, used site-wide). |
| BDT | Bangladeshi Taka, the only currency. Formatted `৳ 1,25,00,000` (Indian/South-Asian grouping) or `Tk 1.25 crore` in compact form. |
| NRB | Non-Resident Bangladeshi buyer. |
| RAJUK | Rajdhani Unnayan Kartripakkha, Dhaka's development authority; its approval number is a trust signal. |
| Block | A reorderable, toggleable page-builder section (§8.1). |
| Snapshot | Pre-rendered HTML of a public page produced by the SSR bundle outside the web host (§9.1). |
| CSR | Client-side rendering. |
| LCP / INP / CLS | Core Web Vitals: Largest Contentful Paint, Interaction to Next Paint, Cumulative Layout Shift. |

### 1.2 ID scheme

| Prefix | Area | Prefix | Area |
|---|---|---|---|
| `BG` | Business goal | `CMS` | Content management |
| `KPI` | Success metric | `SEO` | Search / rendering / performance |
| `PG` | Public page | `ADM` | Admin dashboard |
| `HERO` | Hero section | `DM` | Data model entity |
| `FR-PROJ` | Projects & unit types | `ARC` | Architecture |
| `FR-SRCH` | Search & filters | `NFR` | Non-functional |
| `FR-LOC` | Locations | `QA` | Testing |
| `FR-LEAD` | Leads & forms | `DEP` | Deployment |
| `FR-NEWS` | News / blog | `M` | Milestone |
| `FR-SOC` | Testimonials, partners, FAQs, team | `DEC` | Decision (§17) |
| `FR-CAR` | Careers | `OPEN` | Open item (§18) |
| `FR-SHORT` | Shortlist & compare | | |

---

## 2. Project overview

### 2.1 Business goals

| ID | Goal |
|---|---|
| BG-01 | Generate qualified sales leads for residential, commercial and land projects, with source attribution per lead. |
| BG-02 | Rank on Google for project names, "flat for sale in {area}" and "commercial space in {area}" queries in Dhaka and other operating cities. |
| BG-03 | Build trust: show approvals (RAJUK), construction progress, handed-over projects and the Gemcon Group parent brand. |
| BG-04 | Acquire land through joint-venture (landowner) enquiries. |
| BG-05 | Let non-technical staff publish and edit every public element without a developer. |
| BG-06 | Run on the client's existing shared hosting with zero paid third-party APIs (DEC-01, DEC-07). |

### 2.2 Personas

| ID | Persona | Needs | Primary pages |
|---|---|---|---|
| P1 | Family buyer, Dhaka, 30–50 | Size, bedrooms, location, handover date, price indication, site visit | Home, Projects, Project detail, Location pages |
| P2 | NRB buyer (Gulf, UK, US, Malaysia) | Trust, remote process, progress photos, WhatsApp contact across time zones | NRB page, Project detail, Progress, Contact |
| P3 | Commercial buyer / tenant | Floor area, frontage, parking, road width, location | Commercial segment page, Project detail |
| P4 | Landowner | Partnership terms, track record, simple submission | Landowner page |
| P5 | Job seeker | Open roles, culture, apply with CV | Careers |
| P6 | Content editor (internal) | Fast publishing, previews, media reuse | Admin |
| P7 | Sales executive (internal) | Assigned leads, status pipeline, notes, export | Admin → Leads |

### 2.3 Success metrics

Baseline = first 30 days after launch, measured in GA4 and Google Search Console (GSC).

| ID | Metric | Target | Measured in |
|---|---|---|---|
| KPI-01 | Lead form submissions (all types, excluding spam) | +30% month-over-month for months 2–4, then ≥ baseline × 2 by month 6 | Admin Leads report, GA4 `generate_lead` |
| KPI-02 | Visit-to-lead conversion on project detail pages | ≥ 2.0% of sessions landing on `PG-04` | GA4 exploration |
| KPI-03 | Indexed pages | ≥ 95% of URLs in sitemaps indexed within 30 days of submission | GSC Pages report |
| KPI-04 | Organic sessions | +50% at month 6 versus baseline | GA4 |
| KPI-05 | Core Web Vitals | "Good" for ≥ 75% of mobile page loads on all URL groups | GSC CWV report (CrUX) |
| KPI-06 | Lead first-response time | Median ≤ 4 working hours from `new` to `contacted` | Admin lead status history |
| KPI-07 | Editor self-sufficiency | 0 developer tickets for content changes after month 1 | Ticket log |

---

## 3. Reference analysis summary

Full analysis: Phase 1 doc. Condensed findings and adoption:

| Source | What we adopt | What we avoid |
|---|---|---|
| rancondevelopments.com | Detail-page structure: sticky in-page tabs; "At a glance" facts (units, size, land katha, handover, RAJUK no., facing, road width, lifts, parking); unit-type plans with availability; architect credits; % construction progress with stage bar; nearby travel times; related projects; WhatsApp link with pre-filled project text; editorial restraint and large photography | Brand-only `<title>`s; empty gallery `alt`; no search on home |
| shantaholdings.com | Premium, image-led tone | Client-only rendering (raw HTML = a loader GIF; non-JS crawlers see nothing); generic meta description |
| btibd.com | Hero search widget (size band, bedrooms, location); filter by type/status/location; card stats (size, bed, bath, land); two-step qualifying lead form (timeline, purpose, contact time → contact details); gated brochure; sticky mobile Call/WhatsApp/Enquire bar; keyword-rich detail titles; Landowner, Construction status, NRB pages | Missing home H1; headings used for styling (H4/H6 card data); identical `alt` on every gallery image; testimonials as screenshots; intrusive popup; "18 min read" on home |
| polpharmabiologics.com | Motion language: split-line heading reveals, image mask reveals, counters, logo/team marquees, numbered process steps, tabbed pillars | Motion that blocks content or ignores reduced-motion preferences |
| Gemcon Group dashboard screenshots | Admin IA (grouped sidebar with counts, breadcrumbs, `/` command search, page header with description and actions, KPI cards, quick actions, reorderable lists, media folders, empty states, maintenance and activity screens) | Native selects and `mm/dd/yyyy` inputs; unlabeled icon actions; tables without column control or bulk actions; raw-zero KPIs; duplicate sign-in log rows |

We go beyond all references with: SSR-equivalent crawlable HTML on every public page (§9.1), complete JSON-LD (§9.4), indexable location and segment landing pages (`FR-LOC`), text testimonials, admin-enforced alt text, and measured Core Web Vitals budgets (§9.5).

---

## 4. Scope

### 4.1 In scope (v1.0)

- Public website, English only, all pages in §5.
- Projects with unit types, galleries, floor plans, videos, brochures, progress updates, nearby places, map.
- Search and filters with URL-synced state; indexable segment, status and location landing pages.
- Lead capture (7 lead types), spam protection, in-dashboard pipeline, CSV/XLSX export.
- Page builder with 28 block types (§8.1), global settings, menus, media library, redirects, robots, sitemaps.
- News/blog with categories and tags.
- Testimonials, partners, FAQs, team/leadership, careers with CV upload.
- Browser-only shortlist (favourites) and compare (up to 3 projects).
- Admin with 6 roles, 2FA, audit log, saved table views, dark mode.
- SEO rendering via pre-rendered snapshots on shared hosting (§9.1), JSON-LD, Core Web Vitals budgets.
- Free integrations only: GA4/GTM, GSC, Cloudflare Turnstile, Cloudflare CDN (free plan), OpenStreetMap tiles, Google Maps keyless embed, WhatsApp click-to-chat, hosting SMTP.

### 4.2 Out of scope (v1.0)

| Item | Reason |
|---|---|
| Visitor registration / login | Client decision (Q7) |
| Public agent profiles and agent accounts | Default accepted (Q6); leads go to the sales team |
| SMS, push, WhatsApp Business API, paid email APIs, paid maps | Client decision: no paid APIs (Q10) |
| Bangla language | English only (Q8); see future phases |
| Per-unit inventory, booking, online payment | Default accepted (Q3) |
| External CRM | Outbound webhook only (FR-LEAD-012) |
| Live Node SSR process | Not possible on shared hosting (DEC-01); upgrade path documented |
| Mortgage/EMI calculator | Not requested |

### 4.3 Future phases

| Phase | Item | Precondition |
|---|---|---|
| F1 | Move to VPS and switch to live Inertia SSR (`SEO-REN-07`) | Hosting budget |
| F2 | Bangla (`bn`) translations with hreflang | Translation budget; schema migration to translatable JSON columns |
| F3 | CRM integration through the webhook | CRM chosen |
| F4 | Customer portal (installments, documents) | Separate project |
| F5 | EMI calculator block | Bank partner data |

## 7. Functional requirements by module

Conventions: "Editor" = Content Editor role or higher; permission names refer to §10.2. Acceptance criteria use Given/When/Then. Validation is enforced server-side in Form Requests (ARC-004) and mirrored client-side for UX only.
