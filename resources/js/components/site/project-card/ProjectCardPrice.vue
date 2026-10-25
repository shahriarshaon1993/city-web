<script setup lang="ts">
import { ArrowUpRight } from '@lucide/vue';
import { computed } from 'vue';
import { formatBdtCompact } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01 footer: price-from only when the server sent one (AC-PROJ-01),
// else the muted note. The arrow is decoration (aria-hidden, not focusable):
// the card's only link stays the stretched name; it fills on card hover.
const props = withDefaults(
    defineProps<{
        project: ProjectCard;
        tone?: 'surface' | 'media';
        compact?: boolean;
    }>(),
    { tone: 'surface', compact: false },
);

const media = computed(() => props.tone === 'media');
</script>

<template>
    <div class="flex items-center justify-between gap-4">
        <p v-if="project.price_from_bdt !== null" class="flex min-w-0 flex-col">
            <span
                :class="
                    cn(
                        'text-xs',
                        media ? 'text-white/70' : 'text-muted-foreground',
                    )
                "
            >
                Starting from
            </span>
            <span
                :class="
                    cn(
                        'tabular font-display tracking-tight',
                        compact ? 'text-lg font-bold' : 'text-price',
                    )
                "
            >
                {{ formatBdtCompact(project.price_from_bdt) }}
            </span>
        </p>
        <p
            v-else
            :class="
                cn(
                    'text-sm font-medium',
                    media ? 'text-white/80' : 'text-muted-foreground',
                )
            "
        >
            {{ project.price_note ?? 'Price on request' }}
        </p>

        <span
            :class="
                cn(
                    'relative grid shrink-0 place-items-center overflow-hidden rounded-full ring-1 ring-inset',
                    compact ? 'size-9' : 'size-11',
                    media
                        ? 'text-white ring-white/40'
                        : 'text-foreground ring-border group-hover/card:text-primary-foreground',
                )
            "
            aria-hidden="true"
        >
            <span
                :class="
                    cn(
                        'card-arrow-fill absolute inset-0 rounded-full',
                        media ? 'bg-white/20' : 'bg-primary',
                    )
                "
            />
            <ArrowUpRight class="card-arrow-icon relative size-4.5" />
        </span>
    </div>
</template>
