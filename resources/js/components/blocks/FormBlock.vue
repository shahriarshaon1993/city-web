<script setup lang="ts">
import BlockLinkButton from '@/components/blocks/BlockLinkButton.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import type { FormBlock } from '@/types/blocks';

// CMP-BLK-FORM shell (6.2): heading, intro and side content in 5 columns, the
// form on a greige action panel in 7 (the enquiry-panel surface, direction.md
// §6; the form submit is teal, coral stays with the header). The form itself
// (`data-form`, CMP-FORM-01) is mounted into the panel by 7.1; until then the
// panel links to the contact page, which works without JS.
defineProps<{
    block: FormBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
    >
        <div
            class="grid gap-10 lg:grid-cols-12 lg:items-start lg:gap-x-8 xl:gap-x-10"
        >
            <div v-reveal class="flex flex-col gap-6 lg:col-span-5">
                <component
                    :is="headingTag"
                    :id="headingId"
                    class="font-display text-h2 text-balance"
                >
                    {{ block.data.heading }}
                </component>
                <p
                    v-if="block.data.intro"
                    class="max-w-prose text-lead text-pretty text-muted-foreground"
                >
                    {{ block.data.intro }}
                </p>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div
                    v-if="block.data.side"
                    class="rich-text max-w-prose"
                    v-html="block.data.side"
                />
            </div>
            <div
                :data-form="block.data.form_type"
                class="flex flex-col items-start gap-4 rounded-xl bg-surface-subtle p-6 sm:p-10 lg:col-span-7"
            >
                <BlockLinkButton
                    :link="block.data.fallback"
                    variant="primary"
                />
            </div>
        </div>
    </BlockSection>
</template>
