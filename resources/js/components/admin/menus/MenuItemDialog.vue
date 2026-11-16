<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { blankColumn, cloneNode, linkErrors } from '@/lib/menu-tree';
import { menuIcons } from '@/lib/icons';
import type { MenuNode, MenuOptions } from '@/types';
import { MENU_LIMITS } from '@/types';
import LinkFields from './LinkFields.vue';
import MegaColumnsEditor from './MegaColumnsEditor.vue';

// CMP-ADM-TREE: one dialog adds or edits an item. It works on a copy and writes it back on
// Apply, so Cancel changes nothing; the tree itself is saved with the page's Save button.
const props = defineProps<{
    /** The item to edit, or null to add. */
    node: MenuNode | null;
    /** The new item to fill in (add mode). */
    draft: MenuNode | null;
    options: MenuOptions;
    /** Header items at the top level may open a mega menu. */
    canMega: boolean;
    /** Why the mega switch is off, when it is. */
    megaReason: string | null;
    showDetails: boolean;
}>();

const emit = defineEmits<{ apply: [node: MenuNode] }>();
const open = defineModel<boolean>('open', { default: false });

const copy = ref<MenuNode | null>(null);
const attempted = ref(false);

const adding = computed(() => props.node === null);

watch(open, (isOpen) => {
    if (isOpen) {
        const source = props.node ?? props.draft;

        copy.value = source ? cloneNode(source) : null;
        attempted.value = false;
    }
});

const errors = computed(() =>
    copy.value && attempted.value ? linkErrors(copy.value) : {},
);

const megaProblem = computed(() => {
    if (!copy.value?.is_mega || !attempted.value) {
        return false;
    }

    return (
        copy.value.mega_columns.length === 0 ||
        copy.value.mega_columns.some(
            (column) =>
                column.kind === 'links' &&
                (column.items.length === 0 ||
                    column.items.some(
                        (link) => Object.keys(linkErrors(link)).length > 0,
                    )),
        )
    );
});

function setMega(on: boolean): void {
    if (!copy.value) {
        return;
    }

    copy.value.is_mega = on;

    if (on && copy.value.mega_columns.length === 0) {
        copy.value.mega_columns.push(blankColumn());
    }
}

function apply(): void {
    if (!copy.value) {
        return;
    }

    attempted.value = true;

    if (Object.keys(linkErrors(copy.value)).length > 0 || megaProblem.value) {
        return;
    }

    emit('apply', copy.value);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <form
                v-if="copy"
                class="grid gap-5"
                novalidate
                @submit.prevent="apply"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        adding ? 'Add a menu item' : 'Edit menu item'
                    }}</DialogTitle>
                    <DialogDescription>
                        Changes apply to the menu when you press Save on the
                        page.
                    </DialogDescription>
                </DialogHeader>

                <LinkFields
                    v-model="copy"
                    id-prefix="menu-item"
                    :options="options"
                    :errors="errors"
                />

                <template v-if="showDetails">
                    <div class="grid gap-2">
                        <Label for="menu-item-description">Description</Label>
                        <Input
                            id="menu-item-description"
                            :model-value="copy.description ?? ''"
                            :maxlength="MENU_LIMITS.description"
                            autocomplete="off"
                            aria-describedby="menu-item-description-help"
                            @update:model-value="
                                copy.description = String($event)
                            "
                        />
                        <div class="flex items-start justify-between gap-3">
                            <FieldHelp id="menu-item-description">
                                One line under the label in a mega menu. Up to
                                {{ MENU_LIMITS.description }} characters.
                            </FieldHelp>
                            <CharCounter
                                :value="copy.description ?? ''"
                                :max="MENU_LIMITS.description"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="menu-item-icon">Icon</Label>
                        <Select
                            :model-value="copy.icon ?? 'none'"
                            @update:model-value="
                                copy.icon =
                                    $event === 'none' ? null : String($event)
                            "
                        >
                            <SelectTrigger id="menu-item-icon" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No icon</SelectItem>
                                <SelectItem
                                    v-for="name in options.icons"
                                    :key="name"
                                    :value="name"
                                >
                                    <span class="flex items-center gap-2">
                                        <component
                                            :is="menuIcons[name]"
                                            v-if="menuIcons[name]"
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                        {{ name }}
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-muted-foreground">
                            Shown beside the label in a mega menu.
                        </p>
                    </div>
                </template>

                <div v-if="canMega" class="grid gap-4 border-t pt-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="grid gap-1">
                            <Label for="menu-item-mega">Open a mega menu</Label>
                            <FieldHelp id="menu-item-mega">
                                <template v-if="megaReason">{{
                                    megaReason
                                }}</template>
                                <template v-else
                                    >A wide panel of up to
                                    {{ MENU_LIMITS.megaColumns }} columns under
                                    this item.</template
                                >
                            </FieldHelp>
                        </div>
                        <Switch
                            id="menu-item-mega"
                            :model-value="copy.is_mega"
                            :disabled="!!megaReason && !copy.is_mega"
                            aria-describedby="menu-item-mega-help"
                            @update:model-value="setMega(!!$event)"
                        />
                    </div>

                    <MegaColumnsEditor
                        v-if="copy.is_mega"
                        v-model="copy.mega_columns"
                        id-prefix="menu-item-mega-col"
                        :options="options"
                        :show-errors="attempted"
                    />
                </div>

                <p
                    v-if="
                        attempted && (Object.keys(errors).length || megaProblem)
                    "
                    class="text-sm text-destructive"
                    role="alert"
                >
                    Fix the marked fields to continue.
                </p>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit">
                        {{ adding ? 'Add item' : 'Apply changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
