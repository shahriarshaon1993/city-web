<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Spinner } from '@/components/ui/spinner';
import { move } from '@/routes/admin/media/folders';
import type { MediaFolderNode, MediaFolderTree } from '@/types';
import MediaFolderSelect from './MediaFolderSelect.vue';

// CMS-MED-002 move: the folder takes its subfolders and files along. Targets that would break the
// 3-level limit or sit inside the folder itself are disabled; the server re-checks.
const props = defineProps<{ folder: MediaFolderNode; tree: MediaFolderTree }>();
const open = defineModel<boolean>('open', { default: false });

const form = useForm({ parent_id: null as number | null });

/** Levels the folder spans with its subfolders (1 without children). */
function height(folder: MediaFolderNode): number {
    return 1 + Math.max(0, ...folder.children.map(height));
}

function subtree(folder: MediaFolderNode): number[] {
    return [folder.id, ...folder.children.flatMap(subtree)];
}

const ownIds = computed(() => subtree(props.folder));
const maxParentDepth = computed(() => 3 - height(props.folder));

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.parent_id = props.folder.parent_id;
    }
});

function submit(): void {
    form.patch(move.url(props.folder.id), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Move “{{ folder.name }}”</DialogTitle>
                    <DialogDescription>
                        Its subfolders and files move with it. Folders go 3
                        levels deep at most, so deeper places are greyed out.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="folder-move-target">Move into</Label>
                    <MediaFolderSelect
                        id="folder-move-target"
                        v-model="form.parent_id"
                        :folders="tree.folders"
                        none-label="Top level"
                        :disabled-ids="ownIds"
                        :max-depth="maxParentDepth"
                        :invalid="!!form.errors.parent_id"
                    />
                    <InputError :message="form.errors.parent_id" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="
                            form.processing ||
                            form.parent_id === folder.parent_id
                        "
                    >
                        <Spinner v-if="form.processing" />
                        Move folder
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
