<script setup lang="ts">
import BrochureForm from '@/components/site/forms/BrochureForm.vue';
import CallbackForm from '@/components/site/forms/CallbackForm.vue';
import ContactForm from '@/components/site/forms/ContactForm.vue';
import LandownerForm from '@/components/site/forms/LandownerForm.vue';
import NrbForm from '@/components/site/forms/NrbForm.vue';
import ProjectInquiryForm from '@/components/site/forms/ProjectInquiryForm.vue';
import SiteVisitForm from '@/components/site/forms/SiteVisitForm.vue';
import type { FormProject } from '@/types/forms';

// Local-only reference for 7.1: the seven lead forms on one page, each on the
// greige action panel they use in blocks and dialogs.
defineProps<{
    project: FormProject;
}>();
</script>

<template>
    <div
        class="mx-auto flex max-w-content flex-col gap-16 px-4 py-12 sm:px-6 lg:px-8 lg:py-16 xl:px-10"
    >
        <header class="flex max-w-2xl flex-col gap-3">
            <h1 class="text-h1">Lead forms</h1>
            <p class="text-lead text-muted-foreground">
                The seven forms of 7.1. Submitting a valid one redirects to the
                thank-you page; nothing is stored until 15.1.
            </p>
        </header>

        <section
            v-for="entry in [
                { id: 'project-inquiry', title: 'Project enquiry (2 steps)' },
                { id: 'site-visit', title: 'Site visit' },
                { id: 'brochure-download', title: 'Brochure download' },
                { id: 'callback', title: 'Call back' },
                { id: 'contact', title: 'Contact' },
                { id: 'landowner', title: 'Landowner' },
                { id: 'nrb', title: 'NRB' },
            ]"
            :id="entry.id"
            :key="entry.id"
            class="grid gap-6 lg:grid-cols-12 lg:gap-8"
        >
            <h2 class="text-h3 lg:col-span-4">{{ entry.title }}</h2>
            <div class="rounded-xl bg-surface-subtle p-6 sm:p-10 lg:col-span-8">
                <ProjectInquiryForm
                    v-if="entry.id === 'project-inquiry'"
                    :project="project"
                />
                <SiteVisitForm
                    v-else-if="entry.id === 'site-visit'"
                    :project="project"
                />
                <BrochureForm
                    v-else-if="entry.id === 'brochure-download'"
                    :project="project"
                />
                <CallbackForm
                    v-else-if="entry.id === 'callback'"
                    location="page_block"
                />
                <ContactForm
                    v-else-if="entry.id === 'contact'"
                    location="page_block"
                />
                <LandownerForm
                    v-else-if="entry.id === 'landowner'"
                    location="page_block"
                />
                <NrbForm v-else location="page_block" />
            </div>
        </section>
    </div>
</template>
