<script setup lang="ts">
import { computed, ref } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import type { TabbedPillarsBlock } from '@/types/blocks';

// CMP-BLK-PILLARS: CMP-TABS-01 underline tabs (arrow keys, Home/End — Reka)
// with one panel per pillar (title H3 under the block's H2, text) beside a
// 5:4 photo that crossfades with the tab (opacity only, MOT-R01). Every panel
// stays in the DOM (`force-mount`) so all copy is crawlable; inactive panels
// are `hidden`. Without a block heading the titles become H2. The photo
// column is left out when no pillar has an image.
const props = defineProps<{
    block: TabbedPillarsBlock;
    first: boolean;
}>();

const active = ref('0');
const hasImages = computed(() =>
    props.block.data.tabs.some((tab) => tab.image !== null),
);
const titleTag = computed(() => (props.block.data.heading ? 'h3' : 'h2'));
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        />
        <Tabs v-model="active" class="gap-0">
            <TabsList
                aria-label="Pillars"
                class="mb-8 overflow-x-auto lg:mb-12"
            >
                <TabsTrigger
                    v-for="(tab, index) in block.data.tabs"
                    :key="index"
                    :value="String(index)"
                    class="font-medium"
                >
                    {{ tab.label }}
                </TabsTrigger>
            </TabsList>

            <div
                class="grid gap-8 lg:grid-cols-12 lg:items-center lg:gap-x-8 xl:gap-x-10"
            >
                <div
                    v-if="hasImages"
                    class="relative aspect-[5/4] overflow-hidden rounded-xl bg-surface-subtle lg:col-span-7 lg:row-start-1"
                >
                    <ResponsiveImage
                        v-for="(tab, index) in block.data.tabs"
                        :key="index"
                        :image="tab.image"
                        preset="pillar-image"
                        :class="
                            cn(
                                'absolute inset-0 transition-opacity duration-(--dur-slow) ease-(--ease-standard)',
                                active === String(index)
                                    ? 'opacity-100'
                                    : 'opacity-0',
                            )
                        "
                    />
                </div>

                <div
                    :class="
                        cn(
                            'lg:row-start-1',
                            hasImages
                                ? 'lg:col-span-4 lg:col-start-9'
                                : 'lg:col-span-8',
                        )
                    "
                >
                    <TabsContent
                        v-for="(tab, index) in block.data.tabs"
                        :key="index"
                        :value="String(index)"
                        force-mount
                        class="flex flex-col gap-4 data-[state=inactive]:hidden"
                    >
                        <component :is="titleTag" class="text-h3 text-balance">
                            {{ tab.title }}
                        </component>
                        <p class="text-pretty text-muted-foreground">
                            {{ tab.text }}
                        </p>
                    </TabsContent>
                </div>
            </div>
        </Tabs>
    </BlockSection>
</template>
