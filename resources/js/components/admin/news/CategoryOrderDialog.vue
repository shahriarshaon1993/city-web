<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SortableList from '@/components/admin/catalog/SortableList.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { order } from '@/routes/admin/news/categories';

// CRUD-06: the order of the category row on the news pages. A drop saves at once with "Order saved" +
// Undo, so there is no Save button here.
const props = defineProps<{ items: Array<{ id: number; name: string }> }>();

const open = defineModel<boolean>('open', { default: false });
const rows = ref([...props.items]);

watch(
    () => [open.value, props.items] as const,
    () => {
        rows.value = [...props.items];
    },
);

function save(ids: number[]): void {
    router.patch(order.url(), { ids }, { preserveScroll: true });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Order categories</DialogTitle>
                <DialogDescription>
                    Drag a row, or use the arrows. The order is the one of the
                    category row on the news page. Each move is saved at once.
                </DialogDescription>
            </DialogHeader>

            <SortableList
                v-model="rows"
                label="Categories"
                :item-label="(item) => item.name"
                compact
                @reorder="save"
            >
                <template #default="{ item }">
                    <span class="text-sm">{{ item.name }}</span>
                </template>
            </SortableList>

            <DialogFooter>
                <Button type="button" @click="open = false">Done</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
