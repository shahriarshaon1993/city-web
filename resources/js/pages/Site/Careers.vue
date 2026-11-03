<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BlockRenderer from '@/components/blocks/BlockRenderer.vue';
import CareerFacets from '@/components/site/careers/CareerFacets.vue';
import JobRows from '@/components/site/careers/JobRows.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import JobApplicationForm from '@/components/site/forms/JobApplicationForm.vue';
import { Button } from '@/components/ui/button';
import type { CareersPageProps } from '@/types/careers';

// PG-15 / LAY-CAREERS: the builder intro (the first block owns the H1), then
// "Open roles" — a light H2 on a hairline with the count, the facet rows and
// hairline role rows. A filter with no match shows an empty state; with no
// open roles at all the general CV drop takes the section (FR-CAR-004) in
// the form-block layout (text 5 / greige action panel 7).
const props = defineProps<CareersPageProps>();

const careers = computed(() => props.careers);
const hasOpenings = computed(() => careers.value.total_open > 0);

const countLabel = computed(() => {
    const count = careers.value.roles.length;

    return count === 1
        ? props.copy.roles_count_one
        : props.copy.roles_count.replace('{n}', String(count));
});
</script>

<template>
    <div>
        <BlockRenderer :blocks="blocks" />

        <section
            id="open-roles"
            aria-labelledby="open-roles-heading"
            class="scroll-mt-24 py-(--section-lg)"
        >
            <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8 xl:px-10">
                <template v-if="hasOpenings">
                    <div
                        v-reveal
                        class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2 border-b border-border pb-5 lg:pb-6"
                    >
                        <h2
                            id="open-roles-heading"
                            class="font-display text-h2 text-balance"
                        >
                            {{ copy.roles_heading }}
                        </h2>
                        <p
                            class="text-overline text-muted-foreground uppercase"
                            aria-live="polite"
                        >
                            {{ countLabel }}
                        </p>
                    </div>

                    <CareerFacets
                        :facets="careers.facets"
                        class="border-b border-border"
                    />

                    <JobRows
                        v-if="careers.roles.length > 0"
                        :roles="careers.roles"
                        :apply-by-label="copy.apply_by_label"
                        class="mt-10 lg:mt-14"
                    />

                    <EmptyState
                        v-else
                        variant="no-results"
                        :title="copy.no_match_title"
                        :body="copy.no_match_body"
                        class="mt-6"
                    >
                        <Button as-child variant="outline">
                            <Link href="/careers" preserve-scroll>
                                {{ copy.no_match_action }}
                            </Link>
                        </Button>
                    </EmptyState>
                </template>

                <div
                    v-else
                    class="grid gap-10 lg:grid-cols-12 lg:items-start lg:gap-x-8 xl:gap-x-10"
                >
                    <div v-reveal class="flex flex-col gap-6 lg:col-span-5">
                        <h2
                            id="open-roles-heading"
                            class="font-display text-h2 text-balance"
                        >
                            {{ copy.no_openings_title }}
                        </h2>
                        <p
                            class="max-w-prose text-lead text-pretty text-muted-foreground"
                        >
                            {{ copy.no_openings_body }}
                        </p>
                    </div>
                    <div
                        class="flex flex-col gap-6 rounded-xl bg-surface-subtle p-6 sm:p-10 lg:col-span-7"
                    >
                        <div class="flex flex-col gap-3">
                            <h3 class="font-display text-h3 text-balance">
                                {{ copy.cv_heading }}
                            </h3>
                            <p class="max-w-prose text-muted-foreground">
                                {{ copy.cv_intro }}
                            </p>
                        </div>
                        <JobApplicationForm
                            :job="null"
                            location="careers_page"
                            :success-title="copy.cv_success_title"
                            :success-text="copy.cv_success_text"
                            :success-toast="copy.success_toast"
                        />
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
