<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { MediaFolderNode } from '@/types';
import MediaFolderSelect from './MediaFolderSelect.vue';

// Bulk "Move to folder" of the library (CMS-MED-002): pick a folder or Unfiled.
const props = defineProps<{ count: number; folders: MediaFolderNode[] }>();
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ move: [folderId: number | null] }>();

const target = ref<number | null>(null);

watch(open, (isOpen) => {
    if (isOpen) {
        target.value = null;
    }
});

function submit(): void {
    emit('move', target.value);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>
                        Move {{ props.count }}
                        {{ props.count === 1 ? 'file' : 'files' }}
                    </DialogTitle>
                    <DialogDescription>
                        Folders only organise the library; pages that use these
                        files are not affected.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="media-move-target">Move to</Label>
                    <MediaFolderSelect
                        id="media-move-target"
                        v-model="target"
                        :folders="folders"
                    />
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit">Move</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
