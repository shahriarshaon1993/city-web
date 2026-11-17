<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    shallowRef,
    watch,
} from 'vue';
import NotificationItem from '@/components/admin/notifications/NotificationItem.vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { sendJson } from '@/lib/http';
import { showToast } from '@/lib/notify';
import { feed, index, read, readAll } from '@/routes/admin/notifications';
import type { AdminNotification, NotificationFeed } from '@/types';

// CMP-ADM-NOTIF + FR-LEAD-008a: the bell shows the unread count, polls the feed every 60 s while the
// tab is visible, and toasts what arrived since the page was opened. The first poll only sets the
// baseline, so opening the admin never replays old notifications.
const POLL_MS = 60_000;

const page = usePage();
const unread = ref(
    (page.props.notifications as { unread: number } | undefined)?.unread ?? 0,
);
const items = shallowRef<AdminNotification[]>([]);
const open = ref(false);
const tab = ref<'all' | 'unread'>('all');

const seen = new Set<string>();
let baselineSet = false;
let timer: ReturnType<typeof setInterval> | null = null;
let lastPoll = 0;

// An Inertia visit (e.g. "Mark all read" on the inbox page) brings a fresh count.
watch(
    () => (page.props.notifications as { unread: number } | undefined)?.unread,
    (value) => {
        if (typeof value === 'number') {
            unread.value = value;
        }
    },
);

const shown = computed(() =>
    tab.value === 'unread'
        ? items.value.filter((item) => !item.read)
        : items.value,
);

const badge = computed(() =>
    unread.value > 99 ? '99+' : unread.value > 0 ? String(unread.value) : null,
);

const label = computed(() =>
    unread.value > 0
        ? `Notifications, ${unread.value} unread`
        : 'Notifications, none unread',
);

function announce(fresh: AdminNotification[]): void {
    if (fresh.length === 0) {
        return;
    }

    if (fresh.length === 1) {
        const [only] = fresh;

        showToast({
            type: 'info',
            title: only.title,
            description: only.body ?? undefined,
            action: only.url
                ? { label: 'Open', url: only.url, method: 'get', data: {} }
                : undefined,
        });

        return;
    }

    showToast({
        type: 'info',
        title: `${fresh.length} new notifications`,
        action: {
            label: 'View all',
            url: index.url(),
            method: 'get',
            data: {},
        },
    });
}

async function poll(): Promise<void> {
    lastPoll = Date.now();

    try {
        const data = await sendJson<NotificationFeed>('GET', feed.url());
        const fresh = baselineSet
            ? data.items.filter((item) => !item.read && !seen.has(item.id))
            : [];

        data.items.forEach((item) => seen.add(item.id));
        baselineSet = true;
        items.value = data.items;
        unread.value = data.unread;

        announce(fresh);
    } catch {
        // Offline or signed out: the offline banner and the 401 handling already say so.
    }
}

function onVisibility(): void {
    if (
        document.visibilityState === 'visible' &&
        Date.now() - lastPoll >= POLL_MS
    ) {
        void poll();
    }
}

onMounted(() => {
    void poll();
    timer = setInterval(() => {
        if (document.visibilityState === 'visible') {
            void poll();
        }
    }, POLL_MS);
    document.addEventListener('visibilitychange', onVisibility);
});

onBeforeUnmount(() => {
    if (timer !== null) {
        clearInterval(timer);
    }

    document.removeEventListener('visibilitychange', onVisibility);
});

async function openItem(item: AdminNotification): Promise<void> {
    open.value = false;

    if (!item.read) {
        items.value = items.value.map((entry) =>
            entry.id === item.id ? { ...entry, read: true } : entry,
        );
        unread.value = Math.max(0, unread.value - 1);
        void sendJson('POST', read.url(item.id)).catch(() => undefined);
    }

    if (item.url) {
        router.visit(item.url);
    }
}

async function markAllRead(): Promise<void> {
    try {
        await sendJson('POST', readAll.url());
        items.value = items.value.map((entry) => ({ ...entry, read: true }));
        unread.value = 0;
        showToast({
            type: 'success',
            title: 'All notifications marked as read',
        });
    } catch {
        showToast({
            type: 'error',
            title: 'Something went wrong. Your changes were not saved.',
        });
    }
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                :aria-label="label"
                class="relative"
            >
                <Bell class="size-5" aria-hidden="true" />
                <span
                    v-if="badge"
                    class="absolute top-1 right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-sidebar-primary px-1 text-[10px] leading-none font-semibold text-sidebar-primary-foreground tabular-nums"
                    aria-hidden="true"
                >
                    {{ badge }}
                </span>
            </Button>
        </PopoverTrigger>
        <PopoverContent
            align="end"
            class="w-[380px] max-w-[calc(100vw-1rem)] p-0"
        >
            <div class="flex items-center justify-between gap-3 px-4 pt-3">
                <p class="font-display text-base font-medium">Notifications</p>
                <Button
                    variant="link"
                    size="sm"
                    :disabled="unread === 0"
                    class="h-8 px-0"
                    @click="markAllRead"
                >
                    Mark all read
                </Button>
            </div>

            <Tabs v-model="tab" class="px-4">
                <TabsList variant="underline" class="gap-5">
                    <TabsTrigger value="all">All</TabsTrigger>
                    <TabsTrigger value="unread">
                        Unread<span
                            v-if="unread > 0"
                            class="text-xs font-normal text-muted-foreground tabular-nums"
                            >({{ unread }})</span
                        >
                    </TabsTrigger>
                </TabsList>
            </Tabs>

            <ul class="max-h-[min(24rem,60vh)] divide-y overflow-y-auto">
                <li v-for="item in shown" :key="item.id">
                    <button
                        type="button"
                        class="block min-h-11 w-full px-4 py-3 text-left hover:bg-muted/60 focus-visible:bg-muted/60 focus-visible:outline-none"
                        @click="openItem(item)"
                    >
                        <NotificationItem :notification="item" />
                    </button>
                </li>
                <li
                    v-if="shown.length === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    {{
                        tab === 'unread'
                            ? 'You are all caught up.'
                            : 'Nothing yet. New leads and follow-ups appear here.'
                    }}
                </li>
            </ul>

            <div class="border-t px-4 py-2">
                <Button
                    variant="link"
                    size="sm"
                    as-child
                    class="h-9 px-0"
                    @click="open = false"
                >
                    <Link :href="index.url()">View all notifications</Link>
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
