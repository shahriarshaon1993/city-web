<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import SlugField from '@/components/admin/SlugField.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { store, update } from '@/routes/admin/news/categories';

// ADM-SCR-08 / CRUD-01: a category is a dialog entity (3 fields). One dialog creates (no id) or edits.
// The description is the intro of the category page (FR-NEWS-002); the address changes only when the
// editor changes the URL, and the old one then redirects.
export type CategoryDraft = {
    id?: number;
    name: string;
    slug: string;
    description: string;
};

const props = defineProps<{ draft: CategoryDraft | null }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ name: '', slug: '', description: '' });
const editing = computed(() => props.draft?.id !== undefined);

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({
            name: props.draft?.name ?? '',
            slug: props.draft?.slug ?? '',
            description: props.draft?.description ?? '',
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
                        editing ? 'Edit category' : 'New category'
                    }}</DialogTitle>
                    <DialogDescription>
                        Every post is in exactly one category. The category has
                        its own page on the news section.
                    </DialogDescription>
                </DialogHeader>

                <TextField
                    id="category-name"
                    v-model="form.name"
                    label="Name"
                    required
                    :maxlength="60"
                    placeholder="Buying guides"
                    helper="Shown on posts and in the category row."
                    :error="form.errors.name"
                />
                <SlugField
                    id="category-slug"
                    v-model="form.slug"
                    :from="form.name"
                    source="post_category"
                    :ignore-id="draft?.id ?? null"
                    :published="editing"
                    prefix="/news/category/"
                    label="URL"
                    required
                    :error="form.errors.slug"
                />
                <FormField
                    id="category-description"
                    label="Description"
                    helper="Shown at the top of the category page and used as its meta description. Up to 300 characters."
                    :error="form.errors.description"
                >
                    <template #default="{ describedby, invalid }">
                        <Textarea
                            id="category-description"
                            v-model="form.description"
                            rows="3"
                            maxlength="300"
                            :aria-invalid="invalid || undefined"
                            :aria-describedby="describedby"
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
                        :disabled="
                            form.processing ||
                            form.name.trim() === '' ||
                            form.slug.trim() === ''
                        "
                    >
                        <Spinner v-if="form.processing" />
                        {{ editing ? 'Save changes' : 'Create category' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
