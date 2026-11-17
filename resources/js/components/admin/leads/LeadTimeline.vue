<script setup lang="ts">
import {
    ArrowRightLeft,
    CalendarClock,
    FileText,
    Mail,
    Repeat2,
    StickyNote,
    UserRound,
} from '@lucide/vue';
import type { Component } from 'vue';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import type { LeadTimelineEntry } from '@/types';

// FR-LEAD-009: the lead's history, newest first — submissions, touches, status changes,
// assignments, notes and follow-ups. The title always says what happened; the icon only supports it.
defineProps<{ entries: LeadTimelineEntry[] }>();

const ICONS: Record<LeadTimelineEntry['kind'], Component> = {
    submission: FileText,
    touch: Repeat2,
    status: ArrowRightLeft,
    assignment: UserRound,
    note: StickyNote,
    follow_up: CalendarClock,
    email: Mail,
};
</script>

<template>
    <p v-if="entries.length === 0" class="text-sm text-muted-foreground">
        Nothing has happened on this lead yet.
    </p>
    <ol v-else class="grid gap-0">
        <li
            v-for="entry in entries"
            :key="entry.id"
            class="group relative flex gap-3 pb-5 last:pb-0"
        >
            <span
                class="absolute top-8 bottom-0 left-4 w-px bg-border group-last:hidden"
                aria-hidden="true"
            />
            <span
                class="relative z-[1] grid size-8 shrink-0 place-items-center rounded-full border bg-background text-muted-foreground"
                aria-hidden="true"
            >
                <component :is="ICONS[entry.kind]" class="size-4" />
            </span>
            <div class="min-w-0 flex-1 pt-1">
                <p class="text-sm font-medium">{{ entry.title }}</p>
                <p
                    v-if="entry.detail"
                    class="mt-0.5 text-sm break-words whitespace-pre-line text-muted-foreground"
                >
                    {{ entry.detail }}
                </p>
                <div
                    v-if="entry.html"
                    class="rte-content mt-1.5 rounded-md bg-muted/50 px-3 py-2 text-sm"
                    v-html="entry.html"
                />
                <p class="mt-1 text-xs text-muted-foreground">
                    <template v-if="entry.by">{{ entry.by }} · </template>
                    <time v-if="entry.at" :datetime="entry.at">
                        {{ formatDateTime(entry.at) }} ({{ timeAgo(entry.at) }})
                    </time>
                </p>
            </div>
        </li>
    </ol>
</template>
