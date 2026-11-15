<script setup lang="ts">
import { useEventListener } from '@vueuse/core';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { shortcutsOpen } from '@/composables/useShortcutsDialog';

// HELP-09: lists the keys that work in the admin; `?` opens it from anywhere except a text field.
const mac =
    typeof navigator !== 'undefined' &&
    /Mac|iPhone|iPad/.test(navigator.platform);
const mod = mac ? '⌘' : 'Ctrl';

const shortcuts: Array<{ keys: string[]; label: string }> = [
    { keys: [mod, 'S'], label: 'Save the page you are editing' },
    { keys: ['?'], label: 'Show this list of shortcuts' },
    { keys: ['Esc'], label: 'Close a dialog, menu or drawer' },
    { keys: ['Tab'], label: 'Move to the next control; Shift+Tab goes back' },
];

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    const target = event.target as HTMLElement | null;
    const typing =
        target?.closest('input, textarea, select, [contenteditable="true"]') !==
        null;

    if (event.key === '?' && !typing && !event.ctrlKey && !event.metaKey) {
        event.preventDefault();
        shortcutsOpen.value = true;
    }
});
</script>

<template>
    <Dialog v-model:open="shortcutsOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Keyboard shortcuts</DialogTitle>
                <DialogDescription>
                    These work on every admin screen.
                </DialogDescription>
            </DialogHeader>
            <dl class="grid gap-3">
                <div
                    v-for="shortcut in shortcuts"
                    :key="shortcut.label"
                    class="flex items-center justify-between gap-4 text-sm"
                >
                    <dt>{{ shortcut.label }}</dt>
                    <dd class="flex shrink-0 gap-1">
                        <kbd
                            v-for="key in shortcut.keys"
                            :key="key"
                            class="min-w-7 rounded-md border bg-muted px-2 py-1 text-center font-sans text-xs font-medium"
                            >{{ key }}</kbd
                        >
                    </dd>
                </div>
            </dl>
        </DialogContent>
    </Dialog>
</template>
