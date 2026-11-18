import type { InertiaForm } from '@inertiajs/vue3';
import type { InjectionKey } from 'vue';
import { inject } from 'vue';
import { newKey } from '@/components/admin/catalog/context';
import type {
    BlockErrorMap,
    EditorBlock,
    PageFormData,
    PageOptions,
} from '@/types';
import type { Data, Field, OptionSource } from './blocks';
import { iconOptions } from './blocks';

// What every part of the page builder shares: the page's form, the option lists, the block errors
// of the live check (CMS-PB-003) and helpers for ids, list keys and the data that is sent.
export type PageEditorContext = {
    form: InertiaForm<PageFormData>;
    options: PageOptions;
    /** Server errors of the form by key (`blocks.2.anchor_id`, `slug`). */
    errors: Record<string, string | undefined>;
    /** Block id → messages by path inside its data, from the preview endpoint. */
    blockErrors: Record<string, BlockErrorMap>;
    canEdit: boolean;
};

export const pageEditorKey: InjectionKey<PageEditorContext> =
    Symbol('page-editor');

export function usePageEditor(): PageEditorContext {
    const context = inject(pageEditorKey);

    if (!context) {
        throw new Error('The page editor must provide its context.');
    }

    return context;
}

/** Give every object inside a list a client key (repeater cards need a stable identity). */
function withListKeys(node: unknown): unknown {
    if (Array.isArray(node)) {
        return node.map((item) =>
            item !== null && typeof item === 'object'
                ? { ...(withListKeys(item) as Data), _key: newKey() }
                : item,
        );
    }

    if (node !== null && typeof node === 'object') {
        return Object.fromEntries(
            Object.entries(node as Data).map(([key, value]) => [
                key,
                withListKeys(value),
            ]),
        );
    }

    return node;
}

/** A plain copy of a block with a client key on every list item inside its data. */
export function keyedBlock(block: EditorBlock): EditorBlock {
    const clone = JSON.parse(JSON.stringify(block)) as EditorBlock;
    clone.data = withListKeys(clone.data) as Data;

    return clone;
}

/** Props are reactive proxies; the form needs plain JSON with keys on every list item. */
export function withKeys(data: PageFormData): PageFormData {
    const clone = JSON.parse(JSON.stringify(data)) as PageFormData;

    clone.blocks = clone.blocks.map((block, index) =>
        keyedBlock({
            ...block,
            // A block that has no saved id yet is identified by a negative one.
            id: block.id ?? -(index + 1),
        }),
    );

    return clone;
}

/** What is sent: no client keys, no link state (the server drops them too). */
export function cleanData(node: unknown): unknown {
    if (Array.isArray(node)) {
        return node.map(cleanData);
    }

    if (node !== null && typeof node === 'object') {
        const source = node as Data;
        const isLink = 'link_type' in source;

        return Object.fromEntries(
            Object.entries(source)
                .filter(
                    ([key]) =>
                        key !== '_key' &&
                        !(isLink && ['link', 'key'].includes(key)),
                )
                .map(([key, value]) => [key, cleanData(value)]),
        );
    }

    return node;
}

/** The blocks as the server takes them (save and preview). */
export function blocksPayload(blocks: EditorBlock[]): EditorBlock[] {
    return blocks.map((block) => ({
        ...block,
        data: cleanData(block.data) as Data,
    }));
}

let temporary = 0;

/** The next negative id for a block that is not saved yet. */
export function nextTemporaryId(existing: EditorBlock[]): number {
    const lowest = Math.min(0, ...existing.map((block) => block.id));
    temporary = Math.min(temporary, lowest) - 1;

    return temporary;
}

/** `blockFieldId(-1, 'items.2.title')` → `blk-n1-items-2-title`: the id of that control. */
export function blockFieldId(blockId: number, path: string): string {
    const id = blockId < 0 ? `n${-blockId}` : String(blockId);

    return `blk-${id}-${path.replace(/\./g, '-')}`;
}

/** The choices of a select field. */
export function resolveOptions(
    source: OptionSource,
    options: PageOptions,
): { value: string | number; label: string; group?: string }[] {
    if (Array.isArray(source)) {
        return source;
    }

    if (source.from === 'icons') {
        return iconOptions(options.block_icons);
    }

    return options[source.from];
}

export function isVisible(field: Field, data: Data): boolean {
    return field.when ? field.when(data) : true;
}

/** How many messages lie under a path (`items.2` → the errors of that card). */
export function countUnder(
    errors: BlockErrorMap | undefined,
    prefix: string,
): number {
    if (!errors) {
        return 0;
    }

    return Object.entries(errors)
        .filter(([key]) => key === prefix || key.startsWith(`${prefix}.`))
        .reduce((total, [, messages]) => total + messages.length, 0);
}

export function totalErrors(errors: BlockErrorMap | undefined): number {
    return errors
        ? Object.values(errors).reduce(
              (total, messages) => total + messages.length,
              0,
          )
        : 0;
}
