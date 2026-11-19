<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { useAssetMap } from '@/components/admin/catalog/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/admin/partners';
import type { MediaAsset, PartnerRow, SocialChoice } from '@/types';

// ADM-SCR-09 / CRUD-01: a partner is a dialog entity (name, logo, website, category + the switch).
// FR-SOC-002: the logo is an SVG or an image at least 300 px wide, ideally transparent.
const props = defineProps<{
    partner: PartnerRow | null;
    categories: SocialChoice[];
}>();

const open = defineModel<boolean>('open', { default: false });
const { remember } = useAssetMap();

const form = useForm({
    name: '',
    logo_asset_id: null as number | null,
    url: '',
    category: '' as string,
    is_published: true,
});
const editing = computed(() => props.partner !== null);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    if (props.partner?.logo) {
        remember(props.partner.logo as MediaAsset);
    }

    form.clearErrors();
    form.defaults({
        name: props.partner?.name ?? '',
        logo_asset_id: props.partner?.logo_asset_id ?? null,
        url: props.partner?.url ?? '',
        category: props.partner?.category.value ?? '',
        is_published: props.partner?.is_published ?? true,
    });
    form.reset();
});

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };

    if (props.partner) {
        form.patch(update.url(props.partner.id), options);
    } else {
        form.post(store.url(), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit partner' : 'New partner'
                    }}</DialogTitle>
                    <DialogDescription>
                        Partners appear as logos in the marquee block. Switch
                        one off to hide it without losing it.
                    </DialogDescription>
                </DialogHeader>

                <TextField
                    id="partner-name"
                    v-model="form.name"
                    label="Name"
                    required
                    :maxlength="100"
                    helper="Used as the logo's alt text."
                    :error="form.errors.name"
                />
                <FormField
                    id="partner-logo_asset_id"
                    label="Logo"
                    required
                    helper="An SVG, or a transparent PNG at least 300 px wide."
                    :error="form.errors.logo_asset_id"
                >
                    <template #default="{ describedby, invalid }">
                        <AssetField
                            id="partner-logo_asset_id"
                            v-model="form.logo_asset_id"
                            :kinds="['image', 'svg']"
                            choose-label="Choose logo"
                            :invalid="invalid"
                            :describedby="describedby"
                        />
                    </template>
                </FormField>
                <div class="grid gap-5 sm:grid-cols-2">
                    <ChoiceField
                        id="partner-category"
                        v-model="form.category"
                        label="Category"
                        required
                        :options="categories"
                        placeholder="Choose a category"
                        helper="A marquee block can show one category."
                        :error="form.errors.category"
                    />
                    <TextField
                        id="partner-url"
                        v-model="form.url"
                        label="Website"
                        type="url"
                        inputmode="url"
                        :maxlength="255"
                        placeholder="https://"
                        helper="Optional. The link opens with rel=nofollow."
                        :error="form.errors.url"
                    />
                </div>
                <SwitchRow
                    id="partner-is_published"
                    v-model="form.is_published"
                    label="Published"
                    helper="Shown in the marquee when on."
                />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="
                            form.processing ||
                            form.name.trim() === '' ||
                            form.logo_asset_id === null ||
                            form.category === ''
                        "
                    >
                        <Spinner v-if="form.processing" />
                        {{ editing ? 'Save changes' : 'Add partner' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
