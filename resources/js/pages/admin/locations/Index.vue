<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CircleCheck,
    CircleSlash,
    ExternalLink,
    MapPinPlus,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import CatalogTabs from '@/components/admin/catalog/CatalogTabs.vue';
import CityDialog from '@/components/admin/catalog/CityDialog.vue';
import SortableList from '@/components/admin/catalog/SortableList.vue';
import UsageDialog from '@/components/admin/catalog/UsageDialog.vue';
import DataTableRowActions from '@/components/admin/data-table/DataTableRowActions.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime } from '@/lib/admin-format';
import {
    destroy as destroyLocation,
    order as orderLocations,
} from '@/routes/admin/locations';
import { create } from '@/routes/admin/locations/areas';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    AreaNode,
    CityNode,
    DataTableRowAction,
    UsageBlock,
} from '@/types';

// ADM-SCR-04 Locations (CMP-ADM-TREE): cities with their areas. Drag a row or use the arrows to
// order it (saved at once, "Order saved" + Undo, CRUD-06); an area's badge says whether its page is
// indexable and why not (FR-LOC-003). A city with areas and an area with projects cannot be deleted
// (DEL-03): the dialog lists them.
const props = defineProps<{
    cities: CityNode[];
    canManage: boolean;
    binCount: number;
    minIntroWords: number;
}>();

const cities = ref<CityNode[]>(
    props.cities.map((city) => ({ ...city, areas: [...city.areas] })),
);

watch(
    () => props.cities,
    (next) => {
        cities.value = next.map((city) => ({
            ...city,
            areas: [...city.areas],
        }));
    },
);

const confirm = useConfirm();
const cityOpen = ref(false);
const editingCity = ref<{ id: number; name: string; slug: string } | null>(
    null,
);
const usageOpen = ref(false);
const usageTitle = ref('');
const usage = ref<UsageBlock | null>(null);

function newCity(): void {
    editingCity.value = null;
    cityOpen.value = true;
}

function editCity(city: CityNode): void {
    editingCity.value = { id: city.id, name: city.name, slug: city.slug };
    cityOpen.value = true;
}

function saveOrder(parentId: number | null, ids: number[]): void {
    router.patch(
        orderLocations.url(),
        { ids, parent_id: parentId },
        { preserveScroll: true },
    );
}

async function remove(
    kind: 'city' | 'area',
    node: CityNode | AreaNode,
): Promise<void> {
    if (node.usage) {
        usageTitle.value =
            kind === 'city'
                ? 'This city cannot be deleted yet'
                : 'This area cannot be deleted yet';
        usage.value = node.usage;
        usageOpen.value = true;

        return;
    }

    const answer = await confirm({
        title: kind === 'city' ? 'Delete this city?' : 'Delete this area?',
        subject: node.name,
        description:
            kind === 'city'
                ? 'The city moves to the Recycle bin and can be restored for 90 days.'
                : 'The area page leaves the website. The area moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: kind === 'city' ? 'Delete city' : 'Delete area',
        destructive: true,
    });

    if (answer) {
        router.delete(destroyLocation.url(node.id), { preserveScroll: true });
    }
}

function cityActions(city: CityNode): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit city',
            icon: Pencil,
            hidden: !props.canManage,
            onSelect: () => editCity(city),
        },
        {
            id: 'area',
            label: 'New area in this city',
            icon: MapPinPlus,
            hidden: !props.canManage,
            onSelect: () =>
                router.visit(create.url({ query: { city: city.id } })),
        },
        {
            id: 'view',
            label: 'View on site',
            icon: ExternalLink,
            onSelect: () => window.open(city.public_url, '_blank', 'noopener'),
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !props.canManage,
            onSelect: () => void remove('city', city),
        },
    ];
}

function areaActions(area: AreaNode): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit area',
            icon: Pencil,
            onSelect: () => router.visit(area.edit_url),
        },
        {
            id: 'view',
            label: 'View on site',
            icon: ExternalLink,
            hidden: area.public_url === null,
            onSelect: () =>
                window.open(area.public_url ?? '', '_blank', 'noopener'),
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !props.canManage,
            onSelect: () => void remove('area', area),
        },
    ];
}
</script>

