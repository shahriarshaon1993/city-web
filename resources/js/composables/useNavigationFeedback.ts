import { router } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

/** MOT-PAGE: the dim (and Inertia's progress bar) start after 150 ms. */
const DIM_DELAY = 150;

type VisitInfo = { preserveState: unknown; only: string[] };

/**
 * Feedback around Inertia visits for the public layout:
 * - `navigating`: true while a visit runs longer than 150 ms (MOT-PAGE `.is-navigating`).
 * - after a route change, focus moves to `main` and `announcement` reads
 *   "Navigated to {title}" in a polite live region (A11Y-D06, SEO-A11Y-003).
 * Partial reloads and preserve-state visits (filters, 4.1) keep focus where it is.
 */
export function useNavigationFeedback(
    main: Readonly<Ref<HTMLElement | null>>,
    title: () => string,
) {
    const navigating = ref(false);
    const announcement = ref('');

    let dimTimer = 0;
    let pending = false;
    let lastVisit: VisitInfo | null = null;
    const offs: (() => void)[] = [];

    function onPopState(): void {
        pending = true;
        lastVisit = null;
    }

    async function announce(): Promise<void> {
        // Clear first so the same title is announced again.
        announcement.value = '';
        await nextTick();
        main.value?.focus({ preventScroll: true });
        announcement.value = `Navigated to ${title()}`;
    }

    onMounted(() => {
        offs.push(
            router.on('start', (event) => {
                const visit = event.detail.visit;
                pending = true;
                lastVisit = visit;

                if (visit.async || visit.prefetch || !visit.showProgress) {
                    return;
                }

                window.clearTimeout(dimTimer);
                dimTimer = window.setTimeout(() => {
                    navigating.value = true;
                }, DIM_DELAY);
            }),
            router.on('finish', () => {
                window.clearTimeout(dimTimer);
                navigating.value = false;
            }),
            router.on('navigate', () => {
                // The initial page load also fires `navigate`: only react to visits.
                if (!pending) {
                    return;
                }

                pending = false;
                const visit = lastVisit;
                lastVisit = null;

                if (
                    visit &&
                    (visit.preserveState === true || visit.only.length > 0)
                ) {
                    return;
                }

                void announce();
            }),
        );

        window.addEventListener('popstate', onPopState);
    });

    onBeforeUnmount(() => {
        offs.forEach((off) => off());
        window.removeEventListener('popstate', onPopState);
        window.clearTimeout(dimTimer);
    });

    return { navigating, announcement };
}
