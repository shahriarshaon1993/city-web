<script setup lang="ts" generic="Row extends { id: string | number }">
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronRight,
    GripVertical,
    Rows2,
    Rows3,
    Search,
    TriangleAlert,
    X,
} from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDataTable } from '@/composables/useDataTable';
import { cn } from '@/lib/utils';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';
import DataTableBulkBar from './DataTableBulkBar.vue';
import DataTableChips from './DataTableChips.vue';
import DataTableColumnMenu from './DataTableColumnMenu.vue';
import DataTableExport from './DataTableExport.vue';
import DataTableFilter from './DataTableFilter.vue';
import DataTablePagination from './DataTablePagination.vue';
import DataTableResizeHandle from './DataTableResizeHandle.vue';
import DataTableRowActions from './DataTableRowActions.vue';
import DataTableViews from './DataTableViews.vue';

// CMP-ADM-TABLE / ADM-TBL-001..016 — the one admin list (DEC-34). TanStack Table in manual mode
// (useDataTable) renders with the shadcn-vue Table primitives; the server (AdminTable) does the
// paging, sorting and filtering, and the URL holds the whole state. Cells are slots:
// `#cell-{columnId}="{ row, value, search }"`; `#expanded="{ row }"` fills an expandable row.
const props = withDefaults(
    defineProps<{
        tableKey: string;
        /** Singular name used in messages, e.g. "user". */
        noun: string;
        columns: DataTableColumn[];
        rows: Row[];
        meta: TableMeta;
        filters?: FilterDef[];
        views?: TableViewItem[];
        canShareViews?: boolean;
        searchPlaceholder?: string;
        /** Accessible name of a row, for "Select {name}" and the row actions. */
        rowLabel: (row: Row) => string;
        rowActions?: (row: Row) => DataTableRowAction[];
        bulkActions?: DataTableBulkAction[];
        selectable?: boolean;
        expandable?: boolean;
        clickableRows?: boolean;
        exportable?: boolean;
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        filters: () => [],
        views: () => [],
        canShareViews: false,
        searchPlaceholder: 'Search…',
        bulkActions: () => [],
        selectable: false,
        expandable: false,
        clickableRows: false,
        exportable: true,
        emptyTitle: undefined,
        emptyDescription: undefined,
    },
);

const emit = defineEmits<{
    rowClick: [row: Row];
    bulk: [action: DataTableBulkAction, selection: TableSelection];
}>();

const scope = useId();

const {
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
} = useDataTable<Row>(props);

const tableEl = ref<HTMLElement | null>(null);
const expanded = ref<Set<string | number>>(new Set());
const dragged = ref<string | null>(null);

const rowHeight = computed(() =>
    density.value === 'compact' ? 'h-10' : 'h-14',
);
const hasActions = computed(() => !!props.rowActions);

/** Fixed columns + the chosen widths; narrower screens scroll sideways inside the card. */
const minWidth = computed(
    () =>
        (props.selectable ? 48 : 0) +
        (props.expandable ? 44 : 0) +
        visibleCols.value.reduce((sum, id) => sum + columnWidth(id), 0) +
        (hasActions.value ? 112 : 0),
);

const colCount = computed(
    () =>
        visibleCols.value.length +
        1 +
        (props.selectable ? 1 : 0) +
        (props.expandable ? 1 : 0),
);

const pageSelected = computed(
    () => props.rows.length > 0 && table.getIsAllPageRowsSelected(),
);

const announcement = computed(() =>
    props.meta.total > 0
        ? `Showing ${props.meta.from} to ${props.meta.to} of ${props.meta.total} ${props.noun}s`
        : `No ${props.noun}s`,
);

function toggleExpanded(id: string | number): void {
    const next = new Set(expanded.value);

    if (!next.delete(id)) {
        next.add(id);
    }

    expanded.value = next;
}

function onRowClick(event: MouseEvent, row: Row): void {
    if (
        props.clickableRows &&
        !(event.target as HTMLElement).closest(
            'a, button, input, label, [role="checkbox"], [role="separator"], [data-no-row-click]',
        )
    ) {
        emit('rowClick', row);
    }
}

function runBulk(action: DataTableBulkAction): void {
    const current = selection();

    emit('bulk', action, current);
    action.run(current);
}

/** ADM-TBL-007 double-click: the widest content of the column, clamped to 80–600 px. */
function fit(id: string): void {
    const cells = tableEl.value?.querySelectorAll<HTMLElement>(
        `[data-fit-col="${id}"]`,
    );
    const widest = Math.max(
        0,
        ...Array.from(cells ?? []).map((cell) => cell.scrollWidth),
    );

    commitWidth(id, widest + 40);
}

