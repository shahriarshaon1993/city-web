# Data model conventions, ERD, morph map, framework tables (REQUIREMENT §11)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 11. Data model (`DM`)

Conventions: MySQL 8.0+ / MariaDB 10.11+, InnoDB, `utf8mb4_unicode_ci`; `id` BIGINT unsigned auto-increment; `timestamps()` on all tables unless noted; money in BDT as BIGINT unsigned (whole taka); coordinates `DECIMAL(10,7)`; enums as `VARCHAR(30)` backed by PHP enums (portable across MySQL/MariaDB); JSON columns validated by schema classes. No translatable fields (§7.9). Soft deletes where marked (SD).

### 11.1 ERD (core)

```mermaid
erDiagram
  LOCATIONS ||--o{ LOCATIONS : "city has areas"
  LOCATIONS ||--o{ PROJECTS : contains
  PROJECTS ||--o{ PROJECT_UNIT_TYPES : has
  PROJECTS ||--o{ PROJECT_PROGRESS_UPDATES : has
  PROJECTS ||--o{ PROJECT_NEARBY_PLACES : has
  PROJECTS ||--o{ PROJECT_DOCUMENTS : has
  PROJECTS }o--o{ AMENITIES : amenity_project
  PROJECTS }o--o{ PROJECTS : project_similar
  PROJECTS ||--o{ LEADS : receives
  PROJECT_UNIT_TYPES ||--o{ LEADS : "of interest"
  USERS ||--o{ LEADS : assigned
  LEADS ||--o{ LEAD_ACTIVITIES : timeline
  USERS ||--o{ LEAD_ACTIVITIES : by
  PAGES ||--o{ PAGE_BLOCKS : composed_of
  POST_CATEGORIES ||--o{ POSTS : groups
  POSTS }o--o{ TAGS : taggables
  POSTS }o--o{ PROJECTS : post_project
  TEAM_MEMBERS ||--o{ POSTS : authors
  JOB_POSTS ||--o{ JOB_APPLICATIONS : receives
  OFFICES ||--o{ JOB_POSTS : located_at
  MENUS ||--o{ MENU_ITEMS : has
  MENU_ITEMS ||--o{ MENU_ITEMS : children
  MEDIA_FOLDERS ||--o{ MEDIA_FOLDERS : nests
  MEDIA_FOLDERS ||--o{ MEDIA_ASSETS : holds
  MEDIA_ASSETS ||--|| MEDIA : "spatie file"
  MEDIA_ASSETS ||--o{ MEDIA_USAGES : used_by
  SEO_META }o--|| PROJECTS : "seoable (morph)"
  REVISIONS }o--|| PAGES : "revisionable (morph)"
  SLUG_HISTORIES }o--|| PROJECTS : "sluggable (morph)"
  FAQS }o--o{ PROJECTS : "faqables (morph)"
  USERS ||--o{ SAVED_TABLE_VIEWS : owns
```

Polymorphic relations (morph map aliases, ARC-006): `seo_meta.seoable`, `revisions.revisionable`, `media_usages.usable`, `taggables.taggable`, `faqables.faqable`, `slug_histories.sluggable`, `activity_log.subject`. Morph map: `project, unit_type, location, facet_page, page, page_block, post, post_category, tag, testimonial, partner, faq, team_member, office, job_post, progress_update, setting`.

### 11.2 Entities

Entity → file: DM-USER, DM-VIEW `spec/admin.md` · DM-LOC `spec/locations.md` · DM-PROJ, DM-UNIT, DM-AMEN, DM-PROG, DM-NEAR, DM-PDOC, DM-SIMILAR, DM-FACET `spec/projects.md` · DM-PAGE, DM-BLOCK `spec/page-builder.md` · DM-REV, DM-MENU, DM-SET, DM-SEO, DM-SLUGH `spec/cms.md` · DM-POST, DM-PCAT, DM-TAG `spec/news.md` · DM-TEST, DM-PART, DM-FAQ, DM-TEAM, DM-OFF `spec/social.md` · DM-JOB, DM-APP `spec/careers.md` · DM-LEAD, DM-LEADH, DM-SUBS, DM-WEBH `spec/leads.md` · DM-MASSET, DM-MFOLD, DM-MUSE `spec/media.md` · DM-REDIR, DM-404, DM-SNAP `spec/seo.md`.


Framework tables: `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `notifications`, `activity_log`, `media` (spatie).

Soft deletes: users, locations, projects, pages, posts, team_members, job_posts, job_applications, leads, newsletter_subscribers, media_assets. Purge: soft-deleted leads and applications permanently deleted after 365 days; media after 30 days (CMS-MED-008).
