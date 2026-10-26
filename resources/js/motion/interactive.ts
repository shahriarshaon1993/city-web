import { gsap } from 'gsap';
import type { Cleanup } from '@/motion/effects';
import { compact, finePointer, passed } from '@/motion/measure';

// design/motion.md §8.4 pointer and scrub effects (3.2), lazy chunk only.
// Transform only (MOT-R01); none of it exists under reduced motion, because
// the runtime is never loaded then (MOT-R03). Pointer-driven tweens start in
// event handlers, outside the binding's `gsap.context`, so each effect kills
// its own tweens and clears its props on cleanup.

type QuickTo = ReturnType<typeof gsap.quickTo>;

/**
 * MOT-PARALLAX: `[data-parallax-layer]` drifts inside the overflow-hidden
 * frame (the element with `v-parallax`), scrubbed, by `intensity` % of the
 * frame height (hero ≤ 8, others 10), halved on touch and small screens
 * (MOT-R04). The layer must overflow the frame by that share at the top and
 * the bottom from first paint (e.g. `-inset-y-[10%]`), so no edge shows and
 * nothing jumps when the runtime arrives. A frame already in view (the hero)
 * starts from where it is and only moves down; one below the fold travels
 * the full −share → +share. Off for frames under 200 px.
 */
export function parallax(frame: HTMLElement, intensity: number): Cleanup {
    const layer = frame.querySelector<HTMLElement>('[data-parallax-layer]');
    const rect = frame.getBoundingClientRect();

    if (!layer || rect.height < 200 || rect.bottom <= 0) {
        return;
    }

    const share =
        Math.min(10, Math.max(0, intensity)) / 100 / (compact() ? 2 : 1);
    const shift = (): number => frame.offsetHeight * share;
    const inView = passed(frame, 1);
    const anchor = window.scrollY;

    gsap.fromTo(
        layer,
        { y: inView ? 0 : () => -shift() },
        {
            y: shift,
            ease: 'none',
            scrollTrigger: {
                trigger: frame,
                start: inView ? anchor : 'top bottom',
                end: 'bottom top',
                scrub: true,
                invalidateOnRefresh: true,
            },
        },
    );
}

type Magnet = {
    el: HTMLElement;
    label: HTMLElement | null;
    moves: QuickTo[] | null;
};

/** Owner, 3.2: subtle — 6 px (spec 10), smooth release without overshoot. */
const MAGNET_MAX = 6;
const magnets = new Set<Magnet>();
const pointer = { x: 0, y: 0 };
let magnetFrame = 0;

function releaseMagnet(magnet: Magnet): void {
    magnet.moves = null;
    gsap.to([magnet.el, magnet.label].filter(Boolean), {
        x: 0,
        y: 0,
        duration: 0.6,
        ease: 'power3.out',
        overwrite: true,
    });
}

function moveMagnets(): void {
    magnetFrame = 0;

    magnets.forEach((magnet) => {
        const rect = magnet.el.getBoundingClientRect();
        // The rect includes our own offset: measure from the resting box.
        const centreX =
            rect.left +
            rect.width / 2 -
            Number(gsap.getProperty(magnet.el, 'x'));
        const centreY =
            rect.top +
            rect.height / 2 -
            Number(gsap.getProperty(magnet.el, 'y'));
        // Within 1.4 × the button's bounds.
        const reachX = rect.width * 0.7;
        const reachY = rect.height * 0.7;
        const dx = (pointer.x - centreX) / reachX;
        const dy = (pointer.y - centreY) / reachY;

        if (Math.abs(dx) > 1 || Math.abs(dy) > 1) {
            if (magnet.moves) {
                releaseMagnet(magnet);
            }

            return;
        }

        if (!magnet.moves) {
            gsap.killTweensOf([magnet.el, magnet.label].filter(Boolean));
            const quick = (target: HTMLElement, axis: 'x' | 'y'): QuickTo =>
                gsap.quickTo(target, axis, {
                    duration: 0.4,
                    ease: 'power3.out',
                });
            magnet.moves = [quick(magnet.el, 'x'), quick(magnet.el, 'y')];

            if (magnet.label) {
                magnet.moves.push(
                    quick(magnet.label, 'x'),
                    quick(magnet.label, 'y'),
                );
            }
        }

        const [moveX, moveY, labelX, labelY] = magnet.moves;
        moveX(dx * MAGNET_MAX);
        moveY(dy * MAGNET_MAX);
        // The label travels 1.3 × the button: 0.3 × inside it.
        labelX?.(dx * MAGNET_MAX * 0.3);
        labelY?.(dy * MAGNET_MAX * 0.3);
    });
}

