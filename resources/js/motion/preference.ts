import { onMounted, readonly, ref } from 'vue';
import type { Ref } from 'vue';

// MOT-R03 / SEO-A11Y-005: motion is off when the OS asks for reduced motion,
// the visitor turned on the footer "Reduce motion" toggle, or Settings →
// Motion → animations is off. The last two are `data-reduce-motion` on
// <html>: written by the server (setting) and by the inline script in
// app.blade.php (stored choice) before first paint, so CSS can follow it.

export const REDUCE_MOTION_KEY = 'gc.reduce-motion';

const ATTRIBUTE = 'data-reduce-motion';
const QUERY = '(prefers-reduced-motion: reduce)';

/** Client only. */
export function systemReducesMotion(): boolean {
    return window.matchMedia(QUERY).matches;
}

/** Client only: the effective state, read from the DOM. */
export function isMotionReduced(): boolean {
    return (
        document.documentElement.hasAttribute(ATTRIBUTE) ||
        systemReducesMotion()
    );
}

/** The visitor's stored footer choice (false when storage is blocked). */
export function storedReduceMotion(): boolean {
    try {
        return window.localStorage.getItem(REDUCE_MOTION_KEY) === '1';
    } catch {
        return false;
    }
}

const reduced = ref(false);
let listening = false;

function listen(): void {
    reduced.value = isMotionReduced();

    if (listening) {
        return;
    }

    listening = true;
    window.matchMedia(QUERY).addEventListener('change', () => {
        reduced.value = isMotionReduced();
    });
}

/** Footer toggle: stores the choice and switches the attribute at once. */
export function setReduceMotion(on: boolean): void {
    try {
        if (on) {
            window.localStorage.setItem(REDUCE_MOTION_KEY, '1');
        } else {
            window.localStorage.removeItem(REDUCE_MOTION_KEY);
        }
    } catch {
        // Storage blocked: the choice lasts for this page view.
    }

    document.documentElement.toggleAttribute(ATTRIBUTE, on);
    listen();
}

/** Reactive effective state; false during SSR and until mounted. */
export function useReducedMotion(): Readonly<Ref<boolean>> {
    onMounted(listen);

    return readonly(reduced);
}
