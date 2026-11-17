<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Columns3, Table2 } from '@lucide/vue';
import { computed } from 'vue';
import { board, index } from '@/routes/admin/leads';
import { cn } from '@/lib/utils';

// ADM-SCR-11: Table and Board are two views of the same leads. The switch keeps the search, the
// filters and the saved view, so you land on the same leads either way.
defineProps<{ current: 'table' | 'board' }>();

const page = usePage();

const carried = computed(() => {
    const params = new URLSearchParams(page.url.split('?')[1] ?? '');
    const kept = new URLSearchParams();

    for (const [key, value] of params.entries()) {
        if (key === 'search' || key === 'view' || key.startsWith('filter[')) {
            kept.set(key, value);
        }
    }

    const query = kept.toString();

    return query === '' ? '' : `?${query}`;
});

const links = computed(() => [
    {
        key: 'table' as const,
        label: 'Table',
        icon: Table2,
        href: index.url() + carried.value,
    },
    {
        key: 'board' as const,
        label: 'Board',
        icon: Columns3,
        href: board.url() + carried.value,
    },
]);
</script>

<template>
    <nav aria-label="Leads view" class="flex gap-1">
        <Link
            v-for="item in links"
            :key="item.key"
            :href="item.href"
            :aria-current="current === item.key ? 'page' : undefined"
            :class="
                cn(
                    'inline-flex h-9 items-center gap-1.5 rounded-md px-3 text-sm transition-colors',
                    current === item.key
                        ? 'bg-foreground font-medium text-background'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                )
            "
        >
            <component :is="item.icon" class="size-4" aria-hidden="true" />
            {{ item.label }}
        </Link>
    </nav>
</template>
