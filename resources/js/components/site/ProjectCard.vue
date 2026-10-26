<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import { computed } from 'vue';
import ProjectCardFacts from '@/components/site/project-card/ProjectCardFacts.vue';
import ProjectCardMedia from '@/components/site/project-card/ProjectCardMedia.vue';
import ProjectCardPrice from '@/components/site/project-card/ProjectCardPrice.vue';
import ShortlistHeart from '@/components/site/ShortlistHeart.vue';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01 (DEC-46): no card surface — a rounded 4:5 photo, then name +
// segment, location, one line of facts and a hairline price row. One link
// (the name, stretched over the card); the heart is a separate button above
// it. `featured` is an editorial card with the text over the photo, so it
// fills its two grid columns ≥ 1280 (the parent sets the span) at the height
// of its row. Compare (FR-SHORT-003) arrives in 8.3. No MOT-TILT (owner, 3.2).
const props = withDefaults(
    defineProps<{
        project: ProjectCard;
        variant?: 'default' | 'horizontal' | 'mini' | 'featured';
        headingAs?: 'h2' | 'h3' | 'h4';
        /** The page's LCP image (listing: the first card). */
        priority?: boolean;
        class?: string;
    }>(),
    {
        variant: 'default',
        headingAs: 'h3',
        priority: false,
        class: undefined,
    },
);

const featured = computed(() => props.variant === 'featured');
const horizontal = computed(() => props.variant === 'horizontal');
const mini = computed(() => props.variant === 'mini');

const shell =
    'project-card group/card relative flex h-full rounded-xl has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-4 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid';
const stretchedLink =
    "outline-none after:absolute after:inset-0 after:rounded-xl after:content-['']";
</script>

<template>
    <!-- Featured: the photo sits below the text (-z-10 inside `isolate`) so
         the link's overlay still spans the whole card. -->
    <article
        v-if="featured"
        :class="
            cn(
                shell,
                'isolate min-h-112 flex-col justify-end overflow-hidden bg-stone-900 text-white',
                props.class,
            )
        "
    >
        <ProjectCardMedia
            :project="project"
            fill
            :with-heart="false"
            class="absolute inset-0 -z-10"
        >
            <div
                data-overlay
                class="absolute inset-0 bg-(image:--gradient-hero-bottom) [--overlay:0.9]"
                aria-hidden="true"
            />
        </ProjectCardMedia>
        <ShortlistHeart
            :project-id="project.id"
            :project-name="project.name"
            class="absolute top-3 right-3 z-(--z-raised) backdrop-blur-sm"
        />

        <div
            class="flex flex-col gap-4 p-6 [text-shadow:0_1px_2px_rgb(0_0_0/0.35)] sm:p-8"
        >
            <div class="flex max-w-xl flex-col gap-2">
                <p
                    class="flex items-center gap-1.5 text-overline text-white/80 uppercase"
                >
                    <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                    {{ project.location.area }}, {{ project.location.city }}
                </p>
                <component :is="headingAs" class="text-h2 text-balance">
                    <Link
                        :href="project.url"
                        :class="
                            cn(
                                stretchedLink,
                                'decoration-white/50 decoration-1 underline-offset-[6px] group-hover/card:underline',
                            )
                        "
                    >
                        {{ project.name }}
                    </Link>
                </component>
            </div>
            <ProjectCardFacts :project="project" tone="media" />
            <ProjectCardPrice
                :project="project"
                tone="media"
                class="max-w-xl"
            />
        </div>
    </article>

    <article
        v-else
        :class="
            cn(
                shell,
                'text-foreground',
                horizontal && '@container',
                props.class,
            )
        "
    >
        <div
            :class="
                cn(
                    'flex flex-1 flex-col',
                    mini ? 'gap-3' : 'gap-5',
                    horizontal && '@min-[28rem]:flex-row @min-[28rem]:gap-6',
                )
            "
        >
            <ProjectCardMedia
                :project="project"
                :fill="horizontal"
                :priority="priority"
                :class="
                    cn(
                        'shrink-0 rounded-xl',
                        horizontal &&
                            'aspect-4/3 @min-[28rem]:aspect-auto @min-[28rem]:min-h-56 @min-[28rem]:w-2/5',
                    )
                "
            />

            <div
                :class="
                    cn(
                        'flex min-w-0 flex-1 flex-col',
                        mini ? 'gap-2' : 'gap-3',
                        horizontal && '@min-[28rem]:py-2',
                    )
                "
            >
                <div class="flex items-start justify-between gap-4">
                    <component
                        :is="headingAs"
                        :class="
                            cn('text-balance', mini ? 'text-lg' : 'text-h4')
                        "
                    >
                        <Link
                            :href="project.url"
                            :class="
                                cn(
                                    stretchedLink,
                                    'transition-colors duration-(--dur-fast) group-hover/card:text-primary',
                                )
                            "
                        >
                            {{ project.name }}
                        </Link>
                    </component>
                    <p
                        v-if="!mini"
                        class="shrink-0 pt-1.5 text-overline text-muted-foreground uppercase"
                    >
                        {{ project.segment_label }}
                    </p>
                </div>
                <p
                    class="flex items-center gap-1.5 text-overline text-muted-foreground uppercase"
                >
                    <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                    <span class="truncate">
                        {{ project.location.area }},
                        {{ project.location.city }}
                    </span>
                </p>

                <ProjectCardFacts v-if="!mini" :project="project" />

                <ProjectCardPrice
                    :project="project"
                    :compact="mini"
                    class="mt-auto"
                />
            </div>
        </div>
    </article>
</template>
