<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { MediaFolderNode } from '@/types';
import { flattenFolders } from './folder-tree';

// A folder choice as one select: "Unfiled" / "Top level" first, then every folder indented by depth.
const props = withDefaults(
    defineProps<{
        id: string;
        folders: MediaFolderNode[];
        /** Label of the "no folder" choice. */
        noneLabel?: string;
        /** Folders that may not be chosen (a folder's own subtree when moving it). */
        disabledIds?: number[];
        /** Folders deeper than this cannot be chosen (moving a folder that has subfolders). */
        maxDepth?: number;
        invalid?: boolean;
    }>(),
    {
        noneLabel: 'Unfiled',
        disabledIds: () => [],
        maxDepth: 3,
        invalid: false,
    },
);

/** null = the "none" choice. */
const model = defineModel<number | null>({ required: true });

const NONE = 'none';
const options = computed(() => flattenFolders(props.folders));
const value = computed({
    get: () => (model.value === null ? NONE : String(model.value)),
    set: (next: string) => {
        model.value = next === NONE ? null : Number(next);
    },
});
</script>

<template>
    <Select v-model="value">
        <SelectTrigger
            :id="id"
            class="w-full"
            :aria-invalid="invalid || undefined"
        >
            <SelectValue />
        </SelectTrigger>
        <SelectContent>
            <SelectItem :value="NONE">{{ noneLabel }}</SelectItem>
            <SelectItem
                v-for="folder in options"
                :key="folder.id"
                :value="String(folder.id)"
                :disabled="
                    disabledIds.includes(folder.id) || folder.depth > maxDepth
                "
            >
                <span
                    :style="{ paddingLeft: `${(folder.depth - 1) * 12}px` }"
                    >{{ folder.name }}</span
                >
            </SelectItem>
        </SelectContent>
    </Select>
</template>
