<script setup lang="ts">
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

// A11Y-D02: first focusable element; hidden until focused, then a primary
// button top-left that jumps to <main id="content" tabindex="-1">.
</script>

<template>
    <a
        href="#content"
        :class="
            cn(
                buttonVariants({ variant: 'primary' }),
                'fixed top-3 left-3 z-(--z-skip-link) not-focus:sr-only',
            )
        "
    >
        Skip to content
    </a>
</template>
