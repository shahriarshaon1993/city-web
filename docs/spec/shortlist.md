# Shortlist & compare, languages (REQUIREMENT §7.8–7.9)

> Verbatim from REQUIREMENT.md v1.0.0 (25 Oct 2026). IDs and wording unchanged. `§` references to REQUIREMENT/DESIGN resolve through the section map in `CLAUDE.md`.

### 7.8 Shortlist and compare (`FR-SHORT`)

Confirmed in Phase 1 as browser-only (no accounts).

| ID | Requirement |
|---|---|
| FR-SHORT-001 | Heart button on project cards and PG-04 toggles the project ID in `localStorage['gc.shortlist']` (max 20). Count badge in the header. |
| FR-SHORT-002 | PG-20 fetches cards for stored IDs via `GET /shortlist?ids=` (Inertia visit); unknown or unpublished IDs are dropped from storage. |
| FR-SHORT-003 | Compare: checkbox "Compare" on cards; max 3; a floating compare bar appears with selected names and "Compare" button → `/compare?ids=a,b,c`. Rows: segment, status, location, land area, storeys, units, size range, bedrooms, bathrooms, handover, parking, lifts, amenities (✓/–), price-from (only if shown). |
| FR-SHORT-004 | "Enquire about all" on PG-20 opens the project enquiry form with a multi-select prefilled; one lead per project is created (FR-LEAD-004 applies). |
| FR-SHORT-005 | When `localStorage` is unavailable (private mode errors), hearts are hidden and no error is thrown. |

- **AC-SHORT-01** Given 3 projects in compare, When the user ticks a 4th, Then the checkbox is refused with a toast "You can compare up to 3 projects".

### 7.9 Multilingual and multi-currency

Not in scope (Phase 1 Q8: English only; currency BDT only). Data model keeps all user-facing strings in plain columns; F2 migration path recorded in DEC-15.
