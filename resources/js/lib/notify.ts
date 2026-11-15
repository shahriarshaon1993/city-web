import { router } from '@inertiajs/vue3';
import type { ExternalToast } from 'vue-sonner';
import { login } from '@/routes';
import { notify } from '@/lib/toast';
import type { FlashToast, ToastAction } from '@/types/ui';

// TOAST-01: every admin mutation shows exactly one toast, sent by the server as flash `toast`
// (App\Support\Toasts\Toast) and shown here. Pages never call vue-sonner directly; the
// public site keeps using lib/toast.ts, which this builds on.

/** TOAST-02: a toast with an action button stays 8 s. */
const ACTION_MS = 8000;

type Visit = { url: URL; method: string; data: unknown };

let lastVisit: Visit | null = null;

function runAction(action: ToastAction): void {
    if (action.method === 'get') {
        router.visit(action.url);

        return;
    }

    router.visit(action.url, {
        method: action.method,
        data: action.data ?? {},
        preserveScroll: true,
        preserveState: true,
    });
}

/** Shows one toast; `action` becomes the button (Undo, Open, Retry…). */
export function showToast(toast: FlashToast): void {
    const options: ExternalToast = {};

    if (toast.description) {
        options.description = toast.description;
    }

    if (toast.action) {
        const action = toast.action;

        options.duration = toast.type === 'error' ? Infinity : ACTION_MS;
        options.action = {
            label: action.label,
            onClick: () => runAction(action),
        };
    }

    notify[toast.type](toast.title, options);
}

function retryLastVisit(): void {
    if (!lastVisit) {
        return;
    }

    const { url, method, data } = lastVisit;

    router.visit(url.pathname + url.search, {
        method: method as 'get' | 'post' | 'put' | 'patch' | 'delete',
        data: data as Record<string, never>,
        preserveScroll: true,
        preserveState: true,
    });
}

/** TOAST-04: the fixed wording for a failed request. Returns false for statuses it leaves alone. */
function toastForStatus(status: number, retryAfter: number | null): boolean {
    if (status === 403) {
        showToast({
            type: 'error',
            title: "You don't have permission to do that.",
        });
    } else if (status === 404) {
        showToast({
            type: 'error',
            title: 'This item no longer exists.',
            action: {
                label: 'Reload',
                url: window.location.href,
                method: 'get',
            },
        });
    } else if (status === 419) {
        // The form stays on screen; signing in again in a new tab refreshes the session cookie.
        notify.error(
            'Your session expired. Sign in again to continue — your changes are kept.',
            {
                action: {
                    label: 'Sign in',
                    onClick: () =>
                        window.open(login.url(), '_blank', 'noopener'),
                },
            },
        );
    } else if (status === 429) {
        const seconds = retryAfter ?? 60;

        showToast({
            type: 'error',
            title: `Too many attempts. Try again in ${seconds} ${seconds === 1 ? 'second' : 'seconds'}.`,
        });
    } else if (status >= 500) {
        notify.error('Something went wrong. Your changes were not saved.', {
            action: { label: 'Retry', onClick: retryLastVisit },
        });
    } else {
        return false;
    }

    return true;
}

/** Wires flash toasts and the failure toasts once, from the admin entry (resources/js/admin.ts). */
export function initializeAdminToasts(): void {
    router.on('flash', (event) => {
        const toast = (event as CustomEvent).detail?.flash?.toast as
            | FlashToast
            | undefined;

        if (toast) {
            showToast(toast);
        }
    });

    router.on('before', (event) => {
        const visit = (event as CustomEvent).detail?.visit;

        if (visit) {
            lastVisit = {
                url: visit.url,
                method: visit.method,
                data: visit.data,
            };
        }
    });

    // A non-Inertia answer (403, 404, 419, 429, 500…): toast instead of Inertia's HTML modal, and
    // stay on the page so the form keeps its input (VAL-06). In development a 500 also opens the
    // modal with the exception.
    router.on('httpException', (event) => {
        const response = (event as CustomEvent).detail?.response as
            | { status?: number; headers?: Record<string, string> }
            | undefined;
        const status = response?.status ?? 0;
        const retryAfter = Number(response?.headers?.['retry-after']);
        const handled = toastForStatus(
            status,
            Number.isFinite(retryAfter) && retryAfter > 0 ? retryAfter : null,
        );

        if (handled && !(status >= 500 && import.meta.env.DEV)) {
            event.preventDefault();
        }
    });

    router.on('networkError', () => {
        notify.error('Something went wrong. Your changes were not saved.', {
            action: { label: 'Retry', onClick: retryLastVisit },
        });
    });
}
