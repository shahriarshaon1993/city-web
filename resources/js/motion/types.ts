// Shared between the always-loaded bridge and the lazy motion chunk
// (design/motion.md §8.5). Type-only, so it never pulls GSAP in.

export type MotionKind = 'reveal' | 'split' | 'mask' | 'counter';

/** One directive binding (`v-reveal`, `v-split`, `v-mask`, `v-counter`). */
export type MotionEntry = {
    el: HTMLElement;
    kind: MotionKind;
    /** Binding value: `data-delay` style delay in ms, or the counter index. */
    value: number | undefined;
    modifiers: Partial<Record<string, boolean>>;
    /**
     * Mounted while the runtime was already running (a client visit or a
     * lazy block), as opposed to the server HTML of the first load.
     */
    late: boolean;
    /** Reverts the effect (set while attached). */
    detach: (() => void) | null;
};

export type MotionRuntime = {
    /** Starts the effect; returns its cleanup (`gsap.context` revert). */
    attach: (entry: MotionEntry) => () => void;
    /** `ScrollTrigger.refresh()` after layout changes. */
    refresh: () => void;
    /** Lenis stop/start for dialogs, sheets and menus (§8.3). */
    setPaused: (paused: boolean) => void;
    destroy: () => void;
};

export type MotionOptions = {
    /** Settings → Motion → smooth scroll (MOT-SMOOTH). */
    smoothScroll: boolean;
    paused: boolean;
};
