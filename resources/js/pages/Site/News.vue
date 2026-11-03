<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Inbox } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import NewsCard from '@/components/site/NewsCard.vue';
import NewsCategoryNav from '@/components/site/news/NewsCategoryNav.vue';
import NewsGrid from '@/components/site/news/NewsGrid.vue';
import PaginationNav from '@/components/site/PaginationNav.vue';
import { Button } from '@/components/ui/button';
import type { NewsPageProps } from '@/types/news';

// PG-12 `/news`, PG-13 `/news/category/{slug}` and `/news/tag/{slug}`
// (LAY-NEWS-LIST): light H1 + intro on a hairline, the category row, the
// featured story (PG-12 page 1: horizontal, 12 columns), the results in three
// tiers and pagination as real links. Every post title is an H2 under the one
// H1. No read time on the cards (FR-NEWS-004).
const props = defineProps<NewsPageProps>();

const archive = computed(() => props.news);
const allActive = computed(() => archive.value.kind === 'index');

function visit(url: string): void {
    router.visit(url);
}

const isEmpty = computed(
    () => archive.value.posts.length === 0 && archive.value.featured === null,
);
</script>

<template>
    <div
        class="mx-auto w-full max-w-content px-4 pt-8 pb-(--section-md) sm:px-6 lg:px-8 lg:pt-12 xl:px-10"
    >
        <Breadcrumbs :items="archive.breadcrumbs" />

        <header
            v-reveal.hero
            class="mt-8 grid gap-x-8 gap-y-4 border-b border-border pb-10 lg:mt-12 lg:grid-cols-12 lg:items-end lg:pb-14"
        >
            <h1 data-hero-line class="text-h1 text-balance lg:col-span-7">
                {{ archive.title }}
            </h1>
            <p
                data-hero-line
                class="max-w-prose text-body-lg text-pretty text-muted-foreground lg:col-span-5 lg:col-start-8"
            >
                {{ archive.intro }}
            </p>
        </header>

        <div class="border-b border-border py-1">
            <NewsCategoryNav
                :label="copy.categories_label"
                :categories="archive.categories"
                :active="archive.active_category"
                :all-active="allActive"
            />
        </div>

        <div class="mt-10 lg:mt-14">
            <NewsCard
                v-if="archive.featured"
                :post="archive.featured"
                variant="featured"
                heading-as="h2"
                priority
                class="mb-14 lg:mb-16"
            />

            <NewsGrid
                v-if="archive.posts.length > 0"
                :posts="archive.posts"
                :has-featured="archive.featured !== null"
            />

            <EmptyState
                v-if="isEmpty"
                variant="no-content"
                :icon="Inbox"
                :title="copy.empty_title"
                :body="copy.empty_body"
            >
                <Button as-child variant="outline">
                    <Link href="/news">{{ copy.empty_action }}</Link>
                </Button>
            </EmptyState>

            <PaginationNav
                :page="archive.meta.page"
                :last-page="archive.meta.last_page"
                :pagination="archive.meta.pagination"
                class="mt-16"
                @navigate="visit"
            />
        </div>
    </div>
</template>
