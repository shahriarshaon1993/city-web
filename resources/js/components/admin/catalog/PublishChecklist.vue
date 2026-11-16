<script setup lang="ts">
import { Check, X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { ChecklistItem } from '@/types';

// CMP-ADM-CHECKLIST / AC-PROJ-03: every rule that must pass before Publish, ✓ or ✕ with a "Fix"
// link that opens the right tab and focuses the field. It reads the saved project, so the rail
// says so when there are unsaved changes.
const props = defineProps<{
    items: ChecklistItem[];
    dirty?: boolean;
}>();

const emit = defineEmits<{ fix: [item: ChecklistItem] }>();

const open = computed(() => props.items.filter((item) => !item.passed).length);
</script>

<template>
    <section class="grid gap-3" aria-labelledby="publish-checklist-title">
        <div class="flex items-baseline justify-between gap-2">
            <h2 id="publish-checklist-title" class="text-sm font-semibold">
                Publish checklist
            </h2>
            <p
                :class="
                    open === 0
                        ? 'text-xs text-success'
                        : 'text-xs text-muted-foreground'
                "
            >
                {{ open === 0 ? 'Ready to publish' : `${open} open` }}
            </p>
        </div>

        <ul class="grid gap-2">
            <li
                v-for="item in items"
                :key="item.key"
                class="flex items-start gap-2 text-sm"
            >
                <span
                    :class="
                        item.passed
                            ? 'mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-success text-white'
                            : 'mt-0.5 grid size-4 shrink-0 place-items-center rounded-full bg-destructive text-white'
                    "
                    aria-hidden="true"
                >
                    <Check v-if="item.passed" class="size-3" />
                    <X v-else class="size-3" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="sr-only">{{
                        item.passed ? 'Done: ' : 'Open: '
                    }}</span>
                    {{ item.label }}
                    <span
                        v-if="item.detail"
                        class="block text-xs text-muted-foreground"
                        >{{ item.detail }}</span
                    >
                </span>
                <Button
                    v-if="!item.passed"
                    type="button"
                    variant="link"
                    size="sm"
                    class="h-auto shrink-0 px-0 py-0 text-xs"
                    @click="emit('fix', item)"
                >
                    Fix<span class="sr-only"> {{ item.label }}</span>
                </Button>
            </li>
        </ul>

        <p v-if="dirty" class="text-xs text-muted-foreground">
            The checklist reads the saved project. Save your changes to update
            it.
        </p>
    </section>
</template>
