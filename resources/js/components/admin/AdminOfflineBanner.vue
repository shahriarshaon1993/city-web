<script setup lang="ts">
import { useOnline } from '@vueuse/core';
import { WifiOff } from '@lucide/vue';

// UX-06: offline banner on admin forms. Mounted once in the layout, so every form is covered.
const online = useOnline();
</script>

<template>
    <div role="status" aria-live="polite">
        <p
            v-if="!online"
            class="flex items-center gap-2 border-b border-warning/40 bg-warning/15 px-4 py-2 text-sm text-foreground md:px-6 xl:px-8"
        >
            <WifiOff class="size-4 shrink-0" aria-hidden="true" />
            You're offline — changes will not be sent.
        </p>
    </div>
</template>
