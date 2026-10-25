<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

// CMP-SKEL-01 shapes. Hidden from AT; the region that loads sets aria-busy.
const props = defineProps<{
    shape:
        | 'project-card'
        | 'news-card'
        | 'table-row'
        | 'kpi-card'
        | 'media-tile';
    class?: string;
}>();
</script>

<template>
    <div
        v-if="props.shape === 'project-card'"
        aria-hidden="true"
        :class="
            cn(
                'flex flex-col rounded-2xl bg-card p-2 ring-1 ring-border',
                props.class,
            )
        "
    >
        <Skeleton class="aspect-4/3 w-full rounded-xl" />
        <div class="flex flex-col gap-4 px-3 pt-4 pb-2">
            <div class="flex flex-col gap-2">
                <Skeleton class="h-3.5 w-36 rounded-xs" />
                <Skeleton class="h-6 w-3/4 rounded-sm" />
            </div>
            <Skeleton class="h-15 w-full rounded-lg" />
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-1.5">
                    <Skeleton class="h-3 w-20 rounded-xs" />
                    <Skeleton class="h-6 w-28 rounded-sm" />
                </div>
                <Skeleton class="size-11 rounded-full" />
            </div>
        </div>
    </div>

    <div
        v-else-if="props.shape === 'news-card'"
        aria-hidden="true"
        :class="cn('flex flex-col gap-4', props.class)"
    >
        <Skeleton class="aspect-video w-full rounded-lg" />
        <Skeleton class="h-4 w-28 rounded-xs" />
        <Skeleton class="h-6 w-full rounded-sm" />
        <Skeleton class="h-6 w-2/3 rounded-sm" />
    </div>

    <div
        v-else-if="props.shape === 'table-row'"
        aria-hidden="true"
        :class="cn('flex items-center gap-4 border-b px-4 py-3', props.class)"
    >
        <Skeleton class="size-5 rounded-xs" />
        <Skeleton class="h-4 w-1/4 rounded-xs" />
        <Skeleton class="h-4 w-1/5 rounded-xs" />
        <Skeleton class="h-5.5 w-20 rounded-full" />
        <Skeleton class="ml-auto h-4 w-16 rounded-xs" />
    </div>

    <div
        v-else-if="props.shape === 'kpi-card'"
        aria-hidden="true"
        :class="
            cn('flex flex-col gap-3 rounded-lg border bg-card p-5', props.class)
        "
    >
        <Skeleton class="h-4 w-24 rounded-xs" />
        <Skeleton class="h-10 w-32 rounded-sm" />
        <Skeleton class="h-4 w-20 rounded-xs" />
    </div>

    <div
        v-else
        aria-hidden="true"
        :class="cn('flex flex-col gap-2', props.class)"
    >
        <Skeleton class="aspect-square w-full rounded-lg" />
        <Skeleton class="h-4 w-3/4 rounded-xs" />
    </div>
</template>
