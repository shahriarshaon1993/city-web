<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, Phone } from '@lucide/vue';
import { computed } from 'vue';
import FormDialog from '@/components/forms/FormDialog.vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import BrochureForm from '@/components/site/forms/BrochureForm.vue';
import ProjectInquiryForm from '@/components/site/forms/ProjectInquiryForm.vue';
import SiteVisitForm from '@/components/site/forms/SiteVisitForm.vue';
import { whatsappUrl } from '@/lib/contact';
import { formatPhone } from '@/lib/format';
import type { FormProject } from '@/types/forms';
import type { EnquiryKind, ProjectCopy, ProjectDetail } from '@/types/project';
import type { SitePageProps } from '@/types/site';

// CMP-ENQ-01 dialogs for Enquire / Book a site visit / Brochure, with the
// lead form of that kind (7.1: project_inquiry, site_visit, brochure_download)
// and the project prefilled. The shell is FormDialog (CMP-MODAL-01); below
// the form come the alternatives: hotline, WhatsApp and the contact page.
const props = defineProps<{
    kind: EnquiryKind;
    project: ProjectDetail;
    copy: ProjectCopy;
    /** No-JS / fallback contact page URL for this kind. */
    contactUrl: string;
    returnFocus: HTMLElement | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const page = usePage<SitePageProps>();
const contact = computed(() => page.props.site.contact);

const texts = computed(() => {
    const { copy } = props;
    const pick = {
        enquire: [copy.enquire_title, copy.enquire_text],
        visit: [copy.visit_title, copy.visit_text],
        brochure: [copy.brochure_title, copy.brochure_text],
    }[props.kind];

    return {
        title: pick[0].replace('{project}', props.project.name),
        text: pick[1],
    };
});

const formProject = computed<FormProject>(() => ({
    slug: props.project.slug,
    name: props.project.name,
    unit_types: props.project.unit_types.map((unit) => ({
        id: unit.id,
        name: unit.name,
    })),
}));

const whatsapp = computed(() => {
    const number = props.project.enquiry.whatsapp ?? contact.value.whatsapp;

    return number
        ? whatsappUrl(number, props.project.enquiry.whatsapp_text)
        : null;
});
</script>

<template>
    <FormDialog
        v-model:open="open"
        :overline="`${project.location.area}, ${project.location.city}`"
        :title="texts.title"
        :description="texts.text"
        :return-focus="returnFocus"
    >
        <ProjectInquiryForm v-if="kind === 'enquire'" :project="formProject" />
        <SiteVisitForm v-else-if="kind === 'visit'" :project="formProject" />
        <BrochureForm v-else :project="formProject" />

        <div class="flex flex-col gap-3">
            <p class="text-overline text-muted-foreground uppercase">
                {{ copy.dialog_alternative }}
            </p>
            <ul class="divide-y divide-border border-y border-border">
                <li v-if="contact.hotline">
                    <a
                        :href="`tel:${contact.hotline}`"
                        class="flex min-h-13 items-center gap-3 text-sm transition-colors duration-(--dur-fast) hover:text-primary"
                    >
                        <Phone
                            class="size-5 text-primary"
                            stroke-width="1.5"
                            aria-hidden="true"
                        />
                        <span class="flex-1">Call the hotline</span>
                        <span class="tabular text-muted-foreground">
                            {{ formatPhone(contact.hotline) }}
                        </span>
                    </a>
                </li>
                <li v-if="whatsapp">
                    <a
                        :href="whatsapp"
                        target="_blank"
                        rel="noopener"
                        class="flex min-h-13 items-center gap-3 text-sm transition-colors duration-(--dur-fast) hover:text-primary"
                    >
                        <SocialIcon
                            network="whatsapp"
                            class="size-5 text-whatsapp"
                        />
                        <span class="flex-1">
                            Chat on WhatsApp
                            <span class="sr-only">(opens in a new tab)</span>
                        </span>
                    </a>
                </li>
                <li>
                    <a
                        :href="contactUrl"
                        class="group/contact flex min-h-13 items-center gap-3 text-sm transition-colors duration-(--dur-fast) hover:text-primary"
                    >
                        <span class="flex-1"
                            >Write to us on the contact page</span
                        >
                        <ArrowRight
                            class="size-4 transition-transform duration-(--dur-base) group-hover/contact:translate-x-1"
                            aria-hidden="true"
                        />
                    </a>
                </li>
            </ul>
        </div>
    </FormDialog>
</template>
