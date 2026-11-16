<script setup lang="ts">
import { Copy, ExternalLink, Eye, Trash2 } from '@lucide/vue';
import { computed, useId } from 'vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PublishChecklist from '@/components/admin/catalog/PublishChecklist.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/admin-format';
import { showToast } from '@/lib/notify';
import type { ChecklistItem, ProjectSummary } from '@/types';

// The right rail of the project editor (CMP-ADM-FORM): status, Publish / Unpublish, the public URL
// with a copy button (HELP-04), the publish checklist (AC-PROJ-03) and the verbs Duplicate / Delete.
// Preview of a draft and Schedule arrive with the publishing workflow (14.1).
const props = defineProps<{
    project: ProjectSummary | null;
    checklist: ChecklistItem[];
    dirty: boolean;
    can: { publish: boolean; delete: boolean; create: boolean };
    path: string;
}>();

const emit = defineEmits<{
    publish: [];
    unpublish: [];
    duplicate: [];
    delete: [];
    fix: [item: ChecklistItem];
}>();

const uid = useId();
const open = computed(
    () => props.checklist.filter((item) => !item.passed).length,
);
const live = computed(() => props.project?.status === 'published');

const TONES: Record<string, string> = {
    published: 'success',
    scheduled: 'info',
    archived: 'warning',
};
const tone = computed(
    () => TONES[props.project?.status ?? 'draft'] ?? 'neutral',
);

const publishReason = computed(() => {
    if (!props.project) {
        return 'Save the project first, then publish it.';
    }

    if (props.dirty) {
        return 'Save your changes first.';
    }

    return open.value > 0
        ? `${open.value} checklist ${open.value === 1 ? 'item is' : 'items are'} open.`
        : null;
});

async function copyUrl(): Promise<void> {
    const url = props.project?.public_url;

    if (!url) {
        return;
    }

    try {
        await navigator.clipboard.writeText(url);
        showToast({ type: 'success', title: 'Link copied' });
    } catch {
        showToast({ type: 'error', title: 'Could not copy the link' });
    }
}
</script>

<template>
    <div class="grid gap-4 border-t pt-4">
        <section class="grid gap-3" :aria-labelledby="`${uid}-status`">
            <div class="flex items-center justify-between gap-2">
                <h2 :id="`${uid}-status`" class="text-sm font-semibold">
                    Status
                </h2>
                <StatusBadge
                    :label="project?.status_label ?? 'Not saved yet'"
                    :tone="tone"
                />
            </div>

            <p
                v-if="project?.status === 'published' && project.published_at"
                class="text-xs text-muted-foreground"
            >
                Live since
                {{ formatDateTime(project.published_at).split(' · ')[0] }}.
            </p>
            <p v-else-if="project" class="text-xs text-muted-foreground">
                Hidden from the website until you publish it.
            </p>

            <div v-if="project?.public_url" class="flex items-center gap-1">
                <a
                    :href="project.public_url"
                    target="_blank"
                    rel="noopener"
                    class="min-w-0 flex-1 truncate rounded-md bg-muted px-2 py-1.5 font-mono text-xs hover:underline"
                    >{{ path }}</a
                >
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Copy the public link"
                    @click="copyUrl"
                >
                    <Copy aria-hidden="true" />
                </Button>
            </div>

            <template v-if="can.publish">
                <ReasonButton
                    v-if="!live"
                    type="button"
                    variant="outline"
                    class="w-full"
                    :reason="publishReason"
                    @click="emit('publish')"
                >
                    Publish
                </ReasonButton>
                <ReasonButton
                    v-else
                    type="button"
                    variant="outline"
                    class="w-full"
                    :reason="dirty ? 'Save your changes first.' : null"
                    @click="emit('unpublish')"
                >
                    Unpublish
                </ReasonButton>
            </template>

            <Button
                v-if="live && project?.public_url"
                as-child
                variant="ghost"
                class="w-full"
            >
                <a :href="project.public_url" target="_blank" rel="noopener">
                    <ExternalLink aria-hidden="true" />
                    View on site
                </a>
            </Button>
            <ReasonButton
                v-else-if="project"
                type="button"
                variant="ghost"
                class="w-full"
                reason="Preview of drafts arrives with the publishing workflow."
            >
                <Eye aria-hidden="true" />
                Preview
            </ReasonButton>
        </section>

        <PublishChecklist
            v-if="project"
            :items="checklist"
            :dirty="dirty"
            class="border-t pt-4"
            @fix="(item) => emit('fix', item)"
        />

        <section
            v-if="project"
            class="grid gap-2 border-t pt-4"
            aria-label="Project actions"
        >
            <ReasonButton
                v-if="can.create"
                type="button"
                variant="ghost"
                class="w-full justify-start"
                :reason="
                    dirty
                        ? 'Save your changes first; the copy is made from the saved project.'
                        : null
                "
                @click="emit('duplicate')"
            >
                <Copy aria-hidden="true" />
                Duplicate project
            </ReasonButton>
            <Button
                v-if="can.delete"
                type="button"
                variant="ghost"
                class="w-full justify-start text-destructive hover:text-destructive"
                @click="emit('delete')"
            >
                <Trash2 aria-hidden="true" />
                Delete project
            </Button>
        </section>
    </div>
</template>
