<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, Scale } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import CompareTable from '@/components/site/compare/CompareTable.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { useCompare } from '@/composables/useCompare';
import { useShortlist } from '@/composables/useShortlist';
import { track } from '@/lib/analytics';
import { compareHref, shortlistHref } from '@/lib/shortlist';
import { index as projectsIndex } from '@/routes/site/projects';
import type { ComparePageProps } from '@/types/shortlist';
import type { ProjectCard } from '@/types/site';

// PG-21 / LAY-COMPARE: light H1 7 / intro 5 on a hairline, a legend line,
// then CMP-COMPARE-01. The URL's IDs win (the bar's link, a shared link)
// and become the tab's selection selection; `/compare` without IDs (footer
// link) loads the current selection. Removing a column revisits the page
// with the rest, so the "differs" tint is recomputed by the server.
// `compare_open` fires once per page view with 2+ projects (SEO-ANA-004).
const props = defineProps<ComparePageProps>();

const selection = useCompare();
const saved = useShortlist();
const loading = ref(false);

const projects = computed(() => props.compare.projects);
const waiting = computed(
    () => loading.value || (!selection.ready.value && !props.compare.requested),
);

function visit(ids: number[]): void {
    loading.value = true;
    router.get(
        compareHref(ids),
        {},
        {
            replace: true,
            preserveScroll: true,
            preserveState: true,
            only: ['compare'],
            onSuccess: (page) => {
                selection.replace(
                    (page.props as unknown as ComparePageProps).compare
                        .projects,
                );
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}

onMounted(() => {
    if (props.compare.requested) {
        selection.replace(props.compare.projects);
    } else if (selection.count.value > 0) {
        visit([...selection.ids.value]);
    }

    if (props.compare.projects.length >= 2) {
        track('compare_open', { count: props.compare.projects.length });
    }
});

function remove(project: ProjectCard): void {
    selection.remove(project.id);
    visit(
        projects.value
            .filter((item) => item.id !== project.id)
            .map((item) => item.id),
    );
}

function clear(): void {
    selection.clear();
    visit([]);
}
</script>

<template>
    <section
        aria-labelledby="compare-heading"
        class="mx-auto max-w-content px-4 pt-8 pb-(--section-lg) sm:px-6 lg:px-8 lg:pt-12 xl:px-10"
    >
        <Breadcrumbs :items="compare.breadcrumbs" />

        <div
            class="mt-8 grid gap-6 border-b border-border pb-8 lg:mt-12 lg:grid-cols-12 lg:items-end lg:gap-8 lg:pb-10"
        >
            <h1 id="compare-heading" class="text-h1 text-balance lg:col-span-7">
                {{ copy.compare_title }}
            </h1>
            <div class="flex flex-col gap-4 lg:col-span-5">
                <p class="text-lead text-pretty text-muted-foreground">
                    {{ copy.compare_intro }}
                </p>
                <div
                    v-if="projects.length > 0"
                    class="flex flex-wrap items-center gap-x-6 gap-y-2"
                >
                    <Link
                        v-if="saved.count.value > 0"
                        :href="shortlistHref(saved.ids.value)"
                        class="group/link inline-flex min-h-11 items-center gap-2 text-sm text-foreground transition-colors duration-(--dur-fast) hover:text-primary"
                    >
                        Your shortlist ({{ saved.count.value }})
                        <ArrowRight
                            class="size-4 transition-transform duration-(--dur-fast) group-hover/link:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                    <Button
                        type="button"
                        variant="link"
                        class="px-0"
                        @click="clear"
                    >
                        Clear the comparison
                    </Button>
                </div>
            </div>
        </div>

        <div
            :aria-busy="waiting ? 'true' : 'false'"
            :class="[
                'mt-8 transition-opacity duration-(--dur-fast) lg:mt-10',
                loading && 'opacity-60',
            ]"
        >
            <EmptyState
                v-if="projects.length === 0 && !waiting"
                :icon="Scale"
                :title="copy.compare_empty_title"
                :body="copy.compare_empty_body"
                title-as="h2"
            >
                <Link :href="projectsIndex.url()" :class="buttonVariants()">
                    Explore projects
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </EmptyState>

            <template v-else-if="projects.length > 0">
                <p
                    class="mb-4 flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <span
                        class="size-4 rounded-xs border border-border bg-surface-brand"
                        aria-hidden="true"
                    />
                    {{ copy.compare_legend }}
                </p>
                <CompareTable
                    :projects="projects"
                    :groups="compare.groups"
                    :add-url="projectsIndex.url()"
                    :add-label="copy.compare_add"
                    @remove="remove"
                />
            </template>
        </div>
    </section>
</template>
