<script setup lang="ts">
import { Copy, Eye, EyeOff, Trash2, TriangleAlert } from '@lucide/vue';
import { computed, nextTick } from 'vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { Button } from '@/components/ui/button';
import { SheetDescription, SheetTitle } from '@/components/ui/sheet';
import type { EditorBlock } from '@/types';
import type { BlockBackground, BlockSpacing } from '@/types/blocks';
import BlockFields from './BlockFields.vue';
import { BLOCK_BY_TYPE } from './blocks';
import { blockFieldId, totalErrors, usePageEditor } from './context';

// CMP-ADM-BUILDER block form (the content of the drawer, 480 px): the form of the selected block from
// its definition, the section settings every block has (CMS-PB-001: visibility, section address,
// background, spacing, animation) and the live error list (CMS-PB-003). It sits inside a Sheet below
// 1700 px and in its own column beside the preview from there (BlockDrawer, Edit).
const props = defineProps<{
    block: EditorBlock | null;
    index: number;
    atLimit: boolean;
    /** Inside a Sheet: the title and description are the dialog's. */
    inSheet?: boolean;
}>();

const emit = defineEmits<{
    duplicate: [id: number];
    remove: [id: number];
}>();

const { errors, blockErrors, canEdit, options } = usePageEditor();

const definition = computed(() =>
    props.block ? BLOCK_BY_TYPE[props.block.type] : null,
);
const blockErrorMap = computed(() =>
    props.block ? (blockErrors[String(props.block.id)] ?? {}) : {},
);
const errorCount = computed(() => totalErrors(blockErrorMap.value));

const flat = computed(() =>
    Object.entries(blockErrorMap.value).flatMap(([path, messages]) =>
        messages.map((message) => ({ path, message })),
    ),
);

const envelopeError = (field: string): string | undefined =>
    errors[`blocks.${props.index}.${field}`];

const backgrounds: { value: BlockBackground; label: string }[] = [
    { value: 'default', label: 'Plain' },
    { value: 'muted', label: 'Soft grey' },
    { value: 'brand', label: 'Brand tint' },
    { value: 'dark', label: 'Dark teal' },
    { value: 'gradient', label: 'Dark gradient' },
];

const spacings: { value: BlockSpacing; label: string }[] = [
    { value: 'none', label: 'None' },
    { value: 'sm', label: 'Small' },
    { value: 'md', label: 'Medium' },
    { value: 'lg', label: 'Large' },
];

/** An error link: focus the field, or the nearest list / group above it. */
async function reveal(path: string): Promise<void> {
    if (!props.block) {
        return;
    }

    await nextTick();
    const segments = path.split('.');

    while (segments.length > 0) {
        const element = document.getElementById(
            blockFieldId(props.block.id, segments.join('.')),
        );

        if (element) {
            const control = element.matches('input, select, textarea, button')
                ? element
                : (element.querySelector<HTMLElement>(
                      'input:not([type="hidden"]), select, textarea, button, [tabindex="0"]',
                  ) ?? element);

            control.scrollIntoView({ block: 'center' });
            control.focus({ preventScroll: true });

            return;
        }

        segments.pop();
    }
}

function label(path: string): string {
    const last =
        path
            .split('.')
            .filter((part) => !/^\d+$/.test(part))
            .pop() ?? path;

    return last
        .replace(/_/g, ' ')
        .replace(/^./, (letter) => letter.toUpperCase());
}
</script>

