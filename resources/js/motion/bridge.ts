import { onBeforeUnmount, watch } from 'vue';
import { isMotionReduced } from '@/motion/preference';
import type {
    MotionEntry,
    MotionKind,
    MotionOptions,
    MotionRuntime,
} from '@/motion/types';

// The small, always-loaded half of the motion system (design/motion.md §8.5):
// directives register their elements here and stay no-ops (content visible)
// until the lazy runtime (`motion/index.ts`, GSAP + ScrollTrigger + SplitText
// + Lenis) is loaded after first paint and idle (SEO-PERF-012).

const entries = new Set<MotionEntry>();
const pending = new Set<MotionEntry>();
const pauses = new Set<symbol>();

let runtime: MotionRuntime | null = null;
let loading: Promise<void> | null = null;
let flushFrame = 0;

function attach(entry: MotionEntry): void {
    if (runtime && entries.has(entry) && !entry.detach) {
        entry.detach = runtime.attach(entry);
    }
}

// Elements mounted by a client visit are attached two frames later, after
// Inertia's scroll reset, so "already passed" is measured on the new page.
function flush(): void {
    flushFrame = 0;
    pending.forEach(attach);
    pending.clear();
    runtime?.refresh();
}

function scheduleFlush(): void {
    if (flushFrame === 0) {
        flushFrame = requestAnimationFrame(() => {
            flushFrame = requestAnimationFrame(flush);
        });
    }
}

export function register(
    el: HTMLElement,
    kind: MotionKind,
    value: number | undefined,
    modifiers: MotionEntry['modifiers'],
): void {
    const entry: MotionEntry = {
        el,
        kind,
        value,
        modifiers,
        late: runtime !== null,
        detach: null,
    };
    entries.add(entry);

    if (!runtime) {
        return;
    }

    // The hero is at the top whatever the scroll: clip it before first paint.
    if (modifiers.hero) {
        attach(entry);
    } else {
        pending.add(entry);
        scheduleFlush();
    }
}

export function unregister(el: HTMLElement, kind: MotionKind): void {
    entries.forEach((entry) => {
        if (entry.el === el && entry.kind === kind) {
            entry.detach?.();
            entries.delete(entry);
            pending.delete(entry);
        }
    });
}

/** First paint, then idle (timeout 1500 ms); Safari < 18 has no rIC. */
function afterIdle(): Promise<void> {
    return new Promise((resolve) => {
        requestAnimationFrame(() => {
            if (typeof window.requestIdleCallback === 'function') {
                window.requestIdleCallback(() => resolve(), { timeout: 1500 });
            } else {
                setTimeout(resolve, 200);
            }
        });
    });
}

/**
 * Loads and starts the runtime unless motion is off (MOT-R03). `immediate`
 * skips the idle wait (the footer toggle turned motion back on).
 */
export function startMotion(
    options: Omit<MotionOptions, 'paused'>,
    immediate = false,
): void {
    if (runtime || loading || isMotionReduced()) {
        return;
    }

    loading = (immediate ? Promise.resolve() : afterIdle())
        .then(() => import('@/motion/index'))
        .then(({ createRuntime }) => {
            if (isMotionReduced()) {
                return;
            }

            runtime = createRuntime({ ...options, paused: pauses.size > 0 });
            entries.forEach((entry) => {
                entry.late = false;
                attach(entry);
            });
            pending.clear();
        })
        .catch(() => {
            // Chunk failed to load: everything simply stays visible.
        })
        .finally(() => {
            loading = null;
        });
}

/** Reverts every effect (all content visible) and removes Lenis. */
export function stopMotion(): void {
    entries.forEach((entry) => {
        entry.detach?.();
        entry.detach = null;
    });
    runtime?.destroy();
    runtime = null;
}

/**
 * MOT-SMOOTH / §8.3: Lenis is stopped while `open()` is true (dialogs,
 * sheets, the mega and mobile menus). Safe before the runtime has loaded.
 */
export function usePauseSmoothScroll(open: () => boolean): void {
    const token = Symbol('pause');

    function update(paused: boolean): void {
        if (paused) {
            pauses.add(token);
        } else {
            pauses.delete(token);
        }

        runtime?.setPaused(pauses.size > 0);
    }

    watch(open, update);
    onBeforeUnmount(() => update(false));
}
