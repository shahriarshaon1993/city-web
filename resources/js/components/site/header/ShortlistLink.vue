<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import { computed } from 'vue';
import { buttonVariants } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

// CMP-HEAD-01 shortlist heart. `count` comes from the shortlist store
// (FR-SHORT, 2.2); the URL becomes Wayfinder's `shortlist()` in 8.3.
const props = defineProps<{
    count: number;
    onMedia: boolean;
}>();

const label = computed(() =>
    props.count > 0 ? `Shortlist, ${props.count} saved` : 'Shortlist',
);
</script>

<template>
    <Tooltip>
        <TooltipTrigger as-child>
            <Link
                href="/shortlist"
                :aria-label="label"
                :class="
                    cn(
                        buttonVariants({ variant: 'ghost', size: 'icon' }),
                        onMedia &&
                            'text-white hover:not-disabled:bg-white/15 hover:not-disabled:text-white',
                    )
                "
            >
                <Heart class="size-6" aria-hidden="true" />
                <span
                    v-if="count > 0"
                    class="tabular absolute top-0.5 right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-xs font-semibold text-primary-foreground"
                    aria-hidden="true"
                >
                    {{ count }}
                </span>
            </Link>
        </TooltipTrigger>
        <TooltipContent class="z-(--z-dropdown)">{{ label }}</TooltipContent>
    </Tooltip>
</template>
