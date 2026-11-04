<script setup lang="ts">
import { computed } from 'vue';

// ADM-TBL-004: global search highlights its matches. Plain text segments, no v-html.
const props = defineProps<{ text: string; term?: string }>();

const parts = computed(() => {
    const term = (props.term ?? '').trim();

    if (term === '') {
        return [{ text: props.text, match: false }];
    }

    const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return props.text
        .split(new RegExp(`(${escaped})`, 'gi'))
        .filter((part) => part !== '')
        .map((part) => ({
            text: part,
            match: part.toLowerCase() === term.toLowerCase(),
        }));
});
</script>

<template>
    <span>
        <template v-for="(part, index) in parts" :key="index">
            <mark
                v-if="part.match"
                class="bg-warning-100 rounded-xs px-0.5 text-inherit"
                >{{ part.text }}</mark
            >
            <template v-else>{{ part.text }}</template>
        </template>
    </span>
</template>
