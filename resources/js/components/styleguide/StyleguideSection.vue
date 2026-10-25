<script setup lang="ts">
// One styleguide section: an H2 that labels the region, an optional intro,
// then the specimens.
defineProps<{
    id: string;
    title: string;
    description?: string;
}>();
</script>

<template>
    <section :aria-labelledby="id" class="flex flex-col gap-6">
        <h2 :id="id" class="font-display text-h2">{{ title }}</h2>
        <p v-if="description" class="text-muted-foreground">
            {{ description }}
        </p>
        <slot />
    </section>
</template>
