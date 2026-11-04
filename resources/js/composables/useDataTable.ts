import { router, usePage } from '@inertiajs/vue3';
import { getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import type {
    ColumnDef,
    RowSelectionState,
    Updater,
} from '@tanstack/vue-table';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, toRef, watch } from 'vue';
import {
    clampWidth,
    cycleSort,
    parseTableState,
    serializeTableState,
    viewPayload,
    viewQuery,
} from '@/lib/table-state';
import type { TableUrlState } from '@/lib/table-state';
import { preferences } from '@/routes/admin/profile';
import type {
    DataTableColumn,
    TableDensity,
    TableMeta,
    TableSelection,
    TableViewItem,
    ViewState,
} from '@/types';

type RowWithId = { id: string | number };

/** Props a refetch asks for (AdminTable::props): rows + paging, and the saved views. */
const REFETCH = ['rows', 'meta', 'views'];

/**
 * The state machine of an admin DataTable (ADM-TBL). The URL query string is the state
 * (parseTableState / serializeTableState): every change builds a URL and visits it, so links,
 * Back and new tabs restore the view. Server-affecting changes refetch `rows` + `meta`;
 * client-only ones (columns, widths, density) only replace the URL. TanStack Table runs in
 * manual mode: it holds the column and selection state and builds the header/row models.
 */
