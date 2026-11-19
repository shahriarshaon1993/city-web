<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, provide, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import {
    assetMapKey,
    createAssetMap,
} from '@/components/admin/catalog/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { destroy, index, store, update } from '@/routes/admin/testimonials';
import type { TestimonialEditProps } from '@/types';

// ADM-SCR-09 testimonial editor (FR-SOC-001). A full page, not a dialog: seven fields, a photo and a
// project (owner rule). The quote is plain text, 40–600 characters, shown as text and never as an image.
const props = defineProps<TestimonialEditProps>();

const form = useForm({ ...props.form });
const confirm = useConfirm();
provide(assetMapKey, createAssetMap(props.assets));

watch(
    () => props.lastSaved,
    () => {
        form.defaults({ ...props.form });
        form.reset();
    },
);

const limits = computed(() => props.options.limits);
const quoteTooShort = computed(
    () =>
        form.quote.trim().length > 0 &&
        form.quote.trim().length < limits.value.quote_min,
);

function save(): void {
    if (props.testimonial) {
        form.patch(update.url(props.testimonial.id), { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}

async function remove(): Promise<void> {
    if (!props.testimonial) {
        return;
    }

    const answer = await confirm({
        title: 'Delete this testimonial?',
        subject: props.testimonial.person_name,
        description:
            'It leaves the website at once. It moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete testimonial',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.testimonial.id));
    }
}

const canSave = computed(
    () =>
        form.person_name.trim() !== '' &&
        form.quote.trim().length >= limits.value.quote_min,
);
const fieldId = (key: string): string => `testimonial-${key}`;
</script>

<template>
    <Head :title="testimonial?.person_name ?? 'New testimonial'" />

    <AdminEditor
        :title="
            form.person_name.trim() ||
            testimonial?.person_name ||
            'New testimonial'
        "
        description="A quote from a buyer, owner or tenant. It is shown as text with the name and, if you add one, a photo."
        :form="form"
        :last-saved="lastSaved"
        :save-label="testimonial ? 'Save changes' : 'Add testimonial'"
        :save-reason="
            canSave
                ? null
                : `Enter a name and a quote of at least ${limits.quote_min} characters.`
        "
        :field-id="fieldId"
        @save="save"
    >
        <template #actions>
            <Link
                :href="index.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All testimonials</Link
            >
            <Button
                v-if="testimonial"
                type="button"
                variant="outline"
                @click="remove"
            >
                <Trash2 aria-hidden="true" />
                Delete
            </Button>
        </template>

        <template #rail>
            <div class="grid gap-4">
                <SwitchRow
                    id="testimonial-is_published"
                    v-model="form.is_published"
                    label="Published"
                    helper="Visible on the website when on."
                />
                <SwitchRow
                    id="testimonial-is_nrb"
                    v-model="form.is_nrb"
                    label="NRB buyer"
                    helper="Lets a testimonials block show only buyers from abroad."
                />
            </div>
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="The quote"
                description="Plain text only; there is no star rating. Write what the person said, in their words."
            >
                <div class="grid gap-5">
                    <FormField
                        id="testimonial-quote"
                        label="Quote"
                        required
                        :helper="`${limits.quote_min}–${limits.quote_max} characters.`"
                        :counter="{
                            length: form.quote.length,
                            max: limits.quote_max,
                        }"
                        :error="
                            form.errors.quote ??
                            (quoteTooShort
                                ? `Write at least ${limits.quote_min} characters so the quote says something.`
                                : undefined)
                        "
                    >
                        <template #default="{ id, describedby, invalid }">
                            <Textarea
                                :id="id"
                                v-model="form.quote"
                                :maxlength="limits.quote_max"
                                rows="6"
                                :aria-describedby="describedby"
                                :aria-invalid="invalid || undefined"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>

            <AdminSection
                title="Who said it"
                description="The name and role appear under the quote. A photo is optional; without one the site shows the person's initials."
            >
                <div class="grid gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <TextField
                            id="testimonial-person_name"
                            v-model="form.person_name"
                            label="Name"
                            required
                            :maxlength="limits.name"
                            autocomplete="off"
                            :error="form.errors.person_name"
                        />
                        <TextField
                            id="testimonial-designation"
                            v-model="form.designation"
                            label="Designation or place"
                            :maxlength="limits.designation"
                            placeholder="Software engineer, Toronto"
                            helper="Optional. For example a job, or where they live."
                            :error="form.errors.designation"
                        />
                    </div>
                    <FormField
                        id="testimonial-photo_asset_id"
                        label="Photo"
                        helper="Optional. A square photo at least 400 × 400 px; it is cropped to a circle on the site."
                        :error="form.errors.photo_asset_id"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                id="testimonial-photo_asset_id"
                                v-model="form.photo_asset_id"
                                :kinds="['image']"
                                choose-label="Choose photo"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>

            <AdminSection
                title="Project"
                description="Optional. The quote links to the project's page."
            >
                <ChoiceField
                    id="testimonial-project_id"
                    v-model="form.project_id"
                    label="Related project"
                    :options="options.projects"
                    none-label="No project"
                    placeholder="No project"
                    :error="form.errors.project_id"
                />
            </AdminSection>
        </div>
    </AdminEditor>
</template>
