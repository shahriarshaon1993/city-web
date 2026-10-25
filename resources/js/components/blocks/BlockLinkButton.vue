<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import type { BlockLink } from '@/types/blocks';

// A block CTA (BlockLink): Inertia <Link> for same-site paths, <a> otherwise.
withDefaults(
    defineProps<{
        link: BlockLink;
        variant?: ButtonVariants['variant'];
        size?: ButtonVariants['size'];
    }>(),
    { variant: 'primary', size: 'md' },
);
</script>

<template>
    <Button as-child :variant="variant" :size="size">
        <Link v-if="!link.new_tab && link.url.startsWith('/')" :href="link.url">
            <slot>{{ link.label }}</slot>
        </Link>
        <a
            v-else
            :href="link.url"
            :target="link.new_tab ? '_blank' : undefined"
            :rel="link.new_tab ? 'noopener' : undefined"
        >
            <slot>{{ link.label }}</slot>
        </a>
    </Button>
</template>
