<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ProjectCardPrice from '@/components/site/project-card/ProjectCardPrice.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01 `mini` for map popups and the mobile map strip (CMP-MAP-01):
// a horizontal row — 4:3 thumbnail (mega-card preset), overline segment ·
// area, name, compact price row. One link (the name, stretched); no badges
// or heart, which do not fit a popup.
const props = withDefaults(
    defineProps<{
        project: ProjectCard;
        /** Highlighted in the mobile strip when its marker is active. */
        active?: boolean;
        class?: string;
    }>(),
    { active: false, class: undefined },
);
</script>

<template>
    <article
        :class="
            cn(
                'media-card group/card relative flex gap-3 rounded-lg text-foreground has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-2 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid',
                props.class,
            )
        "
    >
        <ResponsiveImage
            :image="project.cover"
            preset="mega-card"
            img-class="media-zoom"
            class="w-28 shrink-0 self-start overflow-hidden rounded-md"
        />
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <p
                :class="
                    cn(
                        'truncate text-overline uppercase',
                        active ? 'text-primary' : 'text-muted-foreground',
                    )
                "
            >
                {{ project.segment_label }} · {{ project.location.area }}
            </p>
            <p class="font-display text-base leading-snug text-balance">
                <Link
                    :href="project.url"
                    class="outline-none group-hover/card:text-primary after:absolute after:inset-0 after:rounded-lg after:content-['']"
                >
                    {{ project.name }}
                </Link>
            </p>
            <ProjectCardPrice :project="project" compact class="mt-auto" />
        </div>
    </article>
</template>
