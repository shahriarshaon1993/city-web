import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import { formatNumber } from '@/lib/format';
import { line, magnetic, parallax, tilt } from '@/motion/interactive';
import { compact, passed } from '@/motion/measure';
import type { MotionEntry } from '@/motion/types';

// design/motion.md §8.4 effects, GSAP side. Every effect follows MOT-R02:
// an element whose start line is already passed (above the fold, or
// scrolled past) is left exactly as the server rendered it; only elements
// still below get their "from" state. Transform/opacity/clip-path only
// (MOT-R01). Each binding runs in its own `gsap.context`, reverted when the
// element unmounts (MOT-R05), which also kills its ScrollTriggers.

export type Cleanup = (() => void) | void;

/** Runs `fn` inside the binding's context, so later tweens are reverted too. */
export type Within = (fn: () => void) => void;

const REVEAL_START = 0.85;
const MASK_START = 0.8;
const EXPO = 'expo.out';

function seconds(ms: number | undefined): number {
    return Math.min(300, Math.max(0, ms ?? 0)) / 1000;
}

/** MOT-REVEAL: fade-up once at 85 %. */
function reveal(el: HTMLElement, delay: number, within: Within): void {
    if (passed(el, REVEAL_START)) {
        return;
    }

    const small = compact();
    gsap.set(el, { opacity: 0, y: small ? 24 : 32 });
    ScrollTrigger.create({
        trigger: el,
        start: `top ${REVEAL_START * 100}%`,
        once: true,
        onEnter: () =>
            within(() => {
                gsap.to(el, {
                    opacity: 1,
                    y: 0,
                    duration: small ? 0.63 : 0.9,
                    delay,
                    ease: EXPO,
                    clearProps: 'opacity,transform',
                });
            }),
    });
}

/** MOT-STAGGER: the children in one batch, 90 ms apart, ≤ 600 ms in total. */
function stagger(el: HTMLElement, within: Within): void {
    const items = Array.from(el.children).filter(
        (child): child is HTMLElement =>
            child instanceof HTMLElement && !passed(child, REVEAL_START),
    );

    if (items.length === 0) {
        return;
    }

    const small = compact();
    gsap.set(items, { opacity: 0, y: small ? 24 : 32 });
    ScrollTrigger.batch(items, {
        start: `top ${REVEAL_START * 100}%`,
        once: true,
        onEnter: (batch) =>
            within(() => {
                gsap.to(batch, {
                    opacity: 1,
                    y: 0,
                    duration: small ? 0.63 : 0.9,
                    ease: EXPO,
                    stagger: Math.min(0.09, 0.6 / batch.length),
                    clearProps: 'opacity,transform',
                });
            }),
    });
}

/**
 * MOT-REVEAL-HERO: lines of `[data-hero-line]` wipe in by clip-path with
 * opacity kept at 1 (HERO-032), `[data-hero-ctas]` fade up 200 ms after the
 * last line starts. Client visits only (owner, 3.1): on the first load the
 * text has long been painted when the runtime arrives.
 */
function heroText(el: HTMLElement, late: boolean): Cleanup {
    const lines = el.querySelectorAll<HTMLElement>('[data-hero-line]');
    const ctas = el.querySelector<HTMLElement>('[data-hero-ctas]');

    if (!late || lines.length === 0) {
        return;
    }

    const split = SplitText.create(lines, { type: 'lines', aria: 'auto' });
    const timeline = gsap.timeline({ onComplete: () => split.revert() });
    timeline.fromTo(
        split.lines,
        { clipPath: 'inset(0% 0% 100% 0%)' },
        {
            clipPath: 'inset(0% 0% 0% 0%)',
            duration: 0.9,
            ease: EXPO,
            stagger: 0.08,
            clearProps: 'clipPath',
        },
    );

    if (ctas) {
        timeline.from(
            ctas,
            {
                opacity: 0,
                y: 24,
                duration: 0.9,
                ease: EXPO,
                clearProps: 'opacity,transform',
            },
            (split.lines.length - 1) * 0.08 + 0.2,
        );
    }

    return () => split.revert();
}

