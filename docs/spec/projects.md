# Projects & unit types (REQUIREMENT §7.1, DM-PROJ…DM-FACET)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 7.1 Projects and unit types (`FR-PROJ`)

**Description.** A project is the listing entity. It carries facts, media, unit types, amenities, progress updates, nearby places, documents and SEO fields. Public pages: PG-02, PG-03, PG-04. Data: DM-PROJ, DM-UNIT, DM-AMEN, DM-PROG, DM-NEAR.

**Project types and statuses**

| Field | Values |
|---|---|
| Segment | `residential`, `commercial`, `mixed_use`, `land` |
| Status | `upcoming`, `ongoing`, `ready`, `handed_over` |
| Booking state (badge) | `booking_open`, `limited_units`, `sold_out`, `none` |

**User stories**

| ID | Story |
|---|---|
| FR-PROJ-001 | As a buyer, I can open a project page and see its key facts within the first screen (name, segment, status, location, size range, bedrooms range, handover date). |
| FR-PROJ-002 | As a buyer, I can view unit types with size, bedrooms, bathrooms, balconies, facing, floor plan image and availability, and see a price only when the company chose to show it. |
| FR-PROJ-003 | As a buyer, I can browse the gallery in a full-screen lightbox with keyboard and swipe navigation. |
| FR-PROJ-004 | As a buyer, I can download the brochure after giving my name and phone (when gating is on). |
| FR-PROJ-005 | As an NRB buyer, I can see dated construction progress with percentage and photos. |
| FR-PROJ-006 | As a buyer, I can see the location on a map and travel times to nearby landmarks, and open it in Google Maps. |
| FR-PROJ-007 | As a buyer, I can see up to 3 similar projects. |
| FR-PROJ-008 | As an editor, I can create, edit, duplicate, reorder (featured order), publish, schedule, unpublish and soft-delete projects. |
| FR-PROJ-009 | As an editor, I can choose per project and per unit type whether a price is shown. |
| FR-PROJ-010 | As an editor, I can mark a project featured; featured projects appear on PG-01 in manual order. |

**Fields (summary; full schema in DM-PROJ / DM-UNIT)**

- Identity: name, slug, tagline (≤ 120), segment, status, booking state, featured flag, featured order.
- Facts ("At a glance"): address line, location (area), latitude, longitude, land area (katha, decimal 2), storeys (e.g. "2B + G + 12"), total units, units per floor, lifts, car parks, basements, road width (ft), facing, orientation, handover date (month precision), launch date, RAJUK approval no., architect, structural engineer, contractor, custom facts (repeater of label/value, max 12).
- Content: overview (rich text, ≥ 120 words to publish), design narrative (rich text, optional), amenities (multi-select from DM-AMEN + order), feature highlights (repeater: image, title, text; max 8).
- Media: cover image (required), gallery (≥ 4 images to publish, max 60, each with alt + caption + category: exterior/interior/amenity/site/night), video (YouTube URL or uploaded MP4 ≤ 50 MB with WebVTT captions file required when the video has speech — `DESIGN.md` A11Y-D07), virtual tour URL (allowed hosts: `my.matterport.com`, `kuula.co`, `www.youtube.com`), brochure PDF (≤ 20 MB), additional documents (PDF, max 5, each public or gated).
- Pricing: `show_price` (bool, default false), `price_from_bdt` (computed from shown unit types, overridable), `price_note` (≤ 80, e.g. "Excluding registration").
- Unit types (repeater, 1–20): name, size sft (int), size range max (optional), bedrooms, bathrooms, balconies, facing, floor range text, floor plan image (required), plan description (≤ 300 chars, room sizes for screen-reader users — `DESIGN.md` A11Y-D11), 3D plan image, price BDT (optional), `show_price` (bool), availability (`available`, `limited`, `sold_out`), order.
- Progress updates (repeater): date (month), percent 0–100, stage (`foundation`, `structure`, `finishing`, `complete`), note (≤ 300), photos (max 12).
- Nearby places (repeater, max 10): name, category (`school`, `hospital`, `mall`, `mosque`, `transport`, `park`, `office`, `airport`), travel time minutes, mode (`drive`, `walk`).
- Relations: location (required), similar projects (manual, max 3; when empty, auto = same segment + same city, newest first).
- SEO: §9.3 fields via DM-SEO.
- Lead routing: default assignee (user with Sales role, optional), WhatsApp number override.

