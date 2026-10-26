<script setup lang="ts">
import type { DesignTeamMember } from '@/types/project';

// PG-04 design team (LAY-PROJ-DETAIL "architect/engineer/contractor"): a
// typographic credits list — role overline left, firm in light display
// type right, hairline rows — instead of cards (direction.md §4).
defineProps<{
    members: DesignTeamMember[];
}>();
</script>

<template>
    <dl class="divide-y divide-border border-y border-border">
        <div
            v-for="member in members"
            :key="member.role"
            class="grid gap-1 py-5 sm:grid-cols-12 sm:items-baseline sm:gap-6"
        >
            <dt
                class="text-overline text-muted-foreground uppercase sm:col-span-4"
            >
                {{ member.role_label }}
            </dt>
            <dd class="font-display text-h4 font-light sm:col-span-8">
                {{ member.name }}
            </dd>
        </div>
    </dl>
</template>
