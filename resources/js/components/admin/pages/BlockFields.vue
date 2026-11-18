<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { VueDraggable } from 'vue-draggable-plus';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import MediaIdsField from '@/components/admin/catalog/MediaIdsField.vue';
import RelatedPicker from '@/components/admin/catalog/RelatedPicker.vue';
import RepeaterCard from '@/components/admin/catalog/RepeaterCard.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { newKey } from '@/components/admin/catalog/context';
import { moveItem, useCardState } from '@/components/admin/projects/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import type { BlockErrorMap, GroupedOption } from '@/types';
import type { Data, Field } from './blocks';
import BlockLinkField from './BlockLinkField.vue';
import {
    blockFieldId,
    countUnder,
    isVisible,
    resolveOptions,
    usePageEditor,
} from './context';

// The generic form of a block (CMP-ADM-BUILDER drawer): renders the fields of its definition
// (`blocks.ts`) on `data`, edited in place. Lists become reorderable cards (drag or Move up / down,
// CRUD-06) that open themselves when they hold errors (VAL-07). `prefix` is the path of `data` inside
// the block (`items.2`), so every control gets the id the error summary links to, and every error
// of the live check (CMS-PB-003) lands under its field.
const props = defineProps<{
    fields: Field[];
    data: Data;
    blockId: number;
    prefix?: string;
    errors: BlockErrorMap;
    disabled?: boolean;
}>();

const { options } = usePageEditor();
const cards = useCardState();

const visible = computed(() =>
    props.fields.filter((field) => isVisible(field, props.data)),
);

const pathOf = (field: Field): string =>
    props.prefix ? `${props.prefix}.${field.key}` : field.key;
const idOf = (field: Field): string =>
    blockFieldId(props.blockId, pathOf(field));
const errorOf = (field: Field): string | undefined =>
    props.errors[pathOf(field)]?.[0];

function set(field: Field, value: unknown): void {
    props.data[field.key] = value;

    if (field.kind === 'select' && field.resets) {
        for (const key of field.resets) {
            props.data[key] = Array.isArray(props.data[key]) ? [] : null;
        }
    }
}

/** A select takes numbers or text; an optional one that is cleared stores null. */
const selectOptions = (field: Field & { kind: 'select' }) =>
    resolveOptions(field.options, options);

const grouped = (
    from: 'projects' | 'testimonials' | 'faqs' | 'offices',
): GroupedOption[] => options[from] as GroupedOption[];

// --- lists ------------------------------------------------------------------------------------------
type ListField = Field & { kind: 'list' };

function items(field: ListField): Data[] {
    return (props.data[field.key] ??= []) as Data[];
}

function addItem(field: ListField): void {
    const list = items(field);

    if (list.length >= field.max) {
        return;
    }

    const item = { ...field.blank(), _key: newKey() };
    list.push(item);
    cards.set(item._key, true);
}

function removeItem(field: ListField, index: number): void {
    items(field).splice(index, 1);
}

function moveCard(field: ListField, index: number, delta: -1 | 1): void {
    props.data[field.key] = moveItem(items(field), index, delta);
}

function cardTitle(field: ListField, item: Data, index: number): string {
    return (
        field.title?.(item) ||
        `${field.noun.replace(/^./, (c) => c.toUpperCase())} ${index + 1}`
    );
}

const cardPath = (field: ListField, index: number): string =>
    `${pathOf(field)}.${index}`;
</script>

