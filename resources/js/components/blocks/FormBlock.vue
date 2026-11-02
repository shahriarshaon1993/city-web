<script setup lang="ts">
import BlockSection from '@/components/blocks/BlockSection.vue';
import CallbackForm from '@/components/site/forms/CallbackForm.vue';
import ContactForm from '@/components/site/forms/ContactForm.vue';
import LandownerForm from '@/components/site/forms/LandownerForm.vue';
import NrbForm from '@/components/site/forms/NrbForm.vue';
import type { FormBlock } from '@/types/blocks';

// CMP-BLK-FORM (6.2, 7.1): heading, intro and side content in 5 columns, the
// lead form of `form_type` on a greige action panel in 7 (the enquiry-panel
// surface, direction.md §6; the submit is teal, coral stays with the header).
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
                class="rounded-xl bg-surface-subtle p-6 sm:p-10 lg:col-span-7"
            >
                <ContactForm
                    v-if="block.data.form_type === 'contact'"
                    class="w-full"
                    location="page_block"
                />
                <CallbackForm
                    v-else-if="block.data.form_type === 'callback'"
                    class="w-full"
                    location="page_block"
                />
                <LandownerForm
                    v-else-if="block.data.form_type === 'landowner'"
                    class="w-full"
                    location="page_block"
                />
                <NrbForm
                    v-else-if="block.data.form_type === 'nrb'"
                    class="w-full"
                    location="page_block"
                />
            </div>
        </div>
    </BlockSection>
</template>
