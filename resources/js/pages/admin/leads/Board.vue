<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Search, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, useId, watch } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import DataTableChips from '@/components/admin/data-table/DataTableChips.vue';
import DataTableFilter from '@/components/admin/data-table/DataTableFilter.vue';
import DataTableViews from '@/components/admin/data-table/DataTableViews.vue';
import LeadBoard from '@/components/admin/leads/LeadBoard.vue';
import LeadDrawer from '@/components/admin/leads/LeadDrawer.vue';
import LeadReasonDialog from '@/components/admin/leads/LeadReasonDialog.vue';
import LeadViewSwitch from '@/components/admin/leads/LeadViewSwitch.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCan } from '@/composables/useCan';
import { useLeadStatusChange } from '@/composables/useLeadActions';
import {
    parseTableState,
    serializeTableState,
    viewQuery,
} from '@/lib/table-state';
import { board, create } from '@/routes/admin/leads';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    FilterDef,
    FilterOption,
    LeadBoardColumn,
    LeadCard,
    LeadDetail,
    LeadsShared,
    TableViewItem,
} from '@/types';

// ADM-SCR-11 Leads, board view (CMP-ADM-KANBAN). Reads the same search and filters as the table, so
// switching keeps you on the same leads. A drag or the "Move to…" menu changes the status; Won and
// Lost ask for a reason first, every other move shows an Undo toast (TOAST-02).
const props = defineProps<
    LeadsShared & {
        columns: LeadBoardColumn[];
        views: TableViewItem[];
        filterOptions: Record<string, FilterOption[]>;
    }
>();

const { can } = useCan();
const page = usePage();
const scope = useId();

const REFETCH = ['columns', 'views', 'filterOptions'];

const state = computed(() => parseTableState(page.url));

const filters = computed<FilterDef[]>(() => [
    {
        id: 'stage',
        label: 'Open or closed',
        kind: 'enum',
        options: props.filterOptions.stage,
    },
    {
        id: 'type',
        label: 'Type',
        kind: 'enum',
        options: props.filterOptions.type,
    },
    {
        id: 'project',
        label: 'Project',
        kind: 'enum',
        options: props.filterOptions.project,
    },
    ...(props.filterOptions.assignee
        ? [
              {
                  id: 'assignee',
                  label: 'Assignee',
                  kind: 'enum' as const,
                  options: props.filterOptions.assignee,
              },
          ]
        : []),
    {
        id: 'follow_up',
        label: 'Follow-up',
        kind: 'enum',
        options: props.filterOptions.follow_up,
    },
    { id: 'created', label: 'Received', kind: 'date' },
]);

// The board has no status filter: the columns are the statuses.
const ownFilters = computed(() =>
    Object.fromEntries(
        Object.entries(state.value.filters).filter(([key]) => key !== 'status'),
    ),
);

const hasActiveState = computed(
    () => state.value.search !== '' || Object.keys(ownFilters.value).length > 0,
);

function go(patch: {
    search?: string;
    filters?: Record<string, string>;
}): void {
    const query = serializeTableState(
        {
            ...state.value,
            search: patch.search ?? state.value.search,
            filters: patch.filters ?? ownFilters.value,
            sort: [],
            page: 1,
            view: null,
        },
        [],
        25,
    ).toString();

    router.get(
        board.url() + (query ? `?${query}` : ''),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: REFETCH,
        },
    );
}

const searchInput = ref(state.value.search);
const applySearch = useDebounceFn((value: string) => {
    if (value.trim() !== state.value.search) {
        go({ search: value.trim() });
    }
}, 300);

watch(searchInput, (value) => applySearch(value));
watch(
    () => state.value.search,
    (value) => {
        if (value !== searchInput.value.trim()) {
            searchInput.value = value;
        }
    },
);

function setFilters(patch: Record<string, string | null>): void {
    const next = { ...ownFilters.value };

    for (const [id, value] of Object.entries(patch)) {
        if (value === null || value === '') {
            delete next[id];
        } else {
            next[id] = value;
        }
    }

    go({ filters: next });
}

