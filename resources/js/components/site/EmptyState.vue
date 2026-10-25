<script setup lang="ts">
import { CircleAlert, Inbox, SearchX } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

// CMP-EMPTY-01: 72 px muted circle with a 40 px icon, title, one sentence,
// one action (default slot). Copy always comes from props.
const props = withDefaults(
    defineProps<{
        variant?: 'no-results' | 'no-content' | 'error';
        title: string;
        body?: string;
        icon?: Component;
        /** Heading level depends on where it sits; `p` keeps the outline clean. */
        titleAs?: 'p' | 'h2' | 'h3' | 'h4';
        class?: string;
    }>(),
    {
        variant: 'no-content',
        titleAs: 'p',
    },
);

const defaultIcons: Record<NonNullable<typeof props.variant>, Component> = {
    'no-results': SearchX,
    'no-content': Inbox,
    error: CircleAlert,
};

const iconComponent = computed(() => props.icon ?? defaultIcons[props.variant]);
</script>

<template>
    <div
        :class="
            cn(
                'mx-auto flex max-w-md flex-col items-center gap-4 px-4 py-12 text-center',
                props.class,
            )
        "
        :role="variant === 'error' ? 'alert' : undefined"
    >
        <span
            :class="
                cn(
                    'flex size-18 items-center justify-center rounded-full bg-muted text-muted-foreground',
                    variant === 'error' && 'text-destructive',
                )
            "
            aria-hidden="true"
        >
            <component :is="iconComponent" class="size-10" stroke-width="1.5" />
        </span>
        <div class="flex flex-col gap-1.5">
            <component :is="titleAs" class="font-display text-h4">
                {{ title }}
            </component>
            <p v-if="body" class="text-body text-muted-foreground">
                {{ body }}
            </p>
        </div>
        <div v-if="$slots.default" class="mt-2">
            <slot />
        </div>
    </div>
</template>
