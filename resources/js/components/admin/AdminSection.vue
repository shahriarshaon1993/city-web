<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <!-- CMP-ADM-FORM section: the title and its help sit on top, the fields use the full card width below.
         (Owner-approved: the old title-left / fields-right split pushed the fields off-screen.) -->
    <section class="rounded-xl border bg-card p-5 text-card-foreground md:p-7">
        <header class="mb-5 border-b pb-4">
            <h2 class="text-base font-semibold tracking-tight">{{ title }}</h2>
            <p
                v-if="description"
                class="mt-1 max-w-prose text-sm text-muted-foreground"
            >
                {{ description }}
            </p>
        </header>
        <div class="min-w-0">
            <slot />
        </div>
    </section>
</template>
