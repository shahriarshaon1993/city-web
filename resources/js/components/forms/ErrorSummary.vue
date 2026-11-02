<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import { computed, useTemplateRef } from 'vue';
import type { SummaryItem } from '@/composables/useLeadForm';

// CMP-FORM-01 / SEO-A11Y-006: a `role="alert"` summary above the form that
// takes focus when a submit fails; each message links to its control.
const props = defineProps<{
    items: SummaryItem[];
}>();

const root = useTemplateRef<HTMLElement>('root');

const heading = computed(() =>
    props.items.length === 1
        ? 'There is 1 thing to fix'
        : `There are ${props.items.length} things to fix`,
);

function goTo(id: string): void {
    const element = document.getElementById(id);
    const control = element?.matches('input, select, textarea, button')
        ? element
        : element?.querySelector<HTMLElement>(
              'input:not([type="hidden"]), select, textarea, button, [tabindex="0"]',
          );

    (control ?? element)?.focus();
}

defineExpose({
    focus: () => root.value?.focus(),
});
</script>

<template>
    <div
        ref="root"
        role="alert"
        tabindex="-1"
        class="rounded-md border border-destructive/40 bg-destructive/5 px-4 py-3.5 outline-none"
    >
        <p class="flex items-center gap-2 text-sm font-medium text-destructive">
            <TriangleAlert class="size-4 shrink-0" aria-hidden="true" />
            {{ heading }}
        </p>
        <ul class="mt-2 flex flex-col gap-1 text-sm">
            <li v-for="item in items" :key="`${item.id}-${item.message}`">
                <a
                    :href="`#${item.id}`"
                    class="inline-flex min-h-8 items-center text-foreground underline underline-offset-4 hover:text-primary"
                    @click.prevent="goTo(item.id)"
                >
                    {{ item.message }}
                </a>
            </li>
        </ul>
    </div>
</template>
