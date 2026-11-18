<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { PageBlockType } from '@/types/blocks';
import BlockWire from './BlockWire.vue';
import { BLOCKS, CATEGORY_ORDER } from './blocks';

// CMP-ADM-BUILDER "+ Add block": a categorised gallery of all 28 types with a thumbnail, a name and one
// sentence on what the block is for. A page can have at most 30 blocks (CMS-PB-005).
const open = defineModel<boolean>('open', { required: true });

defineProps<{
    /** The types the page may not use as its first block are fine anywhere; this only marks openers. */
    hasOpener: boolean;
}>();

const emit = defineEmits<{ choose: [type: PageBlockType] }>();

const query = ref('');

watch(open, (value) => {
    if (value) {
        query.value = '';
    }
});

const groups = computed(() => {
    const term = query.value.trim().toLowerCase();

    return CATEGORY_ORDER.map((category) => ({
        category,
        blocks: BLOCKS.filter(
            (block) =>
                block.category === category &&
                (term === '' ||
                    block.label.toLowerCase().includes(term) ||
                    block.description.toLowerCase().includes(term)),
        ),
    })).filter((group) => group.blocks.length > 0);
});

function choose(type: PageBlockType): void {
    emit('choose', type);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            class="flex max-h-[min(90dvh,48rem)] flex-col gap-0 p-0 sm:max-w-4xl"
        >
            <DialogHeader class="gap-3 border-b p-6 pr-14">
                <DialogTitle>Add a block</DialogTitle>
                <DialogDescription>
                    Choose what to add to the page. You can move, hide or delete
                    it afterwards.
                    <template v-if="!hasOpener">
                        A page needs a <strong>Hero</strong> or a
                        <strong>Page header</strong> as its first block: its
                        heading is the title (H1) of the page.
                    </template>
                </DialogDescription>
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="query"
                        type="search"
                        class="pl-9"
                        placeholder="Search blocks"
                        aria-label="Search blocks"
                        autocomplete="off"
                    />
                </div>
            </DialogHeader>

            <div class="grid gap-8 overflow-y-auto p-6">
                <section
                    v-for="group in groups"
                    :key="group.category"
                    :aria-labelledby="`cat-${group.category}`"
                >
                    <h3
                        :id="`cat-${group.category}`"
                        class="mb-3 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        {{ group.category }}
                    </h3>
                    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <li v-for="block in group.blocks" :key="block.type">
                            <button
                                type="button"
                                class="group grid h-full w-full gap-3 rounded-xl border p-3 text-left outline-offset-2 transition-colors hover:border-primary/50 hover:bg-muted/40"
                                @click="choose(block.type)"
                            >
                                <BlockWire :wire="block.wire" />
                                <span class="grid gap-0.5">
                                    <span
                                        class="flex items-center gap-2 text-sm font-medium"
                                    >
                                        <component
                                            :is="block.icon"
                                            class="size-4 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        {{ block.label }}
                                    </span>
                                    <span
                                        class="text-xs text-muted-foreground"
                                        >{{ block.description }}</span
                                    >
                                </span>
                            </button>
                        </li>
                    </ul>
                </section>
                <p
                    v-if="groups.length === 0"
                    class="py-8 text-center text-sm text-muted-foreground"
                    role="status"
                >
                    No block matches “{{ query }}”.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
