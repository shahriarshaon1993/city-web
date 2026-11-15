<script setup lang="ts">
import { computed } from 'vue';

// VAL-07: the red count on an editor tab or a repeater card header ("2 errors"), so an error
// in a field that is not on screen can be found. Renders nothing when there are none.
const props = defineProps<{ count: number }>();

const label = computed(() =>
    props.count === 1 ? '1 error' : `${props.count} errors`,
);
</script>

<template>
    <span
        v-if="count > 0"
        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1.5 text-xs font-medium text-white"
        role="status"
    >
        <span aria-hidden="true">{{ count }}</span>
        <span class="sr-only">{{ label }}</span>
    </span>
</template>
