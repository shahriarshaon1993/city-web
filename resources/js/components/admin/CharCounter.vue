<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

// VAL-03: a character counter from 80 % of the limit (always with `always`, for SEO fields).
// `ideal` colours the SEO fields per SEO-ONP-001/002: below `ideal.min` = warning, within
// `ideal` = success, above `max` = destructive.
const props = defineProps<{
    value: string;
    max: number;
    always?: boolean;
    ideal?: { min: number; max: number };
}>();

const length = computed(() => props.value.length);
const visible = computed(
    () => props.always || length.value >= Math.floor(props.max * 0.8),
);

const tone = computed(() => {
    if (length.value > props.max) {
        return 'text-destructive';
    }

    if (props.ideal) {
        if (length.value === 0) {
            return 'text-muted-foreground';
        }

        return length.value >= props.ideal.min &&
            length.value <= props.ideal.max
            ? 'text-success'
            : 'text-warning';
    }

    return length.value >= props.max * 0.95
        ? 'text-warning'
        : 'text-muted-foreground';
});
</script>

<template>
    <p v-if="visible" :class="cn('text-xs tabular-nums', tone)" aria-live="off">
        {{ length }} / {{ max }}
        <span class="sr-only">characters</span>
    </p>
</template>