export function useDataTable<Row extends RowWithId>(props: {
    tableKey: string;
    columns: DataTableColumn[];
    rows: Row[];
    meta: TableMeta;
    views?: TableViewItem[];
}) {
    const page = usePage();

    const path = computed(() => new URL(page.url, 'http://localhost').pathname);
    const urlState = computed(() => parseTableState(page.url));

    const defaultCols = computed(() =>
        props.columns
            .filter((column) => !column.hidden)
            .map((column) => column.id),
    );

    /** Visible column ids in display order (required columns can never be missing). */
    const visibleCols = computed(() => {
        const known = new Set(props.columns.map((column) => column.id));
        const cols = (urlState.value.cols ?? defaultCols.value).filter((id) =>
            known.has(id),
        );

        for (const column of props.columns) {
            if (column.required && !cols.includes(column.id)) {
                cols.unshift(column.id);
            }
        }

        return cols;
    });

    const density = computed<TableDensity>(
        () => urlState.value.density ?? page.props.auth.user.table_density,
    );

    // --- request state ------------------------------------------------------------------------
    const loading = ref(false);
    const failed = ref(false);

    function visit(
        next: TableUrlState,
        options: { replace?: boolean; clientOnly?: boolean } = {},
    ): void {
        const query = serializeTableState(next, defaultCols.value).toString();
        const url = path.value + (query ? `?${query}` : '');

        if (options.clientOnly) {
            router.replace({ url, preserveScroll: true, preserveState: true });

            return;
        }

        failed.value = false;

        router.get(
            url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: options.replace ?? false,
                only: REFETCH,
                onStart: () => {
                    loading.value = true;
                },
                onFinish: () => {
                    loading.value = false;
                },
                onHttpException: () => {
                    failed.value = true;

                    return false;
                },
                onNetworkError: () => {
                    failed.value = true;

                    return false;
                },
            },
        );
    }

    /** A filter, sort, search or paging change: back to page 1, and the view no longer matches. */
    function change(patch: Partial<TableUrlState>, replace = false): void {
        visit(
            { ...urlState.value, page: 1, view: null, ...patch },
            { replace },
        );
    }

    function retry(): void {
        visit(urlState.value);
    }

    // --- search (ADM-TBL-004: debounced 300 ms) ---------------------------------------------
    const searchInput = ref(urlState.value.search);
    const applySearch = useDebounceFn((value: string) => {
        if (value.trim() !== urlState.value.search) {
            change({ search: value.trim() }, true);
        }
    }, 300);

    watch(searchInput, (value) => applySearch(value));
    watch(
        () => urlState.value.search,
        (value) => {
            if (value !== searchInput.value.trim()) {
                searchInput.value = value;
            }
        },
    );

    // --- filters, sort, paging ---------------------------------------------------------------
    function setFilters(patch: Record<string, string | null>): void {
        const filters = { ...urlState.value.filters };

        for (const [id, value] of Object.entries(patch)) {
            if (value === null || value === '') {
                delete filters[id];
            } else {
                filters[id] = value;
            }
        }

        change({ filters });
    }

    function removeFilterKeys(keys: string[]): void {
        setFilters(Object.fromEntries(keys.map((key) => [key, null])));
    }

    function reset(): void {
        searchInput.value = '';
        change({ search: '', filters: {}, sort: [] });
    }

    const hasActiveState = computed(
        () =>
            urlState.value.search !== '' ||
            Object.keys(urlState.value.filters).length > 0 ||
            urlState.value.sort.length > 0,
    );

    function toggleSort(columnId: string, additive: boolean): void {
        change({ sort: cycleSort(urlState.value.sort, columnId, additive) });
    }

    /** Direction and place in the sort the server applied (meta.sort), for aria-sort and the badge. */
    function sortOf(columnId: string): {
        direction: 'asc' | 'desc' | null;
        position: number | null;
    } {
        const index = props.meta.sort.findIndex(
            (sort) => sort.replace(/^-/, '') === columnId,
        );

        if (index === -1) {
            return { direction: null, position: null };
        }

        return {
            direction: props.meta.sort[index].startsWith('-') ? 'desc' : 'asc',
            position: props.meta.sort.length > 1 ? index + 1 : null,
        };
    }

    function setPage(value: number): void {
        visit({
            ...urlState.value,
            page: Math.min(Math.max(1, value), props.meta.last_page),
        });
    }

    function setPerPage(value: number): void {
        change({ perPage: value });
    }

    // --- columns (client state mirrored in the URL) -------------------------------------------
    function setCols(cols: string[]): void {
        visit({ ...urlState.value, cols, view: null }, { clientOnly: true });
    }

    function toggleColumn(id: string): void {
        const column = props.columns.find((item) => item.id === id);

        if (!column || column.required) {
            return;
        }

        setCols(
            visibleCols.value.includes(id)
                ? visibleCols.value.filter((item) => item !== id)
                : // Re-inserted where the table defines it, among the visible ones.
                  props.columns
                      .map((item) => item.id)
                      .filter(
                          (item) =>
                              item === id || visibleCols.value.includes(item),
                      ),
        );
    }

    /** ADM-TBL-006: keyboard-friendly "Move left/right" within the visible columns. */
    function moveColumn(id: string, delta: -1 | 1): void {
        const cols = [...visibleCols.value];
        const from = cols.indexOf(id);
        const to = from + delta;

        if (from === -1 || to < 0 || to >= cols.length) {
            return;
        }

        cols.splice(to, 0, ...cols.splice(from, 1));
        setCols(cols);
    }

    /** Drag and drop on the header: put `id` where `targetId` is. */
    function moveColumnTo(id: string, targetId: string): void {
        const cols = [...visibleCols.value];
        const from = cols.indexOf(id);
        const to = cols.indexOf(targetId);

        if (from === -1 || to === -1 || from === to) {
            return;
        }

        cols.splice(to, 0, ...cols.splice(from, 1));
        setCols(cols);
    }

    function resetColumns(): void {
        visit(
            { ...urlState.value, cols: null, widths: {}, view: null },
            { clientOnly: true },
        );
    }

    const liveWidths = ref<Record<string, number>>({});

    function columnWidth(id: string): number {
        return (
            liveWidths.value[id] ??
            urlState.value.widths[id] ??
            props.columns.find((column) => column.id === id)?.width ??
            160
        );
    }

    /** While dragging: size without touching the URL. */
    function previewWidth(id: string, width: number): void {
        liveWidths.value = { ...liveWidths.value, [id]: clampWidth(width) };
    }

    /** ADM-TBL-007: on release, keyboard step or auto-fit. */
    function commitWidth(id: string, width: number): void {
        const { [id]: _preview, ...rest } = liveWidths.value;

        liveWidths.value = rest;

        visit(
            {
                ...urlState.value,
                widths: { ...urlState.value.widths, [id]: clampWidth(width) },
                view: null,
            },
            { clientOnly: true },
        );
    }

    // --- density (ADM-TBL-013: remembered per user) ---------------------------------------------
    function setDensity(value: TableDensity): void {
        router.patch(
            preferences.url(),
            { table_density: value },
            { preserveScroll: true, preserveState: true, only: ['auth'] },
        );

        if (urlState.value.density !== null) {
            visit(
                { ...urlState.value, density: null, view: null },
                { clientOnly: true },
            );
        }
    }

    // --- TanStack Table (manual mode) ---------------------------------------------------------
    const rowSelection = ref<RowSelectionState>({});
    const allMatching = ref(false);

    const columnDefs = computed<ColumnDef<Row>[]>(() =>
        props.columns.map((column) => ({
            id: column.id,
            header: column.label,
            accessorFn: (row: Row) =>
                (row as Record<string, unknown>)[column.id],
            size: column.width ?? 160,
            minSize: 80,
            maxSize: 600,
            enableHiding: !column.required,
            enableSorting: column.sortable ?? false,
            meta: column,
        })),
    );

    const table = useVueTable<Row>({
        data: toRef(() => props.rows),
        get columns() {
            return columnDefs.value;
        },
        state: {
            get columnVisibility() {
                return Object.fromEntries(
                    props.columns.map((column) => [
                        column.id,
                        visibleCols.value.includes(column.id),
                    ]),
                );
            },
            get columnOrder() {
                return [
                    ...visibleCols.value,
                    ...props.columns
                        .map((column) => column.id)
                        .filter((id) => !visibleCols.value.includes(id)),
                ];
            },
            get columnSizing() {
                return Object.fromEntries(
                    props.columns.map((column) => [
                        column.id,
                        columnWidth(column.id),
                    ]),
                );
            },
            get rowSelection() {
                return rowSelection.value;
            },
        },
        getRowId: (row) => String(row.id),
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
        enableRowSelection: true,
        onRowSelectionChange: (updater: Updater<RowSelectionState>) => {
            rowSelection.value =
                typeof updater === 'function'
                    ? updater(rowSelection.value)
                    : updater;
            allMatching.value = false;
        },
    });

    // New rows (another page, filter or a finished bulk action) start with no selection.
    watch(
        () => props.rows,
        () => {
            rowSelection.value = {};
            allMatching.value = false;
        },
    );

    const selectedCount = computed(
        () => Object.keys(rowSelection.value).length,
    );

    /** Server-side filter state, posted along with "all matching" (ADM-TBL-008). */
    const serverQuery = computed<TableSelection['query']>(() => {
        const query: TableSelection['query'] = {};

        if (urlState.value.search !== '') {
            query.search = urlState.value.search;
        }

        if (Object.keys(urlState.value.filters).length > 0) {
            query.filter = urlState.value.filters;
        }

        if (props.meta.sort.length > 0) {
            query.sort = props.meta.sort.join(',');
        }

        return query;
    });

    function selection(): TableSelection {
        return {
            ids: allMatching.value ? [] : Object.keys(rowSelection.value),
            all: allMatching.value,
            count: allMatching.value ? props.meta.total : selectedCount.value,
            query: serverQuery.value,
        };
    }

    function clearSelection(): void {
        rowSelection.value = {};
        allMatching.value = false;
    }

    // --- saved views (ADM-TBL-010) ---------------------------------------------------------------
    const activeView = computed(
        () =>
            props.views?.find((view) => view.id === urlState.value.view) ??
            null,
    );

    function applyView(view: TableViewItem): void {
        failed.value = false;

        router.get(
            `${path.value}?${viewQuery(view)}`,
            {},
            { preserveState: true, preserveScroll: true, only: REFETCH },
        );
    }

    /** The state a new or updated view stores: what the URL says plus the effective density. */
    function currentViewState(): ViewState {
        return viewPayload(
            { ...urlState.value, density: density.value },
            defaultCols.value,
        );
    }

    // --- export (ADM-TBL-012) ---------------------------------------------------------------------
    function exportParameters(format: 'csv' | 'xlsx'): string {
        const params = new URLSearchParams();

        if (urlState.value.search !== '') {
            params.set('search', urlState.value.search);
        }

        for (const [id, value] of Object.entries(urlState.value.filters)) {
            params.set(`filter[${id}]`, value);
        }

        if (props.meta.sort.length > 0) {
            params.set('sort', props.meta.sort.join(','));
        }

        params.set('format', format);
        params.set('columns', visibleCols.value.join(','));

        return params.toString();
    }

    return {
        table,
        urlState,
        visibleCols,
        density,
        loading,
        failed,
        searchInput,
        hasActiveState,
        selectedCount,
        allMatching,
        activeView,
        retry,
        setFilters,
        removeFilterKeys,
        reset,
        toggleSort,
        sortOf,
        setPage,
        setPerPage,
        toggleColumn,
        moveColumn,
        moveColumnTo,
        resetColumns,
        columnWidth,
        previewWidth,
        commitWidth,
        setDensity,
        selection,
        clearSelection,
        applyView,
        currentViewState,
        exportParameters,
    };
}
