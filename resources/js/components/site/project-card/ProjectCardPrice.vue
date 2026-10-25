<script setup lang="ts">
import { ArrowUpRight } from '@lucide/vue';
import { computed } from 'vue';
import { formatBdtCompact } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01 footer (DEC-46): hairline, then price-from only when the server
// sent one (AC-PROJ-01), else the muted note. The arrow is decoration
// (aria-hidden, not focusable): the card's only link stays the stretched name.
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
    <div
        :class="
            cn(
                'flex items-end justify-between gap-4 border-t',
                compact ? 'pt-3' : 'pt-4',
                media ? 'border-white/20' : 'border-border',
            )
        "
    >
        <!-- min-h-12: a one-line note keeps the hairline level with priced neighbours. -->
        <p
            v-if="project.price_from_bdt !== null"
            class="flex min-h-12 min-w-0 flex-col justify-end"
        >
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
                        'tabular font-display',
                        compact ? 'text-lg font-medium' : 'text-price',
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
                    'flex min-h-12 items-end text-sm',
                    media ? 'text-white/80' : 'text-muted-foreground',
                )
            "
        >
            {{ project.price_note ?? 'Price on request' }}
        </p>

        <ArrowUpRight
            :class="
                cn(
                    'card-arrow-icon mb-1 size-5 shrink-0',
                    media
                        ? 'text-white'
                        : 'text-muted-foreground group-hover/card:text-primary',
                )
            "
            aria-hidden="true"
        />
    </div>
</template>
