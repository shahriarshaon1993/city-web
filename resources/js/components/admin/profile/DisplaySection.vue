<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Monitor, Moon, Rows2, Rows3, Sun } from '@lucide/vue';
import { computed } from 'vue';
import AdminRadioCards from '@/components/admin/AdminRadioCards.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import { useAppearance } from '@/composables/useAppearance';
import { notify } from '@/lib/toast';
import { preferences } from '@/routes/admin/profile';
import type { Appearance, TableDensity } from '@/types';

const user = computed(() => usePage().props.auth.user);
const { appearance, updateAppearance } = useAppearance();

const themes = [
    {
        value: 'light',
        label: 'Light',
        description: 'Bright surfaces.',
        icon: Sun,
    },
    {
        value: 'dark',
        label: 'Dark',
        description: 'Easier on the eyes at night.',
        icon: Moon,
    },
    {
        value: 'system',
        label: 'System',
        description: 'Follow this device.',
        icon: Monitor,
    },
];

const densities = [
    {
        value: 'comfortable',
        label: 'Comfortable',
        description: 'Rows 56 px tall.',
        icon: Rows2,
    },
    {
        value: 'compact',
        label: 'Compact',
        description: 'Rows 40 px tall, more on screen.',
        icon: Rows3,
    },
];

function chooseTheme(value: string): void {
    updateAppearance(value as Appearance);
    notify.success('Theme saved.');
}

function chooseDensity(value: string): void {
    router.patch(
        preferences.url(),
        { table_density: value as TableDensity },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['auth'],
            onSuccess: () => notify.success('Table density saved.'),
        },
    );
}
</script>

<template>
    <AdminSection
        title="Display"
        description="Saved to your account, so it follows you to other computers. Nobody else sees it."
    >
        <div class="grid gap-6">
            <AdminRadioCards
                legend="Colour theme"
                name="theme"
                :options="themes"
                :model-value="appearance"
                @update:model-value="chooseTheme"
            />
            <AdminRadioCards
                legend="Table density"
                name="table_density"
                :options="densities"
                :model-value="user.table_density"
                @update:model-value="chooseDensity"
            />
        </div>
    </AdminSection>
</template>