**Validation rules**

| Field | Rule |
|---|---|
| name | required, 3–90 chars, unique among non-deleted projects |
| slug | auto from name (`str()->slug()`), editable, `^[a-z0-9]+(?:-[a-z0-9]+)*$`, ≤ 80, unique; change creates a slug history row (SEO-TEC-003) |
| latitude / longitude | required to publish; lat 20.5–26.7, lng 88.0–92.7 (Bangladesh bounding box) |
| handover date | ≥ launch date when both set |
| percent (progress) | integer 0–100; a newer update cannot have a lower percent than an older one (warning, overridable with reason) |
| price_bdt | integer ≥ 100,000 and ≤ 10,000,000,000 when present |
| images | JPEG/PNG/WebP/AVIF, ≥ 1200 px wide (gallery), ≥ 1600 px (cover), ≤ 12 MB, alt required (≥ 5, ≤ 125 chars) |
| publish | blocked until: cover, ≥ 4 gallery images, overview ≥ 120 words, location, lat/lng, ≥ 1 unit type (except `land` segment), meta description present or auto-template resolvable |

**Acceptance criteria**

- **AC-PROJ-01** Given a published project with `show_price=false`, When any visitor or crawler loads PG-04, Then no price string appears in HTML, JSON-LD or the Inertia page props.
- **AC-PROJ-02** Given unit types where only "Type B" has `show_price=true` at ৳ 1,45,00,000, When PG-04 renders, Then "Type B" shows "৳ 1.45 crore" and the title block shows "From ৳ 1.45 crore" only if the project's `show_price=true`.
- **AC-PROJ-03** Given a draft project missing a cover image, When the editor clicks Publish, Then publishing is refused and a checklist lists each unmet rule with a link to the field.
- **AC-PROJ-04** Given a project scheduled for 01 Dec 2026 09:00 Asia/Dhaka, When the scheduler runs at or after that minute, Then status becomes published, sitemaps regenerate and a snapshot render is requested (SEO-REN-04).
- **AC-PROJ-05** Given gating is on, When a visitor submits name and a valid phone in the brochure dialog, Then a `brochure_download` lead is stored and the PDF download starts via a signed URL valid 10 minutes.
- **AC-PROJ-06** Given a project is soft-deleted, When its URL is requested, Then the response is 410 Gone for 90 days, then 404; if an editor set a redirect target, 301 to it instead.
- **AC-PROJ-07** Given a project with no manual similar projects, When PG-04 renders, Then up to 3 published projects of the same segment and city appear, excluding itself.

**Edge cases**

- Project with zero unit types (land segment): "Plans" tab hidden; plot sizes come from custom facts.
- Handed-over project: enquiry panel heading switches to "Interested in a resale or a similar project?" and lead type stays `project_inquiry`.
- Gallery image deleted from library while used: blocked by media usage check (CMS-MED-008).
- YouTube video ID invalid: validation calls nothing external; regex on URL shape only (`youtube.com/watch?v=`, `youtu.be/`, `youtube.com/shorts/`).

**Permissions:** `projects.view`, `projects.create`, `projects.update`, `projects.publish`, `projects.delete`. Content Editor cannot delete.

## Data model (REQUIREMENT §11.2)

**DM-PROJ `projects`** (SD)

