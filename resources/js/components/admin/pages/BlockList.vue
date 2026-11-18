<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    Copy,
    Eye,
    EyeOff,
    GripVertical,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    TriangleAlert,
} from '@lucide/vue';
import { VueDraggable } from 'vue-draggable-plus';
import { ref } from 'vue';
import ErrorBadge from '@/components/admin/ErrorBadge.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useConfirm } from '@/composables/useConfirm';
import { cn } from '@/lib/utils';
import type { EditorBlock } from '@/types';
import { BLOCK_BY_TYPE } from './blocks';
import { totalErrors, usePageEditor } from './context';

// CMP-ADM-BUILDER block list (left pane, 320 px): a row per block with drag handle, type icon, title
// (first heading or the type name), visibility eye, a warning when the block is invalid and a ⋯ menu.
// Keyboard (CMS-PB-002): the row's handle takes Space to lift and ↑ ↓ to move (SortableList rules);
// on a focused row Alt+↑/↓ moves it, Ctrl/⌘+D duplicates it, Delete removes it (asks first).
const props = defineProps<{
    blocks: EditorBlock[];
    selectedId: number | null;
    /** The block the pointer is over in the preview (2 px teal outline there, a tint here). */
    hoverId: number | null;
    atLimit: boolean;
    limit: number;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    select: [id: number];
    add: [];
    duplicate: [id: number];
    remove: [id: number];
    toggle: [id: number];
    move: [id: number, delta: -1 | 1];
    reorder: [blocks: EditorBlock[]];
    hover: [id: number | null];
}>();

const { blockErrors } = usePageEditor();
const confirm = useConfirm();
const announcement = ref('');

const definition = (block: EditorBlock) => BLOCK_BY_TYPE[block.type];
const titleOf = (block: EditorBlock): string =>
    definition(block).title(block.data) ?? definition(block).label;
const errorsOf = (block: EditorBlock): number =>
    totalErrors(blockErrors[String(block.id)]);

function say(message: string): void {
    announcement.value = '';
    setTimeout(() => {
        announcement.value = message;
    }, 30);
}

async function onKey(
    event: KeyboardEvent,
    block: EditorBlock,
    index: number,
): Promise<void> {
    if (props.disabled) {
        return;
    }

    if (
        event.altKey &&
        (event.key === 'ArrowUp' || event.key === 'ArrowDown')
    ) {
        event.preventDefault();
        const delta = event.key === 'ArrowUp' ? -1 : 1;
        const target = index + delta;

        if (target >= 0 && target < props.blocks.length) {
            emit('move', block.id, delta);
            say(
                `${titleOf(block)} moved to position ${target + 1} of ${props.blocks.length}.`,
            );
            // Keep the focus on the moved row.
            setTimeout(
                () =>
                    document
                        .querySelector<HTMLElement>(
                            `[data-row-id="${block.id}"] [data-row-main]`,
                        )
                        ?.focus(),
                30,
            );
        }
    } else if (
        (event.ctrlKey || event.metaKey) &&
        event.key.toLowerCase() === 'd'
    ) {
        event.preventDefault();

        if (!props.atLimit) {
            emit('duplicate', block.id);
        }
    } else if (event.key === 'Delete') {
        event.preventDefault();
        const answer = await confirm({
            title: 'Delete this block?',
            subject: titleOf(block),
            description:
                'It is removed from the page. You can undo for 8 seconds, and nothing is final until you save.',
            confirmLabel: 'Delete block',
            destructive: true,
        });

        if (answer) {
            emit('remove', block.id);
        }
    }
}
</script>

