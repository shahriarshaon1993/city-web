<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { nextTick, ref, useTemplateRef } from 'vue';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
import { buttonVariants } from '@/components/ui/button';
import { useLeadForm } from '@/composables/useLeadForm';
import { accepted, email, minLength, phone, required } from '@/lib/forms/rules';
import { notify } from '@/lib/toast';
import type { FormProject } from '@/types/forms';
import type { SitePageProps } from '@/types/site';

// `brochure_download` (PG-04 gated brochure, FR-LEAD): name*, phone*, email,
// consent*. It stays on the page (FR-LEAD-005): the server answers with the
// file link in flash data, the form is replaced by an inline success state
// (UX-04, TOAST-07) and the download starts.
const props = defineProps<{
    project: FormProject;
}>();

const forms = usePage<SitePageProps>().props.forms;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;

const lead = useLeadForm({
    slug: 'brochure-download',
    location: 'brochure_dialog',
    initial: {
        name: '',
        phone_country: forms.default_country,
        phone: '',
        email: '',
        consent: false,
    },
    rules: {
        name: [
            required('Enter your full name.'),
            minLength(2, 'Your name needs at least 2 letters.'),
        ],
        phone: [required('Enter your phone number.'), phone(dial)],
        email: [email()],
        consent: [accepted()],
    },
    extra: () => ({ project: props.project.slug }),
});

const { summary, showSummary, summaryItems } = lead;

const downloadUrl = ref<string | null>(null);
const success = useTemplateRef<HTMLElement>('success');

function startDownload(url: string): void {
    const link = document.createElement('a');

    link.href = url;
    link.download = '';
    document.body.append(link);
    link.click();
    link.remove();
}

function submit(): void {
    lead.submit({
        onFlash: async (flash) => {
            const url = (flash as { download?: { url?: string } } | undefined)
                ?.download?.url;

            if (!url) {
                return;
            }

            downloadUrl.value = url;
            notify.success('Your brochure download has started.');
            startDownload(url);
            await nextTick();
            success.value?.focus();
        },
    });
}
</script>

<template>
    <div
        v-if="downloadUrl"
        ref="success"
        tabindex="-1"
        role="status"
        class="flex flex-col gap-4 outline-none"
    >
        <p class="font-display text-h4">Your download has started</p>
        <p class="text-body text-muted-foreground">
            If nothing happened, use the button below. Our sales team may call
            you with details about {{ project.name }}.
        </p>
        <a
            :href="downloadUrl"
            download
            :class="buttonVariants({ variant: 'outline', size: 'lg' })"
            class="self-start"
        >
            <Download aria-hidden="true" />
            Download the brochure
        </a>
    </div>

    <form
        v-else
        class="flex flex-col gap-5"
        novalidate
        @submit.prevent="submit()"
    >
        <ErrorSummary v-if="showSummary" ref="summary" :items="summaryItems" />

        <PersonFields />

        <FormFooter submit-label="Download brochure" busy-label="Preparing…" />
    </form>
</template>
