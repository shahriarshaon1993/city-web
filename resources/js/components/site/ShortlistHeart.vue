<script setup lang="ts">
import { Heart } from '@lucide/vue';
import { computed } from 'vue';
import { useShortlist } from '@/composables/useShortlist';
import { cn } from '@/lib/utils';

// FR-SHORT-001 heart, a toggle button beside (never inside) the card link.
// Rendered only once storage proved usable (FR-SHORT-005), so it is absent
// from the server HTML and without JavaScript.
const props = defineProps<{
    projectId: number;
    projectName: string;
    class?: string;
}>();

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
                'flex size-11 items-center justify-center rounded-full bg-white/90 text-stone-900 shadow-xs transition-colors duration-(--dur-fast) hover:bg-white',
                props.class,
            )
        "
        @click="shortlist.toggle(projectId)"
    >
        <Heart
            :class="cn('size-5', saved && 'fill-coral-500 text-coral-500')"
            :stroke-width="saved ? 1.6 : 2"
            aria-hidden="true"
        />
    </button>
</template>
