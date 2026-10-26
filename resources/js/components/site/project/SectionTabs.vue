<script setup lang="ts">
import {
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    useTemplateRef,
    watch,
} from 'vue';
import { isMotionReduced } from '@/motion/preference';

// CMP-TABS-02 section tab bar: sticky under the header (and hidden with it,
// `.sticky-under-header`), anchors to the page's sections — not ARIA tabs.
// The active link follows the scroll through an IntersectionObserver band
// (rootMargin -40% / -55%) and gets `aria-current`. Lenis scrolls anchor
// clicks itself; the offset (header + this bar) is `scroll-padding-top`
// (`html:has(.section-tabs)`). Empty sections are left out by the page.
// Below 1024 px the row scrolls sideways with faded edges. From 1024 px,
// once the bar is stuck, the project name and price fade in on the right
// (owner, fix 4.2); they repeat the title block, so they are aria-hidden.
const props = defineProps<{
    sections: { id: string; label: string }[];
    title?: string;
    summary?: string | null;
}>();

const active = ref<string | null>(null);
const stuck = ref(false);
const list = useTemplateRef<HTMLElement>('list');
const nav = useTemplateRef<HTMLElement>('nav');
let observer: IntersectionObserver | null = null;
let stuckObserver: IntersectionObserver | null = null;

onMounted(() => {
    const visible = new Set<string>();

    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    visible.add(entry.target.id);
                } else {
                    visible.delete(entry.target.id);
                }
            }

            const first = props.sections.find((section) =>
                visible.has(section.id),
            );

            if (first) {
                active.value = first.id;
            } else if (
                visible.size === 0 &&
                props.sections[0] &&
                (document
                    .getElementById(props.sections[0].id)
                    ?.getBoundingClientRect().top ?? 0) >
                    window.innerHeight / 2
            ) {
                // Scrolled back above the first section.
                active.value = null;
            }
        },
        { rootMargin: '-40% 0px -55% 0px' },
    );

    for (const section of props.sections) {
        const element = document.getElementById(section.id);

        if (element) {
            observer.observe(element);
        }
    }
});

// Stuck = the bar's top has reached the header line (80 px from 1024 px,
// where the summary shows), so a band starting 1 px lower clips it.
onMounted(() => {
    if (!nav.value) {
        return;
    }

    stuckObserver = new IntersectionObserver(
        ([entry]) => {
            stuck.value =
                !!entry &&
                entry.intersectionRatio < 1 &&
                entry.boundingClientRect.top <= 81;
        },
        { rootMargin: '-81px 0px 0px 0px', threshold: [1] },
    );
    stuckObserver.observe(nav.value);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    stuckObserver?.disconnect();
});

// Keeps the active link inside the sideways-scrolling row.
watch(active, async (id) => {
    await nextTick();
    const row = list.value;
    const link = row?.querySelector<HTMLElement>(`[data-section="${id}"]`);

    if (!row || !link || row.scrollWidth <= row.clientWidth) {
        return;
    }

    const left = link.offsetLeft - row.offsetLeft;

    if (
        left < row.scrollLeft ||
        left + link.offsetWidth > row.scrollLeft + row.clientWidth
    ) {
        row.scrollTo({
            left: Math.max(0, left - 24),
            behavior: isMotionReduced() ? 'auto' : 'smooth',
        });
    }
});
</script>

<template>
    <nav
        ref="nav"
        aria-label="Project sections"
        :data-stuck="stuck || undefined"
        class="section-tabs sticky-under-header group/tabs sticky z-(--z-sticky) border-y border-border bg-background/95 backdrop-blur-md"
    >
        <div
            class="mx-auto flex max-w-content items-center gap-8 sm:px-6 lg:px-8 xl:px-10"
        >
            <ul
                ref="list"
                class="flex min-w-0 [scrollbar-width:none] gap-6 overflow-x-auto mask-x-from-[calc(100%-1.5rem)] px-4 sm:px-0 lg:gap-8 lg:mask-none"
            >
                <li v-for="section in sections" :key="section.id">
                    <a
                        :href="`#${section.id}`"
                        :data-section="section.id"
                        :aria-current="
                            active === section.id ? 'true' : undefined
                        "
                        class="relative inline-flex h-14 items-center text-sm whitespace-nowrap text-muted-foreground transition-colors duration-(--dur-fast) after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-foreground after:transition-transform after:duration-(--dur-base) hover:text-foreground aria-[current=true]:text-foreground aria-[current=true]:after:scale-x-100"
                        @click="active = section.id"
                    >
                        {{ section.label }}
                    </a>
                </li>
            </ul>
            <p
                v-if="title"
                class="ml-auto hidden min-w-0 shrink items-baseline gap-3 opacity-0 transition-opacity duration-(--dur-base) group-data-stuck/tabs:opacity-100 lg:flex"
                aria-hidden="true"
            >
                <span class="truncate font-display text-body text-foreground">
                    {{ title }}
                </span>
                <span
                    v-if="summary"
                    class="tabular shrink-0 text-sm text-muted-foreground"
                >
                    {{ summary }}
                </span>
            </p>
        </div>
    </nav>
</template>
