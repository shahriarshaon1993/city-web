<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Pause, Play } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import HeroShell from '@/components/blocks/hero/HeroShell.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { Button } from '@/components/ui/button';
import { useHeroVideo } from '@/composables/useHeroVideo';
import { cn } from '@/lib/utils';
import type { HeroBlockData } from '@/types/blocks';
import type { SiteShared } from '@/types/site';

// CMP-HERO-01/02: one image, or a video over its poster. The image/poster
// is the page's LCP element (HERO-012, HERO-021, preloaded by the root view).
const props = defineProps<{
    data: HeroBlockData;
    first: boolean;
}>();

const page = usePage<SiteShared>();

const media = useTemplateRef<HTMLElement>('media');
const videoElement = useTemplateRef<HTMLVideoElement>('video');

const video = useHeroVideo({
    video: () => (props.data.media_type === 'video' ? props.data.video : null),
    root: media,
    element: videoElement,
    animations: () => page.props.ui.motion.animations,
    allowMobile: () => page.props.ui.motion.hero_video_mobile,
});

const { inserted, ready, paused, src, toggle, onCanPlayThrough, onError } =
    video;
</script>

<template>
    <HeroShell :data="data" :first="first">
        <template #media>
            <div ref="media" class="absolute inset-0">
                <ResponsiveImage
                    :image="data.image"
                    :mobile="data.mobile_image"
                    preset="hero"
                    :priority="first"
                    class="absolute inset-0"
                />
                <!-- HERO-024; inserted client-side only (HERO-026). -->
                <video
                    v-if="inserted && src"
                    ref="video"
                    :src="src"
                    muted
                    loop
                    playsinline
                    disablepictureinpicture
                    preload="none"
                    :autoplay="!paused"
                    aria-hidden="true"
                    tabindex="-1"
                    :class="
                        cn(
                            'absolute inset-0 size-full object-cover opacity-0 transition-opacity duration-600 ease-(--ease-standard)',
                            ready && 'opacity-100',
                        )
                    "
                    :style="{
                        objectPosition: data.image
                            ? `${data.image.focal.x}% ${data.image.focal.y}%`
                            : undefined,
                    }"
                    @canplaythrough="onCanPlayThrough"
                    @error="onError"
                />
            </div>
        </template>

        <template v-if="inserted" #controls>
            <!-- HERO-027 / WCAG 2.2.2. -->
            <Button
                type="button"
                variant="on-media"
                size="icon"
                :aria-label="
                    paused ? 'Play background video' : 'Pause background video'
                "
                @click="toggle"
            >
                <Play v-if="paused" class="size-5" aria-hidden="true" />
                <Pause v-else class="size-5" aria-hidden="true" />
            </Button>
        </template>
    </HeroShell>
</template>
