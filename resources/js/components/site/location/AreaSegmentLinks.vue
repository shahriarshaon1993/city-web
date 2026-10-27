<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';
import type { AreaDetail } from '@/types/location';

// LAY-LOC-DETAIL "segment pills" in the listing's facet-row style (DEC-53,
// direction.md §6: overline label, options with a muted count, the active
// one underlined): All → the area page, then each segment that has its own
// `/locations/{area}/{segment}` page (FR-LOC-004, ≥ 2 projects). Segments
// without a page are left out — they are not crawl paths. Real links; the
// visit keeps the scroll position, so the visitor stays at the projects.
const props = defineProps<{
    area: AreaDetail;
}>();

const labelId = useId();

const items = computed(() => [
    {
        key: 'all',
        label: 'All',
        count: props.area.project_count,
        url: props.area.url,
        active: props.area.segment === null,
    },
    ...props.area.segments
        .filter((segment) => segment.url !== null)
        .map((segment) => ({
            key: segment.value,
            label: segment.label,
            count: segment.count,
            url: segment.url as string,
            active: props.area.segment === segment.value,
        })),
]);
</script>

<template>
    <div v-if="items.length > 1" class="flex min-w-0 items-center gap-5">
        <span
            :id="labelId"
            class="shrink-0 text-overline text-muted-foreground uppercase"
        >
            Type
        </span>
        <nav
            :aria-labelledby="labelId"
            class="-my-1 flex min-w-0 snap-x [scrollbar-width:none] items-center gap-5 overflow-x-auto py-1 [&::-webkit-scrollbar]:hidden"
        >
            <Link
                v-for="item in items"
                :key="item.key"
                :href="item.url"
                :aria-current="item.active ? 'page' : undefined"
                preserve-scroll
                :class="
                    cn(
                        'relative inline-flex min-h-11 shrink-0 snap-start items-center gap-1.5 rounded-xs text-body whitespace-nowrap text-muted-foreground transition-colors duration-(--dur-fast) after:absolute after:inset-x-0 after:bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-foreground after:transition-transform after:duration-(--dur-base) hover:text-foreground',
                        item.active && 'text-foreground after:scale-x-100',
                    )
                "
            >
                {{ item.label }}
                <span class="tabular text-sm text-muted-foreground">
                    {{ item.count }}
                </span>
            </Link>
        </nav>
    </div>
</template>
