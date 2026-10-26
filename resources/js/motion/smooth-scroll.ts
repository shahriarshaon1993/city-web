import { router } from '@inertiajs/vue3';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

// MOT-SMOOTH + design/motion.md §8.3: one Lenis on the window (native scroll
// position, no transformed wrapper), driven by GSAP's ticker, wheel only
// (`syncTouch: false`). Anchor offsets come from `scroll-padding-top` on
// <html> (header + tab bar + 16 px, app.css), which Lenis reads itself and
// native scrolling uses when Lenis is off. Keyboard scrolling stays native.

const FOCUSABLE = 'a[href], button, input, select, textarea, [tabindex]';

export type SmoothScroll = {
    setPaused: (paused: boolean) => void;
    destroy: () => void;
};

function focusTarget(target: HTMLElement): void {
    if (!target.matches(FOCUSABLE)) {
        target.setAttribute('tabindex', '-1');
    }

    target.focus({ preventScroll: true });
}

/** Same-page `#…` links: smooth scroll, focus the target, replace the hash. */
function anchorClickHandler(lenis: Lenis) {
    return (event: MouseEvent): void => {
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        const link = (event.target as Element | null)?.closest?.('a[href]');

        if (
            !(link instanceof HTMLAnchorElement) ||
            (link.target !== '' && link.target !== '_self') ||
            link.hasAttribute('download')
        ) {
            return;
        }

        const url = new URL(link.href);
        const here = window.location;

        if (
            url.hash.length < 2 ||
            url.origin !== here.origin ||
            url.pathname !== here.pathname ||
            url.search !== here.search
        ) {
            return;
        }

        const target = document.getElementById(
            decodeURIComponent(url.hash.slice(1)),
        );

        if (!target) {
            return;
        }

        // Capture phase: Inertia's <Link> then sees a prevented event and
        // does not start a visit for an in-page anchor.
        event.preventDefault();
        lenis.scrollTo(target, { duration: 1 });
        focusTarget(target);
        window.history.replaceState(window.history.state, '', url.hash);
    };
}

export function createSmoothScroll(paused: boolean): SmoothScroll {
    const lenis = new Lenis({
        lerp: 0.1,
        wheelMultiplier: 1,
        smoothWheel: true,
        syncTouch: false,
        // A link to another page cancels a running glide.
        stopInertiaOnNavigate: true,
    });

    lenis.on('scroll', () => ScrollTrigger.update());
    const tick = (time: number): void => lenis.raf(time * 1000);
    gsap.ticker.add(tick);
    gsap.ticker.lagSmoothing(0);

    if (paused) {
        lenis.stop();
    }

    const onClick = anchorClickHandler(lenis);
    document.addEventListener('click', onClick, true);

    // New visits start at the top unless they preserve scroll or target a
    // hash (Inertia scrolls to it natively); back/forward restoration stays
    // with Inertia, Lenis just re-reads the native position.
    let resetScroll = false;
    const offs = [
        router.on('start', (event) => {
            const visit = event.detail.visit;

            if (!visit.prefetch) {
                resetScroll = visit.preserveScroll === false;
            }
        }),
        router.on('navigate', (event) => {
            if (
                event.detail.type === 'visit' &&
                resetScroll &&
                window.location.hash === ''
            ) {
                lenis.scrollTo(0, { immediate: true, force: true });
            }

            resetScroll = false;
            lenis.resize();
        }),
    ];

    return {
        setPaused(next) {
            if (next) {
                lenis.stop();
            } else {
                lenis.start();
            }
        },
        destroy() {
            offs.forEach((off) => off());
            document.removeEventListener('click', onClick, true);
            gsap.ticker.remove(tick);
            gsap.ticker.lagSmoothing(500, 33);
            lenis.destroy();
        },
    };
}
