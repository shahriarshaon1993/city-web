import type { App, Directive } from 'vue';
import { register, unregister } from '@/motion/bridge';
import type { MotionKind } from '@/motion/types';

// MOT-REVEAL / MOT-STAGGER / MOT-REVEAL-HERO (`v-reveal`, `.stagger` = the
// children in one batch, `.hero` = hero text lines), MOT-SPLIT (`v-split`),
// MOT-MASK (`v-mask`), MOT-COUNTER (`v-counter`, on a `[data-counter]`
// element; value = its index for the stagger); the value of those is a
// delay in ms (0–300). MOT-PARALLAX (`v-parallax="8"`, intensity in %),
// MOT-MAGNETIC (`v-magnetic`), MOT-TILT (`v-tilt`), MOT-LINE (`v-line`).
// No-ops in SSR and until the runtime loads.
export type MotionDirective = Directive<HTMLElement, number | undefined>;

function motionDirective(kind: MotionKind): MotionDirective {
    return {
        mounted(el, binding) {
            register(el, kind, binding.value, binding.modifiers);
        },
        unmounted(el) {
            unregister(el, kind);
        },
    };
}

export const vReveal = motionDirective('reveal');
export const vSplit = motionDirective('split');
export const vMask = motionDirective('mask');
export const vCounter = motionDirective('counter');
export const vParallax = motionDirective('parallax');
export const vMagnetic = motionDirective('magnetic');
export const vTilt = motionDirective('tilt');
export const vLine = motionDirective('line');

export function installMotionDirectives(app: App): void {
    app.directive('reveal', vReveal)
        .directive('split', vSplit)
        .directive('mask', vMask)
        .directive('counter', vCounter)
        .directive('parallax', vParallax)
        .directive('magnetic', vMagnetic)
        .directive('tilt', vTilt)
        .directive('line', vLine);
}
