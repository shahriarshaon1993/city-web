<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, useTemplateRef } from 'vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import JobRows from '@/components/site/careers/JobRows.vue';
import JobSummary from '@/components/site/careers/JobSummary.vue';
import JobApplicationForm from '@/components/site/forms/JobApplicationForm.vue';
import ProjectSection from '@/components/site/project/ProjectSection.vue';
import { provideContactBarOverride } from '@/composables/useContactBar';
import type { CareerJobPageProps } from '@/types/careers';

// PG-16 / LAY-CAREER-DETAIL: overline meta, light H1 and summary on a
// hairline; the sections (light H2 on a hairline) in 8 columns beside the
// sticky greige summary panel in 4 (above the content on phones); then the
// apply form in the form-block layout (text 5 / greige panel 7). On phones
// the contact bar's Enquire becomes "Apply now" (the sticky Apply). A closed
// role (≤ 30 days) keeps its content, says "Applications closed" and lists
// the other open roles instead of the form.
const props = defineProps<CareerJobPageProps>();

const job = computed(() => props.job);
const applyHeading = useTemplateRef<HTMLElement>('applyHeading');

function goToApply(): void {
    const target = document.getElementById('apply');
    const reduce = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    target?.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' });
    applyHeading.value?.focus({ preventScroll: true });
}

if (props.job.is_open) {
    provideContactBarOverride(() => ({
        whatsapp: null,
        whatsappText: null,
        label: props.copy.apply_now,
        onEnquire: goToApply,
    }));
}
</script>

<template>
    <div>
        <div
            class="mx-auto w-full max-w-content px-4 pt-8 sm:px-6 lg:px-8 lg:pt-12 xl:px-10"
        >
            <Breadcrumbs :items="job.breadcrumbs" />

            <header
                v-reveal.hero
                class="mt-8 grid gap-x-8 border-b border-border pb-10 lg:mt-12 lg:grid-cols-12 lg:pb-14"
            >
                <div class="flex flex-col gap-5 lg:col-span-8">
                    <p
                        data-hero-line
                        class="text-overline text-muted-foreground uppercase"
                    >
                        <span class="text-primary">{{ job.department }}</span>
                        <span aria-hidden="true"> · </span>
                        <span class="sr-only">, </span>
                        {{ job.location }}
                        <span aria-hidden="true"> · </span>
                        <span class="sr-only">, </span>
                        {{ job.employment_type_label }}
                    </p>
                    <h1 data-hero-line class="text-h1 text-balance">
                        {{ job.title }}
                    </h1>
                    <p
                        data-hero-line
                        class="max-w-prose text-lead text-pretty text-muted-foreground"
                    >
                        {{ job.summary }}
                    </p>
                </div>
            </header>

            <div class="grid gap-x-8 lg:grid-cols-12 xl:gap-x-10">
                <div
                    class="pt-8 lg:sticky lg:top-28 lg:col-span-4 lg:col-start-9 lg:row-start-1 lg:self-start lg:pt-(--section-sm)"
                >
                    <JobSummary :job="job" :copy="copy" />
                </div>
                <div class="lg:col-span-8 lg:row-start-1">
                    <ProjectSection
                        v-for="section in job.sections"
                        :id="section.id"
                        :key="section.id"
                        :heading="section.heading"
                    >
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <div
                            class="rich-text max-w-prose"
                            v-html="section.body"
                        />
                    </ProjectSection>
                </div>
            </div>
        </div>

        <section
            v-if="job.is_open"
            id="apply"
            aria-labelledby="apply-heading"
            class="scroll-mt-24 py-(--section-lg)"
        >
            <div
                class="mx-auto grid max-w-content gap-10 border-t border-border px-4 pt-(--section-sm) sm:px-6 lg:grid-cols-12 lg:items-start lg:gap-x-8 lg:px-8 xl:gap-x-10 xl:px-10"
            >
                <div v-reveal class="flex flex-col gap-6 lg:col-span-5">
                    <h2
                        id="apply-heading"
                        ref="applyHeading"
                        tabindex="-1"
                        class="font-display text-h2 text-balance outline-none"
                    >
                        {{ copy.apply_heading }}
                    </h2>
                    <p
                        class="max-w-prose text-lead text-pretty text-muted-foreground"
                    >
                        {{ copy.apply_intro }}
                    </p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <div
                        class="rich-text max-w-prose"
                        v-html="copy.apply_side"
                    />
                </div>
                <div
                    class="rounded-xl bg-surface-subtle p-6 sm:p-10 lg:col-span-7"
                >
                    <JobApplicationForm
                        :job="job.slug"
                        location="job_page"
                        :success-title="copy.success_title"
                        :success-text="copy.success_text"
                        :success-toast="copy.success_toast"
                    />
                </div>
            </div>
        </section>

        <section
            v-else-if="other_roles.length > 0"
            aria-labelledby="other-roles-heading"
            class="bg-surface-subtle py-(--section-lg)"
        >
            <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8 xl:px-10">
                <div
                    class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2 border-b border-border pb-5 lg:pb-6"
                >
                    <h2
                        id="other-roles-heading"
                        class="font-display text-h2 text-balance"
                    >
                        {{ copy.other_roles_heading }}
                    </h2>
                    <Link
                        href="/careers"
                        class="text-body underline-offset-4 hover:text-primary hover:underline"
                    >
                        {{ copy.closed_action }}
                    </Link>
                </div>
                <JobRows
                    :roles="other_roles"
                    :apply-by-label="copy.apply_by_label"
                    class="border-t-0"
                />
            </div>
        </section>
    </div>
</template>
