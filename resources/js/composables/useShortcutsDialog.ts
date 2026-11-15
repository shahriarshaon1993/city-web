import { ref } from 'vue';

// HELP-09: the "Keyboard shortcuts" dialog opens with `?` (AdminLayout) or from the account menu.
export const shortcutsOpen = ref(false);

export function useShortcutsDialog() {
    return {
        open: shortcutsOpen,
        show: (): void => {
            shortcutsOpen.value = true;
        },
    };
}
