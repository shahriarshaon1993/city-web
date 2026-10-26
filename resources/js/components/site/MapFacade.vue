<script setup lang="ts">
import { ArrowUpRight, MapPin } from '@lucide/vue';
import { computed, nextTick, ref, useId, useTemplateRef } from 'vue';
import { Button } from '@/components/ui/button';
import { googleMapsEmbedUrl, googleMapsUrl } from '@/lib/maps';

// CMP-MAP-02 / FR-LOC-007: a static map facade — greige box with the 60°
// triangle grid (design/foundations.md §5.4), pin, place and address — so
// no map request is made until "Show interactive map" swaps in the keyless
// Google embed (focus moves to it). "Open in Google Maps" always works,
// with or without JS. Reused by the offices block (6.2).
const props = defineProps<{
    lat: number;
    lng: number;
    /** Iframe title, e.g. "Map of Gemcon Lakeview". */
    title: string;
    place: string;
    address: string | null;
}>();

const patternId = useId();
const loaded = ref(false);
const frame = useTemplateRef<HTMLIFrameElement>('frame');

const point = computed(() => ({ lat: props.lat, lng: props.lng }));

async function load(): Promise<void> {
    loaded.value = true;
    await nextTick();
    frame.value?.focus();
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            class="relative aspect-[4/3] overflow-hidden rounded-xl bg-surface-subtle sm:aspect-video"
        >
            <iframe
                v-if="loaded"
                ref="frame"
                :src="googleMapsEmbedUrl(point)"
                :title="title"
                class="absolute inset-0 size-full border-0"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            />
            <template v-else>
                <svg
                    class="absolute inset-0 size-full text-primary"
                    aria-hidden="true"
                >
                    <defs>
                        <pattern
                            :id="patternId"
                            width="40"
                            height="69.28"
                            patternUnits="userSpaceOnUse"
                        >
                            <path
                                d="M0 0H40M0 34.64H40M0 69.28L40 0M0 0L40 69.28"
                                fill="none"
                                stroke="currentColor"
                                stroke-opacity="0.1"
                                stroke-width="1"
                            />
                        </pattern>
                    </defs>
                    <rect
                        width="100%"
                        height="100%"
                        :fill="`url(#${patternId})`"
                    />
                </svg>
                <div
                    class="absolute top-[36%] left-1/2 -translate-x-1/2 -translate-y-full pb-2 text-primary sm:top-1/2"
                    aria-hidden="true"
                >
                    <MapPin
                        class="size-10 fill-background"
                        stroke-width="1.25"
                    />
                </div>
                <div
                    class="absolute inset-x-0 bottom-0 flex flex-col gap-4 p-5 sm:flex-row sm:items-end sm:justify-between sm:p-8"
                >
                    <div class="flex min-w-0 flex-col gap-1">
                        <p class="text-overline text-primary uppercase">
                            {{ place }}
                        </p>
                        <p v-if="address" class="text-body text-pretty">
                            {{ address }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        class="shrink-0 bg-background/80 backdrop-blur-sm"
                        @click="load"
                    >
                        Show interactive map
                    </Button>
                </div>
            </template>
        </div>
        <a
            :href="googleMapsUrl(point)"
            target="_blank"
            rel="noopener"
            class="group/maps inline-flex min-h-11 items-center gap-1.5 self-start text-sm text-foreground underline-offset-4 transition-colors duration-(--dur-fast) hover:text-primary hover:underline"
        >
            Open in Google Maps
            <ArrowUpRight
                class="size-4 transition-transform duration-(--dur-base) group-hover/maps:translate-x-0.5 group-hover/maps:-translate-y-0.5"
                aria-hidden="true"
            />
            <span class="sr-only">(opens in a new tab)</span>
        </a>
    </div>
</template>
