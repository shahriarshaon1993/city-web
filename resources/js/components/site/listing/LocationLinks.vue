<script setup lang="ts">
import { ArrowUpRight } from '@lucide/vue';
import { useId } from 'vue';
import { formatNumber } from '@/lib/format';
import type { ListingHeader } from '@/types/listing';

// LAY-PROJ-LIST "Locations links": the top areas for this segment/status as
// crawlable links to the area pages (SEO-TEC-020, internal linking).
// Hairline rows in up to four columns on greige, not cards (direction.md §4).
defineProps<{
    heading: string;
    links: ListingHeader['location_links'];
}>();

const headingId = useId();
</script>

<template>
    <section
        v-if="links.length > 0"
        :aria-labelledby="headingId"
        class="bg-surface-subtle py-(--section-md)"
    >
        <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8 xl:px-10">
            <h2
                :id="headingId"
                class="border-b border-border pb-5 font-display text-h2 text-balance"
            >
                {{ heading }}
            </h2>
            <ul class="grid gap-x-6 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
                <li v-for="link in links" :key="link.url">
                    <a
                        :href="link.url"
                        class="group/area flex min-h-11 items-center justify-between gap-4 border-b border-border py-5 transition-colors duration-(--dur-fast) hover:text-primary"
                    >
                        <span class="flex flex-col gap-1">
                            <span
                                class="text-overline text-muted-foreground uppercase"
                            >
                                {{ link.city }} ·
                                {{ formatNumber(link.count) }}
                                {{ link.count === 1 ? 'project' : 'projects' }}
                            </span>
                            <span class="font-display text-h4">
                                {{ link.name }}
                            </span>
                        </span>
                        <ArrowUpRight
                            class="size-5 shrink-0 text-muted-foreground transition-[color,transform] duration-(--dur-base) group-hover/area:text-primary motion-safe:group-hover/area:translate-x-0.5 motion-safe:group-hover/area:-translate-y-0.5"
                            aria-hidden="true"
                        />
                    </a>
                </li>
            </ul>
        </div>
    </section>
</template>
