<script setup lang="ts">
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import ShortlistHeart from '@/components/site/ShortlistHeart.vue';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01 media: 4:5 frame (the parent rounds it) with MOT-CARD-IMG zoom, badges
// top-left (lifecycle, booking) and the shortlist heart top-right, outside
// the link (FR-SHORT-001). `fill` drops the ratio and covers the parent
// (horizontal and featured variants); the parent then owns the height. The
// slot paints over the photo (e.g. a scrim).
const props = withDefaults(
    defineProps<{
        project: ProjectCard;
        fill?: boolean;
        /** Off when the parent places the heart itself (featured). */
        withHeart?: boolean;
        /** The page's one LCP image (SEO-PERF-006): eager + fetchpriority high. */
        priority?: boolean;
        class?: string;
    }>(),
    { fill: false, withHeart: true, priority: false, class: undefined },
);
</script>

<template>
    <div :class="cn('relative overflow-hidden', props.class)">
        <ResponsiveImage
            :image="project.cover"
            preset="project-card"
            img-class="media-zoom"
            :priority="priority"
            :class="fill ? 'absolute inset-0 aspect-auto size-full' : undefined"
        />
        <slot />
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-linear-to-b from-stone-950/25 to-transparent"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute top-4 left-4 flex flex-wrap gap-1.5 pr-14"
        >
            <Badge
                variant="lifecycle"
                :value="project.lifecycle"
                on-media
                dot
                class="backdrop-blur-sm"
            >
                {{ project.lifecycle_label }}
            </Badge>
            <Badge
                v-if="project.booking_state !== 'none' && project.booking_label"
                variant="booking"
                :value="project.booking_state"
                on-media
                class="backdrop-blur-sm"
            >
                {{ project.booking_label }}
            </Badge>
        </div>
        <ShortlistHeart
            v-if="withHeart"
            :project-id="project.id"
            :project-name="project.name"
            class="absolute top-3 right-3 z-(--z-raised) backdrop-blur-sm"
        />
    </div>
</template>