<template>
    <section
        class="flex min-h-0 flex-col rounded-xl border bg-card"
        aria-labelledby="block-list-title"
    >
        <header
            class="flex items-center justify-between gap-2 border-b px-4 py-3"
        >
            <div class="min-w-0">
                <h2 id="block-list-title" class="text-sm font-medium">
                    Blocks
                </h2>
                <p class="text-xs text-muted-foreground">
                    {{ blocks.length }} of {{ limit }}
                </p>
            </div>
            <Button
                type="button"
                size="sm"
                :disabled="disabled || atLimit"
                :title="
                    atLimit
                        ? `A page can have at most ${limit} blocks.`
                        : undefined
                "
                @click="emit('add')"
            >
                <Plus aria-hidden="true" />
                Add block
            </Button>
        </header>

        <div
            v-if="blocks.length === 0"
            class="grid gap-3 p-5 text-sm text-muted-foreground"
        >
            <p class="font-medium text-foreground">No blocks yet</p>
            <p>
                A page is a list of blocks. Start with a Page header or a Hero:
                its heading becomes the title (H1) of the page, which every page
                needs to be published.
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="justify-self-start"
                :disabled="disabled"
                @click="emit('add')"
            >
                <Plus aria-hidden="true" />
                Add the first block
            </Button>
        </div>

        <VueDraggable
            v-else
            :model-value="blocks"
            tag="ol"
            handle="[data-drag-handle]"
            :animation="150"
            :disabled="disabled"
            class="grid max-h-[calc(100dvh-16rem)] gap-px overflow-y-auto p-2"
            aria-label="Blocks of the page, in order"
            @update:model-value="emit('reorder', $event)"
        >
            <li
                v-for="(block, index) in blocks"
                :key="block.id"
                :data-row-id="block.id"
                :class="
                    cn(
                        'group flex items-center gap-1 rounded-lg border border-transparent pr-1 pl-0.5 transition-colors',
                        selectedId === block.id
                            ? 'border-primary/40 bg-primary/5'
                            : 'hover:bg-muted/60',
                        hoverId === block.id &&
                            selectedId !== block.id &&
                            'bg-muted',
                        errorsOf(block) > 0 && 'border-destructive/50',
                        !block.is_visible && 'opacity-60',
                    )
                "
                @mouseenter="emit('hover', block.id)"
                @mouseleave="emit('hover', null)"
                @keydown="onKey($event, block, index)"
            >
                <span
                    data-drag-handle
                    class="grid size-8 shrink-0 cursor-grab place-items-center text-muted-foreground active:cursor-grabbing"
                    aria-hidden="true"
                    title="Drag to reorder"
                >
                    <GripVertical class="size-4" />
                </span>

                <button
                    type="button"
                    data-row-main
                    class="flex min-h-11 min-w-0 flex-1 items-center gap-2.5 rounded-md py-1.5 text-left outline-offset-2"
                    :aria-current="selectedId === block.id ? 'true' : undefined"
                    :aria-label="`${titleOf(block)}, ${definition(block).label}${errorsOf(block) ? `, ${errorsOf(block)} errors` : ''}${block.is_visible ? '' : ', hidden'}. Position ${index + 1} of ${blocks.length}. Alt plus arrow keys move it.`"
                    @click="emit('select', block.id)"
                >
                    <span
                        class="grid size-8 shrink-0 place-items-center rounded-md bg-muted text-muted-foreground"
                    >
                        <component
                            :is="definition(block).icon"
                            class="size-4"
                            aria-hidden="true"
                        />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{
                            titleOf(block)
                        }}</span>
                        <span
                            class="block truncate text-xs text-muted-foreground"
                        >
                            {{ definition(block).label
                            }}<template v-if="!block.is_visible">
                                · hidden</template
                            >
                        </span>
                    </span>
                    <span
                        v-if="errorsOf(block) > 0"
                        class="flex shrink-0 items-center gap-1 text-destructive"
                    >
                        <TriangleAlert class="size-4" aria-hidden="true" />
                        <ErrorBadge :count="errorsOf(block)" />
                    </span>
                </button>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="disabled"
                    :aria-pressed="block.is_visible"
                    :aria-label="
                        block.is_visible
                            ? `Hide ${titleOf(block)}`
                            : `Show ${titleOf(block)}`
                    "
                    :title="
                        block.is_visible
                            ? 'Visible on the page. Press to hide.'
                            : 'Hidden from the page. Press to show.'
                    "
                    @click="emit('toggle', block.id)"
                >
                    <Eye v-if="block.is_visible" aria-hidden="true" />
                    <EyeOff v-else aria-hidden="true" />
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :aria-label="`Actions for ${titleOf(block)}`"
                        >
                            <MoreHorizontal aria-hidden="true" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-52">
                        <DropdownMenuItem @select="emit('select', block.id)">
                            <Pencil aria-hidden="true" /> Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            :disabled="disabled || index === 0"
                            @select="emit('move', block.id, -1)"
                        >
                            <ArrowUp aria-hidden="true" /> Move up
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            :disabled="disabled || index === blocks.length - 1"
                            @select="emit('move', block.id, 1)"
                        >
                            <ArrowDown aria-hidden="true" /> Move down
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            :disabled="disabled || atLimit"
                            @select="emit('duplicate', block.id)"
                        >
                            <Copy aria-hidden="true" /> Duplicate
                            <span class="ml-auto text-xs text-muted-foreground"
                                >Ctrl+D</span
                            >
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            :disabled="disabled"
                            @select="emit('toggle', block.id)"
                        >
                            <EyeOff
                                v-if="block.is_visible"
                                aria-hidden="true"
                            />
                            <Eye v-else aria-hidden="true" />
                            {{ block.is_visible ? 'Hide' : 'Show' }}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            variant="destructive"
                            :disabled="disabled"
                            @select="emit('remove', block.id)"
                        >
                            <Trash2 aria-hidden="true" /> Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </li>
        </VueDraggable>

        <p class="sr-only" role="status" aria-live="polite">
            {{ announcement }}
        </p>
        <p
            v-if="blocks.length"
            class="border-t px-4 py-2.5 text-xs text-muted-foreground"
        >
            Drag the grip to reorder, or press Alt+↑ / Alt+↓ on a row.
        </p>
    </section>
</template>
