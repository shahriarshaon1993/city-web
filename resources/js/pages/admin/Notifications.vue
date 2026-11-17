<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BellRing,
    CheckCheck,
    Ellipsis,
    Trash2,
} from '@lucide/vue';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import NotificationItem from '@/components/admin/notifications/NotificationItem.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useConfirm } from '@/composables/useConfirm';
import { sendJson } from '@/lib/http';
import { cn } from '@/lib/utils';
import {
    clear,
    destroy,
    index,
    read,
    readAll,
} from '@/routes/admin/notifications';
import type {
    AdminNotification,
    NotificationKindOption,
    NotificationStats,
    NotificationsMeta,
} from '@/types';

// ADM-SCR-21: the inbox. A summary band, filters that are real links, rows grouped by day (Dhaka
// time) that open what they are about and mark themselves read, and a hard delete with a confirm
// (DEL-09).
const props = defineProps<{
    rows: AdminNotification[];
    meta: NotificationsMeta;
    filters: { type: string | null; status: 'all' | 'unread' };
    kinds: NotificationKindOption[];
    unread: number;
    stats: NotificationStats;
    hasRead: boolean;
}>();

const confirm = useConfirm();
const REFRESH = ['rows', 'meta', 'unread', 'stats', 'hasRead', 'notifications'];

function link(type: string | null, status: 'all' | 'unread'): string {
    const query: Record<string, string> = {};

    if (type !== null) {
        query.type = type;
    }

    if (status === 'unread') {
        query.status = 'unread';
    }

    return index.url({ query });
}

const typeLinks = computed(() => [
    { label: 'All', value: null as string | null, count: props.stats.total },
    ...props.kinds.map((kind) => ({
        ...kind,
        count: props.stats.kinds[kind.value]?.total ?? 0,
    })),
]);

const filtered = computed(
    () => props.filters.type !== null || props.filters.status !== 'all',
);

const summary = computed(() => [
    {
        label: 'Unread',
        value: props.unread,
        hint: props.unread === 0 ? 'All caught up' : 'Waiting for you',
        accent: props.unread > 0,
    },
    {
        label: 'Today',
        value: props.stats.today,
        hint: 'Since midnight (Dhaka)',
    },
    {
        label: 'In your inbox',
        value: props.stats.total,
        hint: 'All notifications',
    },
]);

// Rows arrive newest first, so a new heading starts whenever the Dhaka calendar day changes.
const DAY = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Dhaka' });
const DAY_LABEL = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Dhaka',
    weekday: 'long',
    day: '2-digit',
    month: 'short',
});

function dayHeading(iso: string | null): string {
    if (iso === null) {
        return 'Earlier';
    }

    const date = new Date(iso);
    const key = DAY.format(date);

    if (key === DAY.format(new Date())) {
        return 'Today';
    }

    if (key === DAY.format(new Date(Date.now() - 86_400_000))) {
        return 'Yesterday';
    }

    return DAY_LABEL.format(date);
}

const groups = computed(() => {
    const out: { heading: string; items: AdminNotification[] }[] = [];

    for (const item of props.rows) {
        const heading = dayHeading(item.created_at);
        const last = out[out.length - 1];

        if (last && last.heading === heading) {
            last.items.push(item);
        } else {
            out.push({ heading, items: [item] });
        }
    }

    return out;
});

async function markRead(item: AdminNotification): Promise<void> {
    await sendJson('POST', read.url(item.id)).catch(() => undefined);
    router.reload({ only: REFRESH });
}

async function open(item: AdminNotification): Promise<void> {
    if (!item.read) {
        await sendJson('POST', read.url(item.id)).catch(() => undefined);
    }

    if (item.url) {
        router.visit(item.url);

        return;
    }

    router.reload({ only: REFRESH });
}

function markAllRead(): void {
    router.post(readAll.url(), {}, { preserveScroll: true });
}

async function remove(item: AdminNotification): Promise<void> {
    const answer = await confirm({
        title: 'Delete this notification?',
        description:
            'It is removed for good. The lead or page it points to is not affected.',
        confirmLabel: 'Delete notification',
        subject: item.title,
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(item.id), { preserveScroll: true });
    }
}

