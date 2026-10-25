import { toast } from 'vue-sonner';
import type { ExternalToast } from 'vue-sonner';

// CMP-TOAST-01 durations: 4 s success/info, 6 s warning, errors stay until
// dismissed. Use these instead of calling vue-sonner's toast directly.
export const notify = {
    success: (title: string, options?: ExternalToast) =>
        toast.success(title, { duration: 4000, ...options }),
    info: (title: string, options?: ExternalToast) =>
        toast.info(title, { duration: 4000, ...options }),
    warning: (title: string, options?: ExternalToast) =>
        toast.warning(title, { duration: 6000, ...options }),
    error: (title: string, options?: ExternalToast) =>
        toast.error(title, { duration: Infinity, ...options }),
};
