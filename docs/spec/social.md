# Testimonials, partners, FAQs, team, offices (REQUIREMENT §7.6, DM-TEST…DM-OFF)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 7.6 Testimonials, partners, FAQs, team (`FR-SOC`)

| ID | Requirement |
|---|---|
| FR-SOC-001 | Testimonials: quote (40–600 chars, plain text), person name, designation/location, related project (optional), photo (optional, square ≥ 400 px), tag `nrb` (bool), rating omitted (no review markup, SEO-SD-011), order, published. Rendered as text, never images. |
| FR-SOC-002 | Partners: name, logo (SVG or PNG ≥ 300 px wide, transparent), URL (optional, `rel="noopener"` and `nofollow`), category (`bank`, `supplier`, `consultant`, `group_company`), order. Used by the marquee block. |
| FR-SOC-003 | FAQs: question (≤ 160), answer (rich text, limited: paragraph, bold, link, list), category (`buying`, `payment`, `legal`, `nrb`, `landowner`, `after_sales`, `general`), order, show_on (multi: faq page, project detail, location, landowner, nrb). Project-specific FAQs attachable to a project. |
| FR-SOC-004 | Team members: name, slug, role title, group (`leadership`, `management`, `team`), photo (portrait 4:5, ≥ 800 px), short bio (≤ 400), LinkedIn URL, email (optional, shown only if `show_email`), order, published. |
| FR-SOC-005 | Offices: name, address, city, phone(s), email, opening hours (structured: day ranges + times), lat/lng, is_head_office. Used in PG-17, footer and `LocalBusiness` JSON-LD. |
| FR-SOC-006 | Awards and certifications are page-builder `awards` block data (image, title, issuer, year) — not a separate entity. |

- **AC-SOC-01** Given 12 partners, When the marquee renders, Then logos appear once in the DOM for assistive technology (duplicate track is `aria-hidden="true"`), and the marquee pauses on hover and on focus within.

**Permissions:** `content.*` (Editor).

## Data model (REQUIREMENT §11.2)

**DM-TEST `testimonials`**: quote text, person_name varchar(100), designation varchar(120) null, project_id FK null, photo_asset_id FK null, is_nrb bool, sort_order, is_published bool.

**DM-PART `partners`**: name, logo_asset_id FK, url null, category varchar(20), sort_order, is_published.

**DM-FAQ `faqs`**: question varchar(160), answer text, category varchar(20), show_on json (list), sort_order, is_published. Pivot `faqables` (faq_id, faqable_type, faqable_id, sort_order).

**DM-TEAM `team_members`** (SD): name, slug unique, role_title varchar(100), group varchar(12), photo_asset_id FK, bio varchar(400) null, linkedin_url null, email null, show_email bool, sort_order, is_published.

**DM-OFF `offices`**: name, address varchar(255), city varchar(60), postal_code varchar(10) null, phones json, email null, hours json (`[{days:["Sat",…],opens:"09:30",closes:"18:30"}]`), latitude, longitude, is_head_office bool, sort_order.
