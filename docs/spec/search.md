# Search & filters (REQUIREMENT §7.2)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 7.2 Search and filters (`FR-SRCH`)

| ID | Requirement |
|---|---|
| FR-SRCH-001 | Filters on PG-02/03/06: keyword (`q`), segment, status, location (area, multi), size band or min/max sft, bedrooms (min), price range (min/max BDT), booking state, featured only. Sort: `featured` (default), `newest`, `handover_asc`, `size_asc`, `size_desc`, `price_asc`, `price_desc`. |
| FR-SRCH-002 | All filter state lives in the URL query string: `?q=&segment=&status=&area[]=&size_min=&size_max=&beds=&price_min=&price_max=&sort=&page=`. Empty values are omitted. Parameter order is normalised (alphabetical) so equal filters produce one URL. |
| FR-SRCH-003 | Changing a filter triggers an Inertia partial reload (`only: ['projects','facets','meta']`) with `preserveScroll: true`, `preserveState: true`, `replace: true` for typing inputs (debounced 350 ms) and `replace: false` for discrete selections so Back undoes them. |
| FR-SRCH-004 | Facet counts are returned with results and shown next to each option; options with 0 results are disabled (not hidden). |
| FR-SRCH-005 | Price filter is shown only when at least `settings.search.price_filter_min_projects` (default 5) published projects have `show_price=true`. Price sorts are hidden under the same condition. Projects without visible price sort last in price sorts. |
| FR-SRCH-006 | Grid/map toggle: map view shows the same result set as markers (FR-LOC-006 map spec). State in URL `view=map`. |
| FR-SRCH-007 | Pagination: 12 per page, numbered links (`?page=n`) rendered as real `<a href>`; no infinite scroll on public listings. |
| FR-SRCH-008 | Clean-URL redirection: a request whose only active filter is one segment, one status or one area is 301-redirected to its landing page (`/projects/type/residential`, `/projects/status/ongoing`, `/locations/{area}`). |
| FR-SRCH-009 | SEO handling of filter URLs is defined in SEO-TEC-020…024. |
| FR-SRCH-010 | Site search (PG-19) uses Laravel Scout with the `database` engine over projects (name, tagline, location names, overview), posts (title, excerpt, body) and pages (title, meta description); MySQL FULLTEXT indexes on those columns. Minimum query length 2; results grouped by type, max 10 per group. |
| FR-SRCH-011 | Keyword search on PG-02 matches project name, tagline and location names (FULLTEXT, natural language mode), falling back to `LIKE %q%` on name for queries shorter than 4 chars. |

**Acceptance criteria**

- **AC-SRCH-01** Given `/projects?beds=3&segment=residential`, When the page loads directly (no JS), Then the server returns the filtered list in HTML (snapshot or CSR) with filters pre-selected, and `<meta name="robots" content="noindex,follow">` plus canonical `/projects/type/residential`.
- **AC-SRCH-02** Given the user selects Area = Uttara and Bedrooms = 4, When results update, Then the URL equals `/projects?area[]=uttara&beds=4`, the Back button restores the previous result set, and focus stays on the changed control.
- **AC-SRCH-03** Given `/projects?segment=commercial`, When requested, Then the server responds 301 to `/projects/type/commercial`.
- **AC-SRCH-04** Given a filter combination with no results, When rendered, Then an empty state shows "No projects match these filters", a "Clear filters" link and the 3 newest projects.

**Validation:** unknown parameters are dropped; numeric params cast and clamped (`size_min` 0–20,000; `beds` 1–6; `page` 1–max); invalid enum values ignored; `q` trimmed to 80 chars, stripped of tags.

**Edge cases:** page beyond last → 404 (not redirect); `area[]` with > 5 values truncated to 5; conflicting `size_min > size_max` → swapped.

**Permissions:** public.
