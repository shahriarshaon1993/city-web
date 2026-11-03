// SEO-ANA-001…004, SEO-PERF-013: the analytics layer. Everything is pushed to
// `window.dataLayer` with snake_case names; Google Tag Manager (or a bare GA4
// tag) is loaded only when an ID is configured, the visitor has granted
// analytics or marketing, and the page has finished loading. Consent Mode v2
// starts denied and is updated from the banner. Browser-only: call from
// handlers and onMounted (SEO-REN-08).

import type { ConsentChoice } from '@/lib/consent';

declare global {
    interface Window {
        dataLayer?: unknown[];
    }
}

/** SEO-ANA-004 events and their parameters. */
export type AnalyticsEvents = {
    page_view: { page_location: string; page_title: string };
    generate_lead: {
        lead_type: string;
        project_slug: string | null;
        form_location: string | null;
    };
    site_visit_request: { project_slug: string | null };
    brochure_download: { project_slug: string | null };
    whatsapp_click: { project_slug: string | null; location: string };
    phone_click: { location: string };
    search: { search_term: string; filters: number };
    filter_apply: { filters: string };
    shortlist_add: { project_slug: string | null };
    compare_open: { count: number };
    video_play: { project_slug: string | null };
    gallery_open: { project_slug: string | null };
    map_open: { location: string };
};

export type AnalyticsIds = {
    gtm_id: string | null;
    ga4_id: string | null;
};

function layer(): unknown[] {
    window.dataLayer = window.dataLayer ?? [];

    return window.dataLayer;
}

/** gtag.js and GTM read `arguments` objects from the dataLayer, not arrays. */
function gtag(..._args: unknown[]): void {
    // eslint-disable-next-line prefer-rest-params
    layer().push(arguments);
}

/** Pushes one event; empty (null) parameters are left out. */
export function track<K extends keyof AnalyticsEvents>(
    name: K,
    params: AnalyticsEvents[K],
): void {
    const clean = Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== null),
    );

    layer().push({ event: name, ...clean });
}

/** SEO-ANA-003: a virtual page view per Inertia visit. */
export function trackPageView(title: string): void {
    track('page_view', {
        page_location: window.location.href,
        page_title: title,
    });
}

let ids: AnalyticsIds = { gtm_id: null, ga4_id: null };
let defaultsSet = false;
let loaded = false;

function grant(value: boolean): 'granted' | 'denied' {
    return value ? 'granted' : 'denied';
}

function loadScript(src: string): void {
    const script = document.createElement('script');

    script.async = true;
    script.src = src;
    document.head.append(script);
}

function load(): void {
    if (loaded) {
        return;
    }

    loaded = true;

    if (ids.gtm_id) {
        layer().push({ 'gtm.start': Date.now(), event: 'gtm.js' });
        loadScript(
            `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(ids.gtm_id)}`,
        );

        return;
    }

    if (ids.ga4_id) {
        gtag('js', new Date());
        // Page views are pushed by trackPageView(); the tag must not add its own.
        gtag('config', ids.ga4_id, { send_page_view: false });
        loadScript(
            `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ids.ga4_id)}`,
        );
    }
}

/** SEO-PERF-013: after the window `load` event, and only for a visitor who agreed. */
function loadWhenReady(): void {
    if ((!ids.gtm_id && !ids.ga4_id) || loaded) {
        return;
    }

    if (document.readyState === 'complete') {
        load();

        return;
    }

    window.addEventListener('load', load, { once: true });
}

/** SEO-ANA-002: consent defaults to denied; a stored choice is applied at once. */
export function initAnalytics(
    configured: AnalyticsIds,
    choice: ConsentChoice | null,
): void {
    ids = configured;

    if (!defaultsSet) {
        defaultsSet = true;
        gtag('consent', 'default', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            wait_for_update: 500,
        });
    }

    if (choice) {
        updateConsent(choice);
    }
}

/** The banner or the preferences dialog decided. */
export function updateConsent(choice: ConsentChoice): void {
    gtag('consent', 'update', {
        analytics_storage: grant(choice.analytics),
        ad_storage: grant(choice.marketing),
        ad_user_data: grant(choice.marketing),
        ad_personalization: grant(choice.marketing),
    });

    if (choice.analytics || choice.marketing) {
        loadWhenReady();
    }
}

/** The slug of the project page being viewed (`/projects/{slug}`), else null. */
export function currentProjectSlug(): string | null {
    return (
        window.location.pathname.match(/^\/projects\/([a-z0-9-]+)\/?$/)?.[1] ??
        null
    );
}

function clickLocation(link: Element): string {
    if (link.closest('nav[aria-label="Quick contact"]')) {
        return 'mobile_bar';
    }

    if (link.closest('footer')) {
        return 'footer';
    }

    if (link.closest('header')) {
        return 'header';
    }

    return 'page';
}

/**
 * SEO-ANA-004 `phone_click` and `whatsapp_click` (FR-LEAD-013): one delegated
 * listener, so every `tel:` and wa.me link counts wherever it is rendered.
 * Returns the unbind function.
 */
export function trackContactClicks(): () => void {
    function onClick(event: MouseEvent): void {
        if (!(event.target instanceof Element)) {
            return;
        }

        const link = event.target.closest('a[href]');
        const href = link?.getAttribute('href') ?? '';

        if (!link) {
            return;
        }

        if (href.startsWith('tel:')) {
            track('phone_click', { location: clickLocation(link) });
        } else if (href.startsWith('https://wa.me/')) {
            track('whatsapp_click', {
                project_slug: currentProjectSlug(),
                location: clickLocation(link),
            });
        }
    }

    document.addEventListener('click', onClick);

    return () => document.removeEventListener('click', onClick);
}

/**
 * FR-LEAD-005 `generate_lead` (plus `site_visit_request`): once per lead, even
 * if Inertia restores the thank-you page from history.
 */
export function trackLeadOnce(
    reference: string,
    lead: AnalyticsEvents['generate_lead'],
): void {
    const key = `gc.lead.${reference}`;

    try {
        if (window.sessionStorage.getItem(key) !== null) {
            return;
        }

        window.sessionStorage.setItem(key, '1');
    } catch {
        // Storage can be blocked: tracking then runs once per page view.
    }

    track('generate_lead', lead);

    if (lead.lead_type === 'site_visit') {
        track('site_visit_request', { project_slug: lead.project_slug });
    }
}
