<script setup lang="ts">
import { CalendarDays, ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { fieldControlClass } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

// CMP-FORM-01 month picker (dates stored with month precision: launch, handover, progress reports).
// Popover with a year stepper and the twelve months; shown as `Dec 2027`, the model is `YYYY-MM`
// ("" when empty). `min` / `max` are `YYYY-MM`; months outside are disabled (VAL-03).
const props = defineProps<{
    id: string;
    placeholder?: string;
    min?: string;
    max?: string;
    /** Adds a "Clear" button; leave off for required fields. */
    clearable?: boolean;
    invalid?: boolean;
    describedby?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{ blur: []; change: [] }>();

const model = defineModel<string | null>({ default: '' });
const open = ref(false);

const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

function parse(value: string | null | undefined): [number, number] | null {
    const match = /^(\d{4})-(\d{2})/.exec(value ?? '');

    return match ? [Number(match[1]), Number(match[2])] : null;
}

const selected = computed(() => parse(model.value));

function startYear(): number {
    return (
        selected.value?.[0] ?? parse(props.min)?.[0] ?? new Date().getFullYear()
    );
}

const year = ref(startYear());

watch(open, (isOpen) => {
    if (isOpen) {
        year.value = startYear();
    }
});

const label = computed(() =>
    selected.value
        ? `${MONTHS[selected.value[1] - 1]} ${selected.value[0]}`
        : '',
);

function key(month: number): string {
    return `${year.value}-${String(month).padStart(2, '0')}`;
}

function disabledMonth(month: number): boolean {
    const value = key(month);

    return (
        (!!props.min && value < props.min) || (!!props.max && value > props.max)
    );
}

const canPrev = computed(
    () => !props.min || `${year.value - 1}-12` >= props.min,
);
const canNext = computed(
    () => !props.max || `${year.value + 1}-01` <= props.max,
);

function pick(month: number): void {
    model.value = key(month);
    open.value = false;
    emit('change');
}

function clear(): void {
    model.value = '';
    open.value = false;
    emit('change');
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <button
                :id="id"
                type="button"
                :disabled="disabled"
                :aria-invalid="invalid || undefined"
                :aria-describedby="describedby"
                aria-haspopup="dialog"
                :class="
                    cn(
                        fieldControlClass,
                        'flex h-11 items-center justify-between gap-2 py-2 text-left',
                        !label && 'text-muted-foreground',
                    )
                "
                @blur="emit('blur')"
            >
                <span class="tabular">{{
                    label || placeholder || 'Choose a month'
                }}</span>
                <CalendarDays
                    class="size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
            </button>
        </PopoverTrigger>
        <PopoverContent class="w-64 p-3" align="start">
            <div class="flex items-center justify-between gap-2 pb-3">
                <Button
                    type="button"
                    variant="outline"
                    size="icon-sm"
                    :disabled="!canPrev"
                    aria-label="Previous year"
                    @click="year--"
                >
                    <ChevronLeft aria-hidden="true" />
                </Button>
                <p class="text-sm font-medium tabular-nums" aria-live="polite">
                    {{ year }}
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="icon-sm"
                    :disabled="!canNext"
                    aria-label="Next year"
                    @click="year++"
                >
                    <ChevronRight aria-hidden="true" />
                </Button>
            </div>
            <div
                class="grid grid-cols-3 gap-1.5"
                role="group"
                :aria-label="`Months of ${year}`"
            >
                <button
                    v-for="(name, index) in MONTHS"
                    :key="name"
                    type="button"
                    :disabled="disabledMonth(index + 1)"
                    :aria-pressed="model === key(index + 1)"
                    :class="
                        cn(
                            'h-10 rounded-md text-sm transition-colors outline-none hover:bg-muted focus-visible:ring-3 focus-visible:ring-ring/25 disabled:pointer-events-none disabled:opacity-40',
                            model === key(index + 1) &&
                                'bg-primary text-primary-foreground hover:bg-primary',
                        )
                    "
                    @click="pick(index + 1)"
                >
                    {{ name }}
                </button>
            </div>
            <Button
                v-if="clearable && label"
                type="button"
                variant="ghost"
                size="sm"
                class="mt-2 w-full"
                @click="clear"
            >
                <X aria-hidden="true" />
                Clear
            </Button>
        </PopoverContent>
    </Popover>
</template>
