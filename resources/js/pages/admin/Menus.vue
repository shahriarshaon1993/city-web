<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MenuEditor from '@/components/admin/menus/MenuEditor.vue';
import type { MenuInfo, MenuOptions, MenuTab } from '@/types';

// ADM-SCR-12: Menus, a tab per menu (CMS-NAV-001). The editor is keyed so a tab switch or a save
// starts from the server's tree (saved items get their IDs).
defineProps<{
    tabs: MenuTab[];
    menu: MenuInfo;
    options: MenuOptions;
    canManage: boolean;
    lastSaved: string | null;
}>();
</script>

<template>
    <Head :title="`${menu.name} · Menus`" />

    <MenuEditor
        :key="`${menu.key}:${lastSaved}`"
        :tabs="tabs"
        :menu="menu"
        :options="options"
        :can-manage="canManage"
        :last-saved="lastSaved"
    />
</template>
