<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { FormOption } from '@/types/forms';

// CMP-FORM-01 Select (Reka, not native). `id` lands on the trigger so labels
// and the error-summary links reach it.
const props = defineProps<{
    id: string;
    options: FormOption[];
    placeholder: string;
    invalid?: boolean;
    describedby?: string;
}>();

const emit = defineEmits<{
    blur: [];
    change: [];
}>();

const model = defineModel<string>({ default: '' });

const value = computed({
    get: () => (model.value === '' ? undefined : model.value),
    set: (next) => {
        model.value = next ?? '';
        emit('change');
    },
});
</script>

<template>
    <Select v-model="value">
        <SelectTrigger
            :id="props.id"
            :aria-invalid="invalid || undefined"
            :aria-describedby="describedby"
            @blur="emit('blur')"
        >
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
