<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import { computed, provide, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import SeoPanel from '@/components/admin/catalog/SeoPanel.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import {
    assetMapKey,
    createAssetMap,
} from '@/components/admin/catalog/context';
import { errorView } from '@/components/admin/projects/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { index as facetsIndex, update } from '@/routes/admin/facets';
import type { AssetMap, FacetFormData, FacetRow } from '@/types';

// ADM-SCR-05 facet page editor: the H1, the intro and the SEO of one landing page. The facet FAQ
// stays demo content until the FAQ module (16.4).
const props = defineProps<{
    facet: FacetRow;
    form: FacetFormData;
    assets: AssetMap;
    canManage: boolean;
    lastSaved: string | null;
}>();

const form = useForm<FacetFormData>(
    JSON.parse(JSON.stringify(props.form)) as FacetFormData,
);
const errors = errorView(form);

provide(assetMapKey, createAssetMap(props.assets));

watch(
    () => props.lastSaved,
    () => {
        form.defaults(JSON.parse(JSON.stringify(props.form)) as FacetFormData);
        form.reset();
    },
);

function fieldId(key: string): string {
    return key.startsWith('seo.')
        ? `seo-${key.slice(4).replace('og_image_asset_id', 'og-image').replace(/_/g, '-')}`
        : `field-${key}`;
}

const heading = computed(() => form.title.trim() || props.facet.title);
</script>

<template>
    <Head :title="heading" />

    <AdminEditor
        :title="heading"
        :description="`The landing page ${facet.path}. Its heading, intro and search settings.`"
        :form="form"
        :last-saved="lastSaved"
        save-label="Save facet page"
        :save-reason="
            canManage ? null : 'You can read this page but not change it.'
        "
        :field-id="fieldId"
        @save="form.patch(update.url(facet.id), { preserveScroll: true })"
    >
        <template #actions>
            <Link
                :href="facetsIndex.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All facet pages</Link
            >
        </template>

        <template #rail>
            <div class="grid gap-2 border-t pt-4">
                <Button as-child variant="ghost" class="w-full">
                    <a :href="facet.public_url" target="_blank" rel="noopener">
                        <ExternalLink aria-hidden="true" />
                        View on site
                    </a>
                </Button>
            </div>
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="Heading and intro"
                description="The H1 and the text above the results."
            >
                <div class="grid gap-5">
                    <TextField
                        id="field-title"
                        v-model="form.title"
                        label="Heading"
                        required
                        :maxlength="80"
                        helper="The page's one H1. Up to 80 characters."
                        :error="errors.title"
                    />
                    <FormField
                        id="field-intro"
                        label="Intro"
                        helper="Optional. A short text under the heading."
                        :error="errors.intro"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="field-intro"
                                v-model="form.intro"
                                :rows="7"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>

            <AdminSection
                title="Search and sharing"
                description="How the page looks in Google and when shared."
            >
                <SeoPanel
                    v-model="form.seo"
                    :path="facet.path"
                    :fallback-title="`${form.title || 'Projects'} in Bangladesh | Gemcon City`"
                    :fallback-description="`Browse ${form.title.toLowerCase() || 'projects'} by Gemcon City.`"
                    :errors="errors"
                />
            </AdminSection>
        </div>
    </AdminEditor>
</template>
