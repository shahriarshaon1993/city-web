<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { SettingsTab } from '@/types';

// The Settings tabs, grouped. From 1280 px a list in the action rail; below it one select above the
// form (a link list of 17 would push the form off the screen). Each tab is its own URL (SEO-style
// real links, deep-linkable); the unsaved-changes guard asks before leaving a dirty tab.
const props = defineProps<{
    tabs: SettingsTab[];
    current: string;
    /** `rail` = the vertical list, `select` = the compact picker. */
    variant: 'rail' | 'select';
}>();

const groups = computed(() => {
    const out: { label: string; tabs: SettingsTab[] }[] = [];

    for (const tab of props.tabs) {
        const group = out.find((item) => item.label === tab.nav);

        if (group) {
            group.tabs.push(tab);
        } else {
            out.push({ label: tab.nav, tabs: [tab] });
        }
    }

    return out;
});

function go(key: string): void {
    const tab = props.tabs.find((item) => item.key === key);

    if (tab && tab.key !== props.current) {
        router.visit(tab.href);
    }
}
</script>

<template>
    <nav
        v-if="variant === 'rail'"
        aria-label="Settings sections"
        class="grid gap-4"
    >
        <div v-for="group in groups" :key="group.label" class="grid gap-1">
            <p
                class="text-overline tracking-[0.16em] text-muted-foreground uppercase"
            >
                {{ group.label }}
            </p>
            <Link
                v-for="tab in group.tabs"
                :key="tab.key"
                :href="tab.href"
                :aria-current="tab.key === current ? 'page' : undefined"
                :class="
                    cn(
                        'rounded-md px-2 py-1.5 text-sm transition-colors',
                        tab.key === current
                            ? 'bg-accent font-medium text-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )
                "
            >
                {{ tab.label }}
            </Link>
        </div>
    </nav>

    <Select
        v-else
        :model-value="current"
        @update:model-value="go(String($event))"
    >
        <SelectTrigger class="w-full" aria-label="Settings section">
            <SelectValue />
        </SelectTrigger>
        <SelectContent>
            <SelectGroup v-for="group in groups" :key="group.label">
                <SelectLabel>{{ group.label }}</SelectLabel>
                <SelectItem
                    v-for="tab in group.tabs"
                    :key="tab.key"
                    :value="tab.key"
                >
                    {{ tab.label }}
                </SelectItem>
            </SelectGroup>
        </SelectContent>
    </Select>
</template>