| Column | Type | Null | Notes |
|---|---|---|---|
| name | varchar(90) | no | unique among non-deleted (app-level) |
| slug | varchar(80) | no | unique |
| tagline | varchar(120) | yes | |
| segment | varchar(20) | no | enum |
| lifecycle | varchar(20) | no | `upcoming`, `ongoing`, `ready`, `handed_over` |
| booking_state | varchar(20) | no | default `none` |
| location_id | FK locations (area) | no | |
| address | varchar(255) | yes | |
| latitude, longitude | decimal(10,7) | yes | required to publish |
| land_area_katha | decimal(8,2) | yes | |
| storeys | varchar(40) | yes | e.g. "2B + G + 12" |
| total_units, car_parks | smallint unsigned | yes | |
| units_per_floor, lifts, basements | tinyint unsigned | yes | |
| road_width_ft | smallint unsigned | yes | |
| facing, orientation | varchar(40) | yes | |
| launch_date, handover_date | date | yes | day stored as 1 when month precision |
| rajuk_approval_no | varchar(80) | yes | |
| architect, structural_engineer, contractor | varchar(150) | yes | |
| custom_facts | json | yes | ≤ 12 |
| overview, design_narrative | longtext | yes | sanitised HTML |
| highlights | json | yes | `[{asset_id,title,text}]` ≤ 8 |
| cover_asset_id | FK media_assets | yes | required to publish |
| video_type | varchar(10) | no | `none` / `youtube` / `upload` |
| video_url | varchar(255) | yes | |
| video_asset_id, video_poster_asset_id, video_captions_asset_id | FK media_assets | yes | captions = WebVTT file |
| virtual_tour_url | varchar(255) | yes | host allowlist |
| show_price | bool | no | false |
| price_from_bdt | bigint unsigned | yes | computed on save; overridable flag `price_from_manual` bool |
| price_note | varchar(80) | yes | |
| is_featured | bool | no | false |
| featured_order | smallint | yes | |
| brochure_gated | bool | no | true |
| default_assignee_id | FK users | yes | |
| whatsapp_override | varchar(20) | yes | E.164 |
| size_min_sft, size_max_sft | int unsigned | yes | denormalised from unit types |
| beds_min, beds_max, baths_min, baths_max | tinyint unsigned | yes | denormalised |
| publish_status | varchar(12) | no | |
| published_at | timestamp | yes | |
| pending_revision_id | FK revisions | yes | |
| lock_version | int unsigned | no | 0 |
| archived_redirect_to | varchar(255) | yes | AC-PROJ-06 |

Indexes: (publish_status, segment, lifecycle), (location_id, publish_status), (is_featured, featured_order), size_min_sft, beds_min, price_from_bdt, FULLTEXT(name, tagline), FULLTEXT(overview).

**DM-UNIT `project_unit_types`**: project_id FK (cascade), name varchar(60), size_sft int unsigned, size_sft_max int unsigned null, bedrooms tinyint null, bathrooms tinyint null, balconies tinyint null, facing varchar(40) null, floor_range varchar(60) null, floor_plan_asset_id FK, plan_description varchar(300) null, plan_3d_asset_id FK null, price_bdt bigint unsigned null, show_price bool default false, availability varchar(12) default `available`, sort_order int. Index (project_id, sort_order).

**DM-AMEN `amenities`**: name varchar(80) unique, slug, icon varchar(40) (Lucide allowlist), category varchar(20) (`building`, `security`, `lifestyle`, `utility`). Pivot `amenity_project` (project_id, amenity_id, sort_order) PK (project_id, amenity_id).

**DM-PROG `project_progress_updates`**: project_id FK, reported_on date, percent tinyint unsigned (0–100), stage varchar(12), note varchar(300) null; photos via media_usages (field `photos`). Index (project_id, reported_on desc).

**DM-NEAR `project_nearby_places`**: project_id FK, name varchar(100), category varchar(20), minutes smallint unsigned, mode varchar(5), sort_order.

**DM-PDOC `project_documents`**: project_id FK, media_asset_id FK, title varchar(100), kind varchar(20) (`brochure`, `price_list`, `specification`, `other`), is_gated bool, sort_order.

**DM-SIMILAR `project_similar`**: project_id, similar_id, sort_order; PK pair.

**DM-FACET `facet_pages`**: kind varchar(10) (`segment` / `status`), value varchar(20), title varchar(80), intro longtext null, sort_order. Unique (kind, value).
