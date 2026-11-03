<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { Button } from '@/components/ui/button';
import { COMPARE_MAX, useCompare } from '@/composables/useCompare';
import { useConsent } from '@/composables/useConsent';
import { compareHref } from '@/lib/shortlist';
import { cn } from '@/lib/utils';

// CMP-COMPARE-01 floating bar (FR-SHORT-003): bottom-centre, above the
// mobile contact bar (CMP-BAR-01) when that is shown; slides up
// (MOT-SLIDE-UP, transform + opacity). Three slots — a ticked project's
// thumbnail is its remove button, an empty slot is a greige square — then
// the count and names (≥ 640 / 768 px), Clear and "Compare (n)" (teal;
// from 2 projects, else a hint line says why). Floating UI, so it may carry a shadow
// (direction.md §4). Not shown on PG-20 (own Compare action) or PG-21,
// nor while the cookie card holds the bottom edge.
const props = defineProps<{
    /** The mobile contact bar is shown: sit above it. */
    lifted: boolean;
}>();

/** Read by SiteLayout to lift toasts above the bar. */
const shown = defineModel<boolean>('shown', { default: false });

const page = usePage();
const compare = useCompare();
const { bannerOpen } = useConsent();

const OWN_PAGES = ['Site/Compare', 'Site/Shortlist'];

const visible = computed(
    () =>
        compare.ready.value &&
        compare.count.value > 0 &&
        !OWN_PAGES.includes(page.component) &&
        !bannerOpen.value,
);

watch(visible, (value) => (shown.value = value), { immediate: true });

const slots = computed(() =>
    Array.from(
        { length: COMPARE_MAX },
        (_, index) => compare.items.value[index] ?? null,
    ),
);
const canCompare = computed(() => compare.count.value >= 2);
const href = computed(() => compareHref(compare.ids.value));
const names = computed(() =>
    compare.items.value.map((item) => item.name).join(' · '),
);

// Spoken after each tick or removal; the bar itself is not a live region.
const announcement = ref('');

watch(
    () => compare.count.value,
    (count, before) => {
        if (!compare.ready.value || before === undefined) {
            return;
        }

        announcement.value =
            count === 0
                ? 'Compare list cleared'
                : `${count} of ${COMPARE_MAX} projects selected to compare`;
    },
);
</script>

<template>
    <div>
        <p class="sr-only" aria-live="polite" aria-atomic="true">
            {{ announcement }}
        </p>
        <Transition
            enter-active-class="transition-[translate,opacity] duration-(--dur-slow) ease-(--ease-out-expo)"
            leave-active-class="transition-[translate,opacity] duration-(--dur-base) ease-(--ease-standard)"
            enter-from-class="translate-y-8 opacity-0"
            leave-to-class="translate-y-8 opacity-0"
        >
            <section
                v-if="visible"
                aria-labelledby="compare-bar-title"
                :class="
                    cn(
                        'fixed inset-x-4 z-(--z-compare-bar) mx-auto max-w-3xl rounded-xl border border-border bg-background/95 p-2 shadow-lg backdrop-blur-md transition-[bottom] duration-(--dur-base) ease-(--ease-standard) sm:inset-x-6 sm:p-3',
                        props.lifted
                            ? 'bottom-[calc(4rem+max(0.5rem,env(safe-area-inset-bottom))+0.75rem)]'
                            : 'bottom-[max(1rem,env(safe-area-inset-bottom))] lg:bottom-6',
                    )
                "
            >
                <h2 id="compare-bar-title" class="sr-only">
                    Projects to compare
                </h2>
                <div class="flex items-center gap-3 sm:gap-4">
                    <ul class="flex shrink-0 gap-1 sm:gap-2">
                        <li
                            v-for="(item, index) in slots"
                            :key="item?.id ?? `empty-${index}`"
                        >
                            <button
                                v-if="item"
                                type="button"
                                class="group/thumb relative block size-11 overflow-hidden rounded-md sm:size-14"
                                :aria-label="`Remove ${item.name} from compare`"
                                @click="compare.remove(item.id)"
                            >
                                <ResponsiveImage
                                    :image="item.cover"
                                    preset="mega-card"
                                    class="aspect-square size-full"
                                />
                                <span
                                    class="absolute inset-0 flex items-center justify-center bg-stone-950/0 text-white transition-colors duration-(--dur-fast) group-hover/thumb:bg-stone-950/50 group-focus-visible/thumb:bg-stone-950/50"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="absolute top-1 right-1 flex size-5 items-center justify-center rounded-full bg-white/90 text-stone-900"
                                    >
                                        <X class="size-3" stroke-width="2.5" />
                                    </span>
                                </span>
                            </button>
                            <span
                                v-else
                                class="block size-11 rounded-md bg-surface-subtle sm:size-14"
                                aria-hidden="true"
                            />
                        </li>
                    </ul>

                    <div class="hidden min-w-0 flex-1 flex-col gap-0.5 sm:flex">
                        <p
                            class="text-overline text-muted-foreground uppercase"
                        >
                            Compare
                            <span class="tabular text-primary"
                                >{{ compare.count.value }} /
                                {{ COMPARE_MAX }}</span
                            >
                        </p>
                        <p
                            class="hidden truncate text-sm md:block"
                            :title="names"
                        >
                            {{ names }}
                        </p>
                    </div>

                    <div
                        class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2"
                    >
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="sm:hidden"
                            aria-label="Clear the compare list"
                            @click="compare.clear()"
                        >
                            <X class="size-5" aria-hidden="true" />
                        </Button>
                        <Button
                            type="button"
                            variant="link"
                            class="hidden sm:inline-flex"
                            @click="compare.clear()"
                        >
                            Clear
                        </Button>
                        <Button v-if="canCompare" as-child>
                            <Link :href="href" class="group/cta">
                                Compare ({{ compare.count.value }})
                                <ArrowRight
                                    class="size-4 transition-transform duration-(--dur-fast) group-hover/cta:translate-x-0.5"
                                    aria-hidden="true"
                                />
                            </Link>
                        </Button>
                        <Button
                            v-else
                            type="button"
                            disabled
                            aria-describedby="compare-bar-hint"
                        >
                            Compare ({{ compare.count.value }})
                        </Button>
                    </div>
                </div>
                <p
                    v-if="!canCompare"
                    id="compare-bar-hint"
                    class="mt-2 border-t border-border px-1 pt-2 text-sm text-muted-foreground"
                >
                    Tick “Compare” on one more project to see them side by side.
                </p>
            </section>
        </Transition>
    </div>
</template>
