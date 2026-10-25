<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Search, SlidersHorizontal } from '@lucide/vue';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import BottomSheet from '@/components/site/BottomSheet.vue';
import SearchFields from '@/components/site/search/SearchFields.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    emptySearchFilters,
    projectSearchSummary,
    projectSearchUrl,
} from '@/lib/search';
import type {
    ProjectSearchFilters,
    ProjectSearchOptions,
} from '@/types/search';

// CMP-SEARCH-01, `hero` variant (4.1 adds `inline`). A real GET form to the
// listing (HERO-042); with JS the submit goes to the clean URL of a single
// facet or the normalised query (HERO-041, lib/search.ts). Below 768 px a
// trigger with the current filters opens the same fields in a bottom sheet
// (HERO-043); without JS the form shows there instead (noscript rule in
// app.blade.php).
const props = defineProps<{
    options: ProjectSearchOptions;
    initial?: Partial<ProjectSearchFilters>;
}>();

const filters = reactive<ProjectSearchFilters>({
    ...emptySearchFilters(),
    ...props.initial,
});

const sheetOpen = ref(false);
const processing = ref(false);

const summary = computed(() => projectSearchSummary(filters, props.options));

function submit(): void {
    router.visit(projectSearchUrl(filters, props.options), {
        onStart: () => {
            processing.value = true;
        },
        onFinish: () => {
            processing.value = false;
            sheetOpen.value = false;
        },
    });
}

// The sheet is a < 768 px pattern only.
let tabletQuery: MediaQueryList | undefined;

function closeOnTablet(event: MediaQueryListEvent): void {
    if (event.matches) {
        sheetOpen.value = false;
    }
}

const page = usePage();

watch(
    () => page.url,
    () => {
        sheetOpen.value = false;
    },
);

onMounted(() => {
    tabletQuery = window.matchMedia('(min-width: 768px)');
    tabletQuery.addEventListener('change', closeOnTablet);
});

onBeforeUnmount(() => {
    tabletQuery?.removeEventListener('change', closeOnTablet);
});
</script>

<template>
    <div>
        <form
            :action="options.action"
            method="get"
            role="search"
            aria-label="Search projects"
            class="project-search-form rounded-xl bg-background/95 p-3 text-foreground shadow-xl backdrop-blur-md max-md:hidden lg:flex lg:items-center lg:gap-2"
            @submit.prevent="submit"
        >
            <SearchFields
                v-model="filters"
                :options="options"
                layout="bar"
                id-prefix="hero-search"
                content-class="z-(--z-dropdown)"
            >
                <Button
                    type="submit"
                    variant="accent"
                    class="h-12 md:col-span-3 lg:shrink-0"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" class="size-5" />
                    <Search v-else class="size-5" aria-hidden="true" />
                    Search projects
                </Button>
            </SearchFields>
        </form>

        <BottomSheet v-model:open="sheetOpen" title="Search projects">
            <template #trigger>
                <Button
                    type="button"
                    variant="accent"
                    size="lg"
                    class="project-search-trigger h-auto min-h-13 w-full flex-col gap-0 py-2 md:hidden"
                >
                    <span class="flex items-center gap-2">
                        <SlidersHorizontal class="size-5" aria-hidden="true" />
                        Search projects
                    </span>
                    <span
                        v-if="summary.length > 0"
                        class="max-w-full truncate text-sm font-normal opacity-90"
                    >
                        {{ summary.join(' · ') }}
                    </span>
                </Button>
            </template>

            <form
                id="project-search-sheet"
                :action="options.action"
                method="get"
                role="search"
                aria-label="Search projects"
                class="pt-2"
                @submit.prevent="submit"
            >
                <SearchFields
                    v-model="filters"
                    :options="options"
                    layout="stacked"
                    id-prefix="sheet-search"
                    content-class="z-(--z-modal)"
                />
            </form>

            <template #footer>
                <Button
                    type="submit"
                    form="project-search-sheet"
                    variant="accent"
                    size="lg"
                    class="w-full"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" class="size-5" />
                    <Search v-else class="size-5" aria-hidden="true" />
                    Search projects
                </Button>
            </template>
        </BottomSheet>
    </div>
</template>
