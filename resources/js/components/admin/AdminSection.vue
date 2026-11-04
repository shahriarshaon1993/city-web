<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <!-- CMP-ADM-FORM section: title + help on the left third, fields on the right two thirds from 1280 px. -->
    <section
        class="grid gap-6 rounded-lg border bg-card p-5 text-card-foreground md:p-6 xl:grid-cols-3 xl:gap-10"
    >
        <div>
            <h2 class="text-base font-semibold">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="min-w-0 xl:col-span-2">
            <slot />
        </div>
    </section>
</template>
