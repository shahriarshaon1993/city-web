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
import type { OrderItem, SocialChoice } from '@/types';

// CRUD-06 for testimonials, partners, FAQs, team and offices: drag a row, or use the grip's keyboard
// (Space, arrows, Space) or the arrows. A finished move saves at once with "Order saved" + Undo, so
// there is no Save button. `groups` splits a list that the site shows in sections (team groups, FAQ
// topics): only the chosen section is ordered, the others keep their place.
const props = defineProps<{
    title: string;
    description: string;
    items: OrderItem[];
    orderUrl: string;
    groups?: SocialChoice[];
}>();

const open = defineModel<boolean>('open', { default: false });
const group = ref<string>(props.groups?.[0]?.value ?? '');
const rows = ref<OrderItem[]>([]);

function refresh(): void {
    rows.value = props.groups
        ? props.items.filter((item) => item.group === group.value)
        : [...props.items];
}

watch(() => [open.value, props.items, group.value] as const, refresh, {
    immediate: true,
});

function save(ids: number[]): void {
    router.patch(props.orderUrl, { ids }, { preserveScroll: true });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div
                v-if="groups"
                class="flex flex-wrap gap-2"
                role="group"
                aria-label="Section to order"
            >
                <Button
                    v-for="option in groups"
                    :key="option.value"
                    type="button"
                    size="sm"
                    :variant="group === option.value ? 'primary' : 'outline'"
                    :aria-pressed="group === option.value"
                    @click="group = option.value"
                    >{{ option.label }}</Button
                >
            </div>

            <SortableList
                v-model="rows"
                :label="title"
                :item-label="(item) => item.label"
                compact
                @reorder="save"
            >
                <template #default="{ item }">
                    <span class="min-w-0">
                        <span class="block truncate text-sm">{{
                            item.label
                        }}</span>
                        <span
                            v-if="item.hint || !item.published"
                            class="block truncate text-xs text-muted-foreground"
                            >{{ item.hint
                            }}<template v-if="!item.published">
                                {{ item.hint ? ' · ' : '' }}Hidden</template
                            ></span
                        >
                    </span>
                </template>
            </SortableList>

            <p v-if="rows.length === 0" class="text-sm text-muted-foreground">
                Nothing to order here yet.
            </p>

            <DialogFooter>
                <Button type="button" @click="open = false">Done</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
