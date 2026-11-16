<script setup lang="ts">
import { Plus } from '@lucide/vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RepeaterCard from '@/components/admin/catalog/RepeaterCard.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { newKey } from '@/components/admin/catalog/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    countErrors,
    fid,
    moveItem,
    useCardState,
    useProjectEditor,
} from './context';

// Documents (FR-PROJ-004, DM-PDOC): up to 5 PDFs (brochure, price list, specification…), each
// public or gated behind a name and phone (AC-PROJ-05). The brochure switch below sets the default.
const { form, options, errors } = useProjectEditor();
const cards = useCardState();

function add(): void {
    if (form.documents.length >= options.limits.documents) {
        return;
    }

    const key = newKey('d');
    form.documents.push({
        key,
        asset_id: null,
        title: '',
        kind: form.documents.some((item) => item.kind === 'brochure')
            ? 'other'
            : 'brochure',
        is_gated: form.brochure_gated,
    });
    cards.set(key, true);
}
</script>

<template>
    <div class="grid gap-6">
        <AdminSection
            title="Brochure download"
            description="What a visitor does to download the brochure."
        >
            <SwitchRow
                id="field-brochure_gated"
                v-model="form.brochure_gated"
                label="Ask for name and phone first"
                helper="On: the brochure is released after the visitor gives a name and phone number, and it becomes a lead. Off: it downloads at once."
                :error="errors.brochure_gated"
            />
        </AdminSection>

        <AdminSection
            title="Documents"
            :description="`PDFs of at most 20 MB from the media library. Up to ${options.limits.documents}.`"
        >
            <div id="field-documents" class="grid gap-3">
                <RepeaterCard
                    v-for="(document, index) in form.documents"
                    :key="document.key"
                    :open="
                        cards.isOpen(
                            document.key,
                            countErrors(errors, `documents.${index}`),
                        )
                    "
                    :title="document.title || `Document ${index + 1}`"
                    :summary="
                        document.is_gated ? 'Name and phone first' : 'Public'
                    "
                    :errors="countErrors(errors, `documents.${index}`)"
                    :noun="`document ${index + 1}`"
                    sortable
                    :first="index === 0"
                    :last="index === form.documents.length - 1"
                    @update:open="
                        (open: boolean) => cards.set(document.key, open)
                    "
                    @remove="form.documents.splice(index, 1)"
                    @move="
                        (delta) =>
                            (form.documents = moveItem(
                                form.documents,
                                index,
                                delta,
                            ))
                    "
                >
                    <div class="grid gap-5">
                        <FormField
                            :id="fid('documents', index, 'asset_id')"
                            label="PDF file"
                            required
                            :error="errors[`documents.${index}.asset_id`]"
                        >
                            <template #default="{ describedby, invalid }">
                                <AssetField
                                    :id="fid('documents', index, 'asset_id')"
                                    v-model="document.asset_id"
                                    :kinds="['document']"
                                    choose-label="Choose PDF"
                                    :invalid="invalid"
                                    :describedby="describedby"
                                />
                            </template>
                        </FormField>
                        <div class="grid gap-5 md:grid-cols-2">
                            <TextField
                                :id="fid('documents', index, 'title')"
                                v-model="document.title"
                                label="Title"
                                required
                                :maxlength="100"
                                :error="errors[`documents.${index}.title`]"
                            />
                            <ChoiceField
                                :id="fid('documents', index, 'kind')"
                                v-model="document.kind"
                                label="Kind"
                                :options="options.document_kinds"
                                :error="errors[`documents.${index}.kind`]"
                            />
                        </div>
                        <SwitchRow
                            :id="fid('documents', index, 'is_gated')"
                            v-model="document.is_gated"
                            label="Ask for name and phone first"
                            :error="errors[`documents.${index}.is_gated`]"
                        />
                    </div>
                </RepeaterCard>

                <p
                    v-if="!form.documents.length"
                    class="text-sm text-muted-foreground"
                >
                    No documents yet.
                </p>
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="
                            form.documents.length >= options.limits.documents
                        "
                        @click="add"
                    >
                        <Plus aria-hidden="true" />
                        Add document
                    </Button>
                </div>
            </div>
        </AdminSection>
    </div>
</template>
