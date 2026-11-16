<script setup lang="ts">
import { Plus, X } from '@lucide/vue';
import { computed, inject, ref } from 'vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
import MediaField from '@/components/admin/media/MediaField.vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import type { MediaAsset, SettingField } from '@/types';
import { settingFieldId, settingsContextKey } from './context';

// One setting, drawn from its registry schema (App\Support\Settings\Field). It renders itself
// again for the children of an object and the rows of a list. HELP-03: helper line under the
// control; VAL-03: maxlength, a counter from 80 % of the limit, the right input type, required
// marker. Errors come from the server (VAL-01) and are shown under the control.
const props = defineProps<{
    field: SettingField;
    /** Dotted path in the form, e.g. `values.thank_you.contact.title`. */
    path: string;
    /** A list row: the label is read by screen readers only. */
    bare?: boolean;
}>();

const model = defineModel<unknown>();

const ctx = inject(settingsContextKey);

if (!ctx) {
    throw new Error('SettingField needs the settings context.');
}

const id = computed(() => settingFieldId(props.path));
const error = computed(() => ctx.errors[props.path]);
const describedby = computed(() =>
    props.field.help && !props.bare ? `${id.value}-help` : undefined,
);

const text = computed(() => String(model.value ?? ''));

const inputTypes: Partial<Record<SettingField['type'], string>> = {
    email: 'email',
    phone: 'tel',
    secret: 'password',
};
const inputModes: Partial<
    Record<SettingField['type'], 'numeric' | 'tel' | 'email' | 'url'>
> = { int: 'numeric', phone: 'tel', email: 'email', url: 'url' };

const inputType = computed(() => inputTypes[props.field.type] ?? 'text');
const inputMode = computed(() => inputModes[props.field.type]);
const maxLength = computed(() =>
    props.field.type === 'int' ? 9 : props.field.max,
);

const placeholder = computed(() => {
    if (props.field.type === 'secret' && ctx.secrets[props.field.key]) {
        return 'Leave blank to keep the saved value';
    }

    return props.field.placeholder;
});

// --- Media ---------------------------------------------------------------------------------------
const asset = ref<MediaAsset | null>(
    props.field.type === 'media' && model.value
        ? (ctx.assets[Number(model.value)] ?? null)
        : null,
);

// --- Object --------------------------------------------------------------------------------------
const children = computed(() => props.field.fields ?? []);
const objectValue = computed(
    () => (model.value ?? {}) as Record<string, unknown>,
);
// An object holding a list (a thank-you page with its steps) folds away; plain groups stay open.
const collapsible = computed(
    () =>
        props.field.type === 'object' &&
        children.value.some((child) => child.type === 'list') &&
        !children.value.some((child) => child.type === 'object'),
);
const hasErrorWithin = computed(() =>
    Object.keys(ctx.errors).some(
        (key) => key === props.path || key.startsWith(`${props.path}.`),
    ),
);

function setChild(key: string, value: unknown): void {
    model.value = { ...objectValue.value, [key]: value };
}

// --- List ----------------------------------------------------------------------------------------
const rows = computed(() => (model.value ?? []) as unknown[]);
const itemName = computed(() => props.field.item?.label.toLowerCase() ?? 'row');
const addReason = computed(() =>
    props.field.max_items && rows.value.length >= props.field.max_items
        ? `You can add up to ${props.field.max_items}.`
        : null,
);

function addRow(): void {
    model.value = [...rows.value, structuredClone(props.field.blank ?? '')];
}

function removeRow(index: number): void {
    model.value = rows.value.filter((_, position) => position !== index);
}

function setRow(index: number, value: unknown): void {
    model.value = rows.value.map((row, position) =>
        position === index ? value : row,
    );
}

function rowField(index: number): SettingField {
    return {
        ...(props.field.item as SettingField),
        label: `${props.field.label}, ${itemName.value} ${index + 1}`,
    };
}
</script>

