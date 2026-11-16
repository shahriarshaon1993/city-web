<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Star } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import SortableList from '@/components/admin/catalog/SortableList.vue';
import { Button } from '@/components/ui/button';
import {
    featured as featuredRoute,
    index as projectsIndex,
} from '@/routes/admin/projects';
import { order } from '@/routes/admin/projects/featured';
import type { FeaturedRow } from '@/types';

// ADM-SCR-02 Featured order: the featured projects as a drag list; the home page shows them in
// this order (FR-PROJ-010). A drop saves at once with "Order saved" + Undo (CRUD-06).
const props = defineProps<{
    featured: FeaturedRow[];
    candidates: FeaturedRow[];
    canManage: boolean;
}>();

const items = ref<FeaturedRow[]>([...props.featured]);
const adding = ref<number | null>(null);

watch(
    () => props.featured,
    (next) => {
        items.value = [...next];
    },
);

const options = computed(() =>
    props.candidates.map((project) => ({
        value: project.id,
        label: project.name,
        group: project.status.label,
    })),
);

const tone = {
    published: 'success',
    scheduled: 'info',
    archived: 'warning',
    draft: 'neutral',
} as const;

function save(ids: number[]): void {
    router.patch(order.url(), { ids }, { preserveScroll: true });
}

function setFeatured(id: number, value: boolean): void {
    router.patch(
        featuredRoute.url(id),
        { featured: value },
        {
            preserveScroll: true,
            onSuccess: () => {
                adding.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Featured order" />

    <AdminPageHeader
        title="Featured order"
        description="The projects shown on the home page, in this order. Drag a row, or use the arrows."
    >
        <template #actions>
            <Button as-child variant="outline">
                <Link :href="projectsIndex.url()">All projects</Link>
            </Button>
        </template>
    </AdminPageHeader>

    <div class="grid max-w-3xl gap-6">
        <p
            v-if="!items.length"
            class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
        >
            No project is featured yet. Pick one below, or use the star in the
            Projects list.
        </p>

        <SortableList
            v-else
            v-model="items"
            label="Featured projects"
            :item-label="(item) => item.name"
            :disabled="!canManage"
            @reorder="save"
        >
            <template #default="{ item, index }">
                <div class="flex min-w-0 items-center gap-3">
                    <span
                        class="w-5 shrink-0 text-right text-sm text-muted-foreground tabular-nums"
                        >{{ index + 1 }}</span
                    >
                    <span
                        class="h-10 w-14 shrink-0 overflow-hidden rounded bg-muted"
                    >
                        <img
                            v-if="item.thumb"
                            :src="item.thumb"
                            alt=""
                            class="size-full object-cover"
                        />
                    </span>
                    <span class="min-w-0">
                        <Link
                            :href="item.edit_url"
                            class="block truncate text-sm font-medium hover:underline"
                            >{{ item.name }}</Link
                        >
                        <span
                            class="block truncate text-xs text-muted-foreground"
                        >
                            {{
                                [item.segment, item.area]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </span>
                    </span>
                    <StatusBadge
                        :label="item.status.label"
                        :tone="
                            tone[item.status.value as keyof typeof tone] ??
                            'neutral'
                        "
                        class="ml-auto hidden sm:inline-flex"
                    />
                </div>
            </template>
            <template #actions="{ item }">
                <Button
                    v-if="canManage"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="setFeatured(item.id, false)"
                >
                    <Star
                        class="fill-warning text-warning"
                        aria-hidden="true"
                    />
                    Remove<span class="sr-only">
                        {{ item.name }} from featured</span
                    >
                </Button>
            </template>
        </SortableList>

        <div v-if="canManage" class="grid gap-3 rounded-lg border p-4">
            <h2 class="text-sm font-semibold">Feature another project</h2>
            <p class="text-sm text-muted-foreground">
                It goes to the end of the list. Draft projects can be featured
                too; they show on the home page once published.
            </p>
            <div class="flex items-center gap-2">
                <div class="min-w-0 flex-1">
                    <AdminSelect
                        id="featured-add"
                        v-model="adding"
                        :options="options"
                        placeholder="Choose a project…"
                    />
                </div>
                <Button
                    type="button"
                    :disabled="adding === null"
                    @click="adding !== null && setFeatured(adding, true)"
                >
                    <Plus aria-hidden="true" />
                    Feature
                </Button>
            </div>
        </div>
    </div>
</template>
