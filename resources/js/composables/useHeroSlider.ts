import Fade from 'embla-carousel-fade';
import emblaCarouselVue from 'embla-carousel-vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useReducedMotion } from '@/motion/preference';

/** HERO-003: 7 s per slide when autoplay is on. */
export const HERO_SLIDE_INTERVAL = 7000;

/**
 * CMP-HERO-03 on Embla + the fade plugin (MOT-SLIDE crossfade; Embla's
 * `duration` is a physics value, 45 ≈ 700 ms). Autoplay is hand-written so
 * the Ken Burns zoom can follow it: it pauses on hover, on focus inside the
 * hero and while the tab is hidden; the pause/play button stops it for good.
 * Reduced motion (OS, footer toggle, settings — MOT-R03): no autoplay,
 * instant slide changes; turning it on later stops the autoplay.
 */
export function useHeroSlider(options: {
    count: () => number;
    autoplay: () => boolean;
}) {
    const [viewport, api] = emblaCarouselVue({ loop: true, duration: 45 }, [
        Fade(),
    ]);

    const selected = ref(0);
    /** The visitor's choice; starts from the block setting. */
    const playing = ref(options.autoplay());
    const hovered = ref(false);
    const focused = ref(false);
    const hidden = ref(false);
    const reducedMotion = useReducedMotion();
    /** After `load`: slides 2+ switch from lazy to eager so fades never show a blank slide. */
    const preloadRest = ref(false);

    const running = computed(
        () =>
            playing.value &&
            !hovered.value &&
            !focused.value &&
            !hidden.value &&
            api.value !== undefined,
    );

    let timer: number | undefined;

    function restartTimer(): void {
        window.clearTimeout(timer);

        if (running.value) {
            timer = window.setTimeout(
                () => api.value?.scrollNext(),
                HERO_SLIDE_INTERVAL,
            );
        }
    }

    function goTo(index: number): void {
        api.value?.scrollTo(index, reducedMotion.value);
    }

    function next(): void {
        goTo((selected.value + 1) % options.count());
    }

    function previous(): void {
        goTo((selected.value - 1 + options.count()) % options.count());
    }

    function togglePlaying(): void {
        playing.value = !playing.value;
    }

    function onPointerEnter(event: PointerEvent): void {
        hovered.value = event.pointerType === 'mouse';
    }

    function onPointerLeave(): void {
        hovered.value = false;
    }

    function onFocusIn(): void {
        focused.value = true;
    }

    function onFocusOut(event: FocusEvent): void {
        const root = event.currentTarget as HTMLElement;
        focused.value = root.contains(event.relatedTarget as Node | null);
    }

    function onVisibilityChange(): void {
        hidden.value = document.visibilityState === 'hidden';
    }

    function onLoad(): void {
        preloadRest.value = true;
    }

    watch(reducedMotion, (reduced) => {
        if (reduced) {
            playing.value = false;
        }
    });

    watch(running, restartTimer);

    watch(api, (instance) => {
        instance?.on('select', () => {
            selected.value = instance.selectedScrollSnap();
            restartTimer();
        });
        restartTimer();
    });

    onMounted(() => {
        if (reducedMotion.value) {
            playing.value = false;
        }

        onVisibilityChange();
        document.addEventListener('visibilitychange', onVisibilityChange);

        if (document.readyState === 'complete') {
            onLoad();
        } else {
            window.addEventListener('load', onLoad, { once: true });
        }
    });

    onBeforeUnmount(() => {
        window.clearTimeout(timer);
        document.removeEventListener('visibilitychange', onVisibilityChange);
        window.removeEventListener('load', onLoad);
    });

    return {
        viewport,
        selected,
        playing,
        running,
        preloadRest,
        goTo,
        next,
        previous,
        togglePlaying,
        rootListeners: {
            pointerenter: onPointerEnter,
            pointerleave: onPointerLeave,
            focusin: onFocusIn,
            focusout: onFocusOut,
        },
    };
}
