<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import HighlightText from './data-table/HighlightText.vue';

// Primary cell of people tables (design/admin.md §12.3): initials avatar, name, a muted second line.
defineProps<{
    name: string;
    secondary?: string | null;
    search?: string;
}>();
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <Avatar class="size-8 shrink-0">
            <AvatarFallback
                class="bg-secondary text-xs font-medium text-secondary-foreground"
            >
                {{ getInitials(name) }}
            </AvatarFallback>
        </Avatar>
        <div class="min-w-0 leading-tight">
            <p class="truncate font-medium">
                <HighlightText :text="name" :term="search" />
            </p>
            <p v-if="secondary" class="truncate text-xs text-muted-foreground">
                <HighlightText :text="secondary" :term="search" />
            </p>
        </div>
    </div>
</template>
