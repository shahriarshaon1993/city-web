<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, provide, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import {
    assetMapKey,
    createAssetMap,
} from '@/components/admin/catalog/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { destroy, index, store, update } from '@/routes/admin/team';
import type { TeamMemberEditProps } from '@/types';

// ADM-SCR-09 team member editor (FR-SOC-004): name, role and group, a portrait (4:5, at least 800 px
// wide), a short bio and contact links. The email shows publicly only when "Show email" is on.
const props = defineProps<TeamMemberEditProps>();

const form = useForm({ ...props.form });
const confirm = useConfirm();
provide(assetMapKey, createAssetMap(props.assets));

watch(
    () => props.lastSaved,
    () => {
        form.defaults({ ...props.form });
        form.reset();
    },
);

const limits = computed(() => props.options.limits);

function save(): void {
    if (props.member) {
        form.patch(update.url(props.member.id), { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}

async function remove(): Promise<void> {
    if (!props.member) {
        return;
    }

    const answer = await confirm({
        title: 'Delete this team member?',
        subject: props.member.name,
        description:
            'They leave the team pages at once. They move to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete team member',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.member.id));
    }
}

const canSave = computed(
    () => form.name.trim() !== '' && form.role_title.trim() !== '',
);
const fieldId = (key: string): string => `member-${key}`;
</script>

<template>
    <Head :title="member?.name ?? 'New team member'" />

    <AdminEditor
        :title="form.name.trim() || member?.name || 'New team member'"
        description="A person on the leadership page and the team grids. Their place in the group is set with Order on the list."
        :form="form"
        :last-saved="lastSaved"
        :save-label="member ? 'Save changes' : 'Add team member'"
        :save-reason="canSave ? null : 'Enter a name and a role.'"
        :field-id="fieldId"
        @save="save"
    >
        <template #actions>
            <Link
                :href="index.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All team members</Link
            >
            <Button
                v-if="member"
                type="button"
                variant="outline"
                @click="remove"
            >
                <Trash2 aria-hidden="true" />
                Delete
            </Button>
        </template>

        <template #rail>
            <SwitchRow
                id="member-is_published"
                v-model="form.is_published"
                label="Published"
                helper="Visible on the website when on."
            />
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="Who they are"
                description="The name, role and group decide where the person appears."
            >
                <div class="grid gap-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <TextField
                            id="member-name"
                            v-model="form.name"
                            label="Name"
                            required
                            :maxlength="limits.name"
                            autocomplete="off"
                            :error="form.errors.name"
                        />
                        <TextField
                            id="member-role_title"
                            v-model="form.role_title"
                            label="Role"
                            required
                            :maxlength="limits.role"
                            placeholder="Managing Director"
                            :error="form.errors.role_title"
                        />
                    </div>
                    <ChoiceField
                        id="member-group"
                        v-model="form.group"
                        label="Group"
                        required
                        :options="options.groups"
                        helper="Leadership has its own larger grid; management and team share a smaller one."
                        :error="form.errors.group"
                    />
                    <FormField
                        id="member-photo_asset_id"
                        label="Portrait"
                        helper="A portrait photo (4:5) at least 800 px wide. Without one the grid shows a placeholder."
                        :error="form.errors.photo_asset_id"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                id="member-photo_asset_id"
                                v-model="form.photo_asset_id"
                                :kinds="['image']"
                                choose-label="Choose portrait"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="member-bio"
                        label="Short bio"
                        helper="Optional. Shown under the name on the leadership page."
                        :counter="{ length: form.bio.length, max: limits.bio }"
                        :error="form.errors.bio"
                    >
                        <template #default="{ id, describedby, invalid }">
                            <Textarea
                                :id="id"
                                v-model="form.bio"
                                :maxlength="limits.bio"
                                rows="4"
                                :aria-describedby="describedby"
                                :aria-invalid="invalid || undefined"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>

            <AdminSection
                title="Contact"
                description="Both are optional. The email is never sent to visitors unless you tick the box."
            >
                <div class="grid gap-5">
                    <TextField
                        id="member-linkedin_url"
                        v-model="form.linkedin_url"
                        label="LinkedIn"
                        type="url"
                        inputmode="url"
                        :maxlength="255"
                        placeholder="https://www.linkedin.com/in/name"
                        :error="form.errors.linkedin_url"
                    />
                    <TextField
                        id="member-email"
                        v-model="form.email"
                        label="Email"
                        type="email"
                        :maxlength="190"
                        autocomplete="off"
                        :error="form.errors.email"
                    />
                    <SwitchRow
                        id="member-show_email"
                        v-model="form.show_email"
                        label="Show email on the website"
                        helper="Needs an email above."
                        :disabled="form.email.trim() === ''"
                        :error="form.errors.show_email"
                    />
                </div>
            </AdminSection>
        </div>
    </AdminEditor>
</template>