function reset(): void {
    searchInput.value = '';
    go({ search: '', filters: {} });
}

function applyView(view: TableViewItem): void {
    router.get(
        `${board.url()}?${viewQuery(view)}`,
        {},
        { preserveState: true, preserveScroll: true, only: REFETCH },
    );
}

const activeView = computed(
    () => props.views.find((view) => view.id === state.value.view) ?? null,
);

// --- drawer ------------------------------------------------------------------------------------------
const activeId = ref<number | null>(props.openLead?.id ?? null);
const drawerOpen = ref(props.openLead !== null);

function openDrawer(card: { id: number }): void {
    activeId.value = card.id;
    drawerOpen.value = true;
}

watch(drawerOpen, (isOpen) => {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    if (!isOpen && params.has('lead')) {
        params.delete('lead');
        const rest = params.toString();

        router.replace({
            url: path + (rest ? `?${rest}` : ''),
            preserveScroll: true,
            preserveState: true,
        });
    }
});

// --- moving cards --------------------------------------------------------------------------------------
const { reasonRequest, reasonOpen, move } = useLeadStatusChange();

/** A card that was dragged, then refused or cancelled, goes back to where the server says it is. */
function restoreColumns(): void {
    router.reload({ only: ['columns'] });
}

function onMove(card: LeadCard | LeadDetail, to: LeadCard['status']): void {
    move(card, to, restoreColumns);
}
</script>

<template>
    <Head title="Leads board" />

    <ListPageHeader
        title="Leads"
        description="Drag a lead to the next step, or use its “Move to…” menu. Open a card to call, take notes and set a follow-up."
        :bin-count="can('leads.delete') ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'lead' } })"
    >
        <template v-if="canCreate" #actions>
            <Button as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New lead
                </Link>
            </Button>
        </template>
        <template #tabs>
            <LeadViewSwitch current="board" />
        </template>
    </ListPageHeader>

    <div
        class="mb-5 flex flex-wrap items-center gap-2 rounded-xl border bg-card p-3"
    >
        <div class="relative w-full sm:w-80">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <label :for="`${scope}-search`" class="sr-only">Search leads</label>
            <Input
                :id="`${scope}-search`"
                v-model="searchInput"
                type="search"
                class="h-9 pr-8 pl-9 text-sm"
                placeholder="Search name, phone, email or message"
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
            :filters="ownFilters"
            :scope="scope"
            @change="setFilters"
        />

        <Button v-if="hasActiveState" variant="ghost" size="sm" @click="reset">
            Reset
        </Button>

        <div class="ml-auto">
            <DataTableViews
                :scope="scope"
                table-key="leads"
                :views="views"
                :active-view="activeView"
                :can-share="canShareViews"
                :current-state="
                    () => ({
                        ...(state.search ? { search: state.search } : {}),
                        ...(Object.keys(ownFilters).length
                            ? { filter: ownFilters }
                            : {}),
                    })
                "
                @apply="applyView"
            />
        </div>
    </div>

    <div v-if="hasActiveState" class="mb-4">
        <DataTableChips
            :defs="filters"
            :filters="ownFilters"
            :search="state.search"
            @remove="
                (keys) =>
                    setFilters(
                        Object.fromEntries(keys.map((key) => [key, null])),
                    )
            "
            @clear-search="searchInput = ''"
        />
    </div>

    <LeadBoard
        :columns="columns"
        :statuses="statuses"
        @open="openDrawer"
        @move="onMove"
    />

    <LeadDrawer
        v-model:open="drawerOpen"
        :lead-id="activeId"
        :initial="openLead"
        :statuses="statuses"
        :assignees="assignees"
        :can-assign="canAssign"
        @move="onMove"
    />
    <LeadReasonDialog
        v-model:open="reasonOpen"
        :request="reasonRequest"
        :reasons="reasons"
        @cancelled="restoreColumns"
    />
</template>
