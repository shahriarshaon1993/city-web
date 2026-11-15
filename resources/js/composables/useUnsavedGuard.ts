import { router } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { onBeforeUnmount } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

// CRUD-04 / UX-10: leaving an editor with unsaved changes asks first. In-app navigation (a
// sidebar link, a breadcrumb) is caught on Inertia's `before` event; closing the tab or
// reloading by the browser's own prompt. A save (POST/PATCH/PUT) is never blocked.
export function useUnsavedGuard(isDirty: () => boolean): void {
    const confirm = useConfirm();
    let bypass = false;

    const stop = router.on('before', (event) => {
        const visit = (event as CustomEvent).detail?.visit;

        if (bypass || !isDirty() || !visit) {
            return;
        }

        const leaving =
            visit.method === 'get' &&
            !visit.prefetch &&
            visit.url.pathname !== window.location.pathname;

        if (!leaving) {
            return;
        }

        void confirm({
            title: 'Leave without saving?',
            description:
                'You have changes that are not saved. If you leave now they are lost.',
            confirmLabel: 'Leave page',
            destructive: true,
        }).then((answer) => {
            if (answer) {
                bypass = true;
                router.visit(visit.url);
                bypass = false;
            }
        });

        return false;
    });

    useEventListener(window, 'beforeunload', (event: BeforeUnloadEvent) => {
        if (isDirty()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    onBeforeUnmount(stop);
}
