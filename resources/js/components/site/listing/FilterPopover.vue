<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { ref } from 'vue';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { usePauseSmoothScroll } from '@/motion/bridge';
import { cn } from '@/lib/utils';

// CMP-FILT-01 desktop filter: a quiet trigger (label, or the chosen value in
// the foreground) opening a panel. Changes inside apply at once, so the
// panel stays open while the results reload behind it.
const props = defineProps<{
    label: string;
    /** The current choice; null = not filtered. */
    value: string | null;
    contentClass?: string;
}>();

const open = ref(false);

usePauseSmoothScroll(() => open.value);
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger
            :class="
                cn(
                    'inline-flex h-11 max-w-44 min-w-0 shrink items-center gap-2 rounded-md border px-3.5 text-sm transition-colors duration-(--dur-fast) data-[state=open]:border-foreground',
                    props.value
                        ? 'border-foreground/60 text-foreground'
                        : 'border-border text-muted-foreground hover:border-foreground/40 hover:text-foreground',
                )
            "
        >
            <span class="truncate">
                {{ props.value ? `${label}: ${props.value}` : label }}
            </span>
            <ChevronDown
                class="size-4 shrink-0 transition-transform duration-(--dur-fast) [[data-state=open]>&]:rotate-180"
                aria-hidden="true"
            />
        </PopoverTrigger>
        <PopoverContent
            align="start"
            :side-offset="8"
            :class="cn('z-(--z-dropdown) w-80 rounded-xl p-5', contentClass)"
        >
            <slot />
        </PopoverContent>
    </Popover>
</template>
