<script setup lang="ts">
// HELP-03: the helper line under a non-obvious field — where the value appears and its limits.
// Give it the field's `id` so the control can point at it with aria-describedby="{id}-help".
defineProps<{ id: string }>();
</script>

<template>
    <p :id="`${id}-help`" class="text-xs text-muted-foreground">
        <slot />
    </p>
</template>
