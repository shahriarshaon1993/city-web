<script setup lang="ts">
import { Heart } from '@lucide/vue';
import { computed } from 'vue';
import { useShortlist } from '@/composables/useShortlist';
import { cn } from '@/lib/utils';

// FR-SHORT-001 heart, a toggle button beside (never inside) the card link.
// Rendered only once storage proved usable (FR-SHORT-005), so it is absent
// from the server HTML and without JavaScript.
const props = withDefaults(
    defineProps<{
        projectId: number;
        projectName: string;
        /** For the `shortlist_add` event (SEO-ANA-004). */
        projectSlug?: string | null;
        /** `plain`: a hairline circle on white (PG-04 title block). */
        tone?: 'media' | 'plain';
        class?: string;
    }>(),
    { projectSlug: null, tone: 'media', class: undefined },
);

const shortlist = useShortlist();
const saved = computed(() => shortlist.has(props.projectId));
</script>

<template>
    <button
        v-if="shortlist.available.value"
        type="button"
        :aria-label="`Add ${projectName} to shortlist`"
        :aria-pressed="saved"
        :class="
            cn(
                'flex size-11 shrink-0 items-center justify-center rounded-full transition-colors duration-(--dur-fast)',
                tone === 'media'
                    ? 'bg-white/90 text-stone-900 shadow-xs hover:bg-white'
                    : 'border border-foreground/30 text-foreground hover:border-foreground',
                props.class,
            )
        "
        @click="shortlist.toggle(projectId, projectSlug)"
    >
        <Heart
            :class="cn('size-5', saved && 'fill-coral-500 text-coral-500')"
            :stroke-width="saved ? 1.6 : 2"
            aria-hidden="true"
        />
    </button>
</template>
