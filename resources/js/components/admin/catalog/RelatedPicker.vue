<script setup lang="ts">
import { Plus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import type { GroupedOption } from '@/types';
import AdminSelect from './AdminSelect.vue';

// A short ordered list picked from options (similar projects ≤ 3, nearby areas ≤ 6): chosen items
// as removable rows, a select to add the next one. Reordering is not needed for these lists.
const props = defineProps<{
    id: string;
    options: GroupedOption[];
    max: number;
    noun: string;
    describedby?: string;
    invalid?: boolean;
}>();

const model = defineModel<number[]>({ required: true });
const pending = ref<number | null>(null);

const chosen = computed(() =>
    model.value.map((id) => ({
        id,
        option: props.options.find((option) => option.value === id),
    })),
);
const free = computed(() =>
    props.options.filter((option) => !model.value.includes(option.value)),
);

function add(): void {
    if (pending.value !== null && model.value.length < props.max) {
        model.value = [...model.value, pending.value];
        pending.value = null;
    }
}

function remove(id: number): void {
    model.value = model.value.filter((item) => item !== id);
}
</script>

<template>
    <div
        :id="id"
        class="grid gap-3"
        role="group"
        :aria-describedby="describedby"
    >
        <ul v-if="chosen.length" class="grid gap-1.5">
            <li
                v-for="item in chosen"
                :key="item.id"
                class="flex items-center justify-between gap-3 rounded-md border px-3 py-1.5 text-sm"
            >
                <span class="min-w-0 truncate">
                    {{ item.option?.label ?? `#${item.id}` }}
                    <span
                        v-if="item.option?.group"
                        class="ml-1 text-xs text-muted-foreground"
                        >{{ item.option.group }}</span
                    >
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="`Remove ${item.option?.label ?? noun}`"
                    @click="remove(item.id)"
                >
                    <X aria-hidden="true" />
                </Button>
            </li>
        </ul>

        <div v-if="model.length < max" class="flex items-center gap-2">
            <div class="min-w-0 flex-1">
                <AdminSelect
                    :id="`${id}-add`"
                    v-model="pending"
                    :options="free"
                    :placeholder="`Add ${noun}…`"
                    :invalid="invalid"
                />
            </div>
            <Button
                type="button"
                variant="outline"
                :disabled="pending === null"
                @click="add"
            >
                <Plus aria-hidden="true" />
                Add
            </Button>
        </div>
        <p v-else class="text-xs text-muted-foreground">
            The limit of {{ max }} is reached.
        </p>
    </div>
</template>
