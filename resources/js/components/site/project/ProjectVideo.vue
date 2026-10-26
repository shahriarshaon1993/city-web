<script setup lang="ts">
import { ArrowUpRight, Play } from '@lucide/vue';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { ProjectVideo } from '@/types/project';

// CMP-VIDEO-01 facade: the 16:9 poster with a 72 px glass play button and
// the duration; nothing loads from YouTube until the click (SEO-PERF-013),
// then a `youtube-nocookie.com` iframe autoplays, or a native <video> with
// the WebVTT captions track (A11Y-D07). Focus moves into the player. The
// virtual tour is a hairline link row below (new tab, allowed hosts only).
const props = defineProps<{
    video: ProjectVideo | null;
    tourUrl: string | null;
    tourLabel: string;
}>();

const playing = ref(false);
const player = useTemplateRef<HTMLIFrameElement | HTMLVideoElement>('player');

const duration = computed(() => {
    const seconds = props.video?.duration_seconds;

    if (!seconds) {
        return null;
    }

    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
});

const embedUrl = computed(() =>
    props.video?.type === 'youtube'
        ? `https://www.youtube-nocookie.com/embed/${encodeURIComponent(props.video.youtube_id)}?autoplay=1&rel=0&modestbranding=1`
        : null,
);

const tourHost = computed(() =>
    props.tourUrl ? new URL(props.tourUrl).hostname.replace(/^www\./, '') : '',
);

async function play(): Promise<void> {
    playing.value = true;
    await nextTick();
    player.value?.focus();
}
</script>

<template>
    <div class="flex flex-col gap-8">
        <div
            v-if="video"
            class="media-card group/video relative aspect-video overflow-hidden rounded-xl bg-stone-950"
        >
            <template v-if="!playing">
                <ResponsiveImage
                    :image="video.poster"
                    preset="video-poster"
                    class="absolute inset-0 size-full"
                    img-class="media-zoom"
                />
                <div
                    data-overlay
                    class="pointer-events-none absolute inset-0 bg-(image:--gradient-hero-bottom) [--overlay:0.7]"
                    aria-hidden="true"
                />
                <button
                    type="button"
                    class="absolute inset-0 flex items-end gap-4 p-5 text-left text-white outline-none focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-white sm:p-8"
                    :aria-label="`Play video: ${video.title}`"
                    @click="play"
                >
                    <span
                        class="flex size-14 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/35 backdrop-blur-[8px] transition-colors duration-(--dur-fast) ring-inset group-hover/video:bg-white/20 sm:size-18"
                        aria-hidden="true"
                    >
                        <Play class="ml-0.5 size-6 fill-current sm:size-7" />
                    </span>
                    <span
                        class="flex min-w-0 flex-col gap-1 pb-1"
                        aria-hidden="true"
                    >
                        <span class="truncate font-display text-h4">
                            {{ video.title }}
                        </span>
                        <span
                            v-if="duration"
                            class="tabular text-overline text-white/80 uppercase"
                        >
                            {{ duration }}
                        </span>
                    </span>
                </button>
            </template>
            <iframe
                v-else-if="embedUrl"
                ref="player"
                :src="embedUrl"
                :title="video.title"
                class="absolute inset-0 size-full"
                allow="
                    autoplay;
                    encrypted-media;
                    picture-in-picture;
                    fullscreen;
                "
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
            />
            <video
                v-else-if="video.type === 'upload'"
                ref="player"
                class="absolute inset-0 size-full"
                :poster="video.poster?.fallback"
                :aria-label="video.title"
                controls
                autoplay
                playsinline
                preload="metadata"
            >
                <source v-if="video.webm" :src="video.webm" type="video/webm" />
                <source :src="video.mp4" type="video/mp4" />
                <track
                    v-if="video.captions"
                    kind="captions"
                    :src="video.captions"
                    srclang="en"
                    label="English"
                    default
                />
            </video>
        </div>

        <a
            v-if="tourUrl"
            :href="tourUrl"
            target="_blank"
            rel="noopener"
            class="group/tour flex min-h-16 items-center justify-between gap-6 border-y border-border py-4"
        >
            <span class="flex flex-col gap-1">
                <span class="text-overline text-muted-foreground uppercase">
                    360° · {{ tourHost }}
                </span>
                <span
                    class="font-display text-h4 transition-colors duration-(--dur-fast) group-hover/tour:text-primary"
                >
                    {{ tourLabel }}
                    <span class="sr-only">(opens in a new tab)</span>
                </span>
            </span>
            <ArrowUpRight
                class="size-5 shrink-0 transition-transform duration-(--dur-base) group-hover/tour:translate-x-0.5 group-hover/tour:-translate-y-0.5"
                aria-hidden="true"
            />
        </a>
    </div>
</template>
