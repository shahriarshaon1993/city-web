<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import CtaBandBlock from '@/components/blocks/CtaBandBlock.vue';
import ProjectGrid from '@/components/blocks/ProjectGrid.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import MapFacade from '@/components/site/MapFacade.vue';
import DesignTeam from '@/components/site/project/DesignTeam.vue';
import EnquiryDialog from '@/components/site/project/EnquiryDialog.vue';
import EnquiryPanel from '@/components/site/project/EnquiryPanel.vue';
import GalleryGrid from '@/components/site/project/GalleryGrid.vue';
import NearbyPlaces from '@/components/site/project/NearbyPlaces.vue';
import ProjectAmenities from '@/components/site/project/ProjectAmenities.vue';
import ProjectDocuments from '@/components/site/project/ProjectDocuments.vue';
import ProjectFacts from '@/components/site/project/ProjectFacts.vue';
import ProjectFaq from '@/components/site/project/ProjectFaq.vue';
import ProjectGallery from '@/components/site/project/ProjectGallery.vue';
import ProjectOverview from '@/components/site/project/ProjectOverview.vue';
import ProjectProgress from '@/components/site/project/ProjectProgress.vue';
import ProjectSection from '@/components/site/project/ProjectSection.vue';
import ProjectTitle from '@/components/site/project/ProjectTitle.vue';
import ProjectVideo from '@/components/site/project/ProjectVideo.vue';
import SectionTabs from '@/components/site/project/SectionTabs.vue';
import UnitTypes from '@/components/site/project/UnitTypes.vue';
import { provideContactBarOverride } from '@/composables/useContactBar';
import { useLightbox } from '@/composables/useLightbox';
import type { LightboxSlide } from '@/composables/useLightbox';
import { formatBdtCompact, formatSft } from '@/lib/format';
import type {
    EnquiryKind,
    GalleryPhoto,
    ProjectPageProps,
} from '@/types/project';
import type { SitePageProps } from '@/types/site';