const sortKeyOf = (id: string): string =>
    props.columns.find((column) => column.id === id)?.sortKey ?? id;

function sortAria(id: string): 'ascending' | 'descending' | 'none' | undefined {
    const { direction } = sortOf(sortKeyOf(id));

    return direction === 'asc'
        ? 'ascending'
        : direction === 'desc'
          ? 'descending'
          : 'none';
}
</script>

<template>
    <section
        class="rounded-lg border bg-card text-card-foreground"
        :aria-label="`${noun} table`"
    >
        <!-- toolbar -->
        <div class="flex flex-wrap items-center gap-2 border-b p-4">
            <div class="relative w-full sm:w-80">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <label :for="`${scope}-search`" class="sr-only"
                    >Search {{ noun }}s</label
                >
                <Input
                    :id="`${scope}-search`"
                    v-model="searchInput"
                    type="search"
                    class="h-9 pr-8 pl-9 text-sm"
                    :placeholder="searchPlaceholder"
                    maxlength="100"
                    autocomplete="off"
                />
                <button
                    v-if="searchInput"
                    type="button"
                    class="absolute top-1/2 right-1.5 grid size-6 -translate-y-1/2 place-items-center rounded-full hover:bg-accent"
                    aria-label="Clear search"
                    @click="searchInput = ''"
                >
                    <X class="size-3.5" aria-hidden="true" />
                </button>
            </div>

            <DataTableFilter
                v-for="def in filters"
                :key="def.id"
                :def="def"
                :filters="urlState.filters"
                :scope="scope"
                @change="setFilters"
            />

            <Button
                v-if="hasActiveState"
                type="button"
                variant="ghost"
                size="sm"
                @click="reset"
            >
                Reset
            </Button>

            <Spinner
                v-if="loading"
                class="size-4 text-muted-foreground"
                aria-hidden="true"
            />

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <DataTableViews
                    :scope="scope"
                    :table-key="tableKey"
                    :views="views"
                    :active-view="activeView"
                    :can-share="canShareViews"
                    :current-state="currentViewState"
                    @apply="applyView"
                />
                <DataTableColumnMenu
                    :scope="scope"
                    :columns="columns"
                    :visible="visibleCols"
                    :width-of="columnWidth"
                    @toggle="toggleColumn"
                    @move="moveColumn"
                    @width="commitWidth"
                    @reset="resetColumns"
                />
                <div
                    class="inline-flex rounded-md border"
                    role="group"
                    aria-label="Row density"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="rounded-r-none"
                        :class="density === 'comfortable' ? 'bg-accent' : ''"
                        :aria-pressed="density === 'comfortable'"
                        aria-label="Comfortable rows"
                        @click="setDensity('comfortable')"
                    >
                        <Rows2 aria-hidden="true" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="rounded-l-none"
                        :class="density === 'compact' ? 'bg-accent' : ''"
                        :aria-pressed="density === 'compact'"
                        aria-label="Compact rows"
                        @click="setDensity('compact')"
                    >
                        <Rows3 aria-hidden="true" />
                    </Button>
                </div>
                <DataTableExport
                    v-if="exportable"
                    :table-key="tableKey"
                    :parameters="exportParameters"
                />
                <slot name="toolbar-actions" />
            </div>
        </div>

        <!-- active filter chips -->
        <div
            v-if="
                hasActiveState &&
                (urlState.search || Object.keys(urlState.filters).length)
            "
            class="border-b px-4 py-3"
        >
            <DataTableChips
                :defs="filters"
                :filters="urlState.filters"
                :search="urlState.search"
                @remove="removeFilterKeys"
                @clear-search="searchInput = ''"
            />
        </div>

        <!-- table -->
        <div
            ref="tableEl"
            class="max-h-[calc(100dvh-14rem)] min-h-40 overflow-auto"
            :aria-busy="loading"
        >
            <Table
                :style="{ minWidth: `${minWidth}px`, tableLayout: 'fixed' }"
                :aria-rowcount="meta.total"
            >
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-if="selectable"
                            class="sticky top-0 z-[2] w-12 px-3"
                        >
                            <Checkbox
                                :model-value="
                                    pageSelected
                                        ? true
                                        : table.getIsSomePageRowsSelected()
                                          ? 'indeterminate'
                                          : false
                                "
                                aria-label="Select all rows on this page"
                                :disabled="rows.length === 0"
                                @update:model-value="
                                    (value) =>
                                        table.toggleAllPageRowsSelected(
                                            value === true,
                                        )
                                "
                            />
                        </TableHead>
                        <TableHead
                            v-if="expandable"
                            class="sticky top-0 z-[2] w-11 px-0"
                        >
                            <span class="sr-only">Details</span>
                        </TableHead>

                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            class="group/head sticky top-0 z-[2]"
                            :class="
                                header.column.columnDef.meta?.align === 'right'
                                    ? 'text-right'
                                    : ''
                            "
                            :style="{
                                width: `${columnWidth(header.column.id)}px`,
                            }"
                            :aria-sort="
                                header.column.getCanSort()
                                    ? sortAria(header.column.id)
                                    : undefined
                            "
                            @dragover.prevent
                            @drop="
                                dragged &&
                                moveColumnTo(dragged, header.column.id)
                            "
                        >
                            <div class="flex items-center gap-1">
                                <span
                                    draggable="true"
                                    class="hidden cursor-grab text-muted-foreground opacity-0 group-hover/head:opacity-100 pointer-fine:block"
                                    aria-hidden="true"
                                    title="Drag to reorder"
                                    @dragstart="dragged = header.column.id"
                                    @dragend="dragged = null"
                                >
                                    <GripVertical class="size-3.5" />
                                </span>

                                <button
                                    v-if="header.column.getCanSort()"
                                    type="button"
                                    class="inline-flex min-w-0 items-center gap-1.5 uppercase hover:text-foreground"
                                    title="Sort. Shift+click adds a second sort (up to 3)."
                                    @click="
                                        toggleSort(
                                            sortKeyOf(header.column.id),
                                            $event.shiftKey,
                                        )
                                    "
                                >
                                    <span
                                        class="truncate"
                                        :data-fit-col="header.column.id"
                                        >{{
                                            header.column.columnDef.header
                                        }}</span
                                    >
                                    <ArrowUp
                                        v-if="
                                            sortOf(sortKeyOf(header.column.id))
                                                .direction === 'asc'
                                        "
                                        class="size-3.5 shrink-0 text-foreground"
                                        aria-hidden="true"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            sortOf(sortKeyOf(header.column.id))
                                                .direction === 'desc'
                                        "
                                        class="size-3.5 shrink-0 text-foreground"
                                        aria-hidden="true"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="size-3.5 shrink-0 opacity-50"
                                        aria-hidden="true"
                                    />
                                    <span
                                        v-if="
                                            sortOf(sortKeyOf(header.column.id))
                                                .position
                                        "
                                        class="grid size-4 place-items-center rounded-full bg-foreground text-[10px] text-background"
                                        aria-hidden="true"
                                    >
                                        {{
                                            sortOf(sortKeyOf(header.column.id))
                                                .position
                                        }}
                                    </span>
                                </button>
                                <span
                                    v-else
                                    class="truncate"
                                    :data-fit-col="header.column.id"
                                    >{{ header.column.columnDef.header }}</span
                                >
                            </div>

                            <DataTableResizeHandle
                                :label="String(header.column.columnDef.header)"
                                :width="columnWidth(header.column.id)"
                                @preview="
                                    (width) =>
                                        previewWidth(header.column.id, width)
                                "
                                @commit="
                                    (width) =>
                                        commitWidth(header.column.id, width)
                                "
                                @fit="fit(header.column.id)"
                            />
                        </TableHead>

                        <TableHead
                            class="sticky top-0 z-[2]"
                            :style="{
                                minWidth: hasActions ? '112px' : undefined,
                            }"
                        >
                            <span class="sr-only">Actions</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody
                    :class="
                        loading
                            ? 'opacity-60 transition-opacity'
                            : 'transition-opacity'
                    "
                >
                    <!-- error with retry (ADM-TBL-014) -->
                    <TableRow v-if="failed">
                        <TableCell :colspan="colCount" class="h-40 text-center">
                            <div
                                role="alert"
                                class="mx-auto grid max-w-sm justify-items-center gap-3"
                            >
                                <TriangleAlert
                                    class="size-6 text-destructive"
                                    aria-hidden="true"
                                />
                                <p class="text-sm">
                                    This table could not be loaded. Check your
                                    connection and try again.
                                </p>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="retry"
                                    >Try again</Button
                                >
                            </div>
                        </TableCell>
                    </TableRow>

                    <!-- skeleton while a fetch has nothing to show yet (ADM-TBL-014: 5 rows) -->
                    <template v-else-if="loading && rows.length === 0">
                        <TableRow
                            v-for="n in 5"
                            :key="`skeleton-${n}`"
                            aria-hidden="true"
                        >
                            <TableCell :colspan="colCount" :class="rowHeight">
                                <Skeleton class="h-4 w-full max-w-md" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- empty -->
                    <TableRow v-else-if="rows.length === 0">
                        <TableCell :colspan="colCount" class="h-48 text-center">
                            <div
                                class="mx-auto grid max-w-sm justify-items-center gap-2"
                            >
                                <template v-if="hasActiveState">
                                    <p class="font-medium">
                                        No {{ noun }}s match
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Nothing fits the search and filters you
                                        chose.
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="mt-1"
                                        @click="reset"
                                    >
                                        Clear search and filters
                                    </Button>
                                </template>
                                <template v-else>
                                    <p class="font-medium">
                                        {{ emptyTitle ?? `No ${noun}s yet` }}
                                    </p>
                                    <p
                                        v-if="emptyDescription"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ emptyDescription }}
                                    </p>
                                    <slot name="empty-action" />
                                </template>
                            </div>
                        </TableCell>
                    </TableRow>

                    <template v-else>
                        <template
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                        >
                            <TableRow
                                :data-state="
                                    row.getIsSelected() ? 'selected' : undefined
                                "
                                :class="
                                    cn(
                                        'hover:bg-muted/50',
                                        clickableRows ? 'cursor-pointer' : '',
                                    )
                                "
                                @click="onRowClick($event, row.original)"
                            >
                                <TableCell
                                    v-if="selectable"
                                    :class="cn('w-12', rowHeight)"
                                >
                                    <Checkbox
                                        :model-value="row.getIsSelected()"
                                        :aria-label="`Select ${rowLabel(row.original)}`"
                                        @update:model-value="
                                            (value) =>
                                                row.toggleSelected(
                                                    value === true,
                                                )
                                        "
                                    />
                                </TableCell>
                                <TableCell
                                    v-if="expandable"
                                    :class="cn('w-11 px-0', rowHeight)"
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-expanded="
                                            expanded.has(row.original.id)
                                        "
                                        :aria-label="`${expanded.has(row.original.id) ? 'Hide' : 'Show'} details: ${rowLabel(row.original)}`"
                                        @click="toggleExpanded(row.original.id)"
                                    >
                                        <ChevronRight
                                            class="transition-transform duration-(--dur-fast) motion-reduce:transition-none"
                                            :class="
                                                expanded.has(row.original.id)
                                                    ? 'rotate-90'
                                                    : ''
                                            "
                                            aria-hidden="true"
                                        />
                                    </Button>
                                </TableCell>

                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                    :class="
                                        cn(
                                            rowHeight,
                                            cell.column.columnDef.meta
                                                ?.align === 'right'
                                                ? 'text-right'
                                                : '',
                                        )
                                    "
                                >
                                    <div
                                        class="min-w-0 truncate"
                                        :data-fit-col="cell.column.id"
                                    >
                                        <slot
                                            :name="`cell-${cell.column.id}`"
                                            :row="row.original"
                                            :value="cell.getValue()"
                                            :search="urlState.search"
                                        >
                                            {{ cell.getValue() }}
                                        </slot>
                                    </div>
                                </TableCell>

                                <TableCell :class="cn('pr-3', rowHeight)">
                                    <DataTableRowActions
                                        v-if="rowActions"
                                        :actions="rowActions(row.original)"
                                        :row-label="rowLabel(row.original)"
                                    />
                                </TableCell>
                            </TableRow>

                            <TableRow
                                v-if="
                                    expandable && expanded.has(row.original.id)
                                "
                                class="bg-muted/30"
                            >
                                <TableCell
                                    :colspan="colCount"
                                    class="px-6 py-4 whitespace-normal"
                                >
                                    <slot name="expanded" :row="row.original" />
                                </TableCell>
                            </TableRow>
                        </template>
                    </template>
                </TableBody>
            </Table>
        </div>

        <div class="border-t">
            <DataTablePagination
                :scope="scope"
                :meta="meta"
                @page="setPage"
                @per-page="setPerPage"
            />
        </div>

        <p class="sr-only" role="status" aria-live="polite">
            {{ announcement }}
        </p>

        <DataTableBulkBar
            v-if="selectable"
            :count="allMatching ? meta.total : selectedCount"
            :total="meta.total"
            :page-selected="pageSelected"
            :all-matching="allMatching"
            :actions="bulkActions"
            @select-all="allMatching = true"
            @clear="clearSelection"
            @run="runBulk"
        />
    </section>
</template>
