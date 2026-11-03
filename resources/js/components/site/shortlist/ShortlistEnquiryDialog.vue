<script setup lang="ts">
import { computed } from 'vue';
import FormDialog from '@/components/forms/FormDialog.vue';
import ProjectInquiryForm from '@/components/site/forms/ProjectInquiryForm.vue';
import type { FormProject } from '@/types/forms';
import type { ProjectCard } from '@/types/site';

// FR-SHORT-004 "Enquire about all": the project_inquiry form (CMP-FORM-02)
// with every shortlisted project ticked in its project list. The form is
// mounted per opening (v-if in FormDialog's content), so a reopened dialog
// starts from the current shortlist.
const props = defineProps<{
    projects: ProjectCard[];
    overline: string;
    title: string;
    text: string;
    returnFocus: HTMLElement | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const formProjects = computed<FormProject[]>(() =>
    props.projects.map((project) => ({
        slug: project.slug,
        name: project.name,
        location: `${project.location.area}, ${project.location.city}`,
    })),
);
</script>

<template>
    <FormDialog
        v-model:open="open"
        :overline="overline"
        :title="title"
        :description="text"
        :return-focus="returnFocus"
    >
        <ProjectInquiryForm :projects="formProjects" />
    </FormDialog>
</template>
