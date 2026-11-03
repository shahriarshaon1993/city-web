<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useId } from 'vue';
import { cn } from '@/lib/utils';
import type { NewsCategoryLink } from '@/types/news';

// PG-12/13 category filter in the listing's facet-row style (direction.md §6,
// wins over CMP-TABS-01's pill): overline label, text links with a muted count,
// the active one underlined by a 1 px foreground line. Real links, so every
// category archive is a crawl path; scrolls sideways on small screens.
defineProps<{
    label: string;
    categories: NewsCategoryLink[];
    /** Slug of the active category; null = "All" (only when `allActive`). */
    active: string | null;
    allActive: boolean;
}>();

const labelId = useId();

const itemClass =
    'relative inline-flex min-h-11 shrink-0 snap-start items-center gap-1.5 rounded-xs text-body whitespace-nowrap text-muted-foreground transition-colors duration-(--dur-fast) after:absolute after:inset-x-0 after:bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-foreground after:transition-transform after:duration-(--dur-base) hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';
</script>

<template>
    <nav :aria-labelledby="labelId" class="flex min-w-0 items-center gap-5">
        <span
            :id="labelId"
            class="shrink-0 text-overline text-muted-foreground uppercase"
        >
            {{ label }}
        </span>
        <ul
            class="-my-1 flex min-w-0 snap-x [scrollbar-width:none] items-center gap-5 overflow-x-auto py-1 [&::-webkit-scrollbar]:hidden"
        >
            <li v-for="category in categories" :key="category.slug ?? 'all'">
                <Link
                    :href="category.url"
                    :aria-current="
                        category.slug === null
                            ? allActive
                                ? 'page'
                                : undefined
                            : category.slug === active
                              ? 'page'
                              : undefined
                    "
                    :class="
                        cn(
                            itemClass,
                            (category.slug === null
                                ? allActive
                                : category.slug === active) &&
                                'text-foreground after:scale-x-100',
                        )
                    "
                >
                    {{ category.name }}
                    <span class="tabular text-sm text-muted-foreground">
                        {{ category.count }}
                    </span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
