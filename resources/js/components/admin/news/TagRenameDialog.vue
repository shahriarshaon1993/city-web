<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/admin/news/tags';
import type { TagRow } from '@/types';

// FR-NEWS-003 / CRUD-01: renaming a tag is one field, so it is a dialog. A name another tag already has
// is refused with a pointer to Merge.
const props = defineProps<{ tag: TagRow | null }>();

const open = defineModel<boolean>('open', { default: false });
const form = useForm({ name: '' });

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({ name: props.tag?.name ?? '' });
        form.reset();
    }
});

function submit(): void {
    if (!props.tag) {
        return;
    }

    form.patch(update.url(props.tag.id), {
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
                    <DialogTitle>Rename tag</DialogTitle>
                    <DialogDescription>
                        The tag page address follows the new name. Every post
                        with this tag keeps it.
                    </DialogDescription>
                </DialogHeader>

                <TextField
                    id="tag-name"
                    v-model="form.name"
                    label="Name"
                    required
                    :maxlength="50"
                    :error="form.errors.name"
                />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="form.processing || form.name.trim() === ''"
                    >
                        <Spinner v-if="form.processing" />
                        Save name
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