function onMagnetPointer(event: PointerEvent): void {
    if (event.pointerType !== 'mouse') {
        return;
    }

    pointer.x = event.clientX;
    pointer.y = event.clientY;
    magnetFrame ||= requestAnimationFrame(moveMagnets);
}

function releaseAllMagnets(): void {
    magnets.forEach((magnet) => {
        if (magnet.moves) {
            releaseMagnet(magnet);
        }
    });
}

function listenMagnets(on: boolean): void {
    if (on) {
        document.addEventListener('pointermove', onMagnetPointer, {
            passive: true,
        });
        document.documentElement.addEventListener(
            'pointerleave',
            releaseAllMagnets,
        );
        window.addEventListener('scroll', releaseAllMagnets, { passive: true });
    } else {
        document.removeEventListener('pointermove', onMagnetPointer);
        document.documentElement.removeEventListener(
            'pointerleave',
            releaseAllMagnets,
        );
        window.removeEventListener('scroll', releaseAllMagnets);
    }
}

/**
 * MOT-MAGNETIC: the button drifts toward a fine pointer within 1.4 × its
 * bounds, `[data-magnetic-label]` 1.3 × as far. One shared, rAF-throttled
 * pointer listener for every magnet; `quickTo`, no new tween per move.
 * Disabled on touch (MOT-R04).
 */
export function magnetic(el: HTMLElement): Cleanup {
    if (!finePointer()) {
        return;
    }

    const magnet: Magnet = {
        el,
        label: el.querySelector<HTMLElement>('[data-magnetic-label]'),
        moves: null,
    };

    if (magnets.size === 0) {
        listenMagnets(true);
    }

    magnets.add(magnet);

    return () => {
        magnets.delete(magnet);

        if (magnets.size === 0) {
            listenMagnets(false);
            cancelAnimationFrame(magnetFrame);
            magnetFrame = 0;
        }

        const targets = [el, magnet.label].filter(Boolean);
        gsap.killTweensOf(targets);
        gsap.set(targets, { clearProps: 'transform' });
    };
}

/**
 * MOT-TILT: rotateX/Y up to 6° toward a fine pointer (perspective 1000 px),
 * `[data-tilt-depth]` lifts 20 px in Z, `[data-tilt-glare]` fades to 0.15 at
 * the edges; reset 0.6 s. `will-change` only while hovered. Disabled on
 * touch. Built for 6.2's feature_cards — project cards keep the image zoom
 * (owner, 3.2: direction.md §6/§7 has no card tilt).
 */
