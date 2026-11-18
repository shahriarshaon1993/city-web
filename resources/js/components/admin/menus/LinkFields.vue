<script setup lang="ts">
import { computed } from 'vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { isEntityType } from '@/lib/menu-tree';
import type {
    LinkTargetInfo,
    MenuLink,
    MenuLinkType,
    MenuOptions,
} from '@/types';
import { MENU_LIMITS } from '@/types';
import LinkTargetSelect from './LinkTargetSelect.vue';

// CMS-NAV-002: the fields every link has — label, what it points at, new tab. Used for a menu
// item and for each link of a mega-menu column. `errors` is the dialog's own client-side check
// (VAL-03); the server answers with the same wording on Save.
const props = withDefaults(
    defineProps<{
        idPrefix: string;
        options: MenuOptions;
        errors: Partial<Record<'label' | 'target' | 'url' | 'anchor', string>>;
        /** A block link allows fewer characters than a menu label (CMS-PB). */
        labelMax?: number;
    }>(),
    { labelMax: MENU_LIMITS.label },
);

const link = defineModel<MenuLink>({ required: true });

const isEntity = computed(() => isEntityType(link.value.link_type));
const noun = computed(
    () =>
        props.options.link_types
            .find((type) => type.value === link.value.link_type)
            ?.label.toLowerCase() ?? 'item',
);

function changeType(value: string): void {
    const type = value as MenuLinkType;

    if (type === link.value.link_type) {
        return;
    }

    link.value.link_type = type;
    link.value.linkable_id = null;
    link.value.link = null;
    link.value.anchor = type === 'anchor' ? '' : null;
    link.value.url = isEntityType(type) ? null : (link.value.url ?? '');
}

function choose(target: LinkTargetInfo): void {
    link.value.linkable_id = target.id;
    link.value.link = {
        status: target.published ? 'ok' : 'unpublished',
        url: target.url,
        target,
    };

    // A new link takes the name of what it points at, to be shortened if it is too long.
    if (link.value.label.trim() === '') {
        link.value.label = target.label.slice(0, MENU_LIMITS.label);
    }
}

const selected = computed(() => link.value.link?.target ?? null);
const unpublished = computed(
    () => isEntity.value && link.value.link?.status === 'unpublished',
);
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-2">
            <Label :for="`${idPrefix}-label`">
                Label
                <span class="text-destructive" aria-hidden="true">*</span>
            </Label>
            <Input
                :id="`${idPrefix}-label`"
                v-model="link.label"
                :maxlength="labelMax"
                autocomplete="off"
                :aria-invalid="!!errors.label || undefined"
                :aria-describedby="`${idPrefix}-label-help`"
            />
            <div class="flex items-start justify-between gap-3">
                <FieldHelp :id="`${idPrefix}-label`">
                    The words visitors see. Up to {{ labelMax }}
                    characters.
                </FieldHelp>
                <CharCounter :value="link.label" :max="labelMax" />
            </div>
            <InputError :message="errors.label" />
        </div>

        <div class="grid gap-2">
            <Label :for="`${idPrefix}-type`">Links to</Label>
            <Select
                :model-value="link.link_type"
                @update:model-value="changeType(String($event))"
            >
                <SelectTrigger :id="`${idPrefix}-type`" class="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="type in options.link_types"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ type.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="isEntity" class="grid gap-2">
            <Label :for="`${idPrefix}-target`">
                Which {{ noun }}
                <span class="text-destructive" aria-hidden="true">*</span>
            </Label>
            <LinkTargetSelect
                :id="`${idPrefix}-target`"
                :type="link.link_type"
                :selected="selected"
                :invalid="!!errors.target"
                :describedby="`${idPrefix}-target-help`"
                @select="choose"
            />
            <FieldHelp :id="`${idPrefix}-target`">
                The address is looked up when the page loads, so it stays right
                if the {{ noun }}’s URL changes.
            </FieldHelp>
            <p v-if="unpublished" class="text-xs text-warning" role="status">
                This {{ noun }} is not public yet. The link stays hidden on the
                website until it is published.
            </p>
            <InputError :message="errors.target" />
        </div>

        <template v-else>
            <div class="grid gap-2">
                <Label :for="`${idPrefix}-url`">
                    <template v-if="link.link_type === 'anchor'"
                        >Page address
                    </template>
                    <template v-else>Address</template>
                    <span
                        v-if="link.link_type === 'url'"
                        class="text-destructive"
                        aria-hidden="true"
                        >*</span
                    >
                </Label>
                <Input
                    :id="`${idPrefix}-url`"
                    :model-value="link.url ?? ''"
                    :maxlength="MENU_LIMITS.url"
                    autocomplete="off"
                    :placeholder="
                        link.link_type === 'anchor'
                            ? '/about (blank = home page)'
                            : '/projects or https://example.com'
                    "
                    :aria-invalid="!!errors.url || undefined"
                    :aria-describedby="`${idPrefix}-url-help`"
                    @update:model-value="link.url = String($event)"
                />
                <FieldHelp :id="`${idPrefix}-url`">
                    Start with / for a page on this site, or https:// for
                    another site. mailto: and tel: also work.
                </FieldHelp>
                <InputError :message="errors.url" />
            </div>

            <div v-if="link.link_type === 'anchor'" class="grid gap-2">
                <Label :for="`${idPrefix}-anchor`">
                    Section id
                    <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <Input
                    :id="`${idPrefix}-anchor`"
                    :model-value="link.anchor ?? ''"
                    :maxlength="MENU_LIMITS.anchor"
                    autocomplete="off"
                    placeholder="enquire"
                    :aria-invalid="!!errors.anchor || undefined"
                    :aria-describedby="`${idPrefix}-anchor-help`"
                    @update:model-value="link.anchor = String($event)"
                />
                <FieldHelp :id="`${idPrefix}-anchor`">
                    The anchor id of a block on that page, without the #.
                </FieldHelp>
                <InputError :message="errors.anchor" />
            </div>
        </template>

        <div class="flex items-start justify-between gap-4">
            <div class="grid gap-1">
                <Label :for="`${idPrefix}-tab`">Open in a new tab</Label>
                <FieldHelp :id="`${idPrefix}-tab`">
                    Use it for other websites. The link gets rel="noopener".
                </FieldHelp>
            </div>
            <Switch
                :id="`${idPrefix}-tab`"
                :model-value="link.new_tab"
                :aria-describedby="`${idPrefix}-tab-help`"
                @update:model-value="link.new_tab = !!$event"
            />
        </div>
    </div>
</template>
