<script setup lang="ts">
import { computed } from 'vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import SeoPanel from '@/components/admin/catalog/SeoPanel.vue';
import { useProjectEditor } from './context';

// SEO: the CMP-ADM-SEO panel. While a field is empty the template of Settings › SEO is used
// (SEO-ONP-001/002); the preview shows a close approximation of it.
const { form, errors, options } = useProjectEditor();

const area = computed(() =>
    options.areas.find((item) => item.value === form.location_id),
);
const fallbackTitle = computed(
    () =>
        `${form.name || 'Project name'}${area.value ? ` – ${area.value.label}` : ''} | Gemcon City`,
);
const fallbackDescription = computed(
    () =>
        `${form.name || 'This project'} by Gemcon City${area.value ? ` in ${area.value.label}` : ''}. Book a site visit.`,
);
</script>

<template>
    <AdminSection
        title="Search and sharing"
        description="How the project looks in Google and when its link is shared."
    >
        <SeoPanel
            v-model="form.seo"
            :path="`/projects/${form.slug || 'project-name'}`"
            :fallback-title="fallbackTitle"
            :fallback-description="fallbackDescription"
            :errors="errors"
        />
    </AdminSection>
</template>
