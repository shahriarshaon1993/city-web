<script setup lang="ts">
import { useId } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';

// A multi-select as hairline checkbox rows (FR-SHORT-004: the shortlist
// enquiry's projects). Name it with FormField `group` (fieldset/legend).
// Each row is a 44 px+ target; `hint` is a muted overline under the label.
defineProps<{
    options: { value: string; label: string; hint?: string }[];
    invalid?: boolean;
}>();

const emit = defineEmits<{
    change: [];
}>();

const model = defineModel<string[]>({ default: () => [] });
const uid = useId();

function toggle(value: string, checked: boolean): void {
    model.value = checked
        ? [...model.value, value]
        : model.value.filter((item) => item !== value);
    emit('change');
}
</script>

<template>
    <ul class="divide-y divide-border border-y border-border">
        <li v-for="(option, index) in options" :key="option.value">
            <div class="flex min-h-14 items-center gap-3 py-2">
                <Checkbox
                    :id="`${uid}-${index}`"
                    :model-value="model.includes(option.value)"
                    :aria-invalid="invalid || undefined"
                    @update:model-value="
                        (checked) => toggle(option.value, checked === true)
                    "
                />
                <label
                    :for="`${uid}-${index}`"
                    class="flex min-w-0 flex-1 cursor-pointer flex-col gap-0.5"
                >
                    <span class="text-sm">{{ option.label }}</span>
                    <span
                        v-if="option.hint"
                        class="text-overline text-muted-foreground uppercase"
                    >
                        {{ option.hint }}
                    </span>
                </label>
            </div>
        </li>
    </ul>
</template>
