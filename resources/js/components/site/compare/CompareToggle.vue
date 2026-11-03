<script setup lang="ts">
import { computed, useId } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { useCompare } from '@/composables/useCompare';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// FR-SHORT-003 "Compare" checkbox on a card (CMP-CARD-01: listing, area
// pages, shortlist). A separate control above the card's stretched link;
// a 4th tick is refused with a toast and stays unticked (AC-SHORT-01).
// Rendered after mount only, so it is absent without JavaScript.
const props = defineProps<{
    project: ProjectCard;
    tone?: 'surface' | 'media';
    class?: string;
}>();

const compare = useCompare();
const id = useId();
const checked = computed(() => compare.has(props.project.id));
</script>

<template>
    <div
        v-if="compare.ready.value"
        :class="
            cn(
                'relative z-(--z-raised) -my-2 flex min-h-11 shrink-0 items-center gap-2.5',
                props.class,
            )
        "
    >
        <Checkbox
            :id="id"
            :model-value="checked"
            :class="
                tone === 'media' &&
                'border-white/70 bg-transparent data-[state=checked]:border-white data-[state=checked]:bg-white data-[state=checked]:text-stone-900'
            "
            @update:model-value="compare.toggle(project)"
        />
        <label
            :for="id"
            :class="
                cn(
                    'cursor-pointer text-sm select-none',
                    tone === 'media'
                        ? 'text-white/90'
                        : 'text-muted-foreground',
                    checked && tone !== 'media' && 'text-foreground',
                )
            "
        >
            Compare<span class="sr-only"> {{ project.name }}</span>
        </label>
    </div>
</template>
