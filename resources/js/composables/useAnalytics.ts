import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';
import { useConsent } from '@/composables/useConsent';
import {
    initAnalytics,
    trackContactClicks,
    trackPageView,
} from '@/lib/analytics';
import type { Seo, SitePageProps } from '@/types/site';

/**
 * Starts the analytics layer from the persistent public layout (SEO-ANA-001…003):
 * Consent Mode defaults, the stored choice, one virtual `page_view` for the
 * landing page and one per Inertia visit, and the phone / WhatsApp click
 * listener. Nothing is loaded from Google here: lib/analytics.ts does that
 * only after consent, once the window has loaded. A choice made later (banner,
 * footer link) reaches the layer through useConsent().save().
 */
export function useAnalytics(): void {
    const page = usePage<SitePageProps>();
    const { consent } = useConsent();

    let removeNavigate: (() => void) | undefined;
    let removeClicks: (() => void) | undefined;

    onMounted(() => {
        initAnalytics(page.props.ui.analytics, consent.value);
        trackPageView(page.props.seo.title);

        // Inertia also fires `navigate` for the initial page (type "initial"): already counted above.
        removeNavigate = router.on('navigate', (event) => {
            if (event.detail.type !== 'initial') {
                trackPageView((event.detail.page.props.seo as Seo).title);
            }
        });
        removeClicks = trackContactClicks();
    });

    onBeforeUnmount(() => {
        removeNavigate?.();
        removeClicks?.();
    });
}
