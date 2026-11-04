<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, watch } from 'vue';
import { describeFilter, filterKeys } from '@/lib/table-filters';
import { relationLabels, resolveLabels } from '@/lib/table-relations';
import type { FilterDef } from '@/types';

// Active filter chips row (CMP-ADM-TABLE): each chip names the filter and removes only itself.
const props = defineProps<{
    defs: FilterDef[];
    filters: Record<string, string>;
    search: string;
}>();

const emit = defineEmits<{
    remove: [keys: string[]];
    clearSearch: [];
}>();

watch(
    () => props.filters,
    (filters) => {
        for (const def of props.defs) {
            if (def.kind === 'relation' && def.optionsUrl && filters[def.id]) {
                void resolveLabels(def.optionsUrl, filters[def.id].split(','));
            }
        }
    },
    { immediate: true },
);

const chips = computed(() =>
    props.defs.flatMap((def) => {
        const text = describeFilter(
            def,
            props.filters,
            def.optionsUrl ? relationLabels(def.optionsUrl) : {},
        );

        return text
            ? [{ key: def.id, label: def.label, text, keys: filterKeys(def) }]
            : [];
    }),
);
</script>

<template>
    <ul
        v-if="chips.length || search"
        class="flex flex-wrap items-center gap-2"
        aria-label="Active filters"
    >
        <li v-if="search">
            <span
                class="inline-flex h-7 items-center gap-1.5 rounded-full border bg-background pr-1 pl-3 text-xs"
            >
                Search: “{{ search }}”
                <button
                    type="button"
                    class="grid size-5 place-items-center rounded-full hover:bg-accent"
                    aria-label="Remove search"
                    @click="emit('clearSearch')"
                >
                    <X class="size-3" aria-hidden="true" />
                </button>
            </span>
        </li>
        <li v-for="chip in chips" :key="chip.key">
            <span
                class="inline-flex h-7 items-center gap-1.5 rounded-full border bg-background pr-1 pl-3 text-xs"
            >
                {{ chip.text }}
                <button
                    type="button"
                    class="grid size-5 place-items-center rounded-full hover:bg-accent"
                    :aria-label="`Remove filter ${chip.label}`"
                    @click="emit('remove', chip.keys)"
                >
                    <X class="size-3" aria-hidden="true" />
                </button>
            </span>
        </li>
    </ul>
</template>
