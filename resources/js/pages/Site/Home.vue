<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { about } from '@/routes';
import type { LcpImage } from '@/types/site';

// Stub until the home blocks of 2.x. The hero image is the page's LCP image
// (preloaded by the root view, HERO-030); the header sits over it
// (`starts_with_hero`). The tall body lets the headroom header be tested.
defineProps<{
    title: string;
    lead: string;
    hero: LcpImage;
}>();
</script>

<template>
    <div>
        <!-- `data-hero`: the mobile contact bar appears once this leaves the viewport (CMP-BAR-01). -->
        <div class="h-[85svh] min-h-80" data-hero>
            <ResponsiveImage
                :image="hero.image"
                :mobile="hero.mobile"
                preset="hero"
                priority
            />
        </div>
        <div class="mx-auto flex min-h-svh max-w-3xl flex-col gap-4 px-6 py-16">
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
    </div>
</template>
