<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, Heart, X } from '@lucide/vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import ShortlistEnquiryDialog from '@/components/site/shortlist/ShortlistEnquiryDialog.vue';
import SkeletonShape from '@/components/site/SkeletonShape.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { useCompare } from '@/composables/useCompare';
import { useShortlist } from '@/composables/useShortlist';
import { compareHref, shortlistHref } from '@/lib/shortlist';
import { notify } from '@/lib/toast';
import { index as projectsIndex } from '@/routes/site/projects';
import type { ShortlistPageProps } from '@/types/shortlist';
import type { ProjectCard as ProjectCardData } from '@/types/site';

// PG-20 / LAY-SHORTLIST in the premium language: light H1 with the count in
// 7 / the device note and the two actions in 5 on a hairline, then the
// cards 3 / 2 / 1 with Compare checkboxes and a Remove button (Undo toast).
// The IDs live in localStorage (FR-SHORT-001): the page visits itself with
// them (FR-SHORT-002) unless the URL already carries the same set (the
// header heart's link), and drops IDs the server did not return. A removal
// only rewrites the URL and props on the client (no request). "Enquire about
// all" opens the project enquiry with every project ticked (FR-SHORT-004).
// Storage blocked (FR-SHORT-005) → an explanation instead of the list.
const props = defineProps<ShortlistPageProps>();

const saved = useShortlist();
const selection = useCompare();

const loading = ref(false);
const enquireOpen = ref(false);
const enquireButton = useTemplateRef<{ $el: HTMLElement }>('enquire');

/** Cards taken off on this page, so Undo can put them back without a request. */
const removed = new Map<number, { card: ProjectCardData; index: number }>();

const projects = computed(() => props.shortlist.projects);

/** Before mount we only know what the URL asked for; without `?ids` that is nothing yet. */
const waiting = computed(
    () => loading.value || (!saved.ready.value && !props.shortlist.requested),
);
const unavailable = computed(() => saved.ready.value && !saved.available.value);
const empty = computed(
    () => !waiting.value && !unavailable.value && projects.value.length === 0,
);

const title = computed(() =>
    projects.value.length > 0 && !waiting.value
        ? `${props.copy.shortlist_title} (${projects.value.length})`
        : props.copy.shortlist_title,
);

const canCompare = computed(() => selection.count.value >= 2);

function sameSet(a: readonly number[], b: readonly number[]): boolean {
    return a.length === b.length && a.every((id) => b.includes(id));
}

function urlIds(): number[] {
    const raw = new URL(window.location.href).searchParams.get('ids') ?? '';

    return raw
        .split(',')
        .map((part) => Number(part.trim()))
        .filter((id) => Number.isInteger(id) && id > 0);
}

/** FR-SHORT-002: IDs we asked for that did not come back are gone for good. */
function dropUnknown(asked: readonly number[], found: readonly number[]): void {
    saved.keepOnly(
        saved.ids.value.filter(
            (id) => !asked.includes(id) || found.includes(id),
        ),
    );
}

