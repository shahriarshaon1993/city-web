# Media library (REQUIREMENT §8.4, DM-MASSET, DM-MFOLD, DM-MUSE)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 8.4 Media library

| ID | Requirement |
|---|---|
| CMS-MED-001 | Library built on `spatie/laravel-medialibrary` 11.x: each library item is a `MediaAsset` model owning one media file; entities reference assets through DM-MUSE (usage pivot), so one upload can be reused anywhere. |
| CMS-MED-002 | Folders (DM-MFOLD): nested up to 3 levels, rename, move, delete (only when empty). "Unfiled" virtual folder. |
| CMS-MED-003 | Upload: drag-and-drop or picker, multiple files, per-file progress, max sizes: image 12 MB, video 50 MB (hero 6 MB, HERO-022), PDF 20 MB, SVG 1 MB. Allowed MIME (checked by content, not extension): image/jpeg, image/png, image/webp, image/avif, image/svg+xml (sanitised with `enshrined/svg-sanitize`), video/mp4, video/webm, application/pdf. |
| CMS-MED-004 | Metadata: alt text (required for images unless "decorative"), caption, credit, title, focal point (x%, y%), tags. Dimensions, size, MIME, duration (video) read automatically. |
| CMS-MED-005 | Conversions (queued): `thumb` 400 px WebP, `card` 800 px, `md` 1280 px, `lg` 1920 px, `xl` 2560 px (only when source ≥ 2560), each AVIF (when `imageavif` exists) + WebP + JPEG fallback for `lg` only; `og` 1200×630 crop around focal point. Responsive `srcset` generated from conversions. The average colour of `thumb` is stored as `dominant_color` for placeholders. |
| CMS-MED-006 | Image processing driver GD (available on shared hosting); Imagick used automatically if present. Max processing memory 512 MB; sources > 40 megapixels rejected. |
| CMS-MED-007 | Usage tracking: asset detail shows every entity/field using it with links. |
| CMS-MED-008 | Safe deletion: an asset with usages cannot be deleted; the dialog offers "Replace everywhere" (pick another asset, updates all usages in one transaction) or cancel. Unused assets delete to trash (soft delete, purged after 30 days). |
| CMS-MED-009 | Search by filename, alt, caption, tags; filter by type, folder, date range, "missing alt"; sort newest/oldest/name/size; grid and list views; 24/48/96 per page. |
| CMS-MED-010 | Picker dialog reused by every media field with "Upload new" inline. |
| CMS-MED-011 | Original files stored under `storage/app/public/media/{id}/`; conversions alongside; public URLs served through `/storage` symlink with `Cache-Control: public, max-age=31536000, immutable` (filenames include a content hash). |

## Data model (REQUIREMENT §11.2)

**DM-MASSET `media_assets`** (SD): folder_id FK null, title varchar(150), alt varchar(125) null, caption varchar(255) null, credit varchar(120) null, is_decorative bool, focal_x, focal_y tinyint (0–100, default 50), dominant_color char(7) null (computed from the `thumb` conversion, used as image placeholder — `DESIGN.md` §5.4), tags json, width, height int null, duration_s smallint null, uploaded_by FK users. Spatie `media` table holds the file (model_type `media_asset`). Index folder_id, FULLTEXT(title, alt, caption).

**DM-MFOLD `media_folders`**: parent_id null, name varchar(60), sort_order. Unique (parent_id, name).

**DM-MUSE `media_usages`**: media_asset_id FK, usable_type, usable_id, field varchar(80), position smallint. Unique (media_asset_id, usable_type, usable_id, field, position); index (usable_type, usable_id).
