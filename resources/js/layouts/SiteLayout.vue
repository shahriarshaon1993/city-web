<script setup lang="ts">
import SeoHead from '@/components/site/SeoHead.vue';

// Persistent public layout, default for every pages/Site/* page (app.ts).
// Header, menus and footer are added in 1.1/1.2.
</script>

<template>
    <div class="contents">
        <SeoHead />
        <slot />
    </div>
</template>
