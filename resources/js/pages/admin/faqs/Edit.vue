<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RelatedPicker from '@/components/admin/catalog/RelatedPicker.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useConfirm } from '@/composables/useConfirm';
import { destroy, index, store, update } from '@/routes/admin/faqs';
import type { FaqEditProps } from '@/types';

// ADM-SCR-09 FAQ editor (FR-SOC-003): the question, a limited rich-text answer (paragraphs, bold, links,
// lists), its topic, the places it appears and the single pages it is attached to.
const props = defineProps<FaqEditProps>();

function copyOf(data: FaqEditProps['form']): FaqEditProps['form'] {
    return JSON.parse(JSON.stringify(data)) as FaqEditProps['form'];
}

const form = useForm(copyOf(props.form));
const confirm = useConfirm();

watch(
    () => props.lastSaved,
    () => {
        form.defaults(copyOf(props.form));
        form.reset();
    },
);

function save(): void {
    if (props.faq) {
        form.patch(update.url(props.faq.id), { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}

async function remove(): Promise<void> {
    if (!props.faq) {
        return;
    }

    const answer = await confirm({
        title: 'Delete this question?',
        subject: props.faq.question,
        description:
            'It leaves the website at once. It moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete question',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.faq.id));
    }
}

function togglePlacement(value: string, on: boolean): void {
    form.show_on = on
        ? [...form.show_on, value]
        : form.show_on.filter((item) => item !== value);
}

const answerText = computed(() => form.answer.replace(/<[^>]*>/g, ' ').trim());
const canSave = computed(
    () => form.question.trim() !== '' && answerText.value !== '',
);
const fieldId = (key: string): string =>
    `faq-${key.replace(/\.\d+$/, '').replace(/\./g, '-')}`;

const projectOptions = computed(() => props.options.projects);
const locationOptions = computed(() => props.options.locations);
const facetOptions = computed(() => props.options.facets);
</script>

<template>
    <Head :title="faq?.question ?? 'New question'" />

    <AdminEditor
        :title="form.question.trim() || faq?.question || 'New question'"
        description="A question and its answer. Choose where it appears: the FAQ page, project and area pages, or FAQ blocks."
        :form="form"
        :last-saved="lastSaved"
        :save-label="faq ? 'Save changes' : 'Add question'"
        :save-reason="canSave ? null : 'Enter the question and its answer.'"
        :field-id="fieldId"
        @save="save"
    >
        <template #actions>
            <Link
                :href="index.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All questions</Link
            >
            <Button v-if="faq" type="button" variant="outline" @click="remove">
                <Trash2 aria-hidden="true" />
                Delete
            </Button>
        </template>

        <template #rail>
            <SwitchRow
                id="faq-is_published"
                v-model="form.is_published"
                label="Published"
                helper="Visible on the website when on."
            />
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="Question and answer"
                description="Short, plain questions work best. The answer can use bold text, links and lists."
            >
                <div class="grid gap-5">
                    <TextField
                        id="faq-question"
                        v-model="form.question"
                        label="Question"
                        required
                        :maxlength="options.limits.question"
                        :error="form.errors.question"
                    />
                    <FormField
                        id="faq-answer"
                        label="Answer"
                        required
                        :error="form.errors.answer"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="faq-answer"
                                v-model="form.answer"
                                limited
                                :rows="8"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <ChoiceField
                        id="faq-category"
                        v-model="form.category"
                        label="Topic"
                        required
                        :options="options.categories"
                        helper="The FAQ page groups questions by topic."
                        :error="form.errors.category"
                    />
                </div>
            </AdminSection>

            <AdminSection
                title="Where it appears"
                description="A question with no place ticked and nothing attached shows only where an FAQ block picks it."
            >
                <div class="grid gap-6">
                    <fieldset class="grid gap-3">
                        <legend class="mb-1 text-sm font-medium">
                            Show on
                        </legend>
                        <label
                            v-for="placement in options.placements"
                            :key="placement.value"
                            class="flex items-center gap-3 text-sm"
                        >
                            <Checkbox
                                :model-value="
                                    form.show_on.includes(placement.value)
                                "
                                @update:model-value="
                                    (value) =>
                                        togglePlacement(
                                            placement.value,
                                            value === true,
                                        )
                                "
                            />
                            {{ placement.label }}
                        </label>
                        <p
                            v-if="form.errors.show_on"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.show_on }}
                        </p>
                    </fieldset>

                    <FormField
                        id="faq-project_ids"
                        label="Only on these projects"
                        helper="Leave empty to show on every project page (when “Project pages” is ticked)."
                        :error="form.errors.project_ids"
                    >
                        <template #default="{ describedby, invalid }">
                            <RelatedPicker
                                id="faq-project_ids"
                                v-model="form.project_ids"
                                :options="projectOptions"
                                :max="50"
                                noun="project"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="faq-location_ids"
                        label="Only on these areas"
                        helper="Leave empty to show on every area page (when “Location pages” is ticked)."
                        :error="form.errors.location_ids"
                    >
                        <template #default="{ describedby, invalid }">
                            <RelatedPicker
                                id="faq-location_ids"
                                v-model="form.location_ids"
                                :options="locationOptions"
                                :max="50"
                                noun="area"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="faq-facet_ids"
                        label="On these listing pages"
                        helper="The project listing landing pages, such as Residential or Ongoing."
                        :error="form.errors.facet_ids"
                    >
                        <template #default="{ describedby, invalid }">
                            <RelatedPicker
                                id="faq-facet_ids"
                                v-model="form.facet_ids"
                                :options="facetOptions"
                                :max="8"
                                noun="listing page"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>
        </div>
    </AdminEditor>
</template>
