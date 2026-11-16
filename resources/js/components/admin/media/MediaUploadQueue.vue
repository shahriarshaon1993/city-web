<script setup lang="ts">
import { CircleAlert, CircleCheck, ChevronDown, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useMediaUploads } from '@/composables/useMediaUploads';
import { formatBytes } from '@/lib/media-upload';
import { cn } from '@/lib/utils';

// CMP-ADM-MEDIA: the upload progress list, docked bottom-right in the library (`dock`) or inline
// in the picker's "Upload new" tab. Every file shows its own progress or the reason it was refused.
const props = withDefaults(defineProps<{ dock?: boolean }>(), { dock: false });

const { items, dismiss, clearFinished } = useMediaUploads();
const collapsed = ref(false);

const busy = computed(
    () =>
        items.filter((item) =>
            ['checking', 'waiting', 'uploading'].includes(item.status),
        ).length,
);
const heading = computed(() =>
    busy.value > 0
        ? `Uploading ${busy.value} ${busy.value === 1 ? 'file' : 'files'}`
        : `${items.length} ${items.length === 1 ? 'upload' : 'uploads'} finished`,
);

const STATUS: Record<string, string> = {
    checking: 'Checking…',
    waiting: 'Waiting…',
    uploading: 'Uploading',
    done: 'Uploaded',
    refused: 'Not accepted',
    failed: 'Failed',
};
</script>

<template>
    <section
        v-if="items.length"
        :class="
            cn(
                'grid overflow-hidden rounded-lg border bg-card text-card-foreground',
                props.dock &&
                    'fixed right-4 bottom-4 z-40 w-[min(24rem,calc(100vw-2rem))] shadow-lg',
            )
        "
        aria-label="Uploads"
    >
        <header class="flex items-center gap-2 border-b px-4 py-2">
            <h2 class="flex-1 text-sm font-medium" aria-live="polite">
                {{ heading }}
            </h2>
            <Button
                v-if="busy === 0"
                type="button"
                variant="ghost"
                size="sm"
                @click="clearFinished"
                >Clear</Button
            >
            <Button
                v-if="dock"
                type="button"
                variant="ghost"
                size="icon-sm"
                :aria-expanded="!collapsed"
                :aria-label="collapsed ? 'Show uploads' : 'Hide uploads'"
                @click="collapsed = !collapsed"
            >
                <ChevronDown
                    :class="
                        cn(
                            'transition-transform motion-reduce:transition-none',
                            collapsed && 'rotate-180',
                        )
                    "
                    aria-hidden="true"
                />
            </Button>
        </header>

        <ul v-show="!collapsed" class="max-h-72 divide-y overflow-y-auto">
            <li
                v-for="item in items"
                :key="item.key"
                class="grid gap-1.5 px-4 py-2.5 text-sm"
            >
                <div class="flex items-center gap-2">
                    <CircleCheck
                        v-if="item.status === 'done'"
                        class="size-4 shrink-0 text-success"
                        aria-hidden="true"
                    />
                    <CircleAlert
                        v-else-if="
                            item.status === 'refused' ||
                            item.status === 'failed'
                        "
                        class="size-4 shrink-0 text-destructive"
                        aria-hidden="true"
                    />
                    <span class="min-w-0 flex-1 truncate" :title="item.name">{{
                        item.name
                    }}</span>
                    <span
                        class="shrink-0 text-xs text-muted-foreground tabular-nums"
                        >{{ formatBytes(item.size) }}</span
                    >
                    <button
                        v-if="
                            ['done', 'refused', 'failed'].includes(item.status)
                        "
                        type="button"
                        class="grid size-6 shrink-0 place-items-center rounded-full hover:bg-accent"
                        :aria-label="`Dismiss ${item.name}`"
                        @click="dismiss(item.key)"
                    >
                        <X class="size-3.5" aria-hidden="true" />
                    </button>
                </div>

                <div
                    v-if="
                        item.status === 'uploading' ||
                        item.status === 'waiting' ||
                        item.status === 'checking'
                    "
                    class="h-1.5 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-label="`${item.name} upload`"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-valuenow="item.progress"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-[width] duration-200 motion-reduce:transition-none"
                        :style="{ width: `${item.progress}%` }"
                    />
                </div>

                <p
                    :class="
                        cn(
                            'text-xs',
                            item.error
                                ? 'text-destructive'
                                : 'text-muted-foreground',
                        )
                    "
                >
                    {{ item.error ?? STATUS[item.status] }}
                    <template v-if="item.status === 'uploading'">
                        · {{ item.progress }}%</template
                    >
                </p>
            </li>
        </ul>
    </section>
</template>
