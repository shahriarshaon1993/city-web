<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { ChevronsUpDown, Search } from '@lucide/vue';
import { nextTick, ref, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { targets } from '@/routes/admin/menus';
import type { LinkTargetInfo, MenuLinkType } from '@/types';

// CMS-NAV-002 / 004: pick the page, project, area, article or category a link points at. The
// list is searched on the server (a site can hold hundreds of projects); the menu stores the ID
// only. Targets that are not public yet are listed, marked, so the editor can still choose them.
const props = defineProps<{
    id: string;
    type: MenuLinkType;
    /** The chosen target, for the button label. */
    selected: LinkTargetInfo | null;
    invalid?: boolean;
    describedby?: string;
}>();

const emit = defineEmits<{ select: [target: LinkTargetInfo] }>();

const open = ref(false);
const query = ref('');
const results = ref<LinkTargetInfo[]>([]);
const loading = ref(false);
const failed = ref(false);
const list = useTemplateRef<HTMLElement>('list');
const search = useTemplateRef<InstanceType<typeof Input>>('search');

let controller: AbortController | null = null;

async function load(): Promise<void> {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    failed.value = false;

    try {
        const response = await fetch(
            targets.url({ query: { type: props.type, q: query.value } }),
            {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        results.value = (await response.json()).targets as LinkTargetInfo[];
        loading.value = false;
    } catch (error) {
        if ((error as Error).name !== 'AbortError') {
            failed.value = true;
            loading.value = false;
        }
    }
}

const debouncedLoad = useDebounceFn(load, 200);

watch(open, async (isOpen) => {
    if (isOpen) {
        query.value = '';
        results.value = [];
        void load();
        await nextTick();
        (search.value?.$el as HTMLInputElement | undefined)?.focus();
    } else {
        controller?.abort();
    }
});

watch(query, () => {
    if (open.value) {
        void debouncedLoad();
    }
});

function choose(target: LinkTargetInfo): void {
    emit('select', target);
    open.value = false;
}

// Arrow keys walk the options; Enter / Space choose the focused one (native buttons).
function onKeydown(event: KeyboardEvent): void {
    if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
        return;
    }

    const options = Array.from(
        list.value?.querySelectorAll<HTMLButtonElement>('button') ?? [],
    );

    if (options.length === 0) {
        return;
    }

    event.preventDefault();

    const current = options.indexOf(
        document.activeElement as HTMLButtonElement,
    );
    const step = event.key === 'ArrowDown' ? 1 : -1;

    if (current === -1) {
        if (event.key === 'ArrowDown') {
            options[0].focus();
        }

        return;
    }

    if (current + step < 0) {
        (search.value?.$el as HTMLInputElement | undefined)?.focus();

        return;
    }

    options[Math.min(options.length - 1, current + step)].focus();
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                :id="id"
                type="button"
                variant="outline"
                class="w-full justify-between font-normal"
                :aria-invalid="invalid || undefined"
                :aria-describedby="describedby"
            >
                <span
                    :class="
                        selected ? 'truncate' : 'truncate text-muted-foreground'
                    "
                >
                    {{ selected?.label ?? 'Choose…' }}
                </span>
                <ChevronsUpDown
                    class="size-4 shrink-0 opacity-60"
                    aria-hidden="true"
                />
            </Button>
        </PopoverTrigger>
        <PopoverContent
            align="start"
            class="w-(--reka-popover-trigger-width) min-w-72 p-0"
            @keydown="onKeydown"
        >
            <div class="flex items-center gap-2 border-b px-3">
                <Search
                    class="size-4 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    ref="search"
                    v-model="query"
                    type="search"
                    class="h-10 border-0 px-0 shadow-none focus-visible:ring-0"
                    placeholder="Search by name…"
                    aria-label="Search by name"
                    autocomplete="off"
                />
            </div>
            <ul
                ref="list"
                class="max-h-64 overflow-y-auto p-1"
                aria-label="Results"
            >
                <li v-for="target in results" :key="target.id">
                    <button
                        type="button"
                        class="flex min-h-11 w-full flex-col items-start rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent focus-visible:bg-accent focus-visible:outline-none"
                        :aria-current="
                            selected?.id === target.id ? 'true' : undefined
                        "
                        @click="choose(target)"
                    >
                        <span class="font-medium">{{ target.label }}</span>
                        <span class="text-xs text-muted-foreground">
                            <template v-if="!target.published"
                                >Not public yet ·
                            </template>
                            {{ target.hint ?? target.url }}
                        </span>
                    </button>
                </li>
            </ul>
            <p
                v-if="loading && results.length === 0"
                class="px-3 py-4 text-sm text-muted-foreground"
                role="status"
            >
                Searching…
            </p>
            <p
                v-else-if="failed"
                class="px-3 py-4 text-sm text-destructive"
                role="alert"
            >
                The list could not be loaded. Close this and try again.
            </p>
            <p
                v-else-if="!loading && results.length === 0"
                class="px-3 py-4 text-sm text-muted-foreground"
                role="status"
            >
                Nothing matches “{{ query }}”.
            </p>
        </PopoverContent>
    </Popover>
</template>
