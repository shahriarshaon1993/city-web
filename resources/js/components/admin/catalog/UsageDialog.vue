<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { UsageBlock } from '@/types';

// DEL-03: "the item is in use, so it cannot be deleted" — the reason, the usages as links (the
// first few, with a link to all of them) and, where a fix exists, a button for it ("Remove from
// these projects and delete", which the page confirms once more).
defineProps<{
    title: string;
    usage: UsageBlock | null;
    /** The one-click fix, when there is one. */
    fixLabel?: string;
}>();

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ fix: [] }>();
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ usage?.message }}</DialogDescription>
            </DialogHeader>

            <ul v-if="usage?.items.length" class="grid gap-1 text-sm">
                <li v-for="item in usage.items" :key="item.href">
                    <Link
                        :href="item.href"
                        class="inline-flex min-h-9 items-center underline underline-offset-4 hover:text-primary"
                        >{{ item.label }}</Link
                    >
                </li>
                <li
                    v-if="usage.total > usage.items.length"
                    class="text-muted-foreground"
                >
                    and {{ usage.total - usage.items.length }} more
                    <Link
                        v-if="usage.see_all"
                        :href="usage.see_all"
                        class="ml-1 underline underline-offset-4"
                        >See all</Link
                    >
                </li>
            </ul>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false"
                    >Close</Button
                >
                <Button
                    v-if="fixLabel"
                    type="button"
                    variant="destructive"
                    @click="emit('fix')"
                    >{{ fixLabel }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
