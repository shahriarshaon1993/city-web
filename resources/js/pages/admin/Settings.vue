<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SettingsEditor from '@/components/admin/settings/SettingsEditor.vue';
import type { SettingsAssets, SettingsGroupInfo, SettingsTab } from '@/types';

// ADM-SCR-15: Settings, a tab per group. The editor is keyed so a tab switch or a save re-reads
// the server values into a fresh form.
defineProps<{
    tabs: SettingsTab[];
    group: SettingsGroupInfo;
    values: Record<string, unknown>;
    secrets: Record<string, boolean>;
    assets: SettingsAssets;
    lastSaved: string | null;
}>();
</script>

<template>
    <Head :title="`${group.label} · Settings`" />

    <SettingsEditor
        :key="`${group.key}:${lastSaved}`"
        :tabs="tabs"
        :group="group"
        :values="values"
        :secrets="secrets"
        :assets="assets"
        :last-saved="lastSaved"
    />
</template>
