<script setup lang="ts">
import { Check, ChevronDown, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DatePicker from '@/components/forms/DatePicker.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { isFilterActive, filterKeys } from '@/lib/table-filters';
import {
    fetchOptions,
    relationLabels,
    resolveLabels,
} from '@/lib/table-relations';
import type { FilterDef, FilterOption } from '@/types';

// ADM-TBL-003: one popover per typed filter. Enum, boolean, date and relation filters apply as
// soon as they change; text and number filters apply on Enter or "Apply".
const props = defineProps<{
    def: FilterDef;
    filters: Record<string, string>;
    /** Prefix that keeps DOM ids unique when a page holds several tables. */
    scope: string;
}>();

const emit = defineEmits<{
    change: [patch: Record<string, string | null>];
}>();

const open = ref(false);
const active = computed(() => isFilterActive(props.def, props.filters));
const id = (suffix: string) => `${props.scope}-${props.def.id}-${suffix}`;

const current = (key: string): string => props.filters[key] ?? '';

// text + number drafts
const draft = ref({ a: '', b: '' });

function syncDraft(): void {
    const [first, second] = filterKeys(props.def);

    draft.value = { a: current(first), b: second ? current(second) : '' };
}

watch(open, (isOpen) => {
    if (isOpen) {
        syncDraft();
    }
});

function applyDraft(): void {
    const [first, second] = filterKeys(props.def);

    emit('change', {
        [first]: draft.value.a.trim() || null,
        ...(second ? { [second]: draft.value.b.trim() || null } : {}),
    });
    open.value = false;
}

function clear(): void {
    emit(
        'change',
        Object.fromEntries(filterKeys(props.def).map((key) => [key, null])),
    );
    open.value = false;
}

// enum
const selected = computed(() =>
    current(props.def.id).split(',').filter(Boolean),
);

function toggleOption(value: string, checked: boolean): void {
    const next = checked
        ? [...selected.value, value]
        : selected.value.filter((item) => item !== value);

    emit('change', { [props.def.id]: next.length > 0 ? next.join(',') : null });
}

// relation (async combobox)
const query = ref('');
const results = ref<FilterOption[]>([]);
const searching = ref(false);
const searchFailed = ref(false);
let controller: AbortController | null = null;
let timer: ReturnType<typeof setTimeout> | undefined;

async function search(term: string): Promise<void> {
    if (!props.def.optionsUrl) {
        return;
    }

    controller?.abort();
    controller = new AbortController();
    searching.value = true;
    searchFailed.value = false;

    try {
        results.value = await fetchOptions(
            props.def.optionsUrl,
            { q: term },
            controller.signal,
        );
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
            searchFailed.value = true;
        }
    } finally {
        searching.value = false;
    }
}

watch(query, (term) => {
    clearTimeout(timer);
    timer = setTimeout(() => void search(term), 250);
});

watch(open, (isOpen) => {
    if (isOpen && props.def.kind === 'relation') {
        query.value = '';
        void search('');
        void resolveLabels(props.def.optionsUrl ?? '', selected.value);
    }
});

