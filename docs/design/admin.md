# Admin components and admin dashboard design (DESIGN §6.6, §12)

> Verbatim from DESIGN.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 6.6 Admin components (`CMP-ADM-*`)

Layout derived from **[Dash]**; see §12 for screen composition.

| ID | Component | Spec | Built on |
|---|---|---|---|
| CMP-ADM-SHELL | App shell | Sidebar (280 px expanded / 72 px collapsed) + main column (top bar 64 px, content). Collapsed state persisted in a cookie (so first paint is correct). < 1024: sidebar becomes an off-canvas `Sheet` | `SidebarProvider`, `Sidebar` |
| CMP-ADM-SIDEBAR | Sidebar | Dark (`--sidebar`); header: logo tile (40 px coral-400 rounded-md with hexagon mark) + "Gemcon City" + overline "Dashboard" (coral-400) **[Dash]** brand block; groups with uppercase labels (`--sidebar-muted`, text-overline): Overview, Content, Sales, Library, SEO, System; items 44 px, icon 20 + label + count badge (stone-200 on `#14363C`, pill) **[Dash]**; active: bg `--sidebar-accent`, 3 px left bar `--sidebar-primary`, text white 600; hover: bg `#092C31`; collapsed: icons with tooltips, badges as dots; footer: "Collapse" button + user mini profile. Keyboard: arrows within list, focus ring coral-400 | `Sidebar`, `SidebarMenu`, `Tooltip`, `Badge` |
| CMP-ADM-TOPBAR | Top bar | Left: breadcrumbs (text-sm, last item foreground 600) **[Dash]**; right: command search trigger (input-look button 320 px with "Search the dashboard" and `/` kbd hint) **[Dash]**, "View site" outline button (opens in new tab, external-link icon), notifications inbox (bell, unread count badge coral), theme toggle, avatar menu (profile, 2FA, sign out). Sticky, bg background/90% + blur, border-bottom | `Breadcrumb`, `Kbd`, `DropdownMenu`, `Avatar` |
| CMP-ADM-PAGEHEAD | Page header | Title (28/36 Manrope 700) + one-line description (text-sm muted) **[Dash]** + actions right (secondary actions as ghost-with-icon e.g. "Sectors", "Users"; primary as filled button e.g. "+ New project") **[Dash]**; optional tabs below; border-bottom; padding 32 px | — |
| CMP-ADM-KPI | KPI card | Icon tile (40 px, muted bg, 20 px icon) top-left, corner arrow link (↗) top-right **[Dash]**, value (36 px Manrope 800 `tnum`), label (overline), delta chip (▲ success / ▼ destructive / — muted) + comparison text; zero-state "—" with helper text (fixes **[Dash]** raw zeros); entire card is a link to the filtered list; skeleton while loading | `Card` |
| CMP-ADM-QUICK | Quick action tile | Icon tile + label, 72 px tall, border, hover bg accent **[Dash]** | `Button` (outline, large) |
| CMP-ADM-TABLE | Data table | Container card (radius lg, border). **Toolbar** (h-16, p-4): global search (w-80), filter buttons (each opens a popover per ADM-TBL-003, shows count when active), "Reset", right: saved views dropdown, column menu (visibility checkboxes, drag order list, width steppers), density toggle, export menu (CSV/XLSX), primary action if any. **Active filter chips** row. **Header**: sticky (top = top bar), text-xs 600 uppercase tracking .06em muted **[Dash]**, sort indicator icons (↕ / ↑ / ↓) with `aria-sort`, resize handle (4 px hit area 12 px) on hover, drag handle on hover for reorder. **Rows**: 56 / 40 px density, hover bg muted/50, selected bg teal-50 (dark: `#14363C`), checkbox column 48 px, primary cell: avatar/thumbnail + title (600) + secondary line muted **[Dash]** (who + role pattern), row actions: inline icon buttons with tooltips for top 2 actions + kebab menu for rest. **Bulk bar**: slides up from bottom of table (sticky bottom 24 px) when selection > 0: "{n} selected" · "Select all {total}" link · action buttons · close (MOT-SLIDE-UP). **Footer**: "Showing 1–25 of 312" · rows per page select · pagination (first/prev/page input/next/last) **[Dash]** | `Table`, `Popover`, `Command`, `DropdownMenu`, `Checkbox`, `Select`, `Pagination`, TanStack v8 |
| CMP-ADM-SORTLIST | Reorderable list | Row cards with drag handle (⋮⋮), thumbnail/icon tile, title + chips (featured, slug in mono chip) + description + meta line, right controls: star (feature), visibility switch, Edit (primary sm), duplicate, delete — every icon button labelled with tooltip **[Dash]** Businesses screen; keyboard reorder via handle (Space to lift, arrows to move, Space to drop, live announcements) | `vue-draggable-plus`, `Switch`, `Tooltip` |
| CMP-ADM-FORM | Form layout | Two-column editor: main (max 880 px) with sections (`Card` with title + description left 1/3, fields right 2/3 on ≥ 1280; stacked below) and a right rail (320 px, sticky): status + publish actions (Save draft, Preview, Publish/Publish changes, Schedule), publish checklist (CMP-ADM-CHECKLIST), SEO panel (CMP-ADM-SEO), revision info. Sticky bottom action bar on < 1280. Unsaved-changes guard on navigation (Inertia `router.on('before')` confirm dialog) | `Card`, `Field`, `Tabs` |
| CMP-ADM-CHECKLIST | Publish checklist | List of rules with ✓ (success) / ✕ (destructive) and "Fix" links that scroll/focus the field; Publish disabled while any ✕ | — |
| CMP-ADM-SEO | SEO panel | Meta title/description inputs with character counters (colour turns warning outside ranges SEO-ONP-001/002), Google SERP preview (desktop/mobile), OG preview card (1200×630 crop), robots select, canonical override | `Input`, `Textarea`, `Select` |
| CMP-ADM-MEDIA | Media library | **[Dash]** layout: left folders card (All files, Unfiled, tree, "+ New folder") + upload dropzone card beneath ("Choose files or drop them here · Up to 12 MB each"); main card: toolbar (search, type, date, sort, "Missing alt" toggle) + grid (tiles 180 px: thumb 4:3, filename truncated, dimensions · size) or list view toggle (top-right segmented control **[Dash]**); selecting a tile opens the detail drawer (preview, alt/caption/credit/title, focal point picker, tags, folder, usages list, replace, delete); footer "Showing 1–24 of 312", per-page, pagination. Upload progress list docks bottom-right | `Card`, `ToggleGroup`, `Sheet`, `Progress` |
| CMP-ADM-PICKER | Media picker | Dialog (max-w 1080) with the library in selection mode (single/multiple, type filter preset), "Upload new" tab, footer with selected count + Insert | `Dialog` |
| CMP-ADM-FOCAL | Focal point picker | Image with crosshair draggable/clickable; arrow keys move 1% (Shift 10%); live preview thumbnails at 16:9, 4:3, 1:1, 4:5 | — |
| CMP-ADM-BUILDER | Page builder | Three panes: block list (left 320 px: draggable rows with type icon, label/first heading, visibility eye toggle, kebab: duplicate/delete/move), block form drawer (right 480 px `Sheet`, non-modal on ≥ 1440 so preview stays visible), live preview (centre iframe with device toggles 1280/834/390 and zoom-to-fit). "+ Add block" opens a categorised gallery dialog with thumbnails of all 28 types. Invalid blocks outlined destructive with error count. Undo toast on delete (8 s) | `Sheet`, `Dialog`, `Tabs`, `vue-draggable-plus` |
| CMP-ADM-RTE | Rich text editor | Tiptap with toolbar (H2/H3/H4 select, bold, italic, link, lists, quote, image from library, YouTube, table, hr, undo/redo), bubble menu for links, heading-order warnings inline, word count + read time footer; prose styles identical to public (§3) | `Toggle`, `Popover` |
| CMP-ADM-KANBAN | Lead board | Columns per status (min 280 px, horizontal scroll), header with count; cards: name, project, type badge, phone (masked for Viewer), age ("2 h"), assignee avatar, follow-up chip (overdue = destructive). Drag between columns; won/lost open reason dialog. Keyboard alternative: card "Move to…" menu | `Card`, `DropdownMenu`, `vue-draggable-plus` |
| CMP-ADM-LEAD | Lead detail drawer | `Sheet` right 560 px: header (name, status select, assignee select, call/WhatsApp/email buttons), tabs: Details (answers, UTM, source), Timeline (activities with icons), Notes (RTE light), Follow-up (date picker) | `Sheet`, `Tabs`, `Select` |
| CMP-ADM-CMDK | Command palette | `/` or Ctrl/⌘+K; grouped results (Projects, Leads, Posts, Pages, Settings, Actions); recent items; arrow navigation; Enter opens | `Command`, `Dialog` |
| CMP-ADM-NOTIF | Notifications popover | 380 px popover from bell: tabs All/Unread, items with icon, title, time, unread dot; "Mark all read", "View all" | `Popover`, `ScrollArea` |
| CMP-ADM-TREE | Tree editor | Menus and locations: nested draggable rows with indent guides, collapse toggles, inline add child, keyboard indent/outdent (Tab/Shift+Tab when a row's handle is focused) | `vue-draggable-plus` |
| CMP-ADM-REV | Revision diff | Side-by-side or inline field diff; rich text diff with ins (success-50) / del (error-50 + strikethrough) | — |
| CMP-ADM-CHART | Charts | Line and bar charts using `--chart-1…5`, 1 px grid stone-200, axis text-xs muted, tooltips; built with shadcn-vue `Chart` (Unovis) | `Chart` |
| CMP-ADM-CONFIRM | Confirm dialog | Title states the action and count ("Delete 3 projects?"), body explains consequence, destructive button labelled with the verb ("Delete projects"); type-to-confirm for irreversible bulk deletes > 20 rows | `AlertDialog` |
| CMP-ADM-STATUS | Status badges | CMP-BADGE-01 admin variants; activity actions as soft badge with dot **[Dash]** ("● Signed in") | `Badge` |
| CMP-ADM-INFO | Key/value panel | Maintenance "Application" and "Data" cards **[Dash]**: rows label (muted) / value (600) with status dot (success/warning/muted) | `Card` |

## 12. Admin dashboard design

### 12.1 Layout (derived from **[Dash]**)

```
┌────────────┬────────────────────────────────────────────────────────────────┐
│ ▣ Gemcon   │ Content › Projects            [⌕ Search the dashboard  /] [View site ↗] [🔔3] [◐] (GA▾) │
│   City     ├────────────────────────────────────────────────────────────────┤
│ DASHBOARD  │ Projects                                   [Amenities] [+ New project] │
│────────────│ Every development on the site, newest first.                   │
│ OVERVIEW   ├────────────────────────────────────────────────────────────────┤
│ ⌂ Dashboard│ ┌ table card ───────────────────────────────────────────────┐ │
│ CONTENT    │ │ [search…] [Segment ▾] [Status ▾] [Area ▾] [Reset]  [Views▾][Cols▾][⇩] │
│ ▦ Projects 24│ │ ☐ PROJECT          SEGMENT  STATUS   AREA   LEADS  UPDATED  ⋯ │ │
│ ⌖ Locations│ │ ☐ ▢ Gemcon Orchid  Resid.   Ongoing  Bash.  12     25 Oct   ⋯ │ │
│ ▤ Pages    │ │ …                                                        │ │
│ ✎ News  13 │ │ Showing 1–25 of 24      Rows [25▾]   ‹ 1 / 1 ›           │ │
│ ❝ Testimon.│ └──────────────────────────────────────────────────────────┘ │
│ ? FAQs     │                                                                │
│ ☺ Team     │                                                                │
│ ✉ Careers 3│                                                                │
│ SALES      │                                                                │
│ ✆ Leads  5 │                                                                │
│ ✈ Subscr.  │                                                                │
│ LIBRARY    │                                                                │
│ ▣ Media 120│                                                                │
│ SEO        │                                                                │
│ ↪ Redirects│                                                                │
│ SYSTEM     │                                                                │
│ ⚙ Settings │                                                                │
│ ☰ Activity │                                                                │
│ ⚒ Maint.   │                                                                │
│ ‹ Collapse │                                                                │
└────────────┴────────────────────────────────────────────────────────────────┘
```

| Element | Spec |
|---|---|
| Canvas | `--surface-subtle` (light: stone-50 `#FEF9F7`; dark: teal-950) with content padding 32 px (24 px < 1280, 16 px < 768); cards white (light) / `#092C31` (dark) with 1 px border and radius lg **[Dash]** |
| Sidebar | CMP-ADM-SIDEBAR; groups: **Overview** (Dashboard), **Content** (Projects, Locations, Pages, Menus, News, Testimonials, Partners, FAQs, Team, Offices, Careers), **Sales** (Leads, Subscribers, Applications), **Library** (Media), **SEO** (Redirects, 404s, Sitemaps & snapshots), **System** (Settings, Users & roles, Activity log, Errors, Maintenance). Counts: projects published, leads unread (coral badge), applications new, media total **[Dash]** |
| Top bar | CMP-ADM-TOPBAR |
| Page header | CMP-ADM-PAGEHEAD |
| Density | Comfortable default; compact toggle per user (ADM-TBL-013) |

### 12.2 Dashboard home widgets

Grid: KPI cards 4 × 2 (≥ 1280), 2 × 4 (768–1279), 1 col (< 768) **[Dash]**; "Quick actions" row of 6 tiles (CMP-ADM-QUICK) **[Dash]**; then 8/4 split: Leads trend chart (line) + Pipeline (bar) | Unread enquiries list + My follow-ups; then 6/6: Leads by project + Lead sources; then Recent activity (8 cols) + SEO health (4 cols). Each widget is a card with title (18 px Manrope 600), optional filter (7/30/90 days) and "View all" link. Content per REQUIREMENT ADM-DASH.

### 12.3 Data table design

CMP-ADM-TABLE. Defaults per table:

| Table | Default columns (order) | Default sort | Row primary cell |
|---|---|---|---|
| Projects | Project, Segment, Lifecycle, Booking, Area, Status, Featured, Leads (30d), Updated | Updated desc | Thumbnail 48×36 + name + slug mono chip |
| Leads | Received, Name, Phone, Type, Project, Status, Assignee, Follow-up, Source | Received desc | Name + type badge; phone with copy and click-to-call |
| Posts | Title, Category, Status, Published, Author, Updated | Published desc | Cover 48×30 + title |
| Media (list view) | File, Type, Dimensions, Size, Folder, Usages, Uploaded | Uploaded desc | Thumb 40 + filename |
| Activity log | When, Who, Action, What, IP **[Dash]** | When desc | Date + relative time ("25 Oct 2026 · 04:18 · 3m ago") **[Dash]**; Who = avatar initials + name + role **[Dash]** |
| Redirects | Source, Target, Code, Hits, Last hit, Active | Hits desc | Source mono |

Dates display `DD MMM YYYY` (not `mm/dd/yyyy`, fixing **[Dash]**); filter date ranges use the Calendar popover.

### 12.4 Form layouts

CMP-ADM-FORM. Project editor tabs in order: Basics · Facts · Content · Media · Unit types · Amenities · Progress · Nearby · Documents · Pricing · Lead routing · SEO · Revisions. Repeaters (unit types, progress, nearby, highlights) render as collapsible cards with drag handles and "Add {item}" buttons; each card header summarises its content ("Type A · 1,850 sft · 3 bed"). Field widths: full for text areas; half for paired numbers; quarter for small numerics (lifts, basements).

### 12.5 Page builder UI

CMP-ADM-BUILDER. Block rows show: drag handle, type icon (from the gallery), title (first heading or type name), visibility eye, warning icon if invalid, kebab. The preview iframe highlights the hovered block (2 px teal outline) and clicking a block in preview selects it in the list. Keyboard: Alt+↑/↓ moves the selected block; Ctrl/⌘+D duplicates; Delete asks for confirmation with undo.

### 12.6 Media library UI

CMP-ADM-MEDIA (layout kept from **[Dash]** Media screen) + picker (CMP-ADM-PICKER) + focal point (CMP-ADM-FOCAL). "Missing alt" tiles show a warning-500 corner triangle (DP-03) with tooltip.

### 12.7 Light / dark mode and branding

Admin follows user preference (light, dark, system), applied via `class="dark"` on `<html>` before first paint (inline script reading the cookie). Sidebar is dark in both modes **[Dash]**; content surfaces switch. Brand consistency with the public site: same tokens, fonts, logo mark, coral used only for unread counts, active indicator and the logo tile — never for primary admin buttons (primary = teal).