function fetchStored(): void {
    const asked = [...saved.ids.value];

    loading.value = true;
    router.get(
        shortlistHref(asked),
        {},
        {
            replace: true,
            preserveScroll: true,
            preserveState: true,
            only: ['shortlist'],
            onSuccess: (page) => {
                const found = (page.props as unknown as ShortlistPageProps)
                    .shortlist.ids;
                dropUnknown(asked, found);
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

/** Client-only visit: the URL and props follow the stored list. */
function showStored(cards: ProjectCardData[]): void {
    router.replace({
        url: shortlistHref(saved.ids.value),
        preserveScroll: true,
        preserveState: true,
        props: (current) => ({
            ...current,
            shortlist: {
                ...(current as unknown as ShortlistPageProps).shortlist,
                requested: true,
                ids: cards.map((card) => card.id),
                projects: cards,
            },
        }),
    });
}

/** After a change in storage (here or in another tab). */
function sync(): void {
    const stored = saved.ids.value;
    const shown = projects.value;
    const missing = stored.filter(
        (id) => !shown.some((card) => card.id === id),
    );

    if (missing.every((id) => removed.has(id)) && missing.length > 0) {
        const cards = shown.filter((card) => stored.includes(card.id));

        for (const id of missing) {
            const entry = removed.get(id)!;
            cards.splice(Math.min(entry.index, cards.length), 0, entry.card);
            removed.delete(id);
        }

        showStored(cards);

        return;
    }

    if (missing.length > 0) {
        fetchStored();

        return;
    }

    if (shown.some((card) => !stored.includes(card.id))) {
        showStored(shown.filter((card) => stored.includes(card.id)));
    }
}

let started = false;

function start(): void {
    started = true;

    if (!saved.available.value) {
        return;
    }

    const asked = urlIds();

    if (props.shortlist.requested && sameSet(asked, saved.ids.value)) {
        dropUnknown(asked, props.shortlist.ids);
    } else if (saved.ids.value.length > 0) {
        fetchStored();
    } else if (props.shortlist.requested) {
        showStored([]);
    }
}

// Both watchers are created in the hook, so they stop with the page.
onMounted(() => {
    watch(
        saved.ready,
        (ready) => {
            if (ready && !started) {
                start();
            }
        },
        { immediate: true },
    );

    watch(
        () => [...saved.ids.value],
        () => {
            if (started && saved.available.value) {
                sync();
            }
        },
    );
});

function remove(card: ProjectCardData): void {
    const index = projects.value.findIndex((item) => item.id === card.id);
    const storeIndex = saved.ids.value.indexOf(card.id);

    removed.set(card.id, { card, index });
    saved.remove(card.id);

    notify.success(`${card.name} removed from your shortlist`, {
        action: {
            label: 'Undo',
            onClick: () => saved.add(card.id, card.slug, storeIndex),
        },
    });
}

function openEnquiry(): void {
    enquireOpen.value = true;
}
</script>

<template>
    <div>
        <section
            aria-labelledby="shortlist-heading"
            class="mx-auto max-w-content px-4 pt-8 pb-(--section-lg) sm:px-6 lg:px-8 lg:pt-12 xl:px-10"
        >
            <Breadcrumbs :items="shortlist.breadcrumbs" />

            <div
                class="mt-8 grid gap-6 border-b border-border pb-8 lg:mt-12 lg:grid-cols-12 lg:items-end lg:gap-8 lg:pb-10"
            >
                <h1
                    id="shortlist-heading"
                    class="text-h1 text-balance lg:col-span-7"
                >
                    {{ title }}
                </h1>
                <div class="flex flex-col gap-5 lg:col-span-5">
                    <p
                        class="flex items-start gap-2 text-sm text-pretty text-muted-foreground"
                    >
                        <Heart
                            class="mt-0.5 size-4 shrink-0 text-primary"
                            aria-hidden="true"
                        />
                        {{ copy.shortlist_note }}
                    </p>
                    <div
                        v-if="projects.length > 0 && !unavailable"
                        class="flex flex-wrap items-center gap-3"
                    >
                        <Button
                            ref="enquire"
                            type="button"
                            aria-haspopup="dialog"
                            @click="openEnquiry"
                        >
                            Enquire about all
                        </Button>
                        <Button v-if="canCompare" variant="outline" as-child>
                            <Link :href="compareHref(selection.ids.value)">
                                Compare selected ({{ selection.count.value }})
                            </Link>
                        </Button>
                        <Button
                            v-else
                            type="button"
                            variant="outline"
                            disabled
                            aria-describedby="shortlist-compare-hint"
                        >
                            Compare selected ({{ selection.count.value }})
                        </Button>
                    </div>
                    <p
                        v-if="
                            projects.length > 0 && !unavailable && !canCompare
                        "
                        id="shortlist-compare-hint"
                        class="text-sm text-muted-foreground"
                    >
                        {{ copy.shortlist_compare_hint }}
                    </p>
                </div>
            </div>

            <div class="mt-10 lg:mt-12" :aria-busy="waiting ? 'true' : 'false'">
                <EmptyState
                    v-if="unavailable"
                    variant="error"
                    :title="copy.shortlist_empty_title"
                    :body="copy.shortlist_unavailable_body"
                    title-as="h2"
                >
                    <Link
                        :href="projectsIndex.url()"
                        :class="buttonVariants({ variant: 'outline' })"
                    >
                        Explore projects
                    </Link>
                </EmptyState>

                <EmptyState
                    v-else-if="empty"
                    :icon="Heart"
                    :title="copy.shortlist_empty_title"
                    :body="copy.shortlist_empty_body"
                    title-as="h2"
                >
                    <Link :href="projectsIndex.url()" :class="buttonVariants()">
                        Explore projects
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </EmptyState>

                <template v-else-if="waiting && projects.length === 0">
                    <div
                        class="grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8"
                        aria-hidden="true"
                    >
                        <SkeletonShape
                            v-for="n in 3"
                            :key="n"
                            shape="project-card"
                        />
                    </div>
                    <noscript data-allow-mismatch="children">
                        <p class="mt-8 text-sm text-muted-foreground">
                            {{ copy.shortlist_empty_body }}
                        </p>
                    </noscript>
                </template>

                <ul
                    v-else
                    :class="[
                        'grid gap-x-6 gap-y-12 transition-opacity duration-(--dur-fast) sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8',
                        loading && 'opacity-60',
                    ]"
                >
                    <li
                        v-for="(project, index) in projects"
                        :key="project.id"
                        class="flex flex-col gap-3"
                    >
                        <ProjectCard
                            :project="project"
                            heading-as="h2"
                            :priority="index === 0"
                            compare
                            class="h-auto flex-1"
                        />
                        <Button
                            type="button"
                            variant="link"
                            class="self-start px-0 text-muted-foreground hover:text-foreground"
                            :aria-label="`Remove ${project.name} from your shortlist`"
                            @click="remove(project)"
                        >
                            <X class="size-4" aria-hidden="true" />
                            Remove
                        </Button>
                    </li>
                </ul>
            </div>
        </section>

        <ShortlistEnquiryDialog
            v-model:open="enquireOpen"
            :projects="projects"
            :overline="copy.shortlist_enquire_overline"
            :title="copy.shortlist_enquire_title"
            :text="copy.shortlist_enquire_text"
            :return-focus="enquireButton?.$el ?? null"
        />
    </div>
</template>
