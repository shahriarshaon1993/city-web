<script setup lang="ts">
import { Copy, Trash2 } from '@lucide/vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import PublishingRail from '@/components/admin/publishing/PublishingRail.vue';
import { Button } from '@/components/ui/button';
import type { ChecklistItem, ProjectSummary } from '@/types';

// The right rail of the project editor (CMP-ADM-FORM): the publishing workflow block (status,
// Publish / Schedule / Publish changes / Discard, Preview, the checklist — PublishingRail, CMS-PUB)
// and the verbs Duplicate / Delete.
defineProps<{
    project: ProjectSummary | null;
    checklist: ChecklistItem[];
    dirty: boolean;
    can: {
        publish: boolean;
        delete: boolean;
        create: boolean;
        update: boolean;
    };
    path: string;
}>();

const emit = defineEmits<{
    duplicate: [];
    delete: [];
    fix: [item: ChecklistItem];
}>();
</script>

<template>
    <div class="grid grid-cols-[minmax(0,1fr)] gap-4">
        <PublishingRail
            :workflow="project"
            noun="project"
            :checklist="checklist"
            :dirty="dirty"
            :can-publish="can.publish"
            :can-edit="can.update"
            :path="path"
            @fix="(item) => emit('fix', item)"
        />

        <p v-if="!project" class="border-t pt-4 text-xs text-muted-foreground">
            Save the project first. It stays a draft until you publish it, and
            then you can preview, schedule or publish it from here.
        </p>

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
