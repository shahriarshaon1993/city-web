<script setup lang="ts" generic="T extends string | number">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

// A select for catalogue editors: string or number values, optional groups (areas by city) and an
// optional "None" choice (Reka items cannot have an empty value, so it is a sentinel).
type Option = { value: T; label: string; group?: string };

const props = defineProps<{
    id?: string;
    options: Option[];
    placeholder?: string;
    invalid?: boolean;
    describedby?: string;
    /** Adds a first choice that clears the value. */
    noneLabel?: string;
    disabled?: boolean;
}>();

const model = defineModel<T | null | ''>({ default: null });

const NONE = '__none__';

const value = computed<string | undefined>({
    get: () =>
        model.value === null || model.value === ''
            ? undefined
            : String(model.value),
    set: (next) => {
        if (next === undefined || next === NONE) {
            model.value = null;

            return;
        }

        const found = props.options.find(
            (option) => String(option.value) === next,
        );

        model.value = found ? found.value : null;
    },
});

const groups = computed(() => {
    const result: Array<{ label: string | null; options: Option[] }> = [];

    for (const option of props.options) {
        const label = option.group ?? null;
        const last = result[result.length - 1];

        if (last && last.label === label) {
            last.options.push(option);
        } else {
            result.push({ label, options: [option] });
        }
    }

    return result;
});
</script>

<template>
    <Select v-model="value" :disabled="disabled">
        <SelectTrigger
            :id="id"
            :aria-invalid="invalid || undefined"
            :aria-describedby="describedby"
        >
            <SelectValue :placeholder="placeholder ?? 'Choose…'" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-if="noneLabel" :value="NONE">{{
                noneLabel
            }}</SelectItem>
            <SelectGroup v-for="(group, index) in groups" :key="index">
                <SelectLabel v-if="group.label">{{ group.label }}</SelectLabel>
                <SelectItem
                    v-for="option in group.options"
                    :key="String(option.value)"
                    :value="String(option.value)"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectGroup>
        </SelectContent>
    </Select>
</template>