export function tilt(card: HTMLElement): Cleanup {
    if (!finePointer()) {
        return;
    }

    const depth = card.querySelector<HTMLElement>('[data-tilt-depth]');
    const glare = card.querySelector<HTMLElement>('[data-tilt-glare]');
    const targets = [card, depth, glare].filter(Boolean);
    let rect: DOMRect | null = null;
    let moves: QuickTo[] = [];
    let frame = 0;
    const point = { x: 0, y: 0 };

    function render(): void {
        frame = 0;

        if (!rect) {
            return;
        }

        const px = (point.x - rect.left) / rect.width - 0.5;
        const py = (point.y - rect.top) / rect.height - 0.5;
        const [rotateX, rotateY, glareTo] = moves;
        rotateX(-py * 12);
        rotateY(px * 12);
        glareTo?.(Math.min(1, Math.hypot(px, py) / 0.71) * 0.15);
    }

    function onEnter(event: PointerEvent): void {
        if (event.pointerType !== 'mouse') {
            return;
        }

        rect = card.getBoundingClientRect();
        gsap.killTweensOf(targets);
        card.style.willChange = 'transform';
        gsap.set(card, {
            transformPerspective: 1000,
            transformStyle: 'preserve-3d',
        });
        moves = [
            gsap.quickTo(card, 'rotationX', {
                duration: 0.3,
                ease: 'power2.out',
            }),
            gsap.quickTo(card, 'rotationY', {
                duration: 0.3,
                ease: 'power2.out',
            }),
        ];

        if (glare) {
            moves.push(gsap.quickTo(glare, 'opacity', { duration: 0.3 }));
        }

        if (depth) {
            gsap.to(depth, { z: 20, duration: 0.3, ease: 'power2.out' });
        }
    }

    function onMove(event: PointerEvent): void {
        if (!rect) {
            return;
        }

        point.x = event.clientX;
        point.y = event.clientY;
        frame ||= requestAnimationFrame(render);
    }

    function onLeave(): void {
        if (!rect) {
            return;
        }

        rect = null;
        moves = [];
        cancelAnimationFrame(frame);
        frame = 0;
        gsap.to(card, {
            rotationX: 0,
            rotationY: 0,
            duration: 0.6,
            ease: 'power2.out',
            overwrite: true,
            onComplete: () => {
                card.style.willChange = '';
            },
        });
        gsap.to([depth, glare].filter(Boolean), {
            z: 0,
            opacity: 0,
            duration: 0.6,
            ease: 'power2.out',
            overwrite: true,
        });
    }

    card.addEventListener('pointerenter', onEnter);
    card.addEventListener('pointermove', onMove, { passive: true });
    card.addEventListener('pointerleave', onLeave);

    return () => {
        card.removeEventListener('pointerenter', onEnter);
        card.removeEventListener('pointermove', onMove);
        card.removeEventListener('pointerleave', onLeave);
        cancelAnimationFrame(frame);
        gsap.killTweensOf(targets);
        gsap.set(targets, { clearProps: 'transform,opacity,willChange' });
    };
}

const LINE_START = 0.7;

/**
 * MOT-LINE (process_steps, 6.1): `[data-line-path]` draws while the steps
 * scroll through 70 % of the viewport — an SVG shape by stroke-dashoffset
 * (`pathLength` 1, so any size works), an HTML rule by scaleX/scaleY
 * (`data-line-axis="x"`, default y). One trigger. Full line when already
 * passed, under reduced motion and without JS.
 */
export function line(el: HTMLElement): Cleanup {
    const path = el.querySelector('[data-line-path]');

    if (!path || passed(el, LINE_START)) {
        return;
    }

    const scrollTrigger = {
        trigger: el,
        start: `top ${LINE_START * 100}%`,
        end: `bottom ${LINE_START * 100}%`,
        scrub: true,
    };

    if (path instanceof SVGGeometryElement) {
        const hadLength = path.getAttribute('pathLength');
        path.setAttribute('pathLength', '1');
        gsap.fromTo(
            path,
            { strokeDasharray: 1, strokeDashoffset: 1 },
            { strokeDashoffset: 0, ease: 'none', scrollTrigger },
        );

        return () => {
            if (hadLength === null) {
                path.removeAttribute('pathLength');
            } else {
                path.setAttribute('pathLength', hadLength);
            }
        };
    }

    if (path instanceof HTMLElement) {
        const horizontal = path.dataset.lineAxis === 'x';
        const axis = horizontal ? 'scaleX' : 'scaleY';
        gsap.fromTo(
            path,
            {
                [axis]: 0,
                transformOrigin: horizontal ? 'left center' : 'center top',
            },
            { [axis]: 1, ease: 'none', scrollTrigger },
        );
    }
}
