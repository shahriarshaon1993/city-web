# CRUD, deletion, recycle bin, toasts, validation & guidance (added 2026-10-25, DEC-33)

> Not part of the original REQUIREMENT/DESIGN. It is the owner's "everything user-friendly and smooth" requirement turned into rules. It builds on the existing components and IDs: CMP-ADM-TABLE/FORM/CONFIRM/CHECKLIST, CMP-TOAST-01, CMP-FORM-01, UX-03/04/06/09/10, ADM-TBL-*, CMS-PUB-*. Behaviour here wins over the spec where they differ (DEC-33). Every admin list is the one TanStack-based `DataTable` (DEC-34).

## 1. Screens (`CRUD`)

| ID | Rule |
| --- | --- |
| CRUD-01 | Every admin entity has a **list page** (DataTable). Create/edit happens on a **full-page editor** (rich entities) or in a **dialog** (≤ 6 simple fields); the entity matrix (§6) fixes which. Quick changes (publish switch, featured star, visibility eye, active toggle) are **inline** with an optimistic update and rollback on error (Inertia v3 optimistic updates). |
| CRUD-02 | List page anatomy, the same everywhere: page header (title · one-line description of what the screen is for · primary button "New {thing}"); status tabs with counts where the entity has a status (All · Published · Draft · Scheduled · Archived, or the entity's own states); right of the tabs a link "Recycle bin ({n})" filtered to this type (hidden when 0 or not permitted); toolbar (search, filters, columns, views, export); row click opens the editor; row actions = Edit · View on site / Preview · Duplicate · Delete (top two as icon buttons with tooltips, the rest in the ⋯ menu, CMP-ADM-TABLE); bulk actions per ADM-TBL-009. |
| CRUD-03 | Create: "New {thing}" opens the editor with sensible defaults (status draft, today's dates, default assignee…). The first save of a page editor redirects to that record's edit URL (Back still works) with a toast. Dialog entities stay on the list, close the dialog, and highlight the new or changed row for 2 s. |
| CRUD-04 | Edit: sticky action rail (≥ 1280) / sticky bottom bar (< 1280) with the primary action and "Last saved {time}"; a dirty marker on the title; **Ctrl/⌘+S** saves; the unsaved-changes guard on navigation and tab close; publishable entities use Save draft / Publish / Publish changes / Schedule (CMS-PUB-002). |
| CRUD-05 | **Duplicate** exists for projects, custom pages, posts, job posts, and unit types/repeater cards inside an editor. It creates a draft "Copy of {name}" with a free slug (`-copy`, `-copy-2`…), copies media references, relations and SEO (never revisions, leads or slug history), opens the copy's editor and toasts "Copy created". |
| CRUD-06 | **Ordering** (entities with `sort_order`, featured order, menu tree, blocks): drag handle + keyboard (Space to lift, arrows, Space to drop; or Move up/down). The order saves at once with the toast "Order saved" + Undo. |
| CRUD-07 | Every create, update, delete, restore, publish and order change writes an activity-log entry (ADM-LOG-001) from its Action (DEC-32). |
| CRUD-08 | Words have one meaning each: **Unpublish** = back to draft, not public; **Archive** = kept in the list under "Archived", public URL 410 for 90 days or a 301 when a redirect is set (CMS-PUB workflow); **Delete** = moved to the Recycle bin (§2); **Restore** = out of the bin (or out of Archived) back to **Draft** — never automatically public; **Delete forever** = permanent. Buttons are verb + noun ("Delete project", "Restore 3 items"). |

## 2. Deletion & Recycle bin (`DEL`)

| ID | Rule |
| --- | --- |
| DEL-01 | Delete = **soft delete into the Recycle bin** for every entity marked "Bin" in §6. That extends the spec's soft-delete list (DM conventions) with testimonials, partners, faqs, offices, amenities, post_categories and redirects. Children edited inside a parent (unit types, progress updates, nearby places, documents, highlights, page blocks, menu items) are removed by editing the parent: they disappear from the form, can be undone before saving, and later through revisions (CMS-PUB-004). |
| DEL-02 | The delete confirm (CMP-ADM-CONFIRM) names the item and its consequence. For a published public entity: "It leaves the website. Visitors get 'Gone' for 90 days" + an optional "Redirect visitors to…" picker (writes `archived_redirect_to` / a DM-REDIR rule). After deleting: toast "{Thing} moved to Recycle bin" with **Undo** for 8 s (UX-09). Bulk: "{n} {things} moved to Recycle bin" + Undo. |
| DEL-03 | **Guards — never orphan data.** An item that is in use cannot be deleted. The dialog says why, lists the usages with links, and offers the fix where one exists: an area with projects ("Move them to another area first"), a city with areas, a post category with posts ("Move posts to…" select inside the dialog), an office used by job posts, an amenity used by projects (detach from all with a second confirm), media used anywhere ("Replace everywhere", CMS-MED-008), a user with open leads (reassign per ADM-AUTH-007), a role with users, a system page (`is_system`), the last Super Admin. A disabled Delete shows the reason in its tooltip. |
| DEL-04 | **Recycle bin screen** (System group in the sidebar, count badge): one DataTable across all bin types. Columns: item (thumbnail + name), type, deleted by, deleted at, auto-delete date. Filters: type, deleted by, date range. Row actions Restore · Delete forever; bulk Restore / Delete forever. Each list's "Recycle bin (n)" link opens it pre-filtered (CRUD-02). |
| DEL-05 | **Restore** brings the item back as Draft (publishable entities) or as it was (others), with its relations and media usages intact. Conflicts are resolved in a dialog, never by failing silently: the slug is now used by another item → enter a new slug (suggested `-restored`); the parent is in the bin (an area for a project, a category for a post) → "Restore {parent} too" or pick another parent. Toast "{Thing} restored" + "Open" action. |
| DEL-06 | **Auto-delete** (nightly `PurgeRecycleBin` Action): leads, job applications, newsletter subscribers 365 days (NFR-PRIV-04); media 30 days (CMS-MED-008, with files and conversions); everything else 90 days (matches the 410 window, AC-PROJ-06); users are never auto-deleted (the audit trail points at them). The bin shows each item's auto-delete date. |
| DEL-07 | Permissions: Restore = the delete permission of that type (permission matrix §10.2); **Delete forever** = Admin+ only, with type-to-confirm (the item name, or "DELETE {n}" for bulk). Deleting forever also deletes private files (CVs, landowner documents) and leaves an activity entry without personal data. |
| DEL-08 | Unique values vs the bin: when a new item's slug/name collides with an item in the bin, the validation message says so and links to it: "This URL belongs to "{name}" in the Recycle bin — restore it or delete it forever." |
| DEL-09 | Hard-delete exceptions (no bin, still confirmed): tags (detached from posts; the confirm shows the post count; merge is preferred, FR-NEWS-003), saved table views (own), 404-log rows, notifications, webhook delivery logs, subscriber **Erase** and personal-data erase (NFR-PRIV-03 — hard by definition). Activity-log entries can never be deleted (ADM-LOG-004). |

## 3. Toast messages (`TOAST`)

| ID | Rule |
| --- | --- |
| TOAST-01 | Every successful admin mutation shows **exactly one** toast, sent by the server as Inertia flash `toast: {type, title, description?, action?}` and shown through one client helper (`lib/notify.ts`, built on the starter's `lib/flashToast.ts`). Pages never call vue-sonner directly. Title pattern "{Thing} {past-tense verb}": "Project saved", "Project published", "Lead assigned to {name}", "Order saved". Plain words; no codes, no exclamation marks, title ≤ 60 characters. |
| TOAST-02 | Reversible actions carry an action button for 8 s: Undo (delete, order change, bulk status change, unpublish), Open/View (create, restore, duplicate). Undo calls the reverse Action and toasts "Undone". |
| TOAST-03 | Validation failures do **not** toast on full forms: the error summary + field errors handle them (CMP-FORM-01). Exception: when the failing field is not on screen (inline toggle, drag reorder, bulk action) → an error toast with the message. |
| TOAST-04 | Failures map to fixed messages: network/500 → "Something went wrong. Your changes were not saved." + Retry (persists until dismissed, CMP-TOAST-01); 403 → "You don't have permission to do that."; 404 on a mutation → "This item no longer exists." + Reload; 409 lock conflict → the CMS-PUB-006 dialog, not a toast; **419** → "Your session expired. Sign in again to continue — your changes are kept." (form state kept, sign-in in a new tab or dialog); 429 → "Too many attempts. Try again in {n} seconds." |
| TOAST-05 | Queued or long work (import, export > 5,000 rows, bulk > 200 rows, media conversions) → info toast "{Work} started — we'll notify you when it's done", then a notification (§10.8) when finished. |
| TOAST-06 | Bulk results report partial success honestly: "12 projects published · 2 skipped" + "View why" (opens a dialog listing each skipped item and its reason). |
| TOAST-07 | Public site: lead forms land on the thank-you page (no toast); inline forms (newsletter, brochure, copy link) show the inline success state + a toast (UX-04); public request errors use TOAST-04 wording. |

## 4. Validation (`VAL`)

| ID | Rule |
| --- | --- |
| VAL-01 | One Form Request per form/action (ARC-004), with exactly the spec's rules (field tables, validation tables, AC). Where the spec is silent the defaults are: strings trimmed, `max` = column length; required = `*` / NOT NULL, otherwise `nullable`; enums `Rule::enum`; foreign keys `exists` on non-deleted rows; slugs `^[a-z0-9]+(?:-[a-z0-9]+)*$` + unique (including the bin, DEL-08); URLs `url` (https unless the spec allows otherwise); emails `email:rfc`; phones FR-LEAD-002; integers `integer` + min/max from the column or spec; money BDT integer within spec limits; dates `date` + spec ranges (Asia/Dhaka); files `file` + `mimetypes` (by content) + `max` + `dimensions` per spec; arrays `max` per the spec's "max n"; rich text purified (NFR-SEC-03). |
| VAL-02 | Messages say what to do, in plain words, from `lang/en/validation.php`: human attribute names ("land area (katha)", "cover image"), the spec's own texts where given ("CV must be 5 MB or smaller", "Image 2 needs alt text or must be marked decorative"), and examples for formats ("Enter a Bangladeshi mobile number, e.g. 01712-345678"). |
| VAL-03 | The client shows limits **before** an error happens: `maxlength` + a character counter from 80 % of the limit (SEO fields always, coloured per SEO-ONP-001/002); required markers (CMP-FORM-01); the right input type / `inputmode`; date pickers disable out-of-range dates; dropzones state the allowed types, max size and minimum pixels, and reject wrong files before uploading. Validation runs on blur, then on change once a field has an error (UX-03). |
| VAL-04 | Live checks: slug fields fill automatically from the name until edited by hand, show availability (✓ / ✕ + reason) after 400 ms, and changing a published entity's slug warns "Old links will redirect to the new URL automatically" (SEO-TEC-003). |
| VAL-05 | Saving a draft is blocked only by format/type rules. Publish-only rules (FR-PROJ publish list, CMS-PB-003/004, alt text…) go to the publish checklist with Fix links (CMP-ADM-CHECKLIST) and block only Publish/Schedule. |
| VAL-06 | Input is never lost: on an error every value is kept (Inertia `preserveState` on errors); files already uploaded stay attached; the unsaved guard + draft autosave (UX-10); a 419 keeps the form (TOAST-04). |
| VAL-07 | Errors are findable in big editors: server errors for nested fields (`unit_types.2.size_sft`) land on that repeater card, which opens itself and shows "2 errors" in its header; editor tabs show a red error count; the error summary links jump to the field across tabs. |
| VAL-08 | The server is the truth. The client mirror (zod on public lead forms, optional) only gives early feedback and never replaces the Form Request. |

## 5. Guidance — "the screen tells you what to do" (`HELP`)

| ID | Rule |
| --- | --- |
| HELP-01 | Every admin page header has a one-line description of the screen's purpose (CMP-ADM-PAGEHEAD), e.g. Projects: "Every development on the website. Drafts stay hidden until you publish them." |
| HELP-02 | Empty states (CMP-EMPTY-01) explain what the list holds, where it appears on the website, and give the one next action: "No projects yet — projects appear on Projects and the home page once published. [New project]". A filtered empty state offers "Clear filters". |
| HELP-03 | Non-obvious fields have a helper line saying where the value appears and its limits ("Shown under the project name on cards. Up to 120 characters."). Image fields state the minimum size and show crop previews of where they are used (CMP-ADM-FOCAL). |
| HELP-04 | Every editor and list row of a public entity has **View on site** (published) or **Preview** (draft/pending, CMS-PUB-005); the public URL is shown under the title with a copy button. |
| HELP-05 | **Getting started** card on the dashboard for a new site: logo & contact details → offices → locations → first project → home page blocks → menus → legal pages → forms consent text → analytics. Each step has a progress tick and a link to the exact screen; the card is dismissible and hides once all steps are done. |
| HELP-06 | Status badges use plain words plus a tooltip with the detail ("Scheduled — goes live 1 Dec 2026, 09:00"; "Pending changes — the live version is older"); colour is never the only signal (A11Y-D09). |
| HELP-07 | Findability: sidebar labels equal page titles; breadcrumbs on every screen; the command palette (ADM-SCR-22) finds screens by synonyms (enquiries → Leads, photos → Media, homepage → Pages › Home). |
| HELP-08 | Disabled buttons always explain why in a tooltip (Publish → "3 checklist items open"; Delete → "Used by 4 projects"). Never hide an action the user has permission for just because it's temporarily unavailable — disable it with the reason. Actions the user lacks permission for are hidden (§10.2). |
| HELP-09 | Keyboard: Ctrl/⌘+S save, `/` or Ctrl/⌘+K command palette, Esc closes, `?` opens a "Keyboard shortcuts" dialog listing them. |
| HELP-10 | Times and numbers read naturally: dates `DD MMM YYYY` + relative time on hover ("3 h ago"), Asia/Dhaka (ARC-016); money and areas per ARC-017; counts with units ("12 projects", not "12"). |

## 6. Entity matrix

Legend — Editor: **Page** full-page editor · **Dialog** · **Inline**. Delete: **Bin** (§2) · **Guard** (DEL-03 applies) · **Hard** (DEL-09) · **—** none. Phase = card that builds it.

| Entity | Editor | Status / publish | Delete | Duplicate | Order | Phase |
| --- | --- | --- | --- | --- | --- | --- |
| Users | Dialog (invite/edit) | Active / inactive | Bin (Super Admin) + Guard (open leads, last Super Admin); never auto-deleted | — | — | 10.3/10.4 |
| Roles | Page | — | Hard + Guard (users assigned; system roles locked) | — | — | 10.3 |
| Media assets | Drawer | — | Bin 30 d + Guard (usages) | — | — | 11.2 |
| Media folders | Inline | — | Hard (only when empty) | — | Yes | 11.2 |
| Settings | Page (tabs) | — | — | — | — | 12.1 |
| Menus / items | Tree editor | — | Items removed in the tree (undo before save) | — | Yes | 12.2 |
| Redirects | Dialog | Active toggle | Bin | — | — | 12.3 |
| 404 log rows | Inline (ignore) | Ignored | Hard | — | — | 12.3 |
| Cities / areas | Page (area), Dialog (city) | Publish (areas) | Bin + Guard (projects, child areas) | — | Yes (tree) | 13.2 |
| Facet pages | Page | — | — (fixed rows) | — | — | 13.2 |
| Amenities | Dialog | — | Bin + Guard (used by projects) | — | Yes | 13.2 |
| Projects | Page (tabs) | Full CMS-PUB + featured | Bin + redirect option (DEL-02) | Yes | Featured order | 13.2/14.1 |
| Leads | Drawer (+ "New lead" dialog, DEC-33) | Pipeline (FR-LEAD-006) | Bin 365 d (Admin) | — | Board | 15.2 |
| Subscribers | — | Pending / active / unsubscribed | Bin 365 d; Erase = Hard | — | — | 15.2 |
| Pages | Page (builder) | Full CMS-PUB | Bin + Guard (`is_system`) | Yes (custom) | Blocks | 16.1/16.2 |
| Posts | Page | Full CMS-PUB | Bin | Yes | — | 16.3 |
| Post categories | Dialog | — | Bin + Guard ("Move posts to…") | — | Yes | 16.3 |
| Tags | Inline (rename, merge) | — | Hard (detach) | — | — | 16.3 |
| Testimonials | Dialog | Published toggle | Bin | — | Yes | 16.4 |
| Partners | Dialog | Published toggle | Bin | — | Yes | 16.4 |
| FAQs | Dialog (large) | Published toggle | Bin | — | Yes | 16.4 |
| Team members | Page | Published toggle | Bin | — | Yes | 16.4 |
| Offices | Page | — | Bin + Guard (job posts) | — | Yes | 16.4 |
| Job posts | Page | Draft / open / closed | Bin | Yes | — | 17.1 |
| Job applications | Drawer | Pipeline (FR-CAR-005) | Bin 365 d | — | — | 17.1 |
| Saved views | Inline | Shared / default | Hard (own) | — | — | 10.3 |
| Activity log | Read-only | — | Never | — | — | 10.3 |
