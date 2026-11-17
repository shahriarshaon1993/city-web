<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import RevisionsPanel from '@/components/admin/publishing/RevisionsPanel.vue';
import { useProjectEditor } from '@/components/admin/projects/context';
import type { ProjectSummary } from '@/types';

// Editor tab "Revisions" (design/admin.md CMP-ADM-FORM): the pending changes and the publish history.
const page = usePage<{
    project: ProjectSummary | null;
    lastSaved: string | null;
}>();
const editor = useProjectEditor();
const project = computed(() => page.props.project);
</script>

<template>
    <AdminSection
        title="Revisions"
        description="Pending changes and what was published before. Compare a version with the live project, or restore it."
    >
        <RevisionsPanel
            v-if="project"
            :type="project.type"
            :id="project.id"
            :refresh-key="`${project.lock_version}:${project.status}`"
            :can-restore="editor.canEdit"
            noun="project"
        />
        <p v-else class="text-sm text-muted-foreground">
            Revisions appear once the project is saved.
        </p>
    </AdminSection>
</template>
