<script setup lang="ts">
// One PG-04 content section (an anchor of CMP-TABS-02): light H2 on a
// hairline, content below. Sections stack in the 8-column content column,
// separated by section-sm spacing.
defineProps<{
    id: string;
    heading: string;
}>();
</script>

<template>
    <section
        :id="id"
        :aria-labelledby="`${id}-heading`"
        class="pt-(--section-sm) last:pb-(--section-md)"
    >
        <h2
            :id="`${id}-heading`"
            v-reveal
            class="mb-8 border-b border-border pb-5 font-display text-h2 text-balance lg:mb-10 lg:pb-6"
        >
            {{ heading }}
        </h2>
        <slot />
    </section>
</template>
