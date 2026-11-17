<script setup lang="ts">
import { computed } from 'vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import DatePicker from '@/components/forms/DatePicker.vue';

// A date + time picker (the go-live time of CMS-PUB-003): the date through DatePicker, the time as
// two selects. The model is local wall time `YYYY-MM-DDTHH:mm` (the zone is the caller's concern).
// A time before `min` on the first allowed day is moved up to `min`, so it cannot be submitted.
const props = defineProps<{
    id: string;
    /** `YYYY-MM-DDTHH:mm`. */
    min: string;
    /** `YYYY-MM-DDTHH:mm`. */
    max: string;
    placeholder?: string;
    invalid?: boolean;
    describedby?: string;
}>();

const model = defineModel<string>({ default: '' });

const date = computed(() => model.value.slice(0, 10));
const hour = computed(() => model.value.slice(11, 13) || '09');
const minute = computed(() => model.value.slice(14, 16) || '00');

const hours = Array.from({ length: 24 }, (_, index) => {
    const value = String(index).padStart(2, '0');

    return { value, label: value };
});

const minutes = computed(() => {
    const steps = Array.from({ length: 12 }, (_, index) =>
        String(index * 5).padStart(2, '0'),
    );

    if (!steps.includes(minute.value)) {
        steps.push(minute.value);
        steps.sort();
    }

    return steps.map((value) => ({ value, label: value }));
});

function set(nextDate: string, nextHour: string, nextMinute: string): void {
    let value = `${nextDate}T${nextHour}:${nextMinute}`;

    if (value < props.min) {
        value = props.min;
    }

    model.value = value;
}
</script>

<template>
    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
        <DatePicker
            :id="id"
            :model-value="date"
            :min="min.slice(0, 10)"
            :max="max.slice(0, 10)"
            :placeholder="placeholder ?? 'Choose a date'"
            :invalid="invalid"
            :describedby="describedby"
            @update:model-value="(value: string) => set(value, hour, minute)"
        />
        <div class="flex items-center gap-1.5">
            <div class="w-20">
                <AdminSelect
                    :id="`${id}-hour`"
                    :model-value="hour"
                    :options="hours"
                    :invalid="invalid"
                    @update:model-value="
                        (value) => set(date, String(value ?? hour), minute)
                    "
                />
            </div>
            <span class="text-muted-foreground" aria-hidden="true">:</span>
            <div class="w-20">
                <AdminSelect
                    :id="`${id}-minute`"
                    :model-value="minute"
                    :options="minutes"
                    :invalid="invalid"
                    @update:model-value="
                        (value) => set(date, hour, String(value ?? minute))
                    "
                />
            </div>
        </div>
    </div>
</template>
