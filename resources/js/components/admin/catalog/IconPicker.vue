<script setup lang="ts">
import { computed } from 'vue';
import { amenityIcons } from '@/lib/amenity-icons';
import { cn } from '@/lib/utils';

// The amenity icon (DM-AMEN allowlist, the Lucide names the public site can draw): one radio
// group of icon tiles, arrow keys and the group label come from the browser.
const props = defineProps<{
    id: string;
    icons: string[];
    invalid?: boolean;
    describedby?: string;
}>();

const model = defineModel<string>({ default: '' });

const available = computed(() =>
    props.icons.filter((name) => name in amenityIcons),
);
</script>

<template>
    <div
        :id="id"
        role="radiogroup"
        aria-label="Icon"
        :aria-describedby="describedby"
        :class="
            cn(
                'grid grid-cols-6 gap-1.5 rounded-lg sm:grid-cols-7',
                invalid && 'ring-1 ring-destructive',
            )
        "
    >
        <label
            v-for="name in available"
            :key="name"
            :title="name.replace(/-/g, ' ')"
            class="relative grid aspect-square cursor-pointer place-items-center rounded-md border text-muted-foreground transition-colors hover:border-foreground/40 hover:text-foreground has-checked:border-primary has-checked:bg-surface-brand has-checked:text-primary has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ring"
        >
            <input
                v-model="model"
                type="radio"
                class="sr-only"
                :name="`${id}-icon`"
                :value="name"
            />
            <component
                :is="amenityIcons[name]"
                class="size-5"
                aria-hidden="true"
            />
            <span class="sr-only">{{ name.replace(/-/g, ' ') }}</span>
        </label>
    </div>
</template>