async function removeRead(): Promise<void> {
    const answer = await confirm({
        title: 'Delete all read notifications?',
        description:
            'Notifications you have already read are removed for good. Unread ones stay.',
        confirmLabel: 'Delete read notifications',
        destructive: true,
    });

    if (answer) {
        router.delete(clear.url(), { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Notifications" />

    <AdminPageHeader
        title="Notifications"
        description="New leads, leads assigned to you and follow-ups that are due. The bell at the top shows the newest ones."
    >
        <template #actions>
            <Button
                variant="outline"
                :disabled="unread === 0"
                @click="markAllRead"
            >
                <CheckCheck class="size-4" aria-hidden="true" />
                Mark all read
            </Button>
            <Button variant="outline" :disabled="!hasRead" @click="removeRead">
                <Trash2 class="size-4" aria-hidden="true" />
                Delete read
            </Button>
        </template>
    </AdminPageHeader>

    <!-- Summary band: hairline cells, light display numbers, overline labels. -->
    <dl
        class="mb-8 grid grid-cols-3 divide-x overflow-hidden rounded-xl border bg-card"
    >
        <div
            v-for="cell in summary"
            :key="cell.label"
            class="px-5 py-4 sm:px-7 sm:py-5"
        >
            <dt
                class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
            >
                {{ cell.label }}
            </dt>
            <dd
                :class="
                    cn(
                        'mt-1 font-display text-3xl/9 font-light tabular-nums sm:text-4xl/10',
                        cell.accent
                            ? 'text-sidebar-primary'
                            : 'text-foreground',
                    )
                "
            >
                {{ cell.value }}
            </dd>
            <p class="mt-1 hidden text-xs text-muted-foreground sm:block">
                {{ cell.hint }}
            </p>
        </div>
    </dl>

    <div
        class="mb-4 flex flex-wrap items-center justify-between gap-x-6 gap-y-3"
    >
        <nav
            aria-label="Filter by type"
            class="flex max-w-full gap-1 overflow-x-auto rounded-full bg-muted p-1"
        >
            <Link
                v-for="option in typeLinks"
                :key="option.value ?? 'all'"
                :href="link(option.value, filters.status)"
                :class="
                    cn(
                        'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full px-4 text-sm font-medium whitespace-nowrap transition-colors',
                        filters.type === option.value
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    )
                "
                :aria-current="
                    filters.type === option.value ? 'true' : undefined
                "
            >
                {{ option.label }}
                <span class="text-xs font-normal tabular-nums opacity-70">{{
                    option.count
                }}</span>
            </Link>
        </nav>

        <Link
            :href="
                link(
                    filters.type,
                    filters.status === 'unread' ? 'all' : 'unread',
                )
            "
            :class="
                cn(
                    'inline-flex h-9 items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors',
                    filters.status === 'unread'
                        ? 'border-foreground bg-foreground text-background'
                        : 'text-muted-foreground hover:border-foreground/40 hover:text-foreground',
                )
            "
            :aria-pressed="filters.status === 'unread'"
        >
            <span
                class="size-2 rounded-full bg-sidebar-primary"
                aria-hidden="true"
            />
            Unread only
        </Link>
    </div>

    <div
        v-if="rows.length > 0"
        class="overflow-hidden rounded-xl border bg-card"
    >
        <section v-for="group in groups" :key="group.heading">
            <h2
                class="border-b bg-muted/40 px-5 py-2.5 text-xs font-medium tracking-wider text-muted-foreground uppercase not-first:border-t sm:px-7"
            >
                {{ group.heading }}
            </h2>
            <ul class="divide-y">
                <li
                    v-for="item in group.items"
                    :key="item.id"
                    :class="
                        cn(
                            'group relative flex items-center gap-2 pr-2 transition-colors sm:pr-4',
                            !item.read &&
                                'bg-primary/[0.035] before:absolute before:inset-y-3 before:left-0 before:w-[3px] before:rounded-r-full before:bg-sidebar-primary',
                        )
                    "
                >
                    <button
                        type="button"
                        class="min-h-11 flex-1 px-5 py-4 text-left transition-colors hover:bg-muted/50 focus-visible:bg-muted/50 focus-visible:outline-none sm:px-7"
                        @click="open(item)"
                    >
                        <NotificationItem :notification="item" detailed />
                    </button>
                    <ArrowUpRight
                        v-if="item.url"
                        class="hidden size-4 shrink-0 text-muted-foreground opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100 sm:block"
                        aria-hidden="true"
                    />
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Actions for: ${item.title}`"
                            >
                                <Ellipsis class="size-4" aria-hidden="true" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                v-if="!item.read"
                                @select="markRead(item)"
                            >
                                <CheckCheck class="size-4" aria-hidden="true" />
                                Mark as read
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                class="text-destructive focus:text-destructive"
                                @select="remove(item)"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </li>
            </ul>
        </section>
    </div>

    <div v-else class="rounded-xl border bg-card px-6 py-20 text-center">
        <span
            class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            aria-hidden="true"
        >
            <BellRing class="size-6" />
        </span>
        <p class="mt-5 font-display text-xl font-medium">
            {{
                filtered
                    ? 'Nothing matches these filters'
                    : 'You are all caught up'
            }}
        </p>
        <p class="mx-auto mt-1.5 max-w-md text-sm text-muted-foreground">
            {{
                filtered
                    ? 'Try another type, or show every notification.'
                    : 'When a lead arrives, is assigned to you or has a follow-up due today, it appears here.'
            }}
        </p>
        <Button v-if="filtered" variant="outline" as-child class="mt-5">
            <Link :href="index.url()">Show all notifications</Link>
        </Button>
    </div>

    <nav
        v-if="meta.last_page > 1"
        aria-label="Pagination"
        class="mt-6 flex items-center justify-between gap-4 text-sm"
    >
        <p class="text-muted-foreground tabular-nums">
            {{ meta.from }}–{{ meta.to }} of {{ meta.total }}
        </p>
        <div class="flex gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!meta.prev_url"
                as-child
            >
                <Link v-if="meta.prev_url" :href="meta.prev_url" rel="prev"
                    >Previous</Link
                >
                <span v-else>Previous</span>
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="!meta.next_url"
                as-child
            >
                <Link v-if="meta.next_url" :href="meta.next_url" rel="next"
                    >Next</Link
                >
                <span v-else>Next</span>
            </Button>
        </div>
    </nav>
</template>
