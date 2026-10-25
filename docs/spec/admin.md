# Admin dashboard — auth, roles, screens, tables, import/export, audit, notifications (REQUIREMENT §10, DM-USER, DM-VIEW)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

## 10. Admin dashboard requirements (`ADM`)

Layout and visual spec: `DESIGN.md` §12 (derived from the Gemcon Group dashboard screenshots). Admin lives at `/admin`, is excluded from SSR and snapshots, sends `X-Robots-Tag: noindex, nofollow`, and loads its own Vite entry (`resources/js/admin.ts`).

### 10.1 Authentication

| ID | Requirement |
|---|---|
| ADM-AUTH-001 | Login with email + password via Laravel Fortify (headless; Inertia pages). Throttle 5 attempts/minute per email+IP, then 15-minute lockout logged to the activity log. |
| ADM-AUTH-002 | 2FA: TOTP (RFC 6238, 6 digits, 30 s) with QR setup, confirmation step, 8 single-use recovery codes, regenerate codes. Optional per user; **required** for Super Admin and Admin (they are redirected to setup until confirmed). |
| ADM-AUTH-003 | Password policy: ≥ 12 chars, mixed case, number, not in a breach list (`Password::uncompromised()` — uses the free HIBP k-anonymity range API; if unreachable, the check is skipped and logged). |
| ADM-AUTH-004 | Password reset by email via hosting SMTP (required configuration even when lead notification emails are off). Reset link valid 60 minutes. |
| ADM-AUTH-005 | Sessions: idle timeout 120 minutes; "Sign out other sessions" on profile; sessions stored in `database`. |
| ADM-AUTH-006 | No public registration route. Users are invited by Admin+ (invite email with set-password link valid 72 h). |
| ADM-AUTH-007 | Deactivated users cannot log in; their assigned open leads are reassigned per FR-LEAD-007 on deactivation (confirmation dialog lists count). |

### 10.2 Roles and permission matrix

Implemented with `spatie/laravel-permission` 7.x (Laravel 12/13, PHP ≥ 8.3). Permissions are seeded; Super Admin can create custom roles from these permissions (e.g. "HR").

| Permission group | Super Admin | Admin | Content Editor | Sales Manager | Sales Executive | Viewer |
|---|---|---|---|---|---|---|
| Dashboard view | ✓ | ✓ | ✓ | ✓ | ✓ (own KPIs) | ✓ |
| Projects: view | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Projects: create/update | ✓ | ✓ | ✓ | – | – | – |
| Projects: publish/schedule | ✓ | ✓ | ✓ | – | – | – |
| Projects: delete/restore | ✓ | ✓ | – | – | – | – |
| Locations, facets, amenities | ✓ | ✓ | ✓ | – | – | view |
| Pages & blocks (incl. home) | ✓ | ✓ | ✓ | – | – | view |
| Legal pages | ✓ | ✓ | – | – | – | view |
| Menus | ✓ | ✓ | ✓ | – | – | view |
| News, categories, tags | ✓ | ✓ | ✓ | – | – | view |
| Testimonials, partners, FAQs, team, offices | ✓ | ✓ | ✓ | – | – | view |
| Media: upload/edit | ✓ | ✓ | ✓ | upload only | – | view |
| Media: delete | ✓ | ✓ | ✓ (unused only) | – | – | – |
| Leads: view all | ✓ | ✓ | – | ✓ | – | ✓ (no phone/email: masked) |
| Leads: view assigned | ✓ | ✓ | – | ✓ | ✓ | – |
| Leads: update status/notes | ✓ | ✓ | – | ✓ | ✓ (assigned) | – |
| Leads: assign | ✓ | ✓ | – | ✓ | – | – |
| Leads: export | ✓ | ✓ | – | ✓ | – | – |
| Leads: delete | ✓ | ✓ | – | – | – | – |
| Subscribers: view/export | ✓ | ✓ | ✓ | ✓ | – | – |
| Careers: jobs | ✓ | ✓ | ✓ | – | – | view |
| Careers: applications | ✓ | ✓ | – | – | – | – |
| SEO: redirects, 404s, robots, sitemaps, snapshots | ✓ | ✓ | ✓ (redirects, 404s) | – | – | view |
| Settings: general/contact/social/header/footer/forms/search/motion | ✓ | ✓ | – | – | – | – |
| Settings: analytics, scripts, webhook, notifications | ✓ | – | – | – | – | – |
| Users & roles | ✓ | users only (cannot assign Super Admin) | – | – | – | – |
| Activity log | ✓ | ✓ | own actions | own actions | own actions | – |
| Maintenance | ✓ | ✓ (caches only) | – | – | – | – |
| Import | ✓ | ✓ | ✓ (content) | ✓ (leads) | – | – |

Every controller action is authorised by a Policy (ARC-005); menu items and buttons are hidden when not permitted, and server responses are 403 regardless of UI.

### 10.3 Dashboard home (`ADM-DASH`)

