import type { TableDensity, ViewState } from '@/types';

// Pure helpers for the URL state of an admin DataTable (ADM-TBL-011). The URL is the single
// source: the page, search, filters, sort, per_page, column ids/order (`cols`), widths and
// density all live in the query string, so a copied link or the Back button restores the view.

export const PER_PAGE_OPTIONS = [10, 25, 50, 100] as const;
export const DEFAULT_PER_PAGE = 25;
/** ADM-TBL-002 */
export const MAX_SORTS = 3;
/** ADM-TBL-007 */
export const MIN_WIDTH = 80;
export const MAX_WIDTH = 600;

export type TableUrlState = {
    search: string;
    filters: Record<string, string>;
    /** Explicit sort from the URL; empty = the server default. */
    sort: string[];
    perPage: number;
    page: number;
    /** Visible column ids in order; null = the table's default. */
    cols: string[] | null;
    widths: Record<string, number>;
    density: TableDensity | null;
    view: number | null;
};

export function parseTableState(url: string): TableUrlState {
    const params = new URL(url, 'http://localhost').searchParams;
    const filters: Record<string, string> = {};

    for (const [key, value] of params.entries()) {
        const match = /^filter\[([a-z0-9_]+)\]$/.exec(key);

        if (match && value !== '') {
            filters[match[1]] = value;
        }
    }

    const perPage = Number(params.get('per_page'));
    const view = Number(params.get('view'));
    const density = params.get('density');
    const cols = params.get('cols');

    return {
        search: params.get('search') ?? '',
        filters,
        sort: (params.get('sort') ?? '').split(',').filter(Boolean),
        perPage: (PER_PAGE_OPTIONS as readonly number[]).includes(perPage)
            ? perPage
            : DEFAULT_PER_PAGE,
        page: Math.max(1, Number(params.get('page')) || 1),
        cols: cols ? cols.split(',').filter(Boolean) : null,
        widths: parseWidths(params.get('widths')),
        density:
            density === 'comfortable' || density === 'compact' ? density : null,
        view: Number.isInteger(view) && view > 0 ? view : null,
    };
}

export function parseWidths(value: string | null): Record<string, number> {
    const widths: Record<string, number> = {};

    for (const pair of (value ?? '').split(',')) {
        const [id, size] = pair.split(':');
        const width = Number(size);

        if (id && Number.isFinite(width)) {
            widths[id] = clampWidth(width);
        }
    }

    return widths;
}

export function clampWidth(width: number): number {
    return Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, Math.round(width)));
}

/** Query string for a state; defaults are left out so URLs stay short. */
export function serializeTableState(
    state: TableUrlState,
    defaultCols: string[],
): URLSearchParams {
    const params = new URLSearchParams();

    if (state.search !== '') {
        params.set('search', state.search);
    }

    for (const [id, value] of Object.entries(state.filters)) {
        if (value !== '') {
            params.set(`filter[${id}]`, value);
        }
    }

    if (state.sort.length > 0) {
        params.set('sort', state.sort.join(','));
    }

    if (state.perPage !== DEFAULT_PER_PAGE) {
        params.set('per_page', String(state.perPage));
    }

    if (state.page > 1) {
        params.set('page', String(state.page));
    }

    if (state.cols !== null && state.cols.join(',') !== defaultCols.join(',')) {
        params.set('cols', state.cols.join(','));
    }

    const widths = Object.entries(state.widths).map(
        ([id, width]) => `${id}:${width}`,
    );

    if (widths.length > 0) {
        params.set('widths', widths.join(','));
    }

    if (state.density !== null) {
        params.set('density', state.density);
    }

    if (state.view !== null) {
        params.set('view', String(state.view));
    }

    return params;
}

/** ADM-TBL-002: click cycles ascending → descending → none; Shift+click adds to the sort (max 3). */
export function cycleSort(
    sorts: string[],
    column: string,
    additive: boolean,
): string[] {
    const current = sorts.find((sort) => sort.replace(/^-/, '') === column);
    const next =
        current === undefined
            ? column
            : current.startsWith('-')
              ? null
              : `-${column}`;

    if (!additive) {
        return next === null ? [] : [next];
    }

    const others = sorts.filter((sort) => sort.replace(/^-/, '') !== column);

    if (next === null) {
        return others;
    }

    if (current === undefined && others.length >= MAX_SORTS) {
        return others;
    }

    // Keep the column's place in the list when only its direction changes.
    return current === undefined
        ? [...others, next]
        : sorts.map((sort) => (sort === current ? next : sort));
}

/** The part of a state a saved view keeps (DM-VIEW `state`; `page` and `view` are never saved). */
export function viewPayload(
    state: TableUrlState,
    defaultCols: string[],
): ViewState {
    const params = serializeTableState(
        { ...state, page: 1, view: null },
        defaultCols,
    );
    const payload: ViewState = {};
    const filter: Record<string, string> = {};

    for (const [key, value] of params.entries()) {
        const match = /^filter\[([a-z0-9_]+)\]$/.exec(key);

        if (match) {
            filter[match[1]] = value;
        } else {
            payload[key] = value;
        }
    }

    if (Object.keys(filter).length > 0) {
        payload.filter = filter;
    }

    return payload;
}

/** Query string that opens a saved view (the server also expands `?view=` alone). */
export function viewQuery(view: { id: number; state: ViewState }): string {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(view.state)) {
        if (typeof value === 'object') {
            for (const [name, item] of Object.entries(value)) {
                params.set(`filter[${name}]`, item);
            }
        } else {
            params.set(key, value);
        }
    }

    params.set('view', String(view.id));

    return params.toString();
}