<template>
    <div v-if="block && definition" class="flex min-h-0 flex-1 flex-col">
        <header class="grid gap-1 border-b p-5 pr-12">
            <component
                :is="inSheet ? SheetTitle : 'h2'"
                class="flex items-center gap-2 text-base font-semibold"
            >
                <component
                    :is="definition.icon"
                    class="size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                {{ definition.title(block.data) ?? definition.label }}
            </component>
            <component
                :is="inSheet ? SheetDescription : 'p'"
                class="text-sm text-muted-foreground"
            >
                {{ definition.label }} · {{ definition.description }}
            </component>
        </header>

        <div
            class="grid min-h-0 flex-1 content-start gap-6 overflow-y-auto p-5"
        >
            <section
                v-if="errorCount > 0"
                class="grid gap-2 rounded-lg border border-destructive/40 bg-destructive/5 p-3"
                aria-label="What needs attention"
            >
                <p
                    class="flex items-center gap-2 text-sm font-medium text-destructive"
                >
                    <TriangleAlert class="size-4" aria-hidden="true" />
                    {{
                        errorCount === 1
                            ? '1 thing needs attention'
                            : `${errorCount} things need attention`
                    }}
                </p>
                <p class="text-xs text-muted-foreground">
                    The block is saved, but the page cannot be published until
                    this is fixed.
                </p>
                <ul class="grid gap-1 text-sm">
                    <li
                        v-for="item in flat"
                        :key="`${item.path}-${item.message}`"
                    >
                        <button
                            type="button"
                            class="text-left text-destructive underline-offset-4 hover:underline"
                            @click="reveal(item.path)"
                        >
                            {{ item.message }}
                        </button>
                    </li>
                </ul>
            </section>

            <BlockFields
                :key="block.id"
                :fields="definition.fields"
                :data="block.data"
                :block-id="block.id"
                :errors="blockErrorMap"
                :disabled="!canEdit"
            />

            <details
                class="group rounded-xl border"
                :open="!!envelopeError('anchor_id') || undefined"
            >
                <summary
                    class="cursor-pointer list-none px-4 py-3 text-sm font-medium marker:hidden"
                >
                    Section settings
                    <span
                        class="ml-1 text-xs font-normal text-muted-foreground"
                    >
                        visibility, address, background, spacing
                    </span>
                </summary>
                <div class="grid gap-5 border-t p-4">
                    <SwitchRow
                        :id="blockFieldId(block.id, 'is_visible')"
                        v-model="block.is_visible"
                        label="Show this block on the page"
                        helper="A hidden block stays in the list but visitors do not see it."
                        :disabled="!canEdit"
                    />
                    <TextField
                        :id="blockFieldId(block.id, 'anchor_id')"
                        :model-value="block.anchor_id ?? ''"
                        label="Section address"
                        helper="Lets links jump to this block, e.g. “history” makes /about#history. Lowercase letters, numbers and hyphens."
                        :error="envelopeError('anchor_id')"
                        :maxlength="41"
                        mono
                        :disabled="!canEdit"
                        @update:model-value="
                            block.anchor_id =
                                String($event ?? '') === ''
                                    ? null
                                    : String($event)
                        "
                    />
                    <ChoiceField
                        :id="blockFieldId(block.id, 'background')"
                        v-model="block.background"
                        label="Background"
                        :options="backgrounds"
                        :error="envelopeError('background')"
                        :disabled="!canEdit"
                    />
                    <ChoiceField
                        :id="blockFieldId(block.id, 'spacing')"
                        v-model="block.spacing"
                        label="Space above and below"
                        :options="spacings"
                        :error="envelopeError('spacing')"
                        :disabled="!canEdit"
                    />
                    <SwitchRow
                        :id="blockFieldId(block.id, 'animate')"
                        v-model="block.animate"
                        label="Animate when it scrolls into view"
                        helper="Switched off automatically for visitors who prefer less motion."
                        :disabled="!canEdit"
                    />
                </div>
            </details>
        </div>

        <footer
            v-if="canEdit"
            class="flex flex-wrap items-center gap-2 border-t p-4"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                @click="block.is_visible = !block.is_visible"
            >
                <Eye v-if="!block.is_visible" aria-hidden="true" />
                <EyeOff v-else aria-hidden="true" />
                {{ block.is_visible ? 'Hide' : 'Show' }}
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="atLimit"
                :title="
                    atLimit
                        ? `A page can have at most ${options.block_limit} blocks.`
                        : undefined
                "
                @click="emit('duplicate', block.id)"
            >
                <Copy aria-hidden="true" />
                Duplicate
            </Button>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="ml-auto text-destructive hover:text-destructive"
                @click="emit('remove', block.id)"
            >
                <Trash2 aria-hidden="true" />
                Delete block
            </Button>
        </footer>
    </div>
</template>
