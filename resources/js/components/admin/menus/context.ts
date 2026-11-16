import type { InjectionKey } from 'vue';
import type { TreeLimits } from '@/lib/menu-tree';
import type { MenuNode } from '@/types';

// What the nested tree list and its rows share with the editor that owns the form: the rules,
// what is lifted / collapsed / being dragged, and the actions. Every change goes through
// `perform` so it is undoable and announced to screen readers.
export type MenuTreeAction =
    | 'moveUp'
    | 'moveDown'
    | 'indent'
    | 'outdent'
    | 'remove';

export type MenuTreeContext = {
    readonly: boolean;
    limits: TreeLimits;
    roots: MenuNode[];
    errors: Record<string, string | undefined>;
    /** A drag is in progress: empty nested lists show a drop zone. */
    dragging: boolean;
    /** Key of the item lifted with Space (keyboard move), if any. */
    lifted: string | null;
    collapsed: string[];
    edit: (node: MenuNode) => void;
    addChild: (node: MenuNode) => void;
    perform: (action: MenuTreeAction, node: MenuNode) => void;
    dragStart: () => void;
    dragEnd: () => void;
    toggleCollapsed: (key: string) => void;
    /** Moves keyboard focus back to an item's handle after the list re-rendered. */
    focusHandle: (key: string) => void;
    announce: (message: string) => void;
};

export const menuTreeContextKey: InjectionKey<MenuTreeContext> =
    Symbol('menu-tree');