| Widget | Content | Visible to |
|---|---|---|
| KPI cards (8) | Unread leads (with Δ vs previous 7 days); Leads this month (Δ vs last month); Site visits scheduled (next 7 days); Conversion: won ÷ closed (90 days); Published projects; Articles published (30 days); Open roles / new applications; Snapshot coverage % (SEO-REN-09) | Per permission |
| Leads trend | Line chart, 90 days, by lead type (toggle) | Lead viewers |
| Pipeline | Horizontal bar by status (current counts) | Lead viewers |
| Leads by project | Top 8 projects by leads (30 days) | Lead viewers |
| Lead sources | Bar by UTM source/medium (30 days) | Lead viewers |
| My follow-ups | Leads with follow-up date ≤ today (assigned to me) | Sales roles |
| Recent activity | Last 10 audit entries | Admin+ |
| Quick actions | New project, Write an article, Upload media, Edit homepage, Post a role, Settings | Per permission |
| SEO health | Top 404s (7 days), pages missing meta descriptions, images missing alt, snapshot misses | Editor+ |
| Unread enquiries | 5 newest `new` leads with "Open" | Lead viewers |

KPI zero-state shows "—" with a helper line (e.g. "No leads yet this month") instead of a bare "0" (screenshot improvement).

### 10.4 Management screens (`ADM-SCR`)

| ID | Screen | Key features |
|---|---|---|
| ADM-SCR-01 | Projects | Advanced table (thumbnail, name, segment, lifecycle, booking, area, publish status, featured, leads 30d, updated); editor with tabs: Basics, Facts, Content, Media, Unit types, Amenities, Progress, Nearby, Documents, Pricing, Lead routing, SEO, Revisions; publish checklist panel; preview button |
| ADM-SCR-02 | Featured order | Drag list of featured projects (like the Businesses screen) |
| ADM-SCR-03 | Amenities | Table + inline create; icon picker |
| ADM-SCR-04 | Locations | Tree (cities → areas) with drag order; area editor with indexability status badge (FR-LOC-003) |
| ADM-SCR-05 | Facet pages | One row per segment/status: intro, FAQ, SEO |
| ADM-SCR-06 | Pages | Table; page builder (CMS-PB-002) |
| ADM-SCR-07 | Menus | Tree editor (CMS-NAV-005) |
| ADM-SCR-08 | News | Posts table; editor (Tiptap) with SEO sidebar; categories; tags (merge, rename) |
| ADM-SCR-09 | Testimonials / Partners / FAQs / Team / Offices | Tables with drag order and publish toggles |
| ADM-SCR-10 | Careers | Jobs table + editor; Applications table + detail drawer with CV download |
| ADM-SCR-11 | Leads | Table view, Board view, detail drawer (FR-LEAD-009), saved views ("My open leads", "Overdue follow-ups", "NRB this month") |
| ADM-SCR-12 | Subscribers | Table, export, unsubscribe, delete (GDPR erase) |
| ADM-SCR-13 | Media | Library (CMS-MED-*) with folders panel, grid/list toggle, detail drawer |
| ADM-SCR-14 | SEO | Tabs: Redirects, 404 monitor, Sitemaps (last generated, URL counts, regenerate), Robots.txt editor with live preview, Snapshots (coverage, misses, render now) |
| ADM-SCR-15 | Settings | Tabs per §8.2 group |
| ADM-SCR-16 | Users & roles | Users table (invite, deactivate, reset 2FA), roles with permission checkboxes |
| ADM-SCR-17 | Activity log | Advanced table: when, who, action, subject, changes (expandable diff), IP; filters; export |
| ADM-SCR-18 | Maintenance | Caches (CMS-CACHE-005), application info (environment, debug, Laravel, PHP, DB, migrations, queue backlog, last scheduler run, disk usage), maintenance mode toggle with message and bypass secret |
| ADM-SCR-19 | Errors | Server and client errors (SEO-ANA-006) |
| ADM-SCR-20 | Profile | Name, avatar, password, 2FA, sessions, theme (light/dark/system), table density preference |
| ADM-SCR-21 | Notifications | Inbox list, mark read/all read, filters by type |
| ADM-SCR-22 | Command palette | `/` or `Ctrl/⌘+K` opens search across projects, leads (by name/phone), posts, pages, settings sections and actions |

### 10.5 Advanced data tables (`ADM-TBL`)

Engine: `@tanstack/vue-table` 8.21.3 in manual (server-side) mode, rendered with shadcn-vue `Table` primitives (CMP-ADM-TABLE). Server: `spatie/laravel-query-builder` allowlisted filters/sorts; responses via API Resources; Inertia partial reloads (`only: ['rows','meta']`).

