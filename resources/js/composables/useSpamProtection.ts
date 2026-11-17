import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { SitePageProps } from '@/types/site';

// FR-LEAD-003: the browser half of spam protection. The honeypot field and the
// "not before" time travel with every form (the server fakes success when the
// field is filled or the form comes back too fast, AC-LEAD-02); Turnstile
// (managed, `interaction-only`) hands over a one-time token. A token that never
// arrives within 3 s is not an error: the server accepts the lead and flags it
// `needs_review`. Browser-only code runs from onMounted (SEO-REN-08).

/** FR-LEAD-003: how long a submit waits for the Turnstile token. */
const TOKEN_WAIT_MS = 3000;

type TurnstileApi = {
    render: (
        element: HTMLElement,
        options: {
            sitekey: string;
            appearance: 'interaction-only';
            callback: (token: string) => void;
            'expired-callback': () => void;
            'error-callback': () => void;
        },
    ) => string;
    reset: (widgetId?: string) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

let scriptPromise: Promise<TurnstileApi | null> | null = null;

/** Loads Cloudflare's script once, the first time a form is in view. */
function loadTurnstile(): Promise<TurnstileApi | null> {
    scriptPromise ??= new Promise((resolve) => {
        const script = document.createElement('script');

        script.src =
            'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.onload = () => resolve(window.turnstile ?? null);
        script.onerror = () => resolve(null);
        document.head.append(script);
    });

    return scriptPromise;
}

export type SpamProtection = ReturnType<typeof useSpamProtection>;

export function useSpamProtection() {
    const config = usePage<SitePageProps>().props.forms.spam;
    const trap = ref('');
    const token = ref<string | null>(null);
    let widgetId: string | undefined;

    /** The hidden fields added to every submit. */
    function fields(): Record<string, string> {
        return {
            ...(config.honeypot.enabled
                ? {
                      [config.honeypot.name]: trap.value,
                      [config.honeypot.valid_from_name]:
                          config.honeypot.valid_from,
                  }
                : {}),
            ...(token.value ? { 'cf-turnstile-response': token.value } : {}),
        };
    }

    /** Renders the widget into `element` (call when the form scrolls into view). */
    async function mount(element: HTMLElement): Promise<void> {
        if (!config.turnstile_site_key || widgetId !== undefined) {
            return;
        }

        const api = await loadTurnstile();

        if (!api) {
            return;
        }

        widgetId = api.render(element, {
            sitekey: config.turnstile_site_key,
            appearance: 'interaction-only',
            callback: (value) => {
                token.value = value;
            },
            'expired-callback': () => {
                token.value = null;
            },
            'error-callback': () => {
                token.value = null;
            },
        });
    }

    /** Waits up to 3 s for a token; resolves either way (a missing token is flagged server-side). */
    function ready(): Promise<void> {
        if (!config.turnstile_site_key || token.value) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            const started = Date.now();
            const timer = window.setInterval(() => {
                if (token.value || Date.now() - started >= TOKEN_WAIT_MS) {
                    window.clearInterval(timer);
                    resolve();
                }
            }, 100);
        });
    }

    /** A token works once: ask for a new one after every submit. */
    function reset(): void {
        token.value = null;

        if (widgetId !== undefined) {
            window.turnstile?.reset(widgetId);
        }
    }

    return {
        config,
        trap,
        token,
        enabled: config.honeypot.enabled || !!config.turnstile_site_key,
        fields,
        mount,
        ready,
        reset,
    };
}
