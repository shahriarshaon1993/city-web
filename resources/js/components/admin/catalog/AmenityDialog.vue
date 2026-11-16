<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import FormField from '@/components/forms/FormField.vue';
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
import { store, update } from '@/routes/admin/amenities';
import type { AmenityDraft, CatalogOption } from '@/types';
import ChoiceField from './ChoiceField.vue';
import IconPicker from './IconPicker.vue';
import TextField from './TextField.vue';

// ADM-SCR-03 / CRUD-01: amenities are a dialog entity (≤ 6 fields). One dialog creates (no id) or
// edits; the row of a change is highlighted by the list afterwards.
const props = defineProps<{
    draft: AmenityDraft | null;
    categories: CatalogOption[];
    icons: string[];
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ name: '', category: 'building', icon: 'sun' });
const editing = computed(() => props.draft?.id !== undefined);

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({
            name: props.draft?.name ?? '',
            category: props.draft?.category ?? 'building',
            icon: props.draft?.icon ?? props.icons[0] ?? 'sun',
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

    if (props.draft?.id !== undefined) {
        form.patch(update.url(props.draft.id), options);
    } else {
        form.post(store.url(), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xl">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit amenity' : 'New amenity'
                    }}</DialogTitle>
                    <DialogDescription>
                        Amenities are listed on a project page as icon rows,
                        grouped by category.
                    </DialogDescription>
                </DialogHeader>

                <TextField
                    id="amenity-name"
                    v-model="form.name"
                    label="Name"
                    required
                    :maxlength="80"
                    placeholder="Rooftop garden"
                    helper="Shown next to the icon on the project page. Up to 80 characters."
                    :error="form.errors.name"
                />
                <ChoiceField
                    id="amenity-category"
                    v-model="form.category"
                    label="Category"
                    required
                    :options="categories"
                    helper="The group the amenity appears in."
                    :error="form.errors.category"
                />
                <FormField
                    id="amenity-icon"
                    label="Icon"
                    required
                    :error="form.errors.icon"
                >
                    <template #default="{ describedby, invalid }">
                        <IconPicker
                            id="amenity-icon"
                            v-model="form.icon"
                            :icons="icons"
                            :invalid="invalid"
                            :describedby="describedby"
                        />
                    </template>
                </FormField>

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
                        {{ editing ? 'Save changes' : 'Create amenity' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
