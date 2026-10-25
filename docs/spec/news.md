# News / blog (REQUIREMENT §7.5, DM-POST, DM-PCAT, DM-TAG)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 7.5 News / blog (`FR-NEWS`)

| ID | Requirement |
|---|---|
| FR-NEWS-001 | Posts: title (≤ 110), slug, excerpt (≤ 200, used as default meta description), cover image (required, ≥ 1600 px, alt), body (Tiptap rich text: H2–H4, paragraph, bold, italic, link, bullet/ordered list, blockquote, image from media library with caption, YouTube embed, table, horizontal rule), category (exactly 1), tags (0–10), author (team member or user), published_at, featured flag, related projects (0–3), SEO fields. |
| FR-NEWS-002 | Categories: name, slug, description (shown on PG-13), order. Default categories seeded: News, Press releases, Buying guides, Construction updates, Events. |
| FR-NEWS-003 | Tags: free entry with autocomplete; merged when names match case-insensitively. |
| FR-NEWS-004 | Read time = ceil(words / 200) minutes, shown on PG-14 only (never on home). |
| FR-NEWS-005 | Body H1 is not allowed in the editor; the post title is the only H1. Headings inside body start at H2 and cannot skip levels (editor blocks H4 directly after H2). |
| FR-NEWS-006 | Images inserted in the body must come from the media library and inherit alt text; editor can override per insertion. |
| FR-NEWS-007 | Related posts: 3 newest published in the same category excluding self; fallback newest overall. |
| FR-NEWS-008 | RSS feed at `/news/feed.xml` (RSS 2.0, 20 latest). |

- **AC-NEWS-01** Given a post scheduled for the future, When requested publicly before that time, Then 404; When previewed through a signed preview URL by an editor, Then it renders with a "Preview" ribbon and `noindex`.
- **AC-NEWS-02** Given a post body with an image lacking alt, When saving, Then save is blocked with "Image 2 needs alt text or must be marked decorative".

**Permissions:** `posts.*`; Content Editor full except delete-forever.

## Data model (REQUIREMENT §11.2)

**DM-POST `posts`** (SD): title varchar(110), slug varchar(110) unique, excerpt varchar(200), body longtext, cover_asset_id FK, category_id FK post_categories, author_team_member_id FK null, author_user_id FK null, is_featured bool, read_minutes tinyint, publish_status, published_at, pending_revision_id, lock_version. Indexes: (publish_status, published_at), category_id, FULLTEXT(title, excerpt), FULLTEXT(body). Pivot `post_project` (post_id, project_id).

**DM-PCAT `post_categories`**: name, slug unique, description text null, sort_order. **DM-TAG `tags`**: name varchar(50) unique (case-insensitive collation), slug unique; `taggables` (tag_id, taggable_type, taggable_id) PK triple.
