<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    /** CRUD-04: the editor has unsaved changes. */
    dirty?: boolean;
}>();
</script>

<template>
    <!-- CMP-ADM-PAGEHEAD: title + one-line description, actions right, optional tabs below, hairline under. The only h1 of an admin page. -->
    <header class="mb-6 border-b pb-6 xl:mb-8 xl:pb-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="font-display text-[1.75rem]/9 font-medium">
                    {{ title }}
                    <span
                        v-if="dirty"
                        class="ml-1 inline-block size-2.5 rounded-full bg-warning align-middle"
                        title="Unsaved changes"
                    >
                        <span class="sr-only">(unsaved changes)</span>
                    </span>
                </h1>
                <p
                    v-if="description"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div
                v-if="$slots.actions"
                class="flex flex-wrap items-center gap-2"
            >
                <slot name="actions" />
            </div>
        </div>
        <div v-if="$slots.tabs" class="mt-6">
            <slot name="tabs" />
        </div>
    </header>
</template>
