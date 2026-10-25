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

// CMP-CARD-01. One link (the name, stretched over the card); the heart is a
// separate button above it. `default`/`horizontal`/`mini` set the photo in a
// matte frame; `featured` is an editorial card with the facts over the photo,
// so it fills its two grid columns ≥ 1280 (the parent sets the span) at the
// height of its row. Compare (FR-SHORT-003) arrives in 8.3; tilt in 3.2.
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

const featured = computed(() => props.variant === 'featured');
const horizontal = computed(() => props.variant === 'horizontal');
const mini = computed(() => props.variant === 'mini');

const shell =
    'project-card group/card relative flex h-full rounded-2xl has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-2 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid';
const stretchedLink =
    "outline-none after:absolute after:inset-0 after:rounded-2xl after:content-['']";
</script>

<template>
    <!-- Featured: the photo sits below the text (-z-10 inside `isolate`) so
         the link's overlay still spans the whole card. -->
    <article
        v-if="featured"
        :class="
            cn(
                shell,
                'isolate min-h-112 flex-col justify-end overflow-hidden bg-stone-900 text-white shadow-sm',
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
                class="absolute inset-0 bg-(image:--gradient-hero-bottom) [--overlay:0.92]"
                aria-hidden="true"
            />
        </ProjectCardMedia>
        <ShortlistHeart
            :project-id="project.id"
            :project-name="project.name"
            class="absolute top-3 right-3 z-(--z-raised) backdrop-blur-sm"
        />

        <div
            class="flex flex-col gap-5 p-5 [text-shadow:0_1px_2px_rgb(0_0_0/0.35)] sm:p-7"
        >
            <div class="flex max-w-xl flex-col gap-2">
                <p
                    class="flex items-center gap-1.5 text-overline text-white/80 uppercase"
                >
                    <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                    {{ project.location.area }}, {{ project.location.city }}
                </p>
                <component :is="headingAs" class="text-h3 text-balance">
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
            <ProjectCardPrice :project="project" tone="media" />
        </div>
    </article>

    <article
        v-else
        :class="
            cn(
                shell,
                'flex-col bg-card p-2 text-card-foreground shadow-xs ring-1 ring-border',
                props.class,
            )
        "
    >
        <div :class="cn('flex flex-1 flex-col', horizontal && '@container')">
            <div
                :class="
                    cn(
                        'flex flex-1 flex-col',
                        horizontal && '@min-[28rem]:flex-row',
                    )
                "
            >
                <ProjectCardMedia
                    :project="project"
                    :fill="horizontal"
                    :class="
                        cn(
                            'shrink-0 rounded-xl',
                            horizontal &&
                                'aspect-4/3 @min-[28rem]:aspect-auto @min-[28rem]:min-h-52 @min-[28rem]:w-2/5',
                        )
                    "
                />

                <div
                    :class="
                        cn(
                            'flex min-w-0 flex-1 flex-col',
                            mini
                                ? 'gap-3 px-2 pt-3 pb-1'
                                : 'gap-4 px-3 pt-4 pb-2',
                            horizontal && '@min-[28rem]:px-5 @min-[28rem]:py-3',
                        )
                    "
                >
                    <div class="flex flex-col gap-1.5">
                        <p
                            class="flex items-center gap-1.5 text-overline text-muted-foreground uppercase"
                        >
                            <MapPin
                                class="size-3.5 shrink-0 text-primary"
                                aria-hidden="true"
                            />
                            <span class="truncate">
                                {{ project.location.area }},
                                {{ project.location.city }}
                            </span>
                        </p>
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
                    </div>

                    <ProjectCardFacts v-if="!mini" :project="project" />

                    <ProjectCardPrice
                        :project="project"
                        :compact="mini"
                        class="mt-auto"
                    />
                </div>
            </div>
        </div>
    </article>
</template>
