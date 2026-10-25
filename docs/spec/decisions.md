# Spec decisions DEC-01…25 and open items OPEN-01…04 (REQUIREMENT §17–18) — build decisions continue in docs/DECISIONS.md from DEC-26

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 17. Assumptions and Decisions Log

Q-numbers refer to the Phase 1 questions (25 Oct 2026). "Client" = answered by the client; "Default" = recommended default accepted.

| ID | Decision | Source | Rationale | Alternatives considered |
|---|---|---|---|---|
| DEC-01 | Shared hosting; SSR via GitHub-Actions-rendered snapshots + server-rendered head; live SSR when on VPS | Client (Q1) | Inertia SSR needs a persistent Node ≥ 22 process unavailable on shared hosting; snapshots are free, correct (hash-gated) and reuse the same SSR bundle | (a) VPS with live SSR — best, costs money; (b) CSR + Blade head only — simpler, weaker for non-JS crawlers and LCP; (c) Blade-rendered public site — breaks the fixed stack |
| DEC-02 | The Gemcon Group dashboard is a visual/IA reference only; this is a standalone app | Client (Q2) | Client clarified it is an example | Module inside the group codebase |
| DEC-03 | Project is the listing entity with unit types; no per-unit inventory | Default (Q3) | Matches all reference sites and BD sales practice | Unit-level inventory with availability per flat |
| DEC-04 | Segments: Residential, Commercial, Mixed-use, Land/Plot | Default (Q4) | Covers "sales, developer, commercial" brief and the "City" name | Residential + commercial only |
| DEC-05 | Price shown only when chosen per project and per unit type | Client (Q5) | Client wants per-post control | Global price toggle; always "Price on request" |
| DEC-06 | No public agent profiles; leads route to sales users | Default (Q6) | Developer sells through an in-house team | Agent directory with Person pages |
| DEC-07 | No visitor accounts; shortlist/compare in localStorage | Client (Q7) + Default | No login wanted; local lists add value at no backend cost | Accounts with saved searches |
| DEC-08 | No paid APIs: no SMS/push; in-dashboard notifications; optional email via hosting SMTP, default off | Client (Q10) | "Only free" — hosting SMTP is included in the plan; off by default to honour "no alerts" | SMS gateway (SSL Wireless), WhatsApp Business API |
| DEC-09 | Single hero by default; slider optional (images only) | Analysis | One LCP element, clearer message | Slider default |
| DEC-10 | Hero video only on ≥ 1024 px, good connections, no reduced motion; poster elsewhere | Analysis | Mobile data cost in BD; LCP protection | Video on all devices |
| DEC-11 | TanStack Table v8.21.3 instead of v9 | Analysis | shadcn-vue's DataTable recipe and column order/resize patterns target v8 APIs; v9 is a new major released in 2026 — re-evaluate at M5 | v9.3.1; AG Grid |
| DEC-12 | `RealEstateListing` + residence types instead of Product/Offer | Analysis | Truthful markup; avoids invalid merchant markup when prices are hidden | Product + Offer |
| DEC-13 | English only; plain columns | Default (Q8) | Faster build | Translatable JSON columns now |
| DEC-14 | Leaflet + OSM for listing/area maps; keyless Google embed on demand for project location | Default (Q9) | Free, no billing account | Google Maps JS API, MapTiler |
| DEC-15 | F2 Bangla path: migrate translatable columns to JSON via `spatie/laravel-translatable`, add `/bn` prefix and hreflang | Analysis | Keeps v1 simple with a known upgrade | Separate Bangla site |
| DEC-16 | TypeScript 6.0.3 (not 7.0.2) | Analysis | `vue-tsc` depends on the TypeScript JS API; TS 7's native compiler changes that API; revisit when Vue tooling declares TS 7 support | TS 7.0.2 |
| DEC-17 | Domain shown in examples as `www.gemconcity.com` (placeholder) with `www` canonical | Assumption | Domain not supplied | Apex canonical |
| DEC-18 | Brochure gating on by default, per project switch | Default (Q12) | Lead capture, bti practice | Always open |
| DEC-19 | Roles: Super Admin, Admin, Content Editor, Sales Manager, Sales Executive, Viewer | Default (Q13) | Matches content + sales split | Fewer roles |
| DEC-20 | Brand colours from the logo JPEG (`#027685`, `#EC6B51`, `#635C5B`) until vector files arrive | Default (Q14) | Only a 200×46 JPEG supplied | Wait for vectors |
| DEC-21 | Strict TypeScript | Default (Q15) | Safer refactors | JS |
| DEC-22 | Developer-specific pages: Landowner, construction progress, handed-over archive (status page), NRB, Careers; no referral programme | Default (Q11) | Reference-site parity and BG-04 | Referral programme page |
| DEC-23 | Database cache/session/queue; queue processed by scheduler every minute | Analysis (DEC-01) | No Redis or Supervisor on shared hosting | Redis (unavailable) |
| DEC-24 | Cloudflare free plan in front of the host | Analysis | Free CDN/HTTP3/Brotli and HTML edge caching | No CDN |
| DEC-25 | katha = 720 sft for conversions shown on site | Assumption | Dhaka convention | 1 katha = 1,440 sft (other regions) — editable constant if client disagrees |

---

## 18. Open items

| ID | Item | Why open | Who decides | Impact if unresolved by M2 |
|---|---|---|---|---|
| OPEN-01 | Production domain and `www` vs apex | Not provided | Client | Placeholder kept in examples; canonical config set at launch |
| OPEN-02 | Confirm the hosting plan allows SSH and PHP 8.4 with GD WebP (and AVIF) | Plan tier unknown | Client IT | Without SSH, deploy falls back to uploading a built zip via the hosting file manager + web-based migration route protected by a one-time token |
| OPEN-03 | Vector logo (SVG/AI) and official brand colours | Only JPEG supplied | Client | Colours stay as sampled (DEC-20) |
| OPEN-04 | GitHub account/organisation to host the repository and run Actions | Required by DEC-01 | Client + developer | Snapshots unavailable → CSR fallback only (SEO-REN-06) |
