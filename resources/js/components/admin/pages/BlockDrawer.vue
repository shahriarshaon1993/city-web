<script setup lang="ts">
import { Sheet, SheetContent } from '@/components/ui/sheet';
import type { EditorBlock } from '@/types';
import BlockPanel from './BlockPanel.vue';

// CMP-ADM-BUILDER block form drawer below 1700 px: a modal Sheet (right, 480 px) around the block form.
// From 1700 px the same form sits in its own column beside the preview instead (Edit), so the preview
// stays visible while editing.
defineProps<{
    block: EditorBlock | null;
    index: number;
    atLimit: boolean;
}>();

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{
    duplicate: [id: number];
    remove: [id: number];
}>();
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent side="right" class="w-full gap-0 p-0 sm:max-w-[30rem]">
            <BlockPanel
                :block="block"
                :index="index"
                :at-limit="atLimit"
                in-sheet
                @duplicate="emit('duplicate', $event)"
                @remove="emit('remove', $event)"
            />
        </SheetContent>
    </Sheet>
</template>
