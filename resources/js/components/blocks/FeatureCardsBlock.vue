<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockLinkButton from '@/components/blocks/BlockLinkButton.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { featureIcons } from '@/lib/feature-icons';
import { cn } from '@/lib/utils';
import type { FeatureCardsBlock } from '@/types/blocks';

// CMP-BLK-FEATURES in the hairline language (direction.md §4/§8): no card
// surface, border or shadow and no icons in circles — each item sits under a
// hairline with a thin teal Lucide line icon (or a `rounded-xl` photo), a
// `text-h4` title, muted copy and an optional text link. Columns 2/3/4 are
// 1 / 2 / n per row. `tilt` opts into MOT-TILT (fine pointers; the icon or
// photo lifts, the photo gets the glare). Item titles are H3 under the
// block's H2; without a block heading they become H2 (headings never skip).
const props = defineProps<{
    block: FeatureCardsBlock;
    first: boolean;
}>();

const columns = computed(
    () =>
        ({
            2: 'sm:grid-cols-2',
            3: 'sm:grid-cols-2 lg:grid-cols-3',
            4: 'sm:grid-cols-2 lg:grid-cols-4',
        })[props.block.data.columns],
);

const itemTag = computed(() => (props.block.data.heading ? 'h3' : 'h2'));
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
        <ul
            v-reveal.stagger
            :class="cn('grid gap-x-8 gap-y-12 xl:gap-x-10', columns)"
        >
            <li
                v-for="(item, index) in block.data.items"
                :key="index"
                v-tilt="block.data.tilt ? undefined : 0"
                :class="
                    cn(
                        'flex flex-col gap-4',
                        !item.image && 'border-t border-border pt-6',
                    )
                "
            >
                <div v-if="item.image" data-tilt-depth class="relative">
                    <ResponsiveImage
                        :image="item.image"
                        preset="feature-image"
                        class="rounded-xl"
                    />
                    <span
                        v-if="block.data.tilt"
                        data-tilt-glare
                        class="pointer-events-none absolute inset-0 rounded-xl bg-linear-to-br from-white to-transparent opacity-0"
                        aria-hidden="true"
                    />
                </div>
                <component
                    :is="featureIcons[item.icon]"
                    v-else-if="item.icon && featureIcons[item.icon]"
                    data-tilt-depth
                    class="size-8 text-primary"
                    :stroke-width="1.25"
                    aria-hidden="true"
                />
                <component :is="itemTag" class="text-h4 text-balance">
                    {{ item.title }}
                </component>
                <p v-if="item.text" class="text-pretty text-muted-foreground">
                    {{ item.text }}
                </p>
                <BlockLinkButton
                    v-if="item.link"
                    :link="item.link"
                    variant="link"
                    class="mt-auto self-start px-0"
                />
            </li>
        </ul>
    </BlockSection>
</template>
