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
import { amenityIcons } from '@/lib/amenity-icons';
import { order } from '@/routes/admin/amenities';

// CRUD-06: the library order of the amenities (the order of lists and pickers). A drop saves at
// once with "Order saved" + Undo, so there is no Save button here.
const props = defineProps<{
    items: Array<{ id: number; name: string; icon: string; category: string }>;
}>();

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
                <DialogTitle>Order amenities</DialogTitle>
                <DialogDescription>
                    Drag a row, or use the arrows. Each move is saved at once.
                </DialogDescription>
            </DialogHeader>

            <SortableList
                v-model="rows"
                label="Amenities"
                :item-label="(item) => item.name"
                compact
                @reorder="save"
            >
                <template #default="{ item }">
                    <span class="flex items-center gap-2 text-sm">
                        <component
                            :is="amenityIcons[item.icon]"
                            v-if="amenityIcons[item.icon]"
                            class="size-4 text-muted-foreground"
                            aria-hidden="true"
                        />
                        {{ item.name }}
                        <span class="text-xs text-muted-foreground">{{
                            item.category
                        }}</span>
                    </span>
                </template>
            </SortableList>

            <DialogFooter>
                <Button type="button" @click="open = false">Done</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
