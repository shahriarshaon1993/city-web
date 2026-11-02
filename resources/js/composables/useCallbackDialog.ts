import { ref, shallowRef } from 'vue';

// The "Request a call back" dialog (FR-LEAD callback) is mounted once in the
// public layout and opened from the footer (and any later trigger). Module
// state is only written on a click, never during SSR.
const isOpen = ref(false);
const opener = shallowRef<HTMLElement | null>(null);

export function useCallbackDialog() {
    return {
        isOpen,
        opener,
        open(): void {
            opener.value =
                document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;
            isOpen.value = true;
        },
    };
}
