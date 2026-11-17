<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    Building2,
    CalendarClock,
    Clock,
    GripVertical,
    Inbox,
    Phone,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getInitials } from '@/composables/useInitials';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { STATUS_TONE } from '@/lib/leads';
import { cn } from '@/lib/utils';
import { index as leadsIndex } from '@/routes/admin/leads';
import type {
    LeadBoardColumn,
    LeadCard,
    LeadStatusOption,
    LeadStatusValue,
} from '@/types';

// CMP-ADM-KANBAN / FR-LEAD-009: one column per status (300 px, the board scrolls sideways inside
// its own region, each column scrolls up and down), with its count and the newest cards. Drag a
// card by its grip to another column, or use the card's "Move to…" menu — the keyboard and touch
// way to do the same. Won and Lost ask for a reason (the page opens the dialog). The columns are a
// local copy so a drop shows at once; the server's answer replaces them, and `move` is told to put
// things back when the move is refused or cancelled.
const props = defineProps<{
    columns: LeadBoardColumn[];
    statuses: LeadStatusOption[];
}>();

const emit = defineEmits<{
    open: [card: LeadCard];
    move: [card: LeadCard, to: LeadStatusValue];
}>();

// Props are reactive proxies, which structuredClone refuses; the data is plain JSON, so copy the lists.
const copyColumns = (columns: LeadBoardColumn[]): LeadBoardColumn[] =>
    columns.map((column) => ({ ...column, cards: [...column.cards] }));

const local = ref<LeadBoardColumn[]>(copyColumns(props.columns));

watch(
    () => props.columns,
    (next) => {
        local.value = copyColumns(next);
    },
);

// Full class names, so Tailwind can see them. Only tokens that exist (50 / 200 / 400 / 700 / 900).
const accent: Record<string, string> = {
    info: 'border-t-info-400',
    warning: 'border-t-warning-400',
    success: 'border-t-success-400',
    error: 'border-t-error-400',
    neutral: 'border-t-stone-400',
};
const dot: Record<string, string> = {
    info: 'bg-info-400',
    warning: 'bg-warning-400',
    success: 'bg-success-400',
    error: 'bg-error-400',
    neutral: 'bg-stone-400',
};

function onAdd(event: { item: HTMLElement }, to: LeadStatusValue): void {
    const id = Number(event.item.dataset.id);
    const card = props.columns
        .flatMap((column) => column.cards)
        .find((item) => item.id === id);

    if (card && card.status !== to) {
        emit('move', card, to);
    }
}

const dueDate = (card: LeadCard): string =>
    card.follow_up_at
        ? formatDateTime(card.follow_up_at).split(' · ')[0].slice(0, 6)
        : '';
</script>

