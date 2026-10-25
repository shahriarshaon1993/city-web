import { useIntersectionObserver } from '@vueuse/core';
import type { MaybeElementRef } from '@vueuse/core';
import { ref } from 'vue';
import type { Ref } from 'vue';

// MOT-MARQUEE / MOT-GRAD / MOT-PULSE: CSS loops pause while their element is
// off-screen (bind `data-offscreen`, the CSS sets animation-play-state). It
// starts on screen, so SSR and no-JS output simply run the loop.
export function useOffscreen(target: MaybeElementRef): Ref<boolean> {
    const offscreen = ref(false);

    useIntersectionObserver(target, (entries) => {
        offscreen.value = !(entries.at(-1)?.isIntersecting ?? true);
    });

    return offscreen;
}