const labels = computed(() => relationLabels(props.def.optionsUrl ?? ''));
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="font-normal"
                :class="active ? 'border-primary text-primary' : ''"
            >
                {{ def.label }}
                <span
                    v-if="active"
                    class="grid size-5 place-items-center rounded-full bg-primary text-xs font-medium text-primary-foreground"
                    aria-hidden="true"
                >
                    {{
                        def.kind === 'enum' || def.kind === 'relation'
                            ? selected.length
                            : '1'
                    }}
                </span>
                <span v-if="active" class="sr-only">(filter on)</span>
                <ChevronDown class="size-4 opacity-60" aria-hidden="true" />
            </Button>
        </PopoverTrigger>
        <PopoverContent align="start" class="w-72 p-3">
            <p class="mb-3 text-sm font-semibold">{{ def.label }}</p>

            <!-- text -->
            <form
                v-if="def.kind === 'text'"
                class="grid gap-3"
                @submit.prevent="applyDraft"
            >
                <Label :for="id('text')" class="sr-only">{{ def.label }}</Label>
                <Input
                    :id="id('text')"
                    v-model="draft.a"
                    class="h-9 text-sm"
                    :placeholder="def.placeholder ?? 'Contains…'"
                    maxlength="100"
                />
                <div class="flex justify-between gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clear"
                        >Clear</Button
                    >
                    <Button type="submit" size="sm">Apply</Button>
                </div>
            </form>

            <!-- enum: multi-select with counts -->
            <ul v-else-if="def.kind === 'enum'" class="grid gap-1">
                <li v-for="option in def.options" :key="option.value">
                    <label
                        :for="id(`opt-${option.value}`)"
                        class="flex min-h-9 cursor-pointer items-center gap-3 rounded-sm px-2 text-sm hover:bg-accent"
                    >
                        <Checkbox
                            :id="id(`opt-${option.value}`)"
                            :model-value="selected.includes(option.value)"
                            @update:model-value="
                                (checked) =>
                                    toggleOption(option.value, checked === true)
                            "
                        />
                        <span class="flex-1">{{ option.label }}</span>
                        <span
                            v-if="option.count !== undefined"
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            {{ option.count }}
                        </span>
                    </label>
                </li>
                <li v-if="active" class="pt-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clear"
                        >Clear</Button
                    >
                </li>
            </ul>

            <!-- boolean -->
            <RadioGroup
                v-else-if="def.kind === 'boolean'"
                :model-value="current(def.id) || 'any'"
                class="grid gap-1"
                :aria-label="def.label"
                @update:model-value="
                    (value) =>
                        emit('change', {
                            [def.id]: value === 'any' ? null : String(value),
                        })
                "
            >
                <label
                    v-for="option in [
                        { value: 'any', label: 'Any' },
                        { value: '1', label: 'Yes' },
                        { value: '0', label: 'No' },
                    ]"
                    :key="option.value"
                    :for="id(`bool-${option.value}`)"
                    class="flex min-h-9 cursor-pointer items-center gap-3 rounded-sm px-2 text-sm hover:bg-accent"
                >
                    <RadioGroupItem
                        :id="id(`bool-${option.value}`)"
                        :value="option.value"
                    />
                    {{ option.label }}
                </label>
            </RadioGroup>

            <!-- date range (Calendar popover, DD MMM YYYY) -->
            <div v-else-if="def.kind === 'date'" class="grid gap-3">
                <div class="grid gap-1.5">
                    <Label :for="id('from')" class="text-xs">From</Label>
                    <DatePicker
                        :id="id('from')"
                        :model-value="current(`${def.id}_from`)"
                        min="2020-01-01"
                        max="2100-12-31"
                        placeholder="Any date"
                        @update:model-value="
                            (value) =>
                                emit('change', {
                                    [`${def.id}_from`]: value || null,
                                })
                        "
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label :for="id('to')" class="text-xs">To</Label>
                    <DatePicker
                        :id="id('to')"
                        :model-value="current(`${def.id}_to`)"
                        min="2020-01-01"
                        max="2100-12-31"
                        placeholder="Any date"
                        @update:model-value="
                            (value) =>
                                emit('change', {
                                    [`${def.id}_to`]: value || null,
                                })
                        "
                    />
                </div>
                <div v-if="active">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clear"
                        >Clear</Button
                    >
                </div>
            </div>

            <!-- number range -->
            <form
                v-else-if="def.kind === 'number'"
                class="grid gap-3"
                @submit.prevent="applyDraft"
            >
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-1.5">
                        <Label :for="id('min')" class="text-xs">From</Label>
                        <Input
                            :id="id('min')"
                            v-model="draft.a"
                            type="number"
                            min="0"
                            inputmode="numeric"
                            class="h-9 text-sm"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label :for="id('max')" class="text-xs">To</Label>
                        <Input
                            :id="id('max')"
                            v-model="draft.b"
                            type="number"
                            min="0"
                            inputmode="numeric"
                            class="h-9 text-sm"
                        />
                    </div>
                </div>
                <div class="flex justify-between gap-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clear"
                        >Clear</Button
                    >
                    <Button type="submit" size="sm">Apply</Button>
                </div>
            </form>

            <!-- relation: async combobox -->
            <div v-else class="grid gap-2">
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Label :for="id('search')" class="sr-only"
                        >Search {{ def.label }}</Label
                    >
                    <Input
                        :id="id('search')"
                        v-model="query"
                        class="h-9 pl-8 text-sm"
                        placeholder="Type a name…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="true"
                        :aria-controls="id('list')"
                    />
                </div>
                <ul
                    :id="id('list')"
                    role="listbox"
                    :aria-label="def.label"
                    aria-multiselectable="true"
                    class="grid max-h-56 gap-0.5 overflow-y-auto"
                >
                    <li
                        v-for="option in results"
                        :key="option.value"
                        role="none"
                    >
                        <button
                            type="button"
                            role="option"
                            :aria-selected="selected.includes(option.value)"
                            class="flex min-h-9 w-full items-center gap-2 rounded-sm px-2 text-left text-sm hover:bg-accent"
                            @click="
                                toggleOption(
                                    option.value,
                                    !selected.includes(option.value),
                                )
                            "
                        >
                            <Check
                                class="size-4 shrink-0 text-primary"
                                :class="
                                    selected.includes(option.value)
                                        ? ''
                                        : 'invisible'
                                "
                                aria-hidden="true"
                            />
                            <span class="min-w-0 flex-1 truncate">
                                {{ option.label }}
                                <span
                                    v-if="option.hint"
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ option.hint }}</span
                                >
                            </span>
                        </button>
                    </li>
                </ul>
                <p
                    v-if="searching"
                    class="px-2 text-xs text-muted-foreground"
                    role="status"
                >
                    Searching…
                </p>
                <p
                    v-else-if="searchFailed"
                    class="px-2 text-xs text-destructive"
                    role="alert"
                >
                    Could not load the list. Try typing again.
                </p>
                <p
                    v-else-if="results.length === 0"
                    class="px-2 text-xs text-muted-foreground"
                >
                    No match.
                </p>
                <p
                    v-if="selected.length"
                    class="px-2 text-xs text-muted-foreground"
                >
                    Selected:
                    {{
                        selected
                            .map((value) => labels[value] ?? `#${value}`)
                            .join(', ')
                    }}
                </p>
                <div v-if="active">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="clear"
                        >Clear</Button
                    >
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
