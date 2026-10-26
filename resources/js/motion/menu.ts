import { gsap } from 'gsap';

// MOT-MENU (CMP-NAV-03): the sheet slides in 420 ms `expo.out`, the
// `[data-menu-link]` groups rise 24 px 60 ms apart (--stagger-tight); closing
// reverses the same timeline in 280 ms, from wherever it is. Transform only.

const timelines = new WeakMap<HTMLElement, gsap.core.Timeline>();

export function animateMenu(
    panel: HTMLElement,
    opening: boolean,
    done: () => void,
): void {
    const running = timelines.get(panel);

    if (opening) {
        running?.kill();
        const links = panel.querySelectorAll('[data-menu-link]');
        const timeline = gsap
            .timeline({ onComplete: done })
            .fromTo(
                panel,
                { xPercent: 100 },
                { xPercent: 0, duration: 0.42, ease: 'expo.out' },
            )
            .fromTo(
                links,
                { y: 24 },
                { y: 0, duration: 0.42, ease: 'expo.out', stagger: 0.06 },
                0.12,
            );
        timelines.set(panel, timeline);

        return;
    }

    if (!running) {
        done();

        return;
    }

    running.eventCallback('onComplete', null);
    running.eventCallback('onReverseComplete', () => {
        timelines.delete(panel);
        running.kill();
        done();
    });
    running.timeScale(Math.max(1, running.time() / 0.28)).reverse();
}
