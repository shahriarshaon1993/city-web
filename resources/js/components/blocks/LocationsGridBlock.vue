<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import AreaTile from '@/components/site/location/AreaTile.vue';
import type { LocationsGridBlock } from '@/types/blocks';

// CMP-BLK-LOCATIONS: the PG-05 area tiles as an editorial block — the first
// area is a large lead tile (5 / 12 of the width from 1024 px) beside the
// others three across, so it never reads as the directory's plain grid.
// Below 1024 px the lead is a full-width 16:9 tile above a 2 / 3 column grid.
// One grid, each area rendered once. Area names are H3 under the block's H2
// (H2 without a block heading).
const props = defineProps<{
    block: LocationsGridBlock;
    first: boolean;
}>();

const rows = computed(() =>
    Math.max(1, Math.ceil((props.block.data.areas.length - 1) / 3)),
);
const nameTag = computed(() => (props.block.data.heading ? 'h3' : 'h2'));
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
            :view-all="block.data.view_all"
        />
        <ul
            v-reveal.stagger
            :style="{ '--rows': rows }"
            class="grid grid-cols-2 gap-x-4 gap-y-10 sm:grid-cols-3 sm:gap-x-6 lg:grid-cols-[minmax(0,5fr)_repeat(3,minmax(0,2.3333fr))] lg:items-start xl:gap-x-8"
        >
            <li
                v-for="(area, index) in block.data.areas"
                :key="area.slug"
                :class="
                    index === 0
                        ? 'col-span-2 sm:col-span-3 lg:col-span-1 lg:row-span-(--rows)'
                        : undefined
                "
            >
                <AreaTile
                    :area="area"
                    :no-projects="block.data.no_projects"
                    :heading-tag="nameTag"
                    :feature="index === 0"
                />
            </li>
        </ul>
    </BlockSection>
</template>