/** MOT-SPLIT: lines rise inside overflow-hidden masks at 80 %, once. */
function splitLines(el: HTMLElement, delay: number): Cleanup {
    if (passed(el, MASK_START)) {
        return;
    }

    const small = compact();
    let done = false;
    const split = SplitText.create(el, {
        type: 'lines',
        mask: 'lines',
        aria: 'auto',
        // Re-splits on resize and font load (debounced by SplitText).
        autoSplit: true,
        onSplit: (self) => {
            if (done) {
                return;
            }

            return gsap.from(self.lines, {
                yPercent: 100,
                duration: small ? 0.63 : 0.9,
                delay,
                ease: EXPO,
                stagger: 0.08,
                scrollTrigger: {
                    trigger: el,
                    start: `top ${MASK_START * 100}%`,
                    once: true,
                },
                onComplete: () => {
                    // Back to plain text: no line wrappers left in the DOM.
                    done = true;
                    self.kill();
                    self.revert();
                },
            });
        },
    });

    return () => split.revert();
}

/**
 * MOT-MASK: a diagonal (hexagon-aligned) wipe from the bottom-left corner
 * plus the image settling from 1.15; a plain vertical wipe on small and
 * touch screens, 0.7 × the duration. The image's hover transition is off
 * while GSAP drives its transform.
 */
function mask(el: HTMLElement, delay: number, within: Within): void {
    if (passed(el, MASK_START)) {
        return;
    }

    const small = compact();
    const duration = small ? 0.84 : 1.2;
    const image = el.querySelector('img');
    const [from, to] = small
        ? ['inset(100% 0% 0% 0%)', 'inset(0% 0% 0% 0%)']
        : [
              'polygon(0% 100%, 0% 100%, 0% 100%)',
              'polygon(0% 100%, 0% -100%, 200% 100%)',
          ];

    gsap.set(el, { clipPath: from });

    if (image) {
        gsap.set(image, { scale: 1.15, transition: 'none' });
    }

    ScrollTrigger.create({
        trigger: el,
        start: `top ${MASK_START * 100}%`,
        once: true,
        onEnter: () =>
            within(() => {
                gsap.to(el, {
                    clipPath: to,
                    duration,
                    delay,
                    ease: EXPO,
                    clearProps: 'clipPath',
                });

                if (image) {
                    gsap.to(image, {
                        scale: 1,
                        duration,
                        delay,
                        ease: EXPO,
                        clearProps: 'transform,transition',
                    });
                }
            }),
    });
}

/**
 * MOT-COUNTER: counts `[data-counter]` up from 0 at 85 %, once, 1.6 s,
 * 90 ms apart by index. Only the text node changes (Vue keeps owning it);
 * the final value is in the HTML and restored on cleanup.
 */
function counter(el: HTMLElement, index: number, within: Within): Cleanup {
    const text = el.firstChild;
    const value = Number(el.dataset.value);
    const decimals = Number(el.dataset.decimals ?? 0);

    if (
        !(text instanceof Text) ||
        !Number.isFinite(value) ||
        passed(el, REVEAL_START)
    ) {
        return;
    }

    const final = text.data;
    const proxy = { value: 0 };
    const render = (): void => {
        text.data = formatNumber(proxy.value, decimals);
    };
    render();

    ScrollTrigger.create({
        trigger: el,
        start: `top ${REVEAL_START * 100}%`,
        once: true,
        onEnter: () =>
            within(() => {
                gsap.to(proxy, {
                    value,
                    duration: 1.6,
                    delay: index * 0.09,
                    ease: 'power2.out',
                    onUpdate: render,
                    onComplete: () => {
                        text.data = final;
                    },
                });
            }),
    });

    return () => {
        text.data = final;
    };
}

function run(entry: MotionEntry, within: Within): Cleanup {
    const { el, value, modifiers } = entry;

    switch (entry.kind) {
        case 'reveal':
            if (modifiers.hero) {
                return heroText(el, entry.late);
            }

            return modifiers.stagger
                ? stagger(el, within)
                : reveal(el, seconds(value), within);
        case 'split':
            return splitLines(el, seconds(value));
        case 'mask':
            return mask(el, seconds(value), within);
        case 'counter':
            return counter(el, value ?? 0, within);
        case 'parallax':
            return parallax(el, value ?? 10);
        case 'magnetic':
            return magnetic(el);
        case 'tilt':
            return tilt(el);
        case 'line':
            return line(el);
    }
}

/** CMS-PB-001: a block saved with `animate: false` keeps everything still. */
function animationsOff(el: HTMLElement): boolean {
    const block = el.closest('[data-block]');

    return block !== null && !block.hasAttribute('data-animate');
}

export function attachEffect(entry: MotionEntry): () => void {
    if (animationsOff(entry.el)) {
        return () => {};
    }

    let cleanup: Cleanup;
    const context = gsap.context(() => {}, entry.el);
    const within: Within = (fn) => {
        context.add(fn);
    };
    within(() => {
        cleanup = run(entry, within);
    });

    return () => {
        context.revert();
        cleanup?.();
    };
}
