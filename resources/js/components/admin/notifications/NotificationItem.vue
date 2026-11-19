<script setup lang="ts">
import {
    Bell,
    CalendarClock,
    FileUser,
    PhoneCall,
    UserCheck,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { cn } from '@/lib/utils';
import type { AdminNotification } from '@/types';

// CMP-ADM-NOTIF row: a tinted icon tile per type, title, one line of detail and the time. The bell
// popover uses the compact form; the inbox uses `detailed` (overline type label, full time on hover).
const props = defineProps<{
    notification: AdminNotification;
    detailed?: boolean;
}>();

const KINDS: Record<string, { icon: Component; tile: string }> = {
    new_lead: { icon: PhoneCall, tile: 'bg-primary/10 text-primary' },
    lead_assigned: { icon: UserCheck, tile: 'bg-info-50 text-info-700' },
    follow_up_due: {
        icon: CalendarClock,
        tile: 'bg-warning-50 text-warning-700',
    },
    new_application: { icon: FileUser, tile: 'bg-primary/10 text-primary' },
};

const kind = computed(
    () =>
        KINDS[props.notification.kind] ?? {
            icon: Bell,
            tile: 'bg-muted text-muted-foreground',
        },
);
</script>

<template>
    <span class="flex w-full items-start gap-4 text-left">
        <span
            :class="
                cn(
                    'flex shrink-0 items-center justify-center rounded-xl',
                    detailed ? 'size-11' : 'size-9',
                    kind.tile,
                )
            "
            aria-hidden="true"
        >
            <component
                :is="kind.icon"
                :class="detailed ? 'size-5' : 'size-4'"
            />
        </span>
        <span class="min-w-0 flex-1">
            <span
                v-if="detailed"
                class="flex items-center gap-2 text-xs font-medium tracking-wider text-muted-foreground uppercase"
            >
                {{ notification.kind_label }}
                <template v-if="notification.created_at">
                    <span aria-hidden="true">·</span>
                    <time
                        :datetime="notification.created_at"
                        :title="formatDateTime(notification.created_at)"
                        class="tracking-normal normal-case"
                        >{{ timeAgo(notification.created_at) }}</time
                    >
                </template>
            </span>
            <span
                class="block leading-snug"
                :class="[
                    detailed ? 'mt-1 text-base' : 'text-sm',
                    notification.read
                        ? 'text-foreground/80'
                        : 'font-medium text-foreground',
                ]"
            >
                {{ notification.title }}
            </span>
            <span
                v-if="notification.body"
                class="mt-0.5 block text-sm text-muted-foreground"
            >
                {{ notification.body }}
            </span>
            <span
                v-if="!detailed && notification.created_at"
                class="mt-1 block text-xs text-muted-foreground"
            >
                {{ timeAgo(notification.created_at) }}
            </span>
        </span>
        <span
            v-if="!notification.read"
            class="mt-2 size-2 shrink-0 rounded-full bg-sidebar-primary"
            aria-hidden="true"
        />
        <span v-if="!notification.read" class="sr-only">(unread)</span>
    </span>
</template>
