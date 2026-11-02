<script setup lang="ts">
import { parseDate } from '@internationalized/date';
import type { DateValue } from '@internationalized/date';
import { CalendarDays } from '@lucide/vue';
import { computed, ref, shallowRef, watch } from 'vue';
import { Calendar } from '@/components/ui/calendar';
import { fieldControlClass } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

// CMP-FORM-01 date picker: Popover + Calendar, shown as `DD MMM YYYY`, the
// model is an ISO date (`YYYY-MM-DD`). Days outside min…max are disabled
// before the visitor can pick them (VAL-03). The calendar opens on the
// chosen day, or on `min`.
const props = defineProps<{
    id: string;
    /** ISO dates. */
    min: string;
    max: string;
    placeholder: string;
    invalid?: boolean;
    describedby?: string;
}>();

const emit = defineEmits<{
    blur: [];
    change: [];
}>();

const model = defineModel<string>({ default: '' });
const open = ref(false);

const selected = computed<DateValue | undefined>({
    get: () => (model.value ? parseDate(model.value) : undefined),
    set: (next) => {
        model.value = next ? next.toString() : '';
        open.value = false;
        emit('change');
    },
});

const view = shallowRef<DateValue>(parseDate(model.value || props.min));

watch(open, (isOpen) => {
    if (isOpen) {
        view.value = parseDate(model.value || props.min);
    }
});
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <button
                :id="id"
                type="button"
                :aria-invalid="invalid || undefined"
                :aria-describedby="describedby"
                aria-haspopup="dialog"
                :class="
                    cn(
                        fieldControlClass,
                        'flex h-11 items-center justify-between gap-2 py-2 text-left',
                        !model && 'text-muted-foreground',
                    )
                "
                @blur="emit('blur')"
            >
                <span class="tabular">
                    {{ model ? formatDate(model) : placeholder }}
                </span>
                <CalendarDays
                    class="size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
            </button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-3" align="start">
            <Calendar
                v-model="selected"
                v-model:placeholder="view"
                :min-value="parseDate(min)"
                :max-value="parseDate(max)"
            />
        </PopoverContent>
    </Popover>
</template>
