<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { DirectoryArea } from '@/types/location';

// PG-05 area tile (5.1, direction.md §6 "Locations"), shared with the
// locations_grid block (6.2): rounded-xl photo, overline project count in
// teal ("No projects yet" muted), light name; no surface. The whole tile is
// the name's link. `feature` swaps the compact tile for the larger photo.
withDefaults(
    defineProps<{
        area: DirectoryArea;
        /** Overline for an area without projects. */
        noProjects: string;
        headingTag?: 'h2' | 'h3';
        feature?: boolean;
        priority?: boolean;
    }>(),
    { headingTag: 'h3', feature: false, priority: false },
);

function countLabel(count: number, noProjects: string): string {
    if (count === 0) {
        return noProjects;
    }

    return count === 1 ? '1 project' : `${formatNumber(count)} projects`;
}
</script>

<template>
    <article
        class="media-card group/tile relative flex flex-col gap-4 rounded-xl has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-4 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid"
    >
        <ResponsiveImage
            :image="area.image"
            :preset="feature ? 'area-feature' : 'area-tile'"
            img-class="media-zoom"
            :priority="priority"
            class="overflow-hidden rounded-xl"
        />
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 flex-col gap-1">
                <p
                    :class="
                        cn(
                            'text-overline uppercase',
                            area.count === 0
                                ? 'text-muted-foreground'
                                : 'text-primary',
                        )
                    "
                >
                    {{ countLabel(area.count, noProjects) }}
                </p>
                <component
                    :is="headingTag"
                    :class="
                        cn(
                            'font-display text-balance',
                            feature ? 'text-h3' : 'text-h4',
                        )
                    "
                >
                    <Link
                        :href="area.url"
                        class="transition-colors duration-(--dur-fast) outline-none group-hover/tile:text-primary after:absolute after:inset-0 after:rounded-xl after:content-['']"
                    >
                        {{ area.name }}
                    </Link>
                </component>
            </div>
            <ArrowRight
                class="mt-6 size-5 shrink-0 text-muted-foreground transition-[color,transform] duration-(--dur-base) group-hover/tile:text-primary motion-safe:group-hover/tile:translate-x-0.5 max-sm:hidden"
                aria-hidden="true"
            />
        </div>
    </article>
</template>
