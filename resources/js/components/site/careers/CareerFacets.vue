<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import type { CareerFacet } from '@/types/careers';

// PG-15 filters (LAY-CAREERS: department, location, type) as facet rows in
// the listing style (direction.md §6): overline label, text options with a
// muted count, the active one underlined by a 1 px foreground line. Real
// query links (owner 8.2) that keep the scroll position; an option without
// matches is shown disabled, not hidden. Rows scroll sideways on phones.
defineProps<{
    facets: CareerFacet[];
}>();

const itemClass =
    'relative inline-flex min-h-11 shrink-0 snap-start items-center gap-1.5 rounded-xs text-body whitespace-nowrap text-muted-foreground transition-colors duration-(--dur-fast) after:absolute after:inset-x-0 after:bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-foreground after:transition-transform after:duration-(--dur-base) hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';
</script>

<template>
    <div class="flex flex-col divide-y divide-border">
        <nav
            v-for="facet in facets"
            :key="facet.key"
            :aria-labelledby="`career-facet-${facet.key}`"
            class="flex min-w-0 items-center gap-5 py-1"
        >
            <span
                :id="`career-facet-${facet.key}`"
                class="w-24 shrink-0 text-overline text-muted-foreground uppercase lg:w-32"
            >
                {{ facet.label }}
            </span>
            <ul
                class="-my-1 flex min-w-0 snap-x [scrollbar-width:none] items-center gap-5 overflow-x-auto py-1 [&::-webkit-scrollbar]:hidden"
            >
                <li>
                    <Link
                        :href="facet.all.url"
                        preserve-scroll
                        :aria-current="facet.all.active ? 'true' : undefined"
                        :class="
                            cn(
                                itemClass,
                                facet.all.active &&
                                    'text-foreground after:scale-x-100',
                            )
                        "
                    >
                        {{ facet.all.label }}
                        <span class="tabular text-sm text-muted-foreground">
                            {{ facet.all.count }}
                        </span>
                    </Link>
                </li>
                <li v-for="option in facet.options" :key="option.value">
                    <Link
                        v-if="option.active || option.count > 0"
                        :href="option.url"
                        preserve-scroll
                        :aria-current="option.active ? 'true' : undefined"
                        :class="
                            cn(
                                itemClass,
                                option.active &&
                                    'text-foreground after:scale-x-100',
                            )
                        "
                    >
                        {{ option.label }}
                        <span class="tabular text-sm text-muted-foreground">
                            {{ option.count }}
                        </span>
                    </Link>
                    <span
                        v-else
                        aria-disabled="true"
                        :class="cn(itemClass, 'cursor-not-allowed opacity-50')"
                    >
                        {{ option.label }}
                        <span class="tabular text-sm">0</span>
                    </span>
                </li>
            </ul>
        </nav>
    </div>
</template>
