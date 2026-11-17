<script setup lang="ts">
import { Plus } from '@lucide/vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import RepeaterCard from '@/components/admin/catalog/RepeaterCard.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { newKey } from '@/components/admin/catalog/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
    countErrors,
    fid,
    moveItem,
    useCardState,
    useProjectEditor,
} from './context';

// Content: the overview (120 words to publish), the optional design narrative and up to 8 feature
// highlights, each an image with a title and a line of text.
const { form, options, errors } = useProjectEditor();
const cards = useCardState();

function addHighlight(): void {
    if (form.highlights.length >= options.limits.highlights) {
        return;
    }

    const key = newKey('h');
    form.highlights.push({ key, asset_id: null, title: '', text: '' });
    cards.set(key, true);
}
</script>

<template>
    <div class="grid gap-6">
        <AdminSection
            title="Overview"
            description="The main description on the project page. At least 120 words to publish."
        >
            <FormField
                id="field-overview"
                label="Overview"
                helper="Use the toolbar for headings, bold, links and lists. Paste plain text to keep it clean."
                :error="errors.overview"
            >
                <template #default="{ describedby, invalid }">
                    <RichTextField
                        id="field-overview"
                        v-model="form.overview"
                        :min-words="120"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </template>
            </FormField>
        </AdminSection>

        <AdminSection
            title="Design narrative"
            description="Optional. The story behind the architecture and interiors."
        >
            <FormField
                id="field-design_narrative"
                label="Design narrative"
                :error="errors.design_narrative"
            >
                <template #default="{ describedby, invalid }">
                    <RichTextField
                        id="field-design_narrative"
                        v-model="form.design_narrative"
                        :rows="7"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </template>
            </FormField>
        </AdminSection>

        <AdminSection
            title="Feature highlights"
            :description="`Up to ${options.limits.highlights}. Each is a photo with a short title and text, shown as a row on the project page.`"
        >
            <div id="field-highlights" class="grid gap-3">
                <RepeaterCard
                    v-for="(highlight, index) in form.highlights"
                    :key="highlight.key"
                    :open="
                        cards.isOpen(
                            highlight.key,
                            countErrors(errors, `highlights.${index}`),
                        )
                    "
                    @update:open="
                        (open: boolean) => cards.set(highlight.key, open)
                    "
                    :title="highlight.title || `Highlight ${index + 1}`"
                    :summary="highlight.text"
                    :errors="countErrors(errors, `highlights.${index}`)"
                    noun="highlight"
                    sortable
                    :first="index === 0"
                    :last="index === form.highlights.length - 1"
                    @remove="form.highlights.splice(index, 1)"
                    @move="
                        (delta) =>
                            (form.highlights = moveItem(
                                form.highlights,
                                index,
                                delta,
                            ))
                    "
                >
                    <div class="grid gap-5">
                        <FormField
                            :id="fid('highlights', index, 'asset_id')"
                            label="Photo"
                            required
                            :error="errors[`highlights.${index}.asset_id`]"
                        >
                            <template #default="{ describedby, invalid }">
                                <AssetField
                                    :id="fid('highlights', index, 'asset_id')"
                                    v-model="highlight.asset_id"
                                    :kinds="['image']"
                                    choose-label="Choose photo"
                                    :invalid="invalid"
                                    :describedby="describedby"
                                />
                            </template>
                        </FormField>
                        <TextField
                            :id="fid('highlights', index, 'title')"
                            v-model="highlight.title"
                            label="Title"
                            required
                            :maxlength="80"
                            :error="errors[`highlights.${index}.title`]"
                        />
                        <FormField
                            :id="fid('highlights', index, 'text')"
                            label="Text"
                            :counter="{
                                length: highlight.text.length,
                                max: 300,
                            }"
                            :error="errors[`highlights.${index}.text`]"
                        >
                            <template #default="{ describedby, invalid }">
                                <Textarea
                                    :id="fid('highlights', index, 'text')"
                                    v-model="highlight.text"
                                    rows="3"
                                    maxlength="300"
                                    :aria-invalid="invalid || undefined"
                                    :aria-describedby="describedby"
                                />
                            </template>
                        </FormField>
                    </div>
                </RepeaterCard>

                <p
                    v-if="!form.highlights.length"
                    class="text-sm text-muted-foreground"
                >
                    No highlights yet.
                </p>
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="
                            form.highlights.length >= options.limits.highlights
                        "
                        @click="addHighlight"
                    >
                        <Plus aria-hidden="true" />
                        Add highlight
                    </Button>
                </div>
            </div>
        </AdminSection>
    </div>
</template>
