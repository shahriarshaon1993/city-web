<script setup lang="ts">
import { Copy, Trash2 } from '@lucide/vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import PublishingRail from '@/components/admin/publishing/PublishingRail.vue';
import type { ChecklistItem, PageSummary } from '@/types';

// The publishing block of the page builder (CMP-ADM-FORM rail, opened from the bottom bar): the CMS-PUB
// workflow (status, Publish / Schedule / Publish changes / Discard, Preview, the checklist) and the
// verbs Duplicate / Delete. The checklist is the live one from the preview endpoint, so it follows the
// editor as it is typed.
defineProps<{
    page: PageSummary | null;
    checklist: ChecklistItem[];
    dirty: boolean;
    can: {
        publish: boolean;
        delete: boolean;
        duplicate: boolean;
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
            :workflow="page"
            noun="page"
            :checklist="checklist"
            :dirty="dirty"
            :can-publish="can.publish"
            :can-edit="can.update"
            :path="path"
            @fix="(item) => emit('fix', item)"
        />

        <p v-if="!page" class="border-t pt-4 text-xs text-muted-foreground">
            Save the page first. It stays a draft until you publish it, and then
            you can preview, schedule or publish it from here.
        </p>

        <section
            v-if="page"
            class="grid gap-2 border-t pt-4"
            aria-label="Page actions"
        >
            <ReasonButton
                v-if="can.duplicate"
                type="button"
                variant="ghost"
                class="w-full justify-start"
                :reason="
                    dirty
                        ? 'Save your changes first; the copy is made from the saved page.'
                        : null
                "
                @click="emit('duplicate')"
            >
                <Copy aria-hidden="true" />
                Duplicate page
            </ReasonButton>
            <ReasonButton
                v-if="can.delete || page.is_system"
                type="button"
                variant="ghost"
                class="w-full justify-start text-destructive hover:text-destructive"
                :reason="
                    page.is_system
                        ? 'System pages are part of the website and cannot be deleted.'
                        : null
                "
                @click="emit('delete')"
            >
                <Trash2 aria-hidden="true" />
                Delete page
            </ReasonButton>
        </section>
    </div>
</template>
