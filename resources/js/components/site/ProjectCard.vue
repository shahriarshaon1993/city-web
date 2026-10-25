<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Bath, BedDouble, LandPlot, Ruler } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import ShortlistHeart from '@/components/site/ShortlistHeart.vue';
import { Badge } from '@/components/ui/badge';
import {
    formatBdtCompact,
    formatKatha,
    formatNumber,
    formatSftRange,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01. One link (the name, stretched over the card); the heart is a
// separate button above it. Facts are a <dl>, never headings. The price shows
// only when the server sent one (AC-PROJ-01). `featured` spans two grid
// columns ≥ 1280 — the parent grid item sets the span. Compare (FR-SHORT-003)
// arrives in 8.3; tilt (MOT-TILT) in 3.2.
const props = withDefaults(
    defineProps<{
        project: ProjectCard;
        variant?: 'default' | 'horizontal' | 'mini' | 'featured';
        headingAs?: 'h2' | 'h3' | 'h4';
        class?: string;
    }>(),
    {
        variant: 'default',
        headingAs: 'h3',
        class: undefined,
    },
);

type Fact = { key: string; label: string; icon: Component; value: string };

function range(min: number | null, max: number | null): string | null {
    if (min === null) {
        return null;
    }

    return max === null || max === min
        ? formatNumber(min)
        : `${formatNumber(min)}–${formatNumber(max)}`;
}

const facts = computed<Fact[]>(() => {
    const p = props.project;
    const list: Fact[] = [];
    const beds = range(p.beds_min, p.beds_max);
    const baths = range(p.baths_min, p.baths_max);

    if (p.size_min_sft !== null) {
        list.push({
            key: 'size',
            label: 'Size',
            icon: Ruler,
            value: formatSftRange(p.size_min_sft, p.size_max_sft),
        });
    }

    if (beds !== null) {
        list.push({
            key: 'beds',
            label: 'Bedrooms',
            icon: BedDouble,
            value: `${beds} ${p.beds_max === 1 ? 'bed' : 'beds'}`,
        });
    }

    if (baths !== null) {
        list.push({
            key: 'baths',
            label: 'Bathrooms',
            icon: Bath,
            value: `${baths} ${p.baths_max === 1 ? 'bath' : 'baths'}`,
        });
    }

    if (p.land_katha !== null) {
        list.push({
            key: 'land',
            label: 'Land',
            icon: LandPlot,
            value: formatKatha(p.land_katha),
        });
    }

    return list;
});

const horizontal = computed(() => props.variant === 'horizontal');
</script>

<template>
    <article
        :class="
            cn(
                'project-card group/card @container relative flex h-full rounded-lg bg-card text-card-foreground shadow-xs ring-1 ring-border has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-2 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid',
                horizontal ? 'flex-row' : 'flex-col',
                props.class,
            )
        "
    >
        <div
            :class="
                cn(
                    'relative shrink-0 overflow-hidden',
                    horizontal ? 'w-2/5 rounded-l-lg' : 'rounded-t-lg',
                )
            "
        >
            <ResponsiveImage
                :image="project.cover"
                preset="project-card"
                img-class="media-zoom"
            />
            <div
                class="pointer-events-none absolute top-3 left-3 flex flex-wrap gap-1.5 pr-14"
            >
                <Badge variant="lifecycle" :value="project.lifecycle" on-media>
                    {{ project.lifecycle_label }}
                </Badge>
                <Badge
                    v-if="
                        project.booking_state !== 'none' &&
                        project.booking_label
                    "
                    variant="booking"
                    :value="project.booking_state"
                    on-media
                >
                    {{ project.booking_label }}
                </Badge>
            </div>
            <ShortlistHeart
                :project-id="project.id"
                :project-name="project.name"
                class="absolute top-2 right-2 z-(--z-raised)"
            />
        </div>

        <div
            :class="
                cn(
                    'flex min-w-0 flex-1 flex-col gap-3',
                    variant === 'mini' ? 'p-4' : 'p-5',
                )
            "
        >
            <div class="flex flex-col gap-1.5">
                <p class="text-overline text-muted-foreground uppercase">
                    {{ project.location.area }}, {{ project.location.city }}
                </p>
                <component
                    :is="headingAs"
                    :class="
                        cn(
                            'text-balance',
                            variant === 'featured' ? 'text-h3' : 'text-h4',
                        )
                    "
                >
                    <Link
                        :href="project.url"
                        class="outline-none group-hover/card:text-primary after:absolute after:inset-0 after:rounded-lg after:content-['']"
                    >
                        {{ project.name }}
                    </Link>
                </component>
            </div>

            <dl
                v-if="variant !== 'mini' && facts.length > 0"
                class="flex flex-wrap gap-x-4 gap-y-2 text-sm"
            >
                <div
                    v-for="fact in facts"
                    :key="fact.key"
                    class="flex items-center gap-1.5 @max-[340px]:basis-[calc(50%-0.5rem)]"
                >
                    <dt>
                        <component
                            :is="fact.icon"
                            class="size-4 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span class="sr-only">{{ fact.label }}</span>
                    </dt>
                    <dd class="tabular font-medium">{{ fact.value }}</dd>
                </div>
            </dl>

            <div class="mt-auto border-t pt-3">
                <p
                    v-if="project.price_from_bdt !== null"
                    class="flex items-baseline gap-1.5"
                >
                    <span class="text-sm text-muted-foreground">From</span>
                    <span class="tabular font-display text-price">
                        {{ formatBdtCompact(project.price_from_bdt) }}
                    </span>
                </p>
                <p v-else class="text-sm text-muted-foreground">
                    {{ project.price_note ?? 'Price on request' }}
                </p>
            </div>
        </div>
    </article>
</template>
