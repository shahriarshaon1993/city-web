<script setup lang="ts">
import { computed } from 'vue';
import NewsCard from '@/components/site/NewsCard.vue';
import type { NewsCard as NewsCardData } from '@/types/site';

// PG-12/13 results in three tiers so a page of nine never reads as one
// template grid (direction.md §5): two large stories, three standard cards,
// then four compact rows on hairlines. A short last page fills the tiers in
// order. The first image in view is the LCP image unless the featured post
// above already is.
const props = defineProps<{
    posts: NewsCardData[];
    /** The featured post leads the page, so the first card is not the LCP. */
    hasFeatured: boolean;
}>();

const pair = computed(() => props.posts.slice(0, 2));
const row = computed(() => props.posts.slice(2, 5));
const compact = computed(() => props.posts.slice(5));
</script>

<template>
    <div class="flex flex-col gap-14 lg:gap-16">
        <ul
            v-if="pair.length > 0"
            v-reveal.stagger
            class="grid gap-x-6 gap-y-12 md:grid-cols-2 xl:gap-x-8"
        >
            <li v-for="(post, index) in pair" :key="post.id">
                <NewsCard
                    :post="post"
                    variant="lead"
                    heading-as="h2"
                    :priority="!hasFeatured && index === 0"
                />
            </li>
        </ul>

        <ul
            v-if="row.length > 0"
            v-reveal.stagger
            class="grid gap-x-6 gap-y-12 md:grid-cols-2 lg:grid-cols-3 xl:gap-x-8"
        >
            <li v-for="post in row" :key="post.id">
                <NewsCard :post="post" heading-as="h2" />
            </li>
        </ul>

        <ul
            v-if="compact.length > 0"
            v-reveal.stagger
            class="grid gap-x-12 lg:grid-cols-2"
        >
            <li
                v-for="post in compact"
                :key="post.id"
                class="border-t border-border py-8"
            >
                <NewsCard :post="post" variant="compact" heading-as="h2" />
            </li>
        </ul>
    </div>
</template>
