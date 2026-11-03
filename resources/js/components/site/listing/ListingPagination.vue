<script setup lang="ts">
import PaginationNav from '@/components/site/PaginationNav.vue';
import { useListing } from '@/composables/useProjectListing';

// CMP-PAGI-01 / FR-SRCH-007: the shared pagination links; a plain click
// loads the same URL as a partial reload of the listing.
const listing = useListing();
</script>

<template>
    <PaginationNav
        :page="listing.meta.value.page"
        :last-page="listing.meta.value.last_page"
        :pagination="listing.meta.value.pagination"
        @navigate="listing.goToPage"
    />
</template>
