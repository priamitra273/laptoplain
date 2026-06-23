import type { ListTask } from '@/pages/project-lazy';

export type DropMode = 'before' | 'inside' | 'after';

export interface MoveTarget {
    parentId: string | null;
    position: number;
}

const findNode = (list: ListTask[], id: string): ListTask | null => {
    for (const item of list) {
        if (item.id === id) {
            return item;
        }
        const found = findNode(item.sub_task_recursive ?? [], id);
        if (found) {
            return found;
        }
    }
    return null;
};

const siblingsOf = (tree: ListTask[], parentId: string | null): ListTask[] => {
    if (parentId === null) {
        return tree;
    }
    return findNode(tree, parentId)?.sub_task_recursive ?? [];
};

export const computeMoveTarget = (tree: ListTask[], draggedKey: string, targetKey: string, mode: DropMode): MoveTarget | null => {
    if (draggedKey === targetKey) {
        return null;
    }

    const target = findNode(tree, targetKey);
    if (!target) {
        return null;
    }

    if (mode === 'inside') {
        const children = target.sub_task_recursive ?? [];
        const position = children.filter((child) => child.id !== draggedKey).length;
        return { parentId: target.id, position };
    }

    const parentId = target.parent_id ?? null;
    const siblings = siblingsOf(tree, parentId).filter((sibling) => sibling.id !== draggedKey);
    const index = siblings.findIndex((sibling) => sibling.id === targetKey);
    if (index === -1) {
        return null;
    }

    return { parentId, position: mode === 'before' ? index : index + 1 };
};
