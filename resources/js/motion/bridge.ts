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

/** Runs `fn` when the main thread is idle (setTimeout where rIC is missing). */
function whenIdle(fn: (deadline: IdleDeadline | null) => void): void {
    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(fn, { timeout: 500 });
    } else {
        setTimeout(() => fn(null), 16);
    }
}

// MOT-R06: setup (measuring, SplitText) runs in idle slices of a few ms, so
// a page full of effects never makes a long task. "Already passed" is still
// measured and the "from" state set in the same slice (MOT-R02).
let draining = false;

function drain(deadline: IdleDeadline | null): void {
    const until = performance.now() + 8;
    const hasTime = (): boolean =>
        deadline
            ? deadline.didTimeout || deadline.timeRemaining() > 2
            : performance.now() < until;

    for (const entry of pending) {
        pending.delete(entry);
        attach(entry);

        if (!hasTime()) {
            break;
        }
    }

    if (pending.size > 0) {
        whenIdle(drain);

        return;
    }

    draining = false;
    runtime?.refresh();
}

function startDrain(): void {
    if (!draining) {
        draining = true;
        whenIdle(drain);
    }
}

// Elements mounted by a client visit are queued two frames later, after
// Inertia's scroll reset, so "already passed" is measured on the new page.
function scheduleFlush(): void {
    if (flushFrame === 0) {
        flushFrame = requestAnimationFrame(() => {
            flushFrame = requestAnimationFrame(() => {
                flushFrame = 0;
                startDrain();
            });
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
                pending.add(entry);
            });
            startDrain();
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
    pending.clear();
    runtime?.destroy();
    runtime = null;
}

/**
 * MOT-MENU for a Vue `<Transition :css="false">` hook: the GSAP timeline
 * when the runtime runs, otherwise instant (MOT-R03, or not loaded yet).
 */
export function animateMenu(
    panel: Element,
    opening: boolean,
    done: () => void,
): void {
    if (runtime && panel instanceof HTMLElement && !isMotionReduced()) {
        runtime.animateMenu(panel, opening, done);
    } else {
        done();
    }
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
