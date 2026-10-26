<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import NewsCard from '@/components/site/NewsCard.vue';
import type { LatestNewsBlock } from '@/types/blocks';

// CMP-BLK-NEWS, editorial layout (DEC-48): the newest post as the lead story
// (7 columns) with the next two as compact rows beside it (5 columns,
// stacked below < 1024); with 6 posts the other three follow as a row of
// default cards. Fewer than 3 posts fall back to the plain grid. No read time
// on home (FR-NEWS-004).
const props = defineProps<{
    block: LatestNewsBlock;
    first: boolean;
}>();

const editorial = computed(() => props.block.data.posts.length >= 3);
const lead = computed(() => props.block.data.posts[0]);
const side = computed(() => props.block.data.posts.slice(1, 3));
const more = computed(() =>
    editorial.value ? props.block.data.posts.slice(3) : props.block.data.posts,
);
const headingAs = computed(() => (props.first ? 'h2' : 'h3'));
</script>

<template>
    <BlockSection
        v-if="block.data.posts.length > 0"
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
            :view-all="block.data.view_all"
        />
        <div
            v-if="editorial && lead"
            class="grid gap-12 lg:grid-cols-12 lg:gap-8 xl:gap-12"
        >
            <NewsCard
                v-reveal
                :post="lead"
                variant="lead"
                :heading-as="headingAs"
                class="lg:col-span-7"
            />
            <ul
                v-reveal.stagger
                class="flex flex-col divide-y divide-border lg:col-span-5"
            >
                <li
                    v-for="post in side"
                    :key="post.id"
                    class="py-8 first:pt-0 last:pb-0"
                >
                    <NewsCard
                        :post="post"
                        variant="compact"
                        :heading-as="headingAs"
                    />
                </li>
            </ul>
        </div>
        <ul
            v-if="more.length > 0"
            v-reveal.stagger
            :class="[
                'grid gap-x-6 gap-y-12 md:grid-cols-2 lg:grid-cols-3 xl:gap-x-8',
                editorial && 'mt-16 border-t border-border pt-12',
            ]"
        >
            <li v-for="post in more" :key="post.id">
                <NewsCard :post="post" :heading-as="headingAs" />
            </li>
        </ul>
    </BlockSection>
</template>
