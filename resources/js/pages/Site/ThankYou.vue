<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import ProjectGrid from '@/components/blocks/ProjectGrid.vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { Button } from '@/components/ui/button';
import { trackLeadOnce } from '@/lib/analytics';
import { whatsappUrl } from '@/lib/contact';
import { home } from '@/routes';
import { index as projects } from '@/routes/site/projects';
import type { ThankYouProps } from '@/types/contact';
import type { SiteShared } from '@/types/site';

// PG-22 / LAY-THANKS in the premium language (direction.md): left-aligned,
// an overline with a thin check, a light H1, the confirmation, then three
// hairline steps and the actions; project forms end with related projects on
// greige. Only one coral invitation exists per view and it is not here:
// "Back to the project" / "Explore our projects" is teal. `generate_lead`
// (FR-LEAD-005) fires once per lead, from the one-request flash `lead`.
const props = defineProps<ThankYouProps>();

const site = computed(() => usePage<SiteShared>().props.site);
const whatsapp = computed(() =>
    site.value.contact.whatsapp
        ? whatsappUrl(site.value.contact.whatsapp, props.whatsapp_text)
        : null,
);

onMounted(() => {
    const lead = props.lead;

    if (!lead) {
        return;
    }

    // A shortlist enquiry is one lead per project, so one event each (FR-SHORT-004).
    const slugs =
        lead.project_slugs.length > 1
            ? lead.project_slugs
            : [lead.project_slug];

    for (const slug of slugs) {
        trackLeadOnce(
            slug && slugs.length > 1
                ? `${lead.reference}.${slug}`
                : lead.reference,
            {
                lead_type: lead.lead_type,
                project_slug: slug,
                form_location: lead.form_location,
            },
        );
    }
});
</script>

<template>
    <div>
        <section
            aria-labelledby="thanks-heading"
            class="mx-auto max-w-content px-4 pt-12 pb-(--section-md) sm:px-6 lg:px-8 lg:pt-20 xl:px-10"
        >
            <div v-reveal.hero class="flex max-w-3xl flex-col gap-5">
                <p
                    data-hero-line
                    class="flex items-center gap-2 text-overline text-primary uppercase"
                >
                    <Check class="size-4" aria-hidden="true" />
                    Request received
                </p>
                <h1
                    id="thanks-heading"
                    data-hero-line
                    class="text-h1 text-balance"
                >
                    {{ title }}
                </h1>
                <p
                    data-hero-line
                    class="max-w-prose text-lead text-pretty text-muted-foreground"
                >
                    {{ message }}
                </p>
                <div data-hero-line class="mt-3 flex flex-wrap gap-3">
                    <Button v-if="project" as-child size="lg">
                        <Link :href="project.url">
                            Back to {{ project.name }}
                        </Link>
                    </Button>
                    <Button v-else as-child size="lg">
                        <Link :href="projects()">Explore our projects</Link>
                    </Button>
                    <Button
                        v-if="whatsapp"
                        as="a"
                        :href="whatsapp"
                        target="_blank"
                        rel="noopener"
                        variant="outline"
                        size="lg"
                    >
                        <SocialIcon
                            network="whatsapp"
                            class="size-5 text-whatsapp"
                        />
                        WhatsApp us
                        <span class="sr-only">(opens in a new tab)</span>
                    </Button>
                    <Button as-child variant="link" size="lg">
                        <Link :href="home()" class="text-foreground">
                            Back to home
                        </Link>
                    </Button>
                </div>
            </div>

            <div
                class="mt-14 grid gap-x-8 gap-y-8 border-t border-border pt-10 lg:mt-20 lg:grid-cols-12 lg:pt-14 xl:gap-x-10"
            >
                <h2 class="text-h2 text-balance lg:col-span-4">
                    What happens next
                </h2>
                <ol
                    v-reveal.stagger
                    class="grid gap-x-8 gap-y-8 sm:grid-cols-3 lg:col-span-8"
                >
                    <li
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="flex flex-col gap-3"
                    >
                        <span
                            class="tabular text-stat font-light text-primary"
                            aria-hidden="true"
                        >
                            {{ String(index + 1).padStart(2, '0') }}
                        </span>
                        <h3 class="text-h4 text-balance">{{ step.title }}</h3>
                        <p class="text-pretty text-muted-foreground">
                            {{ step.text }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <section
            v-if="related && related.projects.length > 0"
            aria-labelledby="related-heading"
            class="bg-surface-subtle py-(--section-md)"
        >
            <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8 xl:px-10">
                <BlockHeader
                    heading-tag="h2"
                    heading-id="related-heading"
                    :heading="related.heading"
                    :view-all="{
                        label: 'View all projects',
                        url: projects().url,
                        new_tab: false,
                    }"
                />
                <ProjectGrid :projects="related.projects" />
            </div>
        </section>
    </div>
</template>
