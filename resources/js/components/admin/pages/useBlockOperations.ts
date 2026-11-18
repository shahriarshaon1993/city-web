import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { notify } from '@/lib/toast';
import type { PageBlockType } from '@/types/blocks';
import type { EditorBlock, PageFormData } from '@/types';
import { BLOCK_BY_TYPE } from './blocks';
import { keyedBlock, nextTemporaryId } from './context';

// CMS-PB-002 / CRUD-06: what you can do to the block list — add, duplicate, hide, move, reorder and
// delete with an 8 s undo (TOAST-02). Everything works on the page's form, so Save sends the result
// and the unsaved-changes guard sees it. A limit of 30 blocks (CMS-PB-005).
const UNDO_MS = 8000;

export function useBlockOperations(
    form: InertiaForm<PageFormData>,
    limit: number,
    select: (id: number | null) => void,
) {
    const blocks = computed(() => form.blocks);
    const atLimit = computed(() => form.blocks.length >= limit);

    function indexOf(id: number): number {
        return form.blocks.findIndex((block) => block.id === id);
    }

    /** Insert a block of the type, after `afterId` or at the end, and open it. */
    function add(
        type: PageBlockType,
        afterId: number | null = null,
    ): EditorBlock | null {
        if (atLimit.value) {
            return null;
        }

        const definition = BLOCK_BY_TYPE[type];
        const block = keyedBlock({
            id: nextTemporaryId(form.blocks),
            type,
            data: definition.blank(),
            is_visible: true,
            anchor_id: null,
            background: 'default',
            spacing: 'md',
            animate: true,
        });

        const at = afterId === null ? form.blocks.length : indexOf(afterId) + 1;
        form.blocks.splice(at, 0, block);
        select(block.id);

        return block;
    }

    function duplicate(id: number): void {
        const index = indexOf(id);

        if (index < 0 || atLimit.value) {
            return;
        }

        const source = form.blocks[index];
        const copy = keyedBlock({
            ...source,
            id: nextTemporaryId(form.blocks),
            anchor_id: null,
        });

        form.blocks.splice(index + 1, 0, copy);
        select(copy.id);
        notify.success('Block duplicated', {
            description: BLOCK_BY_TYPE[copy.type].label,
        });
    }

    function toggleVisible(id: number): void {
        const block = form.blocks[indexOf(id)];

        if (block) {
            block.is_visible = !block.is_visible;
        }
    }

    function move(id: number, delta: -1 | 1): void {
        const index = indexOf(id);
        const target = index + delta;

        if (index < 0 || target < 0 || target >= form.blocks.length) {
            return;
        }

        const next = [...form.blocks];
        [next[index], next[target]] = [next[target], next[index]];
        form.blocks = next;
    }

    function remove(id: number): void {
        const index = indexOf(id);

        if (index < 0) {
            return;
        }

        const [removed] = form.blocks.splice(index, 1);
        select(
            form.blocks[Math.min(index, form.blocks.length - 1)]?.id ?? null,
        );

        notify.info('Block deleted', {
            description: `${BLOCK_BY_TYPE[removed.type].label}. Save the page to make it final.`,
            duration: UNDO_MS,
            action: {
                label: 'Undo',
                onClick: () => {
                    form.blocks.splice(
                        Math.min(index, form.blocks.length),
                        0,
                        removed,
                    );
                    select(removed.id);
                },
            },
        });
    }

    return {
        blocks,
        atLimit,
        add,
        duplicate,
        toggleVisible,
        move,
        remove,
        indexOf,
    };
}
