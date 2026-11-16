<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import SlugField from '@/components/admin/SlugField.vue';
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
import { store, update } from '@/routes/admin/locations/cities';
import TextField from './TextField.vue';

// FR-LOC-001 / CRUD-01: a city is a dialog entity (name + URL). Areas have their own editor.
const props = defineProps<{
    city: { id: number; name: string; slug: string } | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ name: '', slug: '' });
const editing = computed(() => props.city !== null);

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({
            name: props.city?.name ?? '',
            slug: props.city?.slug ?? '',
        });
        form.reset();
    }
});

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };

    if (props.city) {
        form.patch(update.url(props.city.id), options);
    } else {
        form.post(store.url(), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit city' : 'New city'
                    }}</DialogTitle>
                    <DialogDescription>
                        A city groups areas. Its page lists every area and
                        project inside it.
                    </DialogDescription>
                </DialogHeader>

                <TextField
                    id="city-name"
                    v-model="form.name"
                    label="Name"
                    required
                    :maxlength="100"
                    placeholder="Dhaka"
                    :error="form.errors.name"
                />
                <SlugField
                    id="city-slug"
                    v-model="form.slug"
                    :from="form.name"
                    source="location"
                    :ignore-id="city?.id ?? null"
                    :published="editing"
                    prefix="/locations/"
                    label="URL"
                    required
                    :error="form.errors.slug"
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
                        :disabled="
                            form.processing ||
                            form.name.trim() === '' ||
                            form.slug.trim() === ''
                        "
                    >
                        <Spinner v-if="form.processing" />
                        {{ editing ? 'Save changes' : 'Create city' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
