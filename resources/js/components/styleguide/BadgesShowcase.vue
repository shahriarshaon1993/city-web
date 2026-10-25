<script setup lang="ts">
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import { Badge } from '@/components/ui/badge';
import type { BadgeValue, BadgeVariant } from '@/components/ui/badge';

// CMP-BADGE-01: every tone, the md + dot size, and on a photo-like surface.
const badges: { variant: BadgeVariant; value?: BadgeValue; label: string }[] = [
    { variant: 'lifecycle', value: 'upcoming', label: 'Upcoming' },
    { variant: 'lifecycle', value: 'ongoing', label: 'Ongoing' },
    { variant: 'lifecycle', value: 'ready', label: 'Ready' },
    { variant: 'lifecycle', value: 'handed_over', label: 'Handed over' },
    { variant: 'booking', value: 'booking_open', label: 'Booking open' },
    { variant: 'booking', value: 'limited_units', label: 'Limited units' },
    { variant: 'booking', value: 'sold_out', label: 'Sold out' },
    { variant: 'featured', label: 'Featured' },
    { variant: 'neutral', label: '3 bed' },
    { variant: 'status', value: 'draft', label: 'Draft' },
    { variant: 'status', value: 'scheduled', label: 'Scheduled' },
    { variant: 'status', value: 'published', label: 'Published' },
    { variant: 'status', value: 'archived', label: 'Archived' },
    { variant: 'status', value: 'pending_revision', label: 'Pending revision' },
];

const publicBadges = badges.slice(0, 8);
</script>

<template>
    <StyleguideSection id="sg-badges" title="Badges">
        <div class="flex flex-wrap gap-2">
            <Badge
                v-for="badge in badges"
                :key="badge.label"
                :variant="badge.variant"
                :value="badge.value"
            >
                {{ badge.label }}
            </Badge>
        </div>
        <div class="flex flex-wrap gap-2">
            <Badge
                v-for="badge in publicBadges"
                :key="badge.label"
                :variant="badge.variant"
                :value="badge.value"
                size="md"
                dot
            >
                {{ badge.label }}
            </Badge>
        </div>
        <div
            class="flex flex-wrap gap-2 rounded-xl bg-(image:--gradient-brand) p-6"
        >
            <Badge
                v-for="badge in publicBadges"
                :key="badge.label"
                :variant="badge.variant"
                :value="badge.value"
                on-media
            >
                {{ badge.label }}
            </Badge>
        </div>
    </StyleguideSection>
</template>
