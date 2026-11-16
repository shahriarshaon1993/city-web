<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, provide, reactive } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import { update } from '@/routes/admin/settings';
import type { SettingsAssets, SettingsGroupInfo, SettingsTab } from '@/types';
import { settingFieldId, settingsContextKey } from './context';
import SettingField from './SettingField.vue';
import SettingsNav from './SettingsNav.vue';

// CRUD-04: one tab = one editor with its own Save, dirty marker, Ctrl/⌘+S and unsaved-changes guard
// (AdminEditor). The page keys this component by tab and save time, so saving or switching tabs
// starts from the server's values (secrets come back empty).
const props = defineProps<{
    tabs: SettingsTab[];
    group: SettingsGroupInfo;
    values: Record<string, unknown>;
    secrets: Record<string, boolean>;
    assets: SettingsAssets;
    lastSaved: string | null;
}>();

const form = useForm<{
    values: Record<string, any>;
    clear: string[];
}>({
    // Props are reactive proxies, which structuredClone rejects; the values are plain JSON.
    values: JSON.parse(JSON.stringify(props.values)) as Record<string, any>,
    clear: [],
});

provide(
    settingsContextKey,
    reactive({
        get errors() {
            return form.errors as Record<string, string | undefined>;
        },
        assets: props.assets,
        secrets: props.secrets,
        isCleared: (key: string) => form.clear.includes(key),
        toggleClear: (key: string) => {
            form.clear = form.clear.includes(key)
                ? form.clear.filter((item) => item !== key)
                : [...form.clear, key];
        },
    }),
);

const sections = computed(() =>
    props.group.sections.filter((section) => section.fields.length > 0),
);

function save(): void {
    form.patch(update.url({ group: props.group.key }), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AdminEditor
        :title="group.label"
        :description="group.description"
        :form="form"
        :last-saved="lastSaved"
        :field-id="(key) => settingFieldId(key)"
        @save="save"
    >
        <template #rail>
            <div
                class="max-h-[calc(100vh-18rem)] overflow-y-auto border-t pt-4"
            >
                <SettingsNav :tabs="tabs" :current="group.key" variant="rail" />
            </div>
        </template>

        <div class="flex max-w-[1100px] flex-col gap-6">
            <div class="xl:hidden">
                <SettingsNav
                    :tabs="tabs"
                    :current="group.key"
                    variant="select"
                />
            </div>

            <AdminSection
                v-for="section in sections"
                :key="section.title"
                :title="section.title"
                :description="section.description ?? undefined"
            >
                <form class="grid max-w-xl gap-5" @submit.prevent="save">
                    <SettingField
                        v-for="field in section.fields"
                        :key="field.key"
                        v-model="form.values[field.key]"
                        :field="field"
                        :path="`values.${field.key}`"
                    />
                    <button type="submit" class="sr-only" tabindex="-1">
                        Save
                    </button>
                </form>
            </AdminSection>
        </div>
    </AdminEditor>
</template>
