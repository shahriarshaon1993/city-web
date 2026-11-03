<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, CircleAlert, Search } from '@lucide/vue';
import { computed, onMounted, ref, useId, useTemplateRef } from 'vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import NewsCard from '@/components/site/NewsCard.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import { Button } from '@/components/ui/button';
import { search as searchRoute } from '@/routes/site';
import type { SearchPageProps } from '@/types/site-search';

// PG-19 / LAY-SEARCH: one narrow column — a large query box (a real GET
// form, so it works without JavaScript; focused on arrival only when the
// query is empty), the result count, then the groups Projects (horizontal
// cards), News (compact rows) and Pages (hairline rows), each with its
// "see all" link. Under 2 characters the minimum-length message shows
// instead of results (FR-SRCH-010); no results → empty state + popular areas.
const props = defineProps<SearchPageProps>();

const inputId = useId();
const input = useTemplateRef<HTMLInputElement>('input');
const value = ref(props.search.query);
const clientTooShort = ref(false);

const tooShort = computed(() => clientTooShort.value || props.search.too_short);
const hasQuery = computed(
    () => props.search.query !== '' && !props.search.too_short,
);

const heading = computed(() =>
    hasQuery.value
        ? props.copy.results_title.replace('{q}', props.search.query)
        : props.copy.title,
);

const summary = computed(() =>
    (props.search.total === 1 ? props.copy.summary_one : props.copy.summary)
        .replace('{n}', String(props.search.total))
        .replace('{q}', props.search.query),
);

function submit(): void {
    const query = value.value.trim();

    clientTooShort.value = query.length > 0 && query.length < 2;

    if (clientTooShort.value) {
        input.value?.focus();

        return;
    }

    router.get(searchRoute.url(), query === '' ? {} : { q: query });
}

function onInput(): void {
    if (clientTooShort.value && value.value.trim().length !== 1) {
        clientTooShort.value = false;
    }
}

onMounted(() => {
    if (props.search.query === '') {
        input.value?.focus();
    }
});
</script>

<template>
    <div
        class="mx-auto w-full max-w-narrow px-4 pt-8 pb-(--section-lg) sm:px-6 lg:pt-12"
    >
        <Breadcrumbs :items="search.breadcrumbs" />

        <h1 class="mt-8 text-h1 text-balance lg:mt-12">{{ heading }}</h1>

        <form
            role="search"
            :action="searchRoute.url()"
            method="get"
            class="mt-8 flex flex-col gap-2 border-b border-border pb-8"
            novalidate
            @submit.prevent="submit"
        >
            <label :for="inputId" class="text-sm font-medium">
                {{ copy.label }}
            </label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative sm:flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <input
                        :id="inputId"
                        ref="input"
                        v-model="value"
                        type="search"
                        name="q"
                        maxlength="100"
                        autocomplete="off"
                        enterkeyhint="search"
                        :placeholder="copy.placeholder"
                        :aria-invalid="tooShort || undefined"
                        :aria-describedby="
                            tooShort ? `${inputId}-error` : undefined
                        "
                        class="h-14 w-full rounded-md border border-input bg-background pr-4 pl-12 text-lg transition-[border-color,box-shadow] duration-(--dur-fast) outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/25 aria-invalid:border-destructive"
                        @input="onInput"
                    />
                </div>
                <Button type="submit" size="lg" class="h-14">
                    {{ copy.submit }}
                </Button>
            </div>
            <p
                v-if="tooShort"
                :id="`${inputId}-error`"
                class="flex items-start gap-1.5 text-sm text-destructive"
            >
                <CircleAlert
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>{{ copy.min_length }}</span>
            </p>
        </form>

        <template v-if="hasQuery">
            <p
                class="mt-8 text-overline text-muted-foreground uppercase"
                aria-live="polite"
            >
                {{ summary }}
            </p>

            <section
                v-for="group in search.groups"
                :key="group.key"
                :aria-labelledby="`search-${group.key}`"
                class="mt-12 first-of-type:mt-8"
            >
                <div
                    class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2 border-b border-border pb-4"
                >
                    <h2
                        :id="`search-${group.key}`"
                        class="font-display text-h3"
                    >
                        {{ group.heading }}
                        <span class="tabular text-body text-muted-foreground">
                            {{ group.total }}
                        </span>
                    </h2>
                    <Link
                        v-if="group.see_all"
                        :href="group.see_all.url"
                        class="group/all inline-flex min-h-11 items-center gap-2 text-body hover:text-primary"
                    >
                        {{ group.see_all.label }}
                        <ArrowRight
                            class="size-4 transition-transform duration-(--dur-fast) group-hover/all:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                </div>

                <ul
                    v-if="group.key === 'projects'"
                    class="mt-8 flex flex-col gap-8"
                >
                    <li
                        v-for="(project, index) in group.items"
                        :key="project.id"
                    >
                        <ProjectCard
                            :project="project"
                            variant="horizontal"
                            :priority="index === 0"
                        />
                    </li>
                </ul>

                <ul
                    v-else-if="group.key === 'news'"
                    class="divide-y divide-border"
                >
                    <li v-for="post in group.items" :key="post.id" class="py-6">
                        <NewsCard :post="post" variant="compact" />
                    </li>
                </ul>

                <ul v-else class="divide-y divide-border">
                    <li
                        v-for="page in group.items"
                        :key="page.url"
                        class="group/row relative flex flex-col gap-1.5 rounded-xs py-6 has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-4 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid"
                    >
                        <p
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ page.url }}
                        </p>
                        <h3
                            class="font-display text-h4 transition-colors duration-(--dur-fast) group-hover/row:text-primary"
                        >
                            <Link
                                :href="page.url"
                                class="outline-none after:absolute after:inset-0"
                            >
                                {{ page.title }}
                            </Link>
                        </h3>
                        <p class="text-body text-pretty text-muted-foreground">
                            {{ page.description }}
                        </p>
                    </li>
                </ul>
            </section>

            <EmptyState
                v-if="search.groups.length === 0"
                variant="no-results"
                :title="copy.empty_title.replace('{q}', search.query)"
                :body="copy.empty_body"
                class="mt-4"
            />
        </template>

        <section
            v-if="!hasQuery || search.groups.length === 0"
            aria-labelledby="search-suggestions"
            class="mt-12"
        >
            <h2
                id="search-suggestions"
                class="border-b border-border pb-4 text-overline text-muted-foreground uppercase"
            >
                {{ copy.suggestions_heading }}
            </h2>
            <ul class="grid sm:grid-cols-2 sm:gap-x-8">
                <li
                    v-for="area in search.suggestions"
                    :key="area.url"
                    class="border-b border-border"
                >
                    <Link
                        :href="area.url"
                        class="group/area flex min-h-14 items-center justify-between gap-4 py-3 hover:text-primary"
                    >
                        <span class="flex flex-col">
                            <span class="font-display text-lg">{{
                                area.name
                            }}</span>
                            <span
                                class="text-overline text-muted-foreground uppercase"
                            >
                                {{ area.city }} ·
                                {{
                                    copy.suggestion_count.replace(
                                        '{n}',
                                        String(area.count),
                                    )
                                }}
                            </span>
                        </span>
                        <ArrowRight
                            class="size-5 text-muted-foreground transition-[translate,color] duration-(--dur-fast) group-hover/area:translate-x-0.5 group-hover/area:text-primary"
                            stroke-width="1.5"
                            aria-hidden="true"
                        />
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
