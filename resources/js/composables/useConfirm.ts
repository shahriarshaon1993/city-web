import { reactive } from 'vue';

// CMP-ADM-CONFIRM as a promise: `const confirm = useConfirm(); if (await confirm({...})) …`.
// One dialog (ConfirmHost, mounted in AdminLayout) serves every screen, so a page never needs
// its own open/pending state. The title names the object and count, the description the
// consequence (DEL-02), the button the verb (CRUD-08).
export type ConfirmRedirect = {
    /** Label above the picker, e.g. "Redirect visitors to…" (DEL-02). */
    label: string;
    options: Array<{ value: string; label: string }>;
};

export type ConfirmOptions = {
    title: string;
    description: string;
    confirmLabel: string;
    /** The named object, shown in bold under the title ("Aminul Haque"). */
    subject?: string;
    destructive?: boolean;
    /** Irreversible deletes: the exact text to type first (item name, or "DELETE 4"). */
    typeToConfirm?: string;
    redirect?: ConfirmRedirect;
};

/** What a confirmed dialog resolves with; `redirect` is the picked target, or null. */
export type ConfirmResult = { redirect: string | null };

type ConfirmState = {
    open: boolean;
    options: ConfirmOptions | null;
    resolve: ((result: ConfirmResult | null) => void) | null;
};

export const confirmState = reactive<ConfirmState>({
    open: false,
    options: null,
    resolve: null,
});

/** Resolves the pending dialog (called by ConfirmHost). */
export function settleConfirm(result: ConfirmResult | null): void {
    const resolve = confirmState.resolve;

    confirmState.resolve = null;
    confirmState.open = false;
    resolve?.(result);
}

export function useConfirm() {
    return (options: ConfirmOptions): Promise<ConfirmResult | null> => {
        // A second dialog while one is open cancels the first.
        settleConfirm(null);

        return new Promise((resolve) => {
            confirmState.options = options;
            confirmState.resolve = resolve;
            confirmState.open = true;
        });
    };
}
