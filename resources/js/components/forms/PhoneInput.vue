<script setup lang="ts">
import { computed, nextTick } from 'vue';
import { fieldControlClass, fieldReadOnlyClass } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select';
import { BD, formatPhoneInput } from '@/lib/forms/phone';
import { cn } from '@/lib/utils';
import type { FormCountry } from '@/types/forms';

// CMP-FORM-01 phone input (FR-LEAD-002): a country select defaulting to
// Bangladesh (+88) and a number that formats as you type, `01712-345678`.
// The server normalises to E.164; other countries accept a national number
// or a number typed with its `+`.
const props = defineProps<{
    id: string;
    countries: FormCountry[];
    invalid?: boolean;
    describedby?: string;
    autocomplete?: string;
}>();

const emit = defineEmits<{
    blur: [];
    change: [];
}>();

const phone = defineModel<string>('phone', { default: '' });
const country = defineModel<string>('country', { default: BD });

const dial = computed(
    () => props.countries.find((item) => item.code === country.value)?.dial,
);

/** Digits before the caret survive a reformat, so editing mid-number works. */
function onInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const caret = input.selectionStart ?? input.value.length;
    const digitsBefore = input.value.slice(0, caret).replace(/\D/g, '').length;
    const formatted = formatPhoneInput(input.value, country.value);

    input.value = formatted;
    phone.value = formatted;

    void nextTick(() => {
        let seen = 0;
        let position = formatted.length;

        if (digitsBefore === 0) {
            position = 0;
        } else {
            for (let index = 0; index < formatted.length; index++) {
                if (/\d/.test(formatted[index])) {
                    seen++;
                }

                if (seen === digitsBefore) {
                    position = index + 1;
                    break;
                }
            }
        }

        if (document.activeElement === input) {
            input.setSelectionRange(position, position);
        }
    });

    emit('change');
}

function onCountry(value: unknown): void {
    country.value = String(value);
    phone.value = formatPhoneInput(phone.value, country.value);
    emit('change');
}
</script>

<template>
    <div class="flex gap-2">
        <Select :model-value="country" @update:model-value="onCountry">
            <SelectTrigger
                class="w-26 shrink-0 px-3"
                aria-label="Country code"
                :aria-invalid="invalid || undefined"
            >
                <span class="tabular">+{{ dial }}</span>
            </SelectTrigger>
            <SelectContent class="min-w-64">
                <SelectItem
                    v-for="item in countries"
                    :key="item.code"
                    :value="item.code"
                >
                    {{ item.name }} (+{{ item.dial }})
                </SelectItem>
            </SelectContent>
        </Select>
        <input
            :id="id"
            :value="phone"
            type="tel"
            inputmode="tel"
            :autocomplete="autocomplete ?? 'tel-national'"
            :placeholder="country === BD ? '01712-345678' : '50 123 4567'"
            :aria-invalid="invalid || undefined"
            :aria-describedby="describedby"
            :maxlength="country === BD ? 12 : 20"
            :class="cn(fieldControlClass, fieldReadOnlyClass, 'h-11 py-2')"
            @input="onInput"
            @blur="emit('blur')"
        />
    </div>
</template>
