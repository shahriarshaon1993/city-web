<script setup lang="ts">
import EmptyState from '@/components/site/EmptyState.vue';
import SkeletonShape from '@/components/site/SkeletonShape.vue';
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import { Button } from '@/components/ui/button';
</script>

<template>
    <StyleguideSection id="sg-empty" title="Empty states & skeletons">
        <div class="grid gap-6 md:grid-cols-3">
            <EmptyState
                variant="no-results"
                class="rounded-xl border"
                title="No projects match"
                body="Try removing a filter or widening the price range."
            >
                <Button variant="outline">Clear filters</Button>
            </EmptyState>
            <EmptyState
                class="rounded-xl border"
                title="No enquiries yet"
                body="New enquiries from the website appear here."
            >
                <Button>Add a lead</Button>
            </EmptyState>
            <EmptyState
                variant="error"
                class="rounded-xl border"
                title="Could not load"
                body="Something went wrong on our side."
            >
                <Button variant="outline">Retry</Button>
            </EmptyState>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4" aria-busy="true">
            <SkeletonShape shape="project-card" />
            <SkeletonShape shape="news-card" />
            <div class="flex flex-col gap-4">
                <SkeletonShape shape="kpi-card" />
                <SkeletonShape shape="media-tile" class="w-32" />
            </div>
            <div class="rounded-lg border">
                <SkeletonShape v-for="n in 4" :key="n" shape="table-row" />
            </div>
        </div>
    </StyleguideSection>
</template>
