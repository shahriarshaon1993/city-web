<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import CtaBandBlock from '@/components/blocks/CtaBandBlock.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { LocationsPageProps } from '@/types/location';

// PG-05 `/locations` (LAY-LOC-LIST, FR-LOC-005): breadcrumb, light H1 +
// intro, then one row per city (alphabetical): the city as an H2 with its
// overline totals, sticky in 4 columns, beside its area tiles in 8 (3 per
// row; 3 on tablets, 2 compact 1:1 tiles on phones). Tiles are image +
// overline count + light name, no card surface; the whole tile is the
// name's link. The first tile is the page's LCP image. Ends with the CTA band.
const props = defineProps<LocationsPageProps>();

const firstSlug = props.directory.cities[0]?.areas[0]?.slug;

function countLabel(count: number): string {
    if (count === 0) {
        return props.directory.no_projects;
    }

    return count === 1 ? '1 project' : `${formatNumber(count)} projects`;
}
</script>

<template>
    <div>
        <section
            class="mx-auto max-w-content px-4 pt-8 sm:px-6 lg:px-8 lg:pt-12 xl:px-10"
        >
            <Breadcrumbs :items="directory.breadcrumbs" />
            <div
                class="mt-8 grid gap-6 lg:mt-12 lg:grid-cols-12 lg:items-end xl:gap-8"
            >
                <h1 class="font-display text-h1 text-balance lg:col-span-6">
                    {{ directory.title }}
                </h1>
                <p
                    class="max-w-prose text-body-lg text-pretty text-muted-foreground lg:col-span-6"
                >
                    {{ directory.intro }}
                </p>
            </div>
        </section>

        <div
            class="mx-auto flex max-w-content flex-col gap-16 px-4 pt-(--section-sm) pb-(--section-md) sm:px-6 lg:gap-24 lg:px-8 xl:px-10"
        >
            <section
                v-for="city in directory.cities"
                :key="city.name"
                :aria-labelledby="`city-${city.name}`"
                class="grid gap-8 border-t border-border pt-8 lg:grid-cols-12 lg:pt-10 xl:gap-8"
            >
                <div class="lg:col-span-4">
                    <div
                        class="flex flex-col gap-2 lg:sticky lg:top-[calc(var(--header-height)+2rem)]"
                    >
                        <p
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ formatNumber(city.areas.length) }}
                            {{ city.areas.length === 1 ? 'area' : 'areas' }}
                            ·
                            <span class="text-primary">
                                {{ countLabel(city.project_count) }}
                            </span>
                        </p>
                        <h2
                            :id="`city-${city.name}`"
                            class="font-display text-h2 text-balance"
                        >
                            {{ city.name }}
                        </h2>
                    </div>
                </div>

                <ul
                    v-reveal.stagger
                    class="grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 sm:gap-x-6 lg:col-span-8 xl:gap-x-8"
                >
                    <li v-for="area in city.areas" :key="area.slug">
                        <article
                            class="media-card group/tile relative flex flex-col gap-4 rounded-xl has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-4 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid"
                        >
                            <ResponsiveImage
                                :image="area.image"
                                preset="area-tile"
                                img-class="media-zoom"
                                :priority="area.slug === firstSlug"
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
                                        {{ countLabel(area.count) }}
                                    </p>
                                    <h3
                                        class="font-display text-h4 text-balance"
                                    >
                                        <Link
                                            :href="area.url"
                                            class="transition-colors duration-(--dur-fast) outline-none group-hover/tile:text-primary after:absolute after:inset-0 after:rounded-xl after:content-['']"
                                        >
                                            {{ area.name }}
                                        </Link>
                                    </h3>
                                </div>
                                <ArrowRight
                                    class="mt-6 size-5 shrink-0 text-muted-foreground transition-[color,transform] duration-(--dur-base) group-hover/tile:text-primary motion-safe:group-hover/tile:translate-x-0.5 max-sm:hidden"
                                    aria-hidden="true"
                                />
                            </div>
                        </article>
                    </li>
                </ul>
            </section>
        </div>

        <CtaBandBlock :block="cta" :first="false" />
    </div>
</template>
