// Shared between the always-loaded bridge and the lazy motion chunk
// (design/motion.md §8.5). Type-only, so it never pulls GSAP in.

export type MotionKind =
    | 'reveal'
    | 'split'
    | 'mask'
    | 'counter'
    | 'parallax'
    | 'magnetic'
    | 'tilt'
    | 'line';

/** One motion directive binding (`v-reveal`, `v-parallax`, …). */
export type MotionEntry = {
    el: HTMLElement;
    kind: MotionKind;
    /**
     * Binding value: a delay in ms (reveal, split, mask), the counter index,
     * or the parallax intensity in % of the frame height.
     */
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
    /** MOT-MENU: plays the sheet in or reverses it, then calls `done`. */
    animateMenu: (
        panel: HTMLElement,
        opening: boolean,
        done: () => void,
    ) => void;
    destroy: () => void;
};

export type MotionOptions = {
    /** Settings → Motion → smooth scroll (MOT-SMOOTH). */
    smoothScroll: boolean;
    paused: boolean;
};