<template>
    <!-- Switch -->
    <div
        v-if="field.type === 'bool'"
        class="flex items-start justify-between gap-4"
    >
        <div class="grid gap-1">
            <Label :for="id" class="leading-snug">{{ field.label }}</Label>
            <FieldHelp v-if="field.help" :id="id">{{ field.help }}</FieldHelp>
            <InputError :message="error" />
        </div>
        <Switch
            :id="id"
            :model-value="!!model"
            :aria-describedby="describedby"
            @update:model-value="model = $event"
        />
    </div>

    <!-- Object: named children -->
    <component
        :is="collapsible ? 'details' : 'fieldset'"
        v-else-if="field.type === 'object'"
        :open="collapsible && hasErrorWithin ? true : undefined"
        class="group rounded-lg border"
    >
        <component
            :is="collapsible ? 'summary' : 'legend'"
            :class="
                cn(
                    'text-sm font-medium',
                    collapsible
                        ? 'flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3'
                        : 'px-1',
                )
            "
        >
            {{ field.label }}
            <span
                v-if="collapsible && hasErrorWithin"
                class="text-xs font-normal text-destructive"
                >Needs attention</span
            >
            <span
                v-else-if="collapsible"
                class="text-xs font-normal text-muted-foreground group-open:hidden"
                >Edit</span
            >
        </component>
        <div class="grid gap-4 p-4" :class="collapsible ? 'border-t' : ''">
            <SettingField
                v-for="child in children"
                :key="child.key"
                :field="child"
                :path="`${path}.${child.key}`"
                :model-value="objectValue[child.key]"
                @update:model-value="setChild(child.key, $event)"
            />
        </div>
    </component>

    <!-- List: rows of one scalar kind or of objects -->
    <div
        v-else-if="field.type === 'list'"
        class="grid gap-3"
        role="group"
        :aria-labelledby="`${id}-label`"
    >
        <p :id="`${id}-label`" class="text-sm leading-none font-medium">
            {{ field.label }}
            <span
                v-if="field.required"
                class="text-destructive"
                aria-hidden="true"
                >*</span
            >
        </p>
        <FieldHelp v-if="field.help" :id="id">{{ field.help }}</FieldHelp>

        <p v-if="!rows.length" class="text-sm text-muted-foreground">
            Nothing added yet.
        </p>

        <template v-if="field.item">
            <div
                v-for="(row, index) in rows"
                :key="index"
                class="flex items-start gap-2"
            >
                <div class="min-w-0 flex-1">
                    <SettingField
                        :field="rowField(index)"
                        :path="`${path}.${index}`"
                        bare
                        :model-value="row"
                        @update:model-value="setRow(index, $event)"
                    />
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="`Remove ${itemName} ${index + 1}`"
                    @click="removeRow(index)"
                >
                    <X />
                </Button>
            </div>
        </template>
        <template v-else>
            <div
                v-for="(row, index) in rows"
                :key="index"
                class="grid gap-4 rounded-lg border p-4"
            >
                <div class="flex items-center justify-between gap-3">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ index + 1 }}
                    </p>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        :aria-label="`Remove row ${index + 1} of ${field.label}`"
                        @click="removeRow(index)"
                    >
                        <X /> Remove
                    </Button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <SettingField
                        v-for="child in children"
                        :key="child.key"
                        :field="child"
                        :path="`${path}.${index}.${child.key}`"
                        :model-value="
                            (row as Record<string, unknown>)[child.key]
                        "
                        @update:model-value="
                            setRow(index, {
                                ...(row as Record<string, unknown>),
                                [child.key]: $event,
                            })
                        "
                    />
                </div>
            </div>
        </template>

        <InputError :message="error" />
        <div>
            <ReasonButton
                variant="outline"
                size="sm"
                :reason="addReason"
                @click="addRow"
            >
                <Plus />
                {{ field.item ? `Add ${itemName}` : 'Add another' }}
            </ReasonButton>
        </div>
    </div>

    <!-- Library image -->
    <div v-else-if="field.type === 'media'" class="grid gap-2">
        <Label :for="id">{{ field.label }}</Label>
        <MediaField
            :id="id"
            :model-value="(model as number | null) ?? null"
            v-model:asset="asset"
            :kinds="field.kinds"
            choose-label="Choose image"
            :picker-title="`Choose: ${field.label}`"
            :describedby="describedby"
            :invalid="!!error"
            @update:model-value="model = $event"
        />
        <FieldHelp v-if="field.help" :id="id">
            {{ field.help }}
            <template v-if="field.media_hint">
                {{ field.media_hint }}.</template
            >
        </FieldHelp>
        <InputError :message="error" />
    </div>

    <!-- Everything else is one labelled control -->
    <div v-else class="grid gap-2">
        <Label :for="id" :class="bare ? 'sr-only' : ''">
            {{ field.label }}
            <span
                v-if="field.required"
                class="text-destructive"
                aria-hidden="true"
                >*</span
            >
        </Label>

        <Select
            v-if="field.type === 'select'"
            :model-value="text"
            @update:model-value="model = $event"
        >
            <SelectTrigger
                :id="id"
                class="w-full"
                :aria-invalid="!!error || undefined"
                :aria-describedby="describedby"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="option in field.options"
                    :key="option.value"
                    :value="option.value"
                    >{{ option.label }}</SelectItem
                >
            </SelectContent>
        </Select>

        <template v-else-if="field.type === 'secret'">
            <Input
                :id="id"
                :model-value="text"
                type="password"
                autocomplete="new-password"
                :maxlength="maxLength"
                :placeholder="placeholder"
                :disabled="ctx.isCleared(field.key)"
                :aria-invalid="!!error || undefined"
                :aria-describedby="describedby"
                @update:model-value="model = $event"
            />
            <div
                v-if="ctx.secrets[field.key]"
                class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
            >
                <span v-if="ctx.isCleared(field.key)"
                    >The saved value is removed when you save.</span
                >
                <span v-else>A value is saved and hidden.</span>
                <Button
                    type="button"
                    variant="link"
                    size="sm"
                    class="h-auto p-0"
                    @click="ctx.toggleClear(field.key)"
                >
                    {{
                        ctx.isCleared(field.key)
                            ? 'Keep it'
                            : 'Remove the saved value'
                    }}
                </Button>
            </div>
        </template>

        <Textarea
            v-else-if="field.type === 'textarea'"
            :id="id"
            :model-value="text"
            :maxlength="maxLength"
            :placeholder="placeholder"
            :aria-invalid="!!error || undefined"
            :aria-describedby="describedby"
            :class="field.mono ? 'font-mono text-[13px]' : ''"
            @update:model-value="model = $event"
        />

        <Input
            v-else
            :id="id"
            :model-value="text"
            :type="inputType"
            :inputmode="inputMode"
            :maxlength="maxLength"
            :placeholder="placeholder"
            :aria-invalid="!!error || undefined"
            :aria-describedby="describedby"
            :class="field.mono ? 'font-mono text-[13px]' : ''"
            @update:model-value="model = $event"
        />

        <CharCounter
            v-if="field.max && field.type !== 'int' && field.type !== 'select'"
            :value="text"
            :max="field.max"
        />
        <FieldHelp v-if="field.help && !bare" :id="id">{{
            field.help
        }}</FieldHelp>
        <InputError :message="error" />
    </div>
</template>
