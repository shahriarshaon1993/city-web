<script setup lang="ts">
import { X } from '@lucide/vue';
import { ref } from 'vue';
import { Input } from '@/components/ui/input';

// CMS-MED-004 tags: type and press Enter or comma; Backspace in an empty input removes the last.
// Limits mirror UpdateMediaAssetRequest (20 tags, 40 characters each, VAL-03).
const props = withDefaults(
    defineProps<{
        id: string;
        max?: number;
        maxLength?: number;
        describedby?: string;
    }>(),
    { max: 20, maxLength: 40, describedby: undefined },
);

const model = defineModel<string[]>({ required: true });
const draft = ref('');

function commit(): void {
    const tags = draft.value
        .split(',')
        .map((tag) => tag.trim().replace(/\s+/g, ' '))
        .filter(Boolean);
    const next = [...model.value];

    for (const tag of tags) {
        if (
            next.length < props.max &&
            !next.some((item) => item.toLowerCase() === tag.toLowerCase())
        ) {
            next.push(tag.slice(0, props.maxLength));
        }
    }

    model.value = next;
    draft.value = '';
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        commit();
    } else if (
        event.key === 'Backspace' &&
        draft.value === '' &&
        model.value.length
    ) {
        model.value = model.value.slice(0, -1);
    }
}

function remove(tag: string): void {
    model.value = model.value.filter((item) => item !== tag);
}
</script>

<template>
    <div class="grid gap-2">
        <ul
            v-if="model.length"
            class="flex flex-wrap gap-1.5"
            aria-label="Tags"
        >
            <li
                v-for="tag in model"
                :key="tag"
                class="inline-flex h-7 items-center gap-1 rounded-full border bg-background pr-1 pl-3 text-xs"
            >
                {{ tag }}
                <button
                    type="button"
                    class="grid size-5 place-items-center rounded-full hover:bg-accent"
                    :aria-label="`Remove tag ${tag}`"
                    @click="remove(tag)"
                >
                    <X class="size-3" aria-hidden="true" />
                </button>
            </li>
        </ul>
        <Input
            :id="id"
            v-model="draft"
            :maxlength="maxLength"
            autocomplete="off"
            :disabled="model.length >= max"
            :placeholder="
                model.length >= max
                    ? `${max} tags is the limit`
                    : 'Add a tag and press Enter'
            "
            :aria-describedby="describedby"
            @keydown="onKeydown"
            @blur="draft.trim() && commit()"
        />
    </div>
</template>
