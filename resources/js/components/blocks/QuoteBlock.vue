<script setup lang="ts">
import { computed } from 'vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { getInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';
import type { QuoteBlock } from '@/types/blocks';

// CMP-BLK-QUOTE in the quote pattern of direction.md §6: coral quote mark,
// the quote as large light text (text-h2 up to 180 characters, text-h3
// beyond, so 400 characters stay readable), the person behind a hairline with
// a 48 px photo or initials (DEC-47). Left-aligned in 10 columns, on a top
// hairline. The block has no heading of its own, so the section is unnamed.
const props = defineProps<{
    block: QuoteBlock;
    first: boolean;
}>();

const long = computed(() => props.block.data.quote.length > 180);
</script>

<template>
    <BlockSection :block="block" :first="first" :labelled="false">
        <figure
            v-reveal
            class="grid gap-x-8 gap-y-8 border-t border-border pt-10 lg:grid-cols-12 lg:pt-14"
        >
            <svg
                class="size-10 text-brand-accent lg:size-12"
                viewBox="0 0 48 48"
                fill="currentColor"
                aria-hidden="true"
            >
                <path
                    d="M20 12c-6.6 2.2-11 8.1-11 15.4V36h11V25h-6c.3-4.3 2.7-7.6 6.9-9.4zm19 0c-6.6 2.2-11 8.1-11 15.4V36h11V25h-6c.3-4.3 2.7-7.6 6.9-9.4z"
                />
            </svg>
            <div class="flex flex-col gap-10 lg:col-span-10 lg:col-start-2">
                <blockquote>
                    <p
                        :class="
                            cn(
                                'max-w-[36ch] font-display font-light text-balance',
                                long ? 'text-h3' : 'text-h2',
                            )
                        "
                    >
                        {{ block.data.quote }}
                    </p>
                </blockquote>
                <figcaption
                    class="flex items-center gap-4 border-t border-border pt-6 sm:max-w-md"
                >
                    <ResponsiveImage
                        v-if="block.data.photo"
                        :image="block.data.photo"
                        preset="avatar"
                        class="size-12 shrink-0 rounded-full"
                    />
                    <span
                        v-else
                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-medium text-secondary-foreground"
                        aria-hidden="true"
                    >
                        {{ getInitials(block.data.person) }}
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span class="font-medium">{{ block.data.person }}</span>
                        <span
                            v-if="block.data.role"
                            class="text-sm text-muted-foreground"
                        >
                            {{ block.data.role }}
                        </span>
                    </span>
                </figcaption>
            </div>
        </figure>
    </BlockSection>
</template>