<template>
    <div class="grid gap-5">
        <template
            v-for="field in visible"
            :key="`${field.key}-${field.kind}-${field.label}`"
        >
            <!-- text -->
            <FormField
                v-if="field.kind === 'text' && field.rows"
                :id="idOf(field)"
                :label="field.label"
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field)"
                :counter="{
                    length: String(data[field.key] ?? '').length,
                    max: field.max,
                }"
            >
                <template #default="{ describedby, invalid }">
                    <Textarea
                        :id="idOf(field)"
                        :model-value="data[field.key] ?? ''"
                        :rows="field.rows"
                        :maxlength="field.max"
                        :disabled="disabled"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                        @update:model-value="set(field, String($event))"
                    />
                </template>
            </FormField>
            <TextField
                v-else-if="field.kind === 'text'"
                :id="idOf(field)"
                :label="field.label"
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field)"
                :maxlength="field.max"
                :placeholder="field.placeholder"
                :disabled="disabled"
                :model-value="data[field.key] ?? ''"
                @update:model-value="set(field, String($event ?? ''))"
            />

            <!-- number -->
            <TextField
                v-else-if="field.kind === 'number'"
                :id="idOf(field)"
                :label="
                    field.suffix
                        ? `${field.label} (${field.suffix})`
                        : field.label
                "
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field)"
                numeric
                :min="field.min"
                :max="field.max"
                :step="field.step"
                :disabled="disabled"
                :model-value="data[field.key] ?? null"
                @update:model-value="set(field, $event === '' ? null : $event)"
            />

            <!-- rich text -->
            <FormField
                v-else-if="field.kind === 'html'"
                :id="idOf(field)"
                :label="field.label"
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field)"
            >
                <template #default="{ describedby, invalid }">
                    <RichTextField
                        :id="idOf(field)"
                        :model-value="data[field.key] ?? ''"
                        :rows="field.rows"
                        :min-words="field.minWords"
                        :invalid="invalid"
                        :describedby="describedby"
                        @update:model-value="set(field, $event)"
                    />
                </template>
            </FormField>

            <!-- select -->
            <ChoiceField
                v-else-if="field.kind === 'select'"
                :id="idOf(field)"
                :label="field.label"
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field)"
                :options="selectOptions(field)"
                :none-label="
                    field.required ? undefined : (field.none ?? 'None')
                "
                :disabled="disabled"
                :model-value="data[field.key] ?? null"
                @update:model-value="set(field, $event === '' ? null : $event)"
            />

            <!-- switch -->
            <SwitchRow
                v-else-if="field.kind === 'switch'"
                :id="idOf(field)"
                :label="field.label"
                :helper="field.helper"
                :error="errorOf(field)"
                :disabled="disabled"
                :model-value="!!data[field.key]"
                @update:model-value="set(field, $event)"
            />

            <!-- one library item -->
            <FormField
                v-else-if="field.kind === 'image' || field.kind === 'asset'"
                :id="idOf(field)"
                :label="field.label"
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field)"
            >
                <template #default="{ describedby, invalid }">
                    <AssetField
                        :id="idOf(field)"
                        :model-value="data[field.key] ?? null"
                        :kinds="
                            field.kind === 'asset'
                                ? field.mediaKinds
                                : (field.mediaKinds ?? ['image', 'svg'])
                        "
                        :picker-title="
                            field.pickerTitle ??
                            `Choose ${field.label.toLowerCase()}`
                        "
                        :invalid="invalid"
                        :describedby="describedby"
                        @update:model-value="set(field, $event)"
                    />
                </template>
            </FormField>

            <!-- several library images -->
            <FormField
                v-else-if="field.kind === 'images'"
                :id="idOf(field)"
                :label="field.label"
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field) ?? errors[`${pathOf(field)}.0`]?.[0]"
            >
                <template #default="{ describedby, invalid }">
                    <MediaIdsField
                        :id="idOf(field)"
                        :model-value="data[field.key] ?? []"
                        :max="field.max"
                        :picker-title="field.pickerTitle ?? 'Choose photos'"
                        :invalid="invalid"
                        :describedby="describedby"
                        @update:model-value="set(field, $event)"
                    />
                </template>
            </FormField>

            <!-- a button or link -->
            <BlockLinkField
                v-else-if="field.kind === 'link'"
                :model-value="data[field.key] ?? null"
                :block-id="blockId"
                :path="pathOf(field)"
                :label="field.label"
                :required="field.required"
                :options="options"
                :errors="errors"
                :disabled="disabled"
                @update:model-value="set(field, $event)"
            />

            <!-- picked records -->
            <FormField
                v-else-if="field.kind === 'ids'"
                :id="idOf(field)"
                :label="field.label"
                :required="field.required"
                :helper="field.helper"
                :error="errorOf(field)"
                group
            >
                <template #default="{ describedby, invalid }">
                    <RelatedPicker
                        :id="idOf(field)"
                        :model-value="data[field.key] ?? []"
                        :options="grouped(field.from)"
                        :max="field.max"
                        :noun="field.noun"
                        :invalid="invalid"
                        :describedby="describedby"
                        @update:model-value="set(field, $event)"
                    />
                </template>
            </FormField>

            <!-- repeater -->
            <div
                v-else-if="field.kind === 'list'"
                :id="idOf(field)"
                class="grid gap-3"
            >
                <div class="flex items-baseline justify-between gap-3">
                    <span class="text-sm font-medium">
                        {{ field.label }}
                        <template v-if="field.required">
                            <span class="text-destructive" aria-hidden="true"
                                >*</span
                            >
                            <span class="sr-only">(required)</span>
                        </template>
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ items(field).length }} of {{ field.max }}
                        <template v-if="field.min > 0">
                            · at least {{ field.min }}</template
                        >
                    </span>
                </div>
                <p
                    v-if="field.helper"
                    class="-mt-2 text-sm text-muted-foreground"
                >
                    {{ field.helper }}
                </p>

                <VueDraggable
                    :model-value="items(field)"
                    handle="[data-drag-handle]"
                    :animation="150"
                    class="grid gap-3"
                    :disabled="disabled"
                    @update:model-value="data[field.key] = $event"
                >
                    <RepeaterCard
                        v-for="(item, index) in items(field)"
                        :key="item._key ?? index"
                        :title="cardTitle(field, item, index)"
                        :summary="field.summary?.(item)"
                        :noun="`${field.noun} ${index + 1}`"
                        :errors="countUnder(errors, cardPath(field, index))"
                        :first="index === 0"
                        :last="index === items(field).length - 1"
                        :disabled="disabled"
                        sortable
                        :open="
                            cards.isOpen(
                                item._key ?? String(index),
                                countUnder(errors, cardPath(field, index)),
                            )
                        "
                        @update:open="
                            cards.set(item._key ?? String(index), $event)
                        "
                        @move="moveCard(field, index, $event)"
                        @remove="removeItem(field, index)"
                    >
                        <BlockFields
                            :fields="field.fields"
                            :data="item"
                            :block-id="blockId"
                            :prefix="cardPath(field, index)"
                            :errors="errors"
                            :disabled="disabled"
                        />
                    </RepeaterCard>
                </VueDraggable>

                <InputError :message="errorOf(field)" />

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="justify-self-start"
                    :disabled="disabled || items(field).length >= field.max"
                    @click="addItem(field)"
                >
                    <Plus aria-hidden="true" />
                    Add {{ field.noun }}
                </Button>
                <p
                    v-if="items(field).length >= field.max"
                    class="text-xs text-muted-foreground"
                >
                    The limit of {{ field.max }} is reached.
                </p>
            </div>
        </template>
    </div>
</template>
