import { router } from '@inertiajs/vue3';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import { attachEffect } from '@/motion/effects';
import { createSmoothScroll } from '@/motion/smooth-scroll';
import type { MotionOptions, MotionRuntime } from '@/motion/types';

// The lazy motion chunk (design/motion.md §8.5, SEO-PERF-004/012): only ever
// reached through the dynamic import in `motion/bridge.ts`.
gsap.registerPlugin(ScrollTrigger, SplitText);

export function createRuntime(options: MotionOptions): MotionRuntime {
    const smooth = options.smoothScroll
        ? createSmoothScroll(options.paused)
        : null;

    let refreshFrame = 0;

    // MOT-R05: re-measure once the new page's (dimensioned) images mounted.
    function refresh(): void {
        cancelAnimationFrame(refreshFrame);
        refreshFrame = requestAnimationFrame(() => ScrollTrigger.refresh());
    }

    const offNavigate = router.on('navigate', refresh);

    // Manual test hook (3.1): `__gcMotion.scrollTriggers()` in the console.
    (window as Window & { __gcMotion?: object }).__gcMotion = {
        scrollTriggers: () => ScrollTrigger.getAll().length,
    };

    return {
        attach: attachEffect,
        refresh,
        setPaused: (paused) => smooth?.setPaused(paused),
        destroy() {
            offNavigate();
            cancelAnimationFrame(refreshFrame);
            smooth?.destroy();
        },
    };
}
