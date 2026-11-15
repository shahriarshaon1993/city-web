/** Data a toast action or a bulk request posts; assignable to Inertia's request payload. */
export type RequestData = Record<
    string,
    | string
    | number
    | boolean
    | null
    | Array<string | number>
    | Record<string, string>
>;

/** TOAST-02: an action button on a toast (Undo, Open, Retry). `get` visits, anything else sends a request. */
export type ToastAction = {
    label: string;
    url: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    data?: RequestData;
};

/** `flash.toast` sent by App\Support\Toasts\Toast (TOAST-01). */
export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    title: string;
    description?: string | null;
    action?: ToastAction | null;
};
