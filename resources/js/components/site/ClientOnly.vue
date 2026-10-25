<script setup lang="ts">
import { onMounted, ref } from 'vue';

// SEO-REN-08: renders its slot only after mount, so browser-only code
// (window, localStorage, matchMedia) never runs during SSR or hydration.
// The `fallback` slot is what SSR and the first client render show.
const mounted = ref(false);

onMounted(() => {
    mounted.value = true;
});
</script>

<template>
    <slot v-if="mounted" />
    <slot v-else name="fallback" />
</template>
