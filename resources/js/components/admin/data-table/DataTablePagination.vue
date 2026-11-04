<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { PER_PAGE_OPTIONS } from '@/lib/table-state';
import type { TableMeta } from '@/types';

// ADM-TBL-001 footer: "Showing 1–25 of 312", rows per page, first / previous / page input / next / last.
const props = defineProps<{ scope: string; meta: TableMeta }>();

const emit = defineEmits<{
    page: [page: number];
    perPage: [perPage: number];
}>();

const pageInput = ref(String(props.meta.page));

watch(
    () => props.meta.page,
    (value) => {
        pageInput.value = String(value);
    },
);

function goToInput(): void {
    const value = Number.parseInt(pageInput.value, 10);

    if (Number.isFinite(value)) {
        emit('page', value);
    }

    pageInput.value = String(props.meta.page);
}
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-3 text-sm"
    >
        <p class="text-muted-foreground tabular-nums">
            <template v-if="meta.total > 0">
                Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}
            </template>
            <template v-else>No rows</template>
        </p>

        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <div class="flex items-center gap-2">
                <label :for="`${scope}-per-page`" class="text-muted-foreground"
                    >Rows per page</label
                >
                <Select
                    :model-value="String(meta.per_page)"
                    @update:model-value="
                        (value) => emit('perPage', Number(value))
                    "
                >
                    <SelectTrigger
                        :id="`${scope}-per-page`"
                        class="h-9 w-20 text-sm"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="size in PER_PAGE_OPTIONS"
                            :key="size"
                            :value="String(size)"
                        >
                            {{ size }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <nav class="flex items-center gap-1" aria-label="Table pages">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="meta.page <= 1"
                    aria-label="First page"
                    @click="emit('page', 1)"
                >
                    <ChevronsLeft aria-hidden="true" />
                </Button>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="meta.page <= 1"
                    aria-label="Previous page"
                    @click="emit('page', meta.page - 1)"
                >
                    <ChevronLeft aria-hidden="true" />
                </Button>
                <form
                    class="flex items-center gap-2 px-1"
                    @submit.prevent="goToInput"
                >
                    <label :for="`${scope}-page`" class="sr-only">Page</label>
                    <Input
                        :id="`${scope}-page`"
                        v-model="pageInput"
                        class="h-9 w-14 px-2 text-center text-sm tabular-nums"
                        inputmode="numeric"
                        :aria-label="`Page, of ${meta.last_page}`"
                        @blur="goToInput"
                    />
                    <span class="text-muted-foreground tabular-nums"
                        >of {{ meta.last_page }}</span
                    >
                </form>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="meta.page >= meta.last_page"
                    aria-label="Next page"
                    @click="emit('page', meta.page + 1)"
                >
                    <ChevronRight aria-hidden="true" />
                </Button>
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="meta.page >= meta.last_page"
                    aria-label="Last page"
                    @click="emit('page', meta.last_page)"
                >
                    <ChevronsRight aria-hidden="true" />
                </Button>
            </nav>
        </div>
    </div>
</template>
