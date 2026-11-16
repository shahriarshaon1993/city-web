<script setup lang="ts">
import { computed } from 'vue';
import MediaField from '@/components/admin/media/MediaField.vue';
import type { MediaAsset, MediaKind } from '@/types';
import { useAssetMap } from './context';

// One library item in a catalogue editor: the MediaField (CMS-MED-010) whose preview comes from
// the editor's asset map, so the page never fetches an asset it already holds.
defineProps<{
    id: string;
    kinds?: MediaKind[];
    chooseLabel?: string;
    pickerTitle?: string;
    invalid?: boolean;
    describedby?: string;
}>();

const model = defineModel<number | null>({ required: true });
const { assets, remember } = useAssetMap();

const asset = computed<MediaAsset | null>({
    get: () => (model.value === null ? null : (assets[model.value] ?? null)),
    set: (next) => {
        if (next) {
            remember(next);
        }
    },
});
</script>

<template>
    <MediaField
        :id="id"
        v-model="model"
        v-model:asset="asset"
        :kinds="kinds"
        :choose-label="chooseLabel"
        :picker-title="pickerTitle"
        :invalid="invalid"
        :describedby="describedby"
    />
</template>
