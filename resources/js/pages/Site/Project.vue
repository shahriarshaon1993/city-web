<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import GalleryGrid from '@/components/site/project/GalleryGrid.vue';
import ProjectAmenities from '@/components/site/project/ProjectAmenities.vue';
import ProjectFacts from '@/components/site/project/ProjectFacts.vue';
import ProjectGallery from '@/components/site/project/ProjectGallery.vue';
import ProjectOverview from '@/components/site/project/ProjectOverview.vue';
import ProjectSection from '@/components/site/project/ProjectSection.vue';
import ProjectTitle from '@/components/site/project/ProjectTitle.vue';
import SectionTabs from '@/components/site/project/SectionTabs.vue';
import UnitTypes from '@/components/site/project/UnitTypes.vue';
import { useLightbox } from '@/composables/useLightbox';
import type { LightboxSlide } from '@/composables/useLightbox';
import { formatBdtCompact, formatSft } from '@/lib/format';
import type { GalleryPhoto, ProjectPageProps } from '@/types/project';
import type { SitePageProps } from '@/types/site';

// PG-04 `/projects/{slug}` (LAY-PROJ-DETAIL), part I (4.2): breadcrumb,
// gallery hero, title block, sticky section tabs, then Overview, At a
// glance, Amenities, Plans and Gallery in the 8-column content column.
// Sections without content are left out, with their tab (CMP-TABS-02).
// 4.3 adds video, progress, location, documents, FAQ, similar projects and
// the enquiry panel in columns 9–12.
const props = defineProps<ProjectPageProps>();

const page = usePage<SitePageProps>();
const lightbox = useLightbox();

const photos = computed(() =>
    props.project.gallery.filter(
        (item): item is GalleryPhoto => item.image !== null,
    ),
);

const sections = computed(() => {
    const { project, copy } = props;

    return [
        {
            id: 'overview',
            label: copy.overview_tab,
            shown: !!project.overview || project.highlights.length > 0,
        },
        { id: 'at-a-glance', label: copy.facts_tab, shown: true },
        {
            id: 'amenities',
            label: copy.amenities_tab,
            shown: project.amenities.length > 0,
        },
        // Edge case: land projects have no unit types and no Plans tab.
        {
            id: 'plans',
            label: copy.units_tab,
            shown: project.unit_types.length > 0,
        },
        {
            id: 'gallery',
            label: copy.gallery_tab,
            shown: photos.value.length > 0,
        },
    ].filter((section) => section.shown);
});

const shown = computed(
    () => new Set(sections.value.map((section) => section.id)),
);

function openGallery(index: number): void {
    const slides: LightboxSlide[] = photos.value.map((photo) => ({
        image: photo.image,
        category: { value: photo.category, label: photo.category_label },
    }));

    void lightbox.open(slides, index, `${props.project.name} photos`);
}

function openPlan(index: number): void {
    const slides: LightboxSlide[] = props.project.unit_types.flatMap((unit) =>
        unit.floor_plan
            ? [
                  {
                      image: {
                          ...unit.floor_plan,
                          caption: `${unit.name} · ${formatSft(unit.size_sft)}`,
                          credit: unit.floor_plan.credit,
                      },
                  },
              ]
            : [],
    );

    void lightbox.open(slides, index, `${props.project.name} floor plans`);
}

// Shown in the stuck tab bar from 1024 px (same rule as the title block).
const priceSummary = computed(() =>
    props.project.price_from_bdt !== null
        ? `${props.copy.price_from_label} ${formatBdtCompact(props.project.price_from_bdt)}`
        : props.project.price_note,
);

const enquireUrl = computed(
    () =>
        `${page.props.site.header.cta.url}?project=${encodeURIComponent(props.project.slug)}`,
);
</script>

<template>
    <div>
        <div
            class="mx-auto max-w-content px-4 pt-6 sm:px-6 lg:px-8 lg:pt-8 xl:px-10"
        >
            <Breadcrumbs :items="project.breadcrumbs" />
            <ProjectGallery
                class="mt-6 lg:mt-8"
                :project="project"
                :photos="photos"
                @open="openGallery"
            />
            <div class="mt-10 grid gap-6 lg:mt-14 lg:grid-cols-12 xl:gap-8">
                <ProjectTitle
                    class="lg:col-span-8"
                    :project="project"
                    :copy="copy"
                />
            </div>
        </div>

        <div class="mt-10 lg:mt-14">
            <SectionTabs
                :sections="sections"
                :title="project.name"
                :summary="priceSummary"
            />

            <div
                class="mx-auto grid max-w-content gap-6 px-4 sm:px-6 lg:grid-cols-12 lg:px-8 xl:gap-8 xl:px-10"
            >
                <div class="min-w-0 lg:col-span-8">
                    <ProjectSection
                        v-if="shown.has('overview')"
                        id="overview"
                        :heading="copy.overview_heading"
                    >
                        <ProjectOverview
                            :overview="project.overview"
                            :narrative="project.design_narrative"
                            :highlights="project.highlights"
                        />
                    </ProjectSection>

                    <ProjectSection
                        id="at-a-glance"
                        :heading="copy.facts_heading"
                    >
                        <ProjectFacts
                            :facts="project.facts"
                            :custom-facts="project.custom_facts"
                        />
                    </ProjectSection>

                    <ProjectSection
                        v-if="shown.has('amenities')"
                        id="amenities"
                        :heading="copy.amenities_heading"
                    >
                        <ProjectAmenities :amenities="project.amenities" />
                    </ProjectSection>

                    <ProjectSection
                        v-if="shown.has('plans')"
                        id="plans"
                        :heading="copy.units_heading"
                    >
                        <UnitTypes
                            :units="project.unit_types"
                            :enquire-url="enquireUrl"
                            @open-plan="openPlan"
                        />
                    </ProjectSection>

                    <ProjectSection
                        v-if="shown.has('gallery')"
                        id="gallery"
                        :heading="copy.gallery_heading"
                    >
                        <GalleryGrid :photos="photos" @open="openGallery" />
                    </ProjectSection>
                </div>
            </div>
        </div>
    </div>
</template>
