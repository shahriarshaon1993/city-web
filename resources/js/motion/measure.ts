// Shared by the lazy effect modules (never imported by the bridge).

/** MOT-R04 + the "Mobile" column: shorter moves on touch and small screens. */
export function compact(): boolean {
    return window.matchMedia(
        '(max-width: 767px), (hover: none), (pointer: coarse)',
    ).matches;
}

/** MOT-R04: magnetic and tilt only follow a fine, hovering pointer. */
export function finePointer(): boolean {
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}

/** The element's top is above `start` × the viewport height (MOT-R02). */
export function passed(el: Element, start: number): boolean {
    return el.getBoundingClientRect().top < window.innerHeight * start;
}
