<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import { cn } from '@/lib/utils';

// CRUD-02: the head of every list page — title, one-line description, primary button ("New
// {thing}", in the `actions` slot), status tabs with counts where the entity has a status, and
// at the right of the tabs "Recycle bin (n)" filtered to this type (hidden when 0 or not permitted).
export type ListTab = {
    key: string;
    label: string;
    count?: number | null;
    href: string;
    active: boolean;
};

defineProps<{
    title: string;
    description: string;
    tabs?: ListTab[];
    /** Items of this type in the bin; the link is hidden at 0 / null. */
    binCount?: number | null;
    binHref?: string;
}>();
</script>

<template>
    <AdminPageHeader :title="title" :description="description">
        <template v-if="$slots.actions" #actions
            ><slot name="actions"
        /></template>
        <template v-if="$slots.tabs" #tabs><slot name="tabs" /></template>
    </AdminPageHeader>

    <div
        v-if="(tabs && tabs.length) || (binCount && binHref)"
        class="-mt-2 mb-5 flex flex-wrap items-center justify-between gap-x-6 gap-y-2"
    >
        <nav
            v-if="tabs && tabs.length"
            :aria-label="`${title} status`"
            class="flex flex-wrap gap-1"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.key"
                :href="tab.href"
                preserve-scroll
                :aria-current="tab.active ? 'page' : undefined"
                :class="
                    cn(
                        'inline-flex h-9 items-center gap-1.5 rounded-md px-3 text-sm transition-colors',
                        tab.active
                            ? 'bg-foreground font-medium text-background'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    )
                "
            >
                {{ tab.label }}
                <span
                    v-if="tab.count != null"
                    :class="
                        cn(
                            'text-xs tabular-nums',
                            tab.active ? 'opacity-80' : '',
                        )
                    "
                    >{{ tab.count }}</span
                >
            </Link>
        </nav>
        <span v-else />

        <Link
            v-if="binCount && binHref"
            :href="binHref"
            class="inline-flex h-9 items-center gap-1.5 rounded-md px-2 text-sm text-muted-foreground hover:text-foreground"
        >
            <Trash2 class="size-4" aria-hidden="true" />
            Recycle bin ({{ binCount }})
        </Link>
    </div>
</template>