// PG-04 `/projects/{slug}` (LAY-PROJ-DETAIL): breadcrumb, gallery hero,
// title block, sticky section tabs, then in 8 columns Overview, At a
// glance, Amenities, Plans, Gallery (4.2), Video and tour, Design team,
// Progress, Location, Documents and FAQ (4.3). Sections without content
// are left out, with their tab (CMP-TABS-02). The enquiry panel sticks in
// columns 9–12 and ends with the content grid, before Similar projects;
// below 1024 px it is an inline card under the title (tablet) or before
// Similar projects (phones), plus the mobile contact bar (CMP-BAR-01).
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
        {
            id: 'video',
            label: copy.video_tab,
            shown: !!project.video || !!project.virtual_tour_url,
        },
        {
            id: 'progress',
            label: copy.progress_tab,
            shown: project.progress.length > 0,
        },
        {
            id: 'location',
            label: copy.location_tab,
            shown: !!project.coordinates || project.nearby.length > 0,
        },
        {
            id: 'documents',
            label: copy.documents_tab,
            shown: project.documents.length > 0,
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

// Without JS the actions go to the contact page with the lead type (7.1).
const enquiryLinks = computed<Record<EnquiryKind, string>>(() => ({
    enquire: enquireUrl.value,
    visit: `${enquireUrl.value}&type=site_visit`,
    brochure: `${enquireUrl.value}&type=brochure_download`,
}));

const similarViewAll = computed(() =>
    props.project.segment_url
        ? {
              label: `All ${props.project.segment_label.toLowerCase()} projects`,
              url: props.project.segment_url,
              new_tab: false,
          }
        : null,
);

const dialogKind = ref<EnquiryKind>('enquire');
const dialogOpen = ref(false);
const dialogOpener = ref<HTMLElement | null>(null);

function openDialog(kind: EnquiryKind): void {
    dialogOpener.value =
        document.activeElement instanceof HTMLElement
            ? document.activeElement
            : null;
    dialogKind.value = kind;
    dialogOpen.value = true;
}

provideContactBarOverride(() => ({
    whatsapp: props.project.enquiry.whatsapp,
    whatsappText: props.project.enquiry.whatsapp_text,
    onEnquire: () => openDialog('enquire'),
}));
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
                <EnquiryPanel
                    class="mt-4 hidden md:grid lg:hidden"
                    layout="inline"
                    :project="project"
                    :copy="copy"
                    :links="enquiryLinks"
                    @open="openDialog"
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

                    <ProjectSection
                        v-if="shown.has('video')"
                        id="video"
                        :heading="copy.video_heading"
                    >
                        <ProjectVideo
                            :video="project.video"
                            :tour-url="project.virtual_tour_url"
                            :tour-label="copy.tour_label"
                        />
                    </ProjectSection>

                    <ProjectSection
                        v-if="project.design_team.length > 0"
                        id="design-team"
                        :heading="copy.team_heading"
                    >
                        <DesignTeam :members="project.design_team" />
                    </ProjectSection>

                    <ProjectSection
                        v-if="shown.has('progress')"
                        id="progress"
                        :heading="copy.progress_heading"
                    >
                        <ProjectProgress
                            :updates="project.progress"
                            :stages="copy.progress_stages"
                            :project-name="project.name"
                            :timeline-heading="copy.progress_timeline_heading"
                        />
                    </ProjectSection>

                    <ProjectSection
                        v-if="shown.has('location')"
                        id="location"
                        :heading="copy.location_heading"
                    >
                        <div class="flex flex-col gap-12">
                            <MapFacade
                                v-if="project.coordinates"
                                :lat="project.coordinates.lat"
                                :lng="project.coordinates.lng"
                                :title="`Map of ${project.name}`"
                                :place="`${project.location.area}, ${project.location.city}`"
                                :address="project.address"
                            />
                            <div v-if="project.nearby.length > 0">
                                <h3 class="mb-4 font-display text-h4">
                                    {{ copy.nearby_heading }}
                                </h3>
                                <NearbyPlaces :places="project.nearby" />
                            </div>
                        </div>
                    </ProjectSection>

                    <ProjectSection
                        v-if="shown.has('documents')"
                        id="documents"
                        :heading="copy.documents_heading"
                    >
                        <ProjectDocuments
                            :documents="project.documents"
                            :request-url="enquiryLinks.brochure"
                            @request="openDialog('brochure')"
                        />
                    </ProjectSection>

                    <ProjectSection
                        v-if="project.faq.length > 0"
                        id="faq"
                        :heading="copy.faq_heading"
                    >
                        <ProjectFaq :items="project.faq" />
                    </ProjectSection>
                </div>

                <!-- CMP-ENQ-01: sticky until the end of the content grid. -->
                <div class="hidden pt-(--section-sm) lg:col-span-4 lg:block">
                    <EnquiryPanel
                        class="sticky-under-tabs sticky"
                        :project="project"
                        :copy="copy"
                        :links="enquiryLinks"
                        @open="openDialog"
                    />
                </div>
            </div>
        </div>

        <div
            class="mx-auto max-w-content px-4 pb-(--section-md) sm:px-6 md:hidden"
        >
            <EnquiryPanel
                layout="inline"
                :project="project"
                :copy="copy"
                :links="enquiryLinks"
                @open="openDialog"
            />
        </div>

        <section
            v-if="project.similar.length > 0"
            aria-labelledby="similar-heading"
            class="bg-surface-subtle py-(--section-md)"
        >
            <div class="mx-auto max-w-content px-4 sm:px-6 lg:px-8 xl:px-10">
                <BlockHeader
                    heading-tag="h2"
                    heading-id="similar-heading"
                    :heading="copy.similar_heading"
                    :view-all="similarViewAll"
                />
                <ProjectGrid :projects="project.similar" />
            </div>
        </section>

        <CtaBandBlock :block="cta" :first="false" />

        <EnquiryDialog
            v-model:open="dialogOpen"
            :kind="dialogKind"
            :project="project"
            :copy="copy"
            :contact-url="enquiryLinks[dialogKind]"
            :return-focus="dialogOpener"
        />
    </div>
</template>
