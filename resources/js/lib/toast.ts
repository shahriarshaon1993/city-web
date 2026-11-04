import type { ExternalToast } from 'vue-sonner';

// CMP-TOAST-01 durations: 4 s success/info, 6 s warning, errors stay until
// dismissed. Use these instead of calling vue-sonner's toast directly.
// vue-sonner (~7 KB gzip) is loaded on the first toast, not in the initial
// route (SEO-PERF-004); toasts only follow a user action, so the extra
// round trip is invisible, and the layout mounts <Toaster> after hydration.
type Variant = 'success' | 'info' | 'warning' | 'error';

function show(
    variant: Variant,
    duration: number,
    title: string,
    options?: ExternalToast,
): void {
    void import('vue-sonner').then(({ toast }) => {
        toast[variant](title, { duration, ...options });
    });
}

export const notify = {
    success: (title: string, options?: ExternalToast): void =>
        show('success', 4000, title, options),
    info: (title: string, options?: ExternalToast): void =>
        show('info', 4000, title, options),
    warning: (title: string, options?: ExternalToast): void =>
        show('warning', 6000, title, options),
    error: (title: string, options?: ExternalToast): void =>
        show('error', Infinity, title, options),
};
