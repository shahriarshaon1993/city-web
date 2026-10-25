# Locations & area landing pages (REQUIREMENT §7.3, DM-LOC)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 7.3 Locations and area landing pages (`FR-LOC`)

| ID | Requirement |
|---|---|
| FR-LOC-001 | Locations are a two-level tree: City (e.g. Dhaka, Chattogram, Gazipur, Narayanganj) → Area (e.g. Bashundhara R/A, Uttara, Gulshan, Purbachal). Projects link to an Area. |
| FR-LOC-002 | Each Area has: name, slug, city, hero image, intro (rich text), facts repeater (label/value, max 8), latitude/longitude (centre), zoom (11–16), FAQ items (max 8), nearby areas (manual, max 6), SEO fields. |
| FR-LOC-003 | An Area page is indexable only when it has ≥ 1 published project and an intro of ≥ 150 words; otherwise it renders with `noindex,follow` and is excluded from sitemaps. The admin shows the reason. |
| FR-LOC-004 | `/locations/{area}/{segment}` exists only when the area has ≥ 2 published projects in that segment; otherwise 404. Its intro is optional; when absent, a template sentence plus the area intro is used and the page is `noindex,follow`. |
| FR-LOC-005 | PG-05 lists cities with their areas and published project counts, alphabetical. |
| FR-LOC-006 | Maps: area and listing maps use Leaflet 1.9.4 with OpenStreetMap standard tiles and required attribution; marker clustering via `leaflet.markercluster`; the map loads only when its container enters the viewport or when the user picks map view. Tiles never load on initial page view of PG-04. |
| FR-LOC-007 | Project detail location uses a static facade (a brand-styled SVG map-pattern placeholder, `DESIGN.md` CMP-MAP-02, showing the address and area name; no tile requests) with a "Show interactive map" button that loads a keyless Google Maps embed iframe (`https://maps.google.com/maps?q={lat},{lng}&z=16&output=embed`) and an "Open in Google Maps" link (`https://www.google.com/maps/search/?api=1&query={lat},{lng}`). |

**Acceptance criteria**

- **AC-LOC-01** Given area "Purbachal" with 0 published projects, When `/locations/purbachal` is requested, Then it returns 200 with `noindex,follow` and it is absent from `sitemap-locations.xml`.
- **AC-LOC-02** Given an area with 3 residential projects, When `/locations/uttara/residential` is requested, Then it returns 200 listing exactly those 3, and the breadcrumb is Home › Locations › Uttara › Residential.

**Permissions:** `locations.*` (Editor).

## Data model (REQUIREMENT §11.2)

**DM-LOC `locations`** (SD)

| Column | Type | Null | Notes |
|---|---|---|---|
| parent_id | FK locations | yes | null = city |
| type | varchar(10) | no | `city` / `area` |
| name | varchar(100) | no | |
| slug | varchar(100) | no | unique (areas and cities share namespace) |
| intro | longtext | yes | sanitised HTML |
| facts | json | yes | `[{label,value}]` ≤ 8 |
| hero_asset_id | FK media_assets | yes | |
| latitude, longitude | decimal(10,7) | yes | |
| zoom | tinyint | no | default 14 |
| publish_status | varchar(12) | no | |
| published_at | timestamp | yes | |
| sort_order | int | no | |

Indexes: (type, publish_status), parent_id. Pivot `location_nearby` (location_id, nearby_id, sort_order) PK (location_id, nearby_id).
