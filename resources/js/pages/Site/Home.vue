<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { about } from '@/routes';
import type { LcpImage } from '@/types/site';

// Stub until the home blocks of 2.x. The hero image is the page's LCP image
// (preloaded by the root view, HERO-030).
defineProps<{
    title: string;
    lead: string;
    hero: LcpImage;
}>();
</script>

<template>
    <main>
        <div class="h-[60svh] min-h-80">
            <ResponsiveImage
                :image="hero.image"
                :mobile="hero.mobile"
                preset="hero"
                priority
            />
        </div>
        <div class="mx-auto flex max-w-3xl flex-col gap-4 px-6 py-16">
            <h1 class="font-display text-5xl font-extrabold tracking-tight">
                {{ title }}
            </h1>
            <p class="text-lg">{{ lead }}</p>
            <p>
                <Link
                    :href="about()"
                    class="font-medium text-primary underline underline-offset-4"
                >
                    About
                </Link>
            </p>
        </div>
    </main>
</template>
