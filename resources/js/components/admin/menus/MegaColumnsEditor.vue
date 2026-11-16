<script setup lang="ts">
import { ArrowDown, ArrowUp, Plus, Trash2 } from '@lucide/vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { blankColumn, blankLink, linkErrors } from '@/lib/menu-tree';
import type { MegaKind, MenuColumn, MenuOptions } from '@/types';
import { MENU_LIMITS } from '@/types';
import LinkFields from './LinkFields.vue';

// CMS-NAV-003: up to 4 columns. A column is a hand-made link list or one the site fills itself
// (project types, top areas by project count, featured projects).
defineProps<{
    idPrefix: string;
    options: MenuOptions;
    /** Show what is wrong with the columns (after the first Apply). */
    showErrors: boolean;
}>();

const columns = defineModel<MenuColumn[]>({ required: true });

const KINDS: { value: MegaKind; label: string; help: string }[] = [
    {
        value: 'links',
        label: 'Link list',
        help: 'Links you choose, up to 8.',
    },
    {
        value: 'segments',
        label: 'Project types (automatic)',
        help: 'Residential, Commercial, Mixed-use and Land, each with its icon and one-line description.',
    },
    {
        value: 'locations',
        label: 'Top areas (automatic)',
        help: 'The 8 areas with the most projects, with their project counts.',
    },
    {
        value: 'featured_projects',
        label: 'Featured projects (automatic)',
        help: 'Three projects marked as featured, shown as small cards.',
    },
];

function help(kind: MegaKind): string {
    return KINDS.find((item) => item.value === kind)?.help ?? '';
}

function addColumn(): void {
    columns.value.push(blankColumn());
}

function removeColumn(index: number): void {
    columns.value.splice(index, 1);
}

function moveColumn(index: number, by: number): void {
    const [column] = columns.value.splice(index, 1);

    columns.value.splice(index + by, 0, column);
}

function setKind(column: MenuColumn, kind: MegaKind): void {
    column.kind = kind;

    if (kind !== 'links') {
        column.items = [];
    } else if (column.items.length === 0) {
        column.items.push(blankLink());
    }
}

function addLink(column: MenuColumn): void {
    column.items.push(blankLink());
}
</script>

<template>
    <div class="grid gap-4">
        <p
            v-if="columns.length === 0 && showErrors"
            class="text-sm text-destructive"
            role="alert"
        >
            Add at least one column, or switch the mega menu off.
        </p>

        <fieldset
            v-for="(column, index) in columns"
            :key="column.key"
            class="grid gap-4 rounded-lg border p-4"
        >
            <legend class="px-1 text-sm font-medium">
                Column {{ index + 1 }}
            </legend>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label :for="`${idPrefix}-${column.key}-kind`">Shows</Label>
                    <Select
                        :model-value="column.kind"
                        @update:model-value="
                            setKind(column, String($event) as MegaKind)
                        "
                    >
                        <SelectTrigger
                            :id="`${idPrefix}-${column.key}-kind`"
                            class="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="kind in KINDS"
                                :key="kind.value"
                                :value="kind.value"
                            >
                                {{ kind.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-2">
                    <Label :for="`${idPrefix}-${column.key}-heading`"
                        >Heading</Label
                    >
                    <Input
                        :id="`${idPrefix}-${column.key}-heading`"
                        :model-value="column.heading ?? ''"
                        :maxlength="MENU_LIMITS.heading"
                        autocomplete="off"
                        @update:model-value="column.heading = String($event)"
                    />
                    <CharCounter
                        :value="column.heading ?? ''"
                        :max="MENU_LIMITS.heading"
                    />
                </div>
            </div>

            <p class="text-xs text-muted-foreground">{{ help(column.kind) }}</p>

            <template v-if="column.kind === 'links'">
                <p
                    v-if="column.items.length === 0 && showErrors"
                    class="text-sm text-destructive"
                    role="alert"
                >
                    Add at least one link, or remove the column.
                </p>

                <div
                    v-for="(link, linkIndex) in column.items"
                    :key="link.key"
                    class="grid gap-3 border-t pt-4"
                >
                    <p
                        class="text-overline tracking-[0.16em] text-muted-foreground uppercase"
                    >
                        Link {{ linkIndex + 1 }}
                    </p>
                    <LinkFields
                        v-model="column.items[linkIndex]"
                        :id-prefix="`${idPrefix}-${link.key}`"
                        :options="options"
                        :errors="showErrors ? linkErrors(link) : {}"
                    />
                    <div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="column.items.splice(linkIndex, 1)"
                        >
                            <Trash2 aria-hidden="true" />
                            Remove link {{ linkIndex + 1 }}
                        </Button>
                    </div>
                </div>

                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="column.items.length >= MENU_LIMITS.megaLinks"
                        @click="addLink(column)"
                    >
                        <Plus aria-hidden="true" />
                        Add link
                    </Button>
                    <span
                        v-if="column.items.length >= MENU_LIMITS.megaLinks"
                        class="ml-2 text-xs text-muted-foreground"
                    >
                        A column holds {{ MENU_LIMITS.megaLinks }} links at
                        most.
                    </span>
                </div>
            </template>

            <div class="flex flex-wrap gap-2 border-t pt-4">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="index === 0"
                    @click="moveColumn(index, -1)"
                >
                    <ArrowUp aria-hidden="true" />
                    Move column {{ index + 1 }} up
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="index === columns.length - 1"
                    @click="moveColumn(index, 1)"
                >
                    <ArrowDown aria-hidden="true" />
                    Move down
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="text-destructive"
                    @click="removeColumn(index)"
                >
                    <Trash2 aria-hidden="true" />
                    Remove column {{ index + 1 }}
                </Button>
            </div>
        </fieldset>

        <div>
            <Button
                type="button"
                variant="outline"
                :disabled="columns.length >= MENU_LIMITS.megaColumns"
                @click="addColumn"
            >
                <Plus aria-hidden="true" />
                Add column
            </Button>
            <span
                v-if="columns.length >= MENU_LIMITS.megaColumns"
                class="ml-2 text-xs text-muted-foreground"
            >
                A mega menu has {{ MENU_LIMITS.megaColumns }} columns at most.
            </span>
        </div>
    </div>
</template>