<template>
    <Head title="Locations" />

    <ListPageHeader
        title="Locations"
        description="Cities and the areas inside them. Each area has its own page with the projects there."
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'location' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="newCity">
                <Plus aria-hidden="true" />
                New city
            </Button>
            <Button as-child>
                <Link :href="create.url()">
                    <MapPinPlus aria-hidden="true" />
                    New area
                </Link>
            </Button>
        </template>
        <template #tabs>
            <CatalogTabs current="locations" />
        </template>
    </ListPageHeader>

    <div class="max-w-4xl">
        <div
            v-if="!cities.length"
            class="rounded-lg border border-dashed p-8 text-center"
        >
            <p class="font-medium">No cities yet</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                Add a city such as Dhaka, then its areas. Projects are placed in
                an area, and visitors browse by area.
            </p>
            <Button v-if="canManage" class="mt-4" @click="newCity">
                <Plus aria-hidden="true" />
                New city
            </Button>
        </div>

        <SortableList
            v-else
            v-model="cities"
            label="Cities"
            :item-label="(item) => item.name"
            :disabled="!canManage"
            align-top
            @reorder="(ids) => saveOrder(null, ids)"
        >
            <template #default="{ item: city }">
                <section
                    class="grid gap-3"
                    :aria-label="`${city.name} and its areas`"
                >
                    <header class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <h2 class="text-base font-semibold">{{ city.name }}</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ city.areas_count }}
                            {{ city.areas_count === 1 ? 'area' : 'areas' }} ·
                            {{ city.projects_count }}
                            {{
                                city.projects_count === 1
                                    ? 'project'
                                    : 'projects'
                            }}
                            ·
                            <span class="font-mono text-xs"
                                >/locations/{{ city.slug }}</span
                            >
                        </p>
                    </header>

                    <SortableList
                        v-if="city.areas.length"
                        v-model="city.areas"
                        :label="`Areas in ${city.name}`"
                        :item-label="(area) => area.name"
                        :disabled="!canManage"
                        compact
                        class="ml-1"
                        @reorder="(ids) => saveOrder(city.id, ids)"
                    >
                        <template #default="{ item: area }">
                            <div
                                class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1"
                            >
                                <Link
                                    :href="area.edit_url"
                                    class="text-sm font-medium hover:underline"
                                    >{{ area.name }}</Link
                                >
                                <span
                                    :title="
                                        area.pending
                                            ? 'Published. Changes are waiting: the live version is older.'
                                            : area.scheduled_at
                                              ? `Scheduled. Goes live ${formatDateTime(area.scheduled_at)} (Dhaka time).`
                                              : undefined
                                    "
                                >
                                    <StatusBadge
                                        :label="
                                            area.pending
                                                ? 'Pending changes'
                                                : area.status_label
                                        "
                                        :tone="
                                            area.pending
                                                ? 'warning'
                                                : area.status === 'published'
                                                  ? 'success'
                                                  : area.status === 'scheduled'
                                                    ? 'info'
                                                    : 'neutral'
                                        "
                                    />
                                </span>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span
                                            class="inline-flex items-center gap-1 text-xs"
                                            :class="
                                                area.indexable
                                                    ? 'text-success'
                                                    : 'text-muted-foreground'
                                            "
                                            tabindex="0"
                                        >
                                            <CircleCheck
                                                v-if="area.indexable"
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                            <CircleSlash
                                                v-else
                                                class="size-3.5"
                                                aria-hidden="true"
                                            />
                                            {{
                                                area.indexable
                                                    ? 'Indexable'
                                                    : 'Not indexed'
                                            }}
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent class="max-w-72">
                                        <template v-if="area.indexable">
                                            Search engines may index this page:
                                            it has a published project and an
                                            intro of at least
                                            {{ minIntroWords }} words.
                                        </template>
                                        <template v-else>
                                            Sent as noindex,follow and left out
                                            of the sitemaps.
                                            {{ area.reasons.join(' ') }}
                                        </template>
                                    </TooltipContent>
                                </Tooltip>
                                <span class="text-xs text-muted-foreground">
                                    {{ area.projects_count }}
                                    {{
                                        area.projects_count === 1
                                            ? 'project'
                                            : 'projects'
                                    }}
                                </span>
                            </div>
                        </template>
                        <template #actions="{ item: area }">
                            <DataTableRowActions
                                :actions="areaActions(area)"
                                :row-label="area.name"
                            />
                        </template>
                    </SortableList>
                    <p v-else class="text-sm text-muted-foreground">
                        No areas yet.
                        <Link
                            v-if="canManage"
                            :href="create.url({ query: { city: city.id } })"
                            class="underline underline-offset-4"
                            >Add the first area</Link
                        >
                    </p>
                </section>
            </template>
            <template #actions="{ item: city }">
                <DataTableRowActions
                    :actions="cityActions(city)"
                    :row-label="city.name"
                />
            </template>
        </SortableList>
    </div>

    <CityDialog v-model:open="cityOpen" :city="editingCity" />
    <UsageDialog v-model:open="usageOpen" :title="usageTitle" :usage="usage" />
</template>
