<script setup lang="ts">
import { Link2, Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import LinkFields from '@/components/admin/menus/LinkFields.vue';
import { Button } from '@/components/ui/button';
import type { BlockErrorMap, MenuLink, PageOptions } from '@/types';
import { blankLink } from './blocks';
import { blockFieldId } from './context';

// A button or link of a block (CMS-NAV-002 shape, CMS-PB-001 CTA): the same picker as a menu item —
// label, what it points at, new tab — with a "Remove" for the optional ones. The label allows 24
// characters here (the CTA rule).
const props = defineProps<{
    blockId: number;
    /** Dotted path of the link inside the block data (`primary_cta`, `items.1.link`). */
    path: string;
    label: string;
    required?: boolean;
    options: PageOptions;
    errors: BlockErrorMap;
    disabled?: boolean;
}>();

const link = defineModel<MenuLink | null>({ required: true });

const id = computed(() => blockFieldId(props.blockId, props.path));

const sub = (name: string): string | undefined =>
    props.errors[`${props.path}.${name}`]?.[0];

const fieldErrors = computed(() => ({
    label: sub('label'),
    target: sub('linkable_id') ?? sub('link_type'),
    url: sub('url'),
    anchor: sub('anchor'),
}));

function add(): void {
    link.value = blankLink();
}
</script>

<template>
    <div :id="id" class="grid gap-2">
        <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-medium">
                {{ label }}
                <template v-if="required">
                    <span class="text-destructive" aria-hidden="true">*</span>
                    <span class="sr-only">(required)</span>
                </template>
            </span>
            <Button
                v-if="link && !required && !disabled"
                type="button"
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
                @click="link = null"
            >
                <Trash2 aria-hidden="true" />
                Remove
            </Button>
        </div>

        <div v-if="link" class="rounded-lg border p-4">
            <LinkFields
                v-model="link"
                :id-prefix="id"
                :options="options"
                :errors="fieldErrors"
                :label-max="24"
            />
        </div>
        <Button
            v-else
            type="button"
            variant="outline"
            size="sm"
            class="justify-self-start"
            :disabled="disabled"
            @click="add"
        >
            <Plus aria-hidden="true" />
            Add {{ label.toLowerCase() }}
        </Button>

        <InputError :message="errors[path]?.[0]" />
        <p
            v-if="!link && !required"
            class="flex items-center gap-1.5 text-xs text-muted-foreground"
        >
            <Link2 class="size-3.5" aria-hidden="true" />
            Optional. Without it the block shows no {{ label.toLowerCase() }}.
        </p>
    </div>
</template>