| ID | Feature | Requirement |
|---|---|---|
| ADM-TBL-001 | Server-side pagination | `page`, `per_page` ∈ {10, 25, 50, 100}; total count; first/prev/next/last + page input |
| ADM-TBL-002 | Sorting | Click header cycles asc → desc → none; Shift+click multi-sort (max 3); allowlisted columns only |
| ADM-TBL-003 | Filtering | Per-column filters by type: text (contains), enum (multi-select with counts), date range (Calendar, `DD MMM YYYY`), number range, boolean, relation (async combobox) |
| ADM-TBL-004 | Global search | Debounced 300 ms across allowlisted columns; highlights matches |
| ADM-TBL-005 | Column visibility | Column menu with checkboxes; required columns locked |
| ADM-TBL-006 | Column reordering | Drag header handles; keyboard: column menu "Move left/right" |
| ADM-TBL-007 | Column resizing | Drag handle on header edge (min 80 px, max 600 px); double-click auto-fits; keyboard: column menu "Width" stepper |
| ADM-TBL-008 | Row selection | Checkbox column; select page; "Select all {n} matching" banner (server-side selection by filter) |
| ADM-TBL-009 | Bulk actions | Sticky bulk bar when ≥ 1 selected: per table (publish, unpublish, archive, delete, assign, change status, move folder, export selected, add tag); destructive actions confirm with count; run as queued batch when > 200 rows with progress toast |
| ADM-TBL-010 | Saved views | Save current state (filters, sort, columns, order, widths, per_page, density) as a named view; personal or shared (Admin+); set default; URL `?view={id}`; stored in DM-VIEW |
| ADM-TBL-011 | URL state | Full table state mirrored in the query string so links are shareable |
| ADM-TBL-012 | Export | CSV or XLSX of the current filtered/sorted view (visible columns, in order) via `spatie/simple-excel` streaming; > 5,000 rows → queued, notification with signed download link (24 h) |
| ADM-TBL-013 | Density | Comfortable (row 56 px) / Compact (40 px); remembered per user |
| ADM-TBL-014 | Empty / loading / error | Skeleton rows (5) during first load; inline spinner on refetch keeps rows (no layout jump); empty state with message and action; error row with retry |
| ADM-TBL-015 | Accessibility | Semantic `<table>`, `aria-sort` on headers, row checkbox labels "Select {row name}", focusable cells via roving tabindex optional (not required), keyboard operable menus |
| ADM-TBL-016 | Performance | Server response ≤ 500 ms p95 for 10,000 rows with indexes; virtualisation not needed at ≤ 100 rows/page |

### 10.6 Import / export

| ID | Requirement |
|---|---|
| ADM-IO-001 | Import (CSV/XLSX, ≤ 10 MB, ≤ 20,000 rows) for: leads (e.g. Facebook Lead Ads export), subscribers, redirects, locations, FAQs. Steps: upload → column mapping (auto-match by header) → preview first 20 rows with validation → confirm → queued import → report (created/updated/skipped/failed) + downloadable error CSV. |
| ADM-IO-002 | Export available on every table (ADM-TBL-012); exports are logged in the activity log with row count and filters. |

### 10.7 Activity / audit log

| ID | Requirement |
|---|---|
| ADM-LOG-001 | `spatie/laravel-activitylog` logs create/update/delete/restore/publish of every admin-managed model with changed attributes (old → new, rich text truncated to 500 chars), causer, IP, user agent. |
| ADM-LOG-002 | Auth events: login success (once per session — fixes the duplicate rows seen in the reference screenshot), login failure, lockout, logout, 2FA enabled/disabled, password changed. |
| ADM-LOG-003 | Settings changes log key-level diffs; secrets (Turnstile secret, webhook secret, Cloudflare token) logged as "changed" without values. |
| ADM-LOG-004 | Retention 365 days (pruned nightly). Read-only; no one can delete entries in the UI. |

### 10.8 Notifications (admin)

Database notifications (Laravel), listed in ADM-SCR-21, badge on the top-bar inbox, toast on arrival while the admin is open (poll 60 s). Types: new lead, lead assigned to you, follow-up due today (daily 09:00), new job application, scheduled publish succeeded/failed, import finished, export ready, snapshot coverage < 95%, failed jobs > 0. Email copies only when `notifications.email_enabled` (FR-LEAD-008).

## Data model (REQUIREMENT §11.2)

**DM-USER `users`** (SD)

| Column | Type | Null | Notes |
|---|---|---|---|
| name | varchar(120) | no | |
| email | varchar(190) | no | unique |
| password | varchar(255) | no | bcrypt cost 12 |
| phone | varchar(20) | yes | E.164 |
| avatar_asset_id | FK media_assets | yes | |
| is_active | bool | no | default true |
| two_factor_secret, two_factor_recovery_codes | text | yes | encrypted (Fortify) |
| two_factor_confirmed_at | timestamp | yes | |
| theme | varchar(10) | no | `system` |
| table_density | varchar(12) | no | `comfortable` |
| last_login_at | timestamp | yes | |
| remember_token | varchar(100) | yes | |

Roles/permissions: spatie tables `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

**DM-VIEW `saved_table_views`**: user_id FK, table_key varchar(40), name varchar(60), state json, is_shared bool, is_default bool. Index (table_key, user_id).
