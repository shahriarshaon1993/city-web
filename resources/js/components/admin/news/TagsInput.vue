<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import { Input } from '@/components/ui/input';

// FR-NEWS-003: free entry with autocomplete. Enter or a comma adds the tag; a name that matches one
// already on the post (in any letter case) is not added twice, and one that matches an existing tag
// uses that tag (the server resolves the name). Up to `max` tags (FR-NEWS-001).
const props = defineProps<{
    id: string;
    suggestions: string[];
    max: number;
    describedby?: string;
    invalid?: boolean;
}>();

const model = defineModel<string[]>({ required: true });
const text = ref('');
const listId = useId();

const full = computed(() => model.value.length >= props.max);
const free = computed(() =>
    props.suggestions.filter(
        (name) =>
            !model.value.some(
                (tag) => tag.toLowerCase() === name.toLowerCase(),
            ),
    ),
);

function add(raw: string): void {
    const name = raw.replace(/\s+/g, ' ').trim().slice(0, 50);

    if (
        name === '' ||
        full.value ||
        model.value.some((tag) => tag.toLowerCase() === name.toLowerCase())
    ) {
        text.value = '';

        return;
    }

    // Use the spelling of an existing tag.
    const known = props.suggestions.find(
        (tag) => tag.toLowerCase() === name.toLowerCase(),
    );

    model.value = [...model.value, known ?? name];
    text.value = '';
}

function remove(index: number): void {
    model.value = model.value.filter((_, position) => position !== index);
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        add(text.value);
    } else if (
        event.key === 'Backspace' &&
        text.value === '' &&
        model.value.length > 0
    ) {
        remove(model.value.length - 1);
    }
}
</script>

<template>
    <div class="grid gap-2">
        <ul
            v-if="model.length"
            class="flex flex-wrap gap-2"
            aria-label="Tags on this post"
        >
            <li
                v-for="(tag, index) in model"
                :key="tag"
                class="inline-flex min-h-8 items-center gap-1 rounded-md border bg-muted/40 pr-1 pl-2.5 text-sm"
            >
                {{ tag }}
                <button
                    type="button"
                    class="grid size-6 place-items-center rounded text-muted-foreground hover:bg-muted hover:text-foreground"
                    :aria-label="`Remove tag ${tag}`"
                    @click="remove(index)"
                >
                    <X class="size-3.5" aria-hidden="true" />
                </button>
            </li>
        </ul>
        <Input
            :id="id"
            v-model="text"
            :list="listId"
            :disabled="full"
            :placeholder="
                full ? `Up to ${max} tags` : 'Type a tag and press Enter'
            "
            autocomplete="off"
            maxlength="50"
            :aria-invalid="invalid || undefined"
            :aria-describedby="describedby"
            @keydown="onKeydown"
            @blur="add(text)"
        />
        <datalist :id="listId">
            <option v-for="name in free" :key="name" :value="name" />
        </datalist>
        <p class="text-xs text-muted-foreground tabular-nums">
            {{ model.length }} of {{ max }} tags
        </p>
    </div>
</template>
