<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ProjectSearch from '@/components/site/search/ProjectSearch.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { BlockLink, HeroBlockData } from '@/types/blocks';

// CMP-HERO-01 layout shared by the single and slider heroes: media slot,
// overlay (§2.4, min 35 % with a headline), text in the dark scope with the
// TOK-C27 shadow, CTAs, search (CMP-SEARCH-01) and the scroll cue. Height
// is reserved by min-height (HERO-033). Text is visible at first paint
// (HERO-032); MOT-REVEAL-HERO wipes the lines in on client visits only
// (`v-reveal.hero`, owner 3.1). The media layer has MOT-PARALLAX (≤ 8 %),
// the CTAs MOT-MAGNETIC (fine pointers only).
// `data-hero`: the mobile contact bar appears once this leaves the viewport.
defineOptions({ inheritAttrs: false });

const props = defineProps<{
    data: HeroBlockData;
    /** First block of the page: the headline is the H1 (CMS-PB-004). */
    first: boolean;
}>();

const overlay = computed(() => {
    const minimum = props.data.headline ? 35 : 0;

    return Math.min(80, Math.max(minimum, props.data.overlay_opacity)) / 100;
});

const overlayClass = computed(
    () =>
        ({
            solid: 'bg-[rgb(16_12_11/var(--overlay))]',
            'gradient-bottom': 'bg-(image:--gradient-hero-bottom)',
            'gradient-left': 'bg-(image:--gradient-hero-left)',
        })[props.data.overlay_style],
);

const heightClass = computed(
    () =>
        ({
            full: 'min-h-[max(100svh,560px)]',
            large: 'min-h-[80svh]',
            medium: 'min-h-[60svh]',
        })[props.data.height],
);

const centered = computed(() => props.data.text_align === 'center');
const withSearch = computed(
    () => props.data.show_search && props.data.search !== null,
);

const ctas = computed(() =>
    [
        // DEC-46: solid white + glass on the photo (overrides DEC-43's teal primary).
        { link: props.data.primary_cta, variant: 'light' as const },
        { link: props.data.secondary_cta, variant: 'on-media' as const },
    ].filter(
        (cta): cta is { link: BlockLink; variant: 'light' | 'on-media' } =>
            cta.link !== null,
    ),
);

function isInternal(link: BlockLink): boolean {
    return !link.new_tab && link.url.startsWith('/');
}

// MOT-CUE stops after the first scroll.
const cueStopped = ref(false);

function stopCue(): void {
    cueStopped.value = true;
}

onMounted(() => {
    window.addEventListener('scroll', stopCue, { once: true, passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', stopCue);
});
</script>

<template>
    <section
        v-bind="$attrs"
        v-parallax="8"
        data-hero
        :class="
            cn(
                'relative isolate flex flex-col overflow-hidden bg-stone-950',
                heightClass,
            )
        "
        :style="{ '--overlay': overlay }"
    >
        <!-- MOT-PARALLAX ≤ 8 %: the layer overflows by that share. -->
        <div data-parallax-layer class="absolute inset-x-0 -inset-y-[8%] -z-10">
            <slot name="media" />
            <div
                data-overlay
                :class="cn('absolute inset-0', overlayClass)"
                aria-hidden="true"
            />
        </div>

        <div
            :class="
                cn(
                    'mx-auto flex w-full max-w-content flex-1 flex-col px-4 pt-24 sm:px-6 lg:px-8 lg:pt-32 xl:px-10',
                    withSearch ? 'pb-6 md:pb-10' : 'pb-[15svh]',
                )
            "
        >
            <div
                :class="
                    cn(
                        'theme-dark flex flex-1 gap-6 bg-transparent text-foreground',
                        centered
                            ? 'flex-col items-center justify-center text-center'
                            : 'flex-col justify-end md:flex-row md:items-end md:justify-between',
                    )
                "
            >
                <div
                    v-reveal.hero
                    :class="
                        cn(
                            'flex max-w-4xl flex-col gap-5 [text-shadow:0_1px_2px_rgb(0_0_0/0.35)] md:gap-6',
                            centered && 'items-center',
                        )
                    "
                >
                    <p
                        v-if="data.eyebrow"
                        data-hero-line
                        class="text-overline text-white/80 uppercase"
                    >
                        {{ data.eyebrow }}
                    </p>
                    <component
                        :is="first ? 'h1' : 'h2'"
                        data-hero-line
                        class="font-display text-display text-balance text-white"
                    >
                        {{ data.headline }}
                    </component>
                    <p
                        v-if="data.subheadline"
                        data-hero-line
                        class="max-w-2xl text-lead text-pretty text-white/85"
                    >
                        {{ data.subheadline }}
                    </p>
                    <div
                        v-if="ctas.length > 0"
                        data-hero-ctas
                        :class="
                            cn(
                                'mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap',
                                centered && 'sm:justify-center',
                            )
                        "
                    >
                        <Button
                            v-for="cta in ctas"
                            :key="cta.link.label"
                            v-magnetic
                            as-child
                            :variant="cta.variant"
                            size="lg"
                            class="[text-shadow:none] max-sm:w-full"
                        >
                            <Link
                                v-if="isInternal(cta.link)"
                                :href="cta.link.url"
                            >
                                <span data-magnetic-label class="inline-flex">
                                    {{ cta.link.label }}
                                </span>
                            </Link>
                            <a
                                v-else
                                :href="cta.link.url"
                                :target="
                                    cta.link.new_tab ? '_blank' : undefined
                                "
                                :rel="cta.link.new_tab ? 'noopener' : undefined"
                            >
                                <span data-magnetic-label class="inline-flex">
                                    {{ cta.link.label }}
                                </span>
                            </a>
                        </Button>
                    </div>
                </div>

                <div
                    v-if="$slots.controls"
                    :class="
                        cn(
                            'flex shrink-0 items-center gap-2',
                            centered ? 'justify-center' : 'self-end',
                        )
                    "
                >
                    <slot name="controls" />
                </div>
            </div>

            <ProjectSearch
                v-if="withSearch && data.search"
                :options="data.search"
                class="mt-8 lg:mt-10"
            />

            <span
                v-if="data.height === 'full'"
                :class="
                    cn(
                        'mx-auto mt-4 hidden text-white/80 motion-reduce:hidden md:block',
                        !cueStopped && 'hero-cue',
                    )
                "
                aria-hidden="true"
            >
                <ChevronDown class="size-3" stroke-width="3" />
            </span>
        </div>
    </section>
</template>