<template>
    <div
        class="-mx-1 flex items-start gap-4 overflow-x-auto px-1 pb-4"
        role="region"
        aria-label="Lead board"
        tabindex="0"
    >
        <section
            v-for="column in local"
            :key="column.status"
            :class="
                cn(
                    'flex max-h-[calc(100dvh-19rem)] min-h-64 w-[300px] shrink-0 flex-col overflow-hidden rounded-xl border border-t-4 bg-background/60',
                    accent[STATUS_TONE[column.status]],
                )
            "
            :aria-labelledby="`board-${column.status}`"
        >
            <header
                class="flex items-center justify-between gap-2 px-4 pt-3.5 pb-2.5"
            >
                <h2
                    :id="`board-${column.status}`"
                    class="flex min-w-0 items-center gap-2 text-sm font-semibold tracking-tight"
                >
                    <span
                        :class="
                            cn(
                                'size-2 shrink-0 rounded-full',
                                dot[STATUS_TONE[column.status]],
                            )
                        "
                        aria-hidden="true"
                    />
                    <span class="truncate">{{ column.label }}</span>
                </h2>
                <span
                    class="min-w-6 rounded-full bg-muted px-2 py-0.5 text-center text-xs font-medium text-muted-foreground tabular-nums"
                >
                    {{ column.count }}
                </span>
            </header>

            <div
                class="relative min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 pb-3"
            >
                <VueDraggable
                    v-model="column.cards"
                    group="leads"
                    :animation="150"
                    filter=".no-drag"
                    :prevent-on-filter="false"
                    handle=".card-handle"
                    ghost-class="opacity-40"
                    class="grid min-h-28 grid-cols-[minmax(0,1fr)] content-start gap-2.5"
                    @add="(event) => onAdd(event, column.status)"
                >
                    <article
                        v-for="card in column.cards"
                        :key="card.id"
                        :data-id="card.id"
                        :class="
                            cn(
                                'group relative min-w-0 rounded-lg border bg-card p-3.5 shadow-xs transition-shadow hover:shadow-md',
                                !card.can.update && 'no-drag',
                            )
                        "
                    >
                        <span
                            v-if="card.can.update"
                            class="card-handle absolute top-3.5 left-1 hidden cursor-grab text-muted-foreground/60 hover:text-foreground pointer-fine:block"
                            aria-hidden="true"
                            title="Drag to another column"
                        >
                            <GripVertical class="size-3.5" />
                        </span>

                        <div class="flex min-w-0 items-start gap-2">
                            <button
                                type="button"
                                class="min-w-0 flex-1 truncate rounded-sm text-left text-sm font-semibold outline-offset-2 hover:underline"
                                @click="emit('open', card)"
                            >
                                {{ card.name }}
                            </button>
                            <DropdownMenu v-if="card.can.update">
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="-mt-1.5 -mr-1.5 size-7 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100 data-[state=open]:opacity-100 pointer-coarse:opacity-100"
                                        :aria-label="`Move ${card.name} to another status`"
                                    >
                                        <ArrowRightLeft aria-hidden="true" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-52">
                                    <DropdownMenuLabel
                                        class="text-xs font-normal text-muted-foreground"
                                    >
                                        Move to…
                                    </DropdownMenuLabel>
                                    <DropdownMenuItem
                                        v-for="option in statuses"
                                        :key="option.value"
                                        :disabled="option.value === card.status"
                                        @select="
                                            emit('move', card, option.value)
                                        "
                                    >
                                        {{ option.label }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <div
                            class="mt-1.5 flex min-w-0 items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span
                                class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-foreground/80"
                            >
                                {{ card.type_label }}
                            </span>
                            <span
                                v-if="card.project"
                                class="flex min-w-0 items-center gap-1"
                            >
                                <Building2
                                    class="size-3 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="truncate">{{ card.project }}</span>
                            </span>
                        </div>

                        <p
                            class="mt-2.5 flex items-center gap-1.5 text-xs text-muted-foreground tabular-nums"
                        >
                            <Phone class="size-3 shrink-0" aria-hidden="true" />
                            {{ card.phone }}
                        </p>

                        <footer
                            class="mt-3 flex items-center justify-between gap-2 border-t pt-2.5"
                        >
                            <div
                                class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1"
                            >
                                <span
                                    class="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                >
                                    <Clock class="size-3" aria-hidden="true" />
                                    {{
                                        card.created_at
                                            ? timeAgo(card.created_at)
                                            : ''
                                    }}
                                </span>
                                <span
                                    v-if="card.follow_up_at"
                                    :class="
                                        cn(
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium',
                                            card.follow_up_overdue
                                                ? 'bg-error-50 text-error-700'
                                                : 'bg-info-50 text-info-700',
                                        )
                                    "
                                >
                                    <CalendarClock
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    {{
                                        card.follow_up_overdue
                                            ? 'Overdue'
                                            : 'Due'
                                    }}
                                    · {{ dueDate(card) }}
                                </span>
                            </div>
                            <Avatar
                                v-if="card.assignee"
                                class="size-6 shrink-0"
                            >
                                <AvatarFallback
                                    class="bg-teal-100 text-[10px] font-semibold text-teal-800"
                                    :title="card.assignee.name"
                                >
                                    {{ getInitials(card.assignee.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                v-else
                                class="grid size-6 shrink-0 place-items-center rounded-full border border-dashed text-[10px] text-muted-foreground"
                                title="Unassigned"
                            >
                                ?
                            </span>
                        </footer>
                    </article>
                </VueDraggable>

                <div
                    v-if="column.cards.length === 0"
                    class="pointer-events-none absolute inset-x-3 top-0 grid min-h-28 place-items-center rounded-lg border border-dashed text-center text-xs text-muted-foreground"
                >
                    <span class="grid justify-items-center gap-1.5">
                        <Inbox class="size-5 opacity-60" aria-hidden="true" />
                        No leads here
                    </span>
                </div>
            </div>

            <footer
                v-if="column.count > column.cards.length"
                class="border-t bg-background/60 px-4 py-2.5 text-xs"
            >
                <Link
                    :href="
                        leadsIndex.url({
                            query: { 'filter[status]': column.status },
                        })
                    "
                    class="font-medium text-primary underline-offset-4 hover:underline"
                >
                    Show all {{ column.count }} in the table →
                </Link>
            </footer>
        </section>
    </div>
</template>
