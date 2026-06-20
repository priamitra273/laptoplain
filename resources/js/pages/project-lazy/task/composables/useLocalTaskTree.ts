import type { SavedTaskPayload } from '@/pages/project-lazy';
import { ref, watch, type ComputedRef, type Ref } from 'vue';

export type TaskTreeNode<T> = {
    id: string;
    parent_id: string | null;
    progress: number;
    status?: { score?: number } | null;
    sub_task_recursive: T[];
};

export interface NodeAdapter<T> {
    build: (payload: SavedTaskPayload) => T;
    patch: (node: T, payload: SavedTaskPayload) => void;
}

export type LocalTaskTreeSource<T> = Ref<T[]> | ComputedRef<T[]>;

export interface LocalTaskTreeOptions {
    onProjectProgress?: (value: number) => void;
}

const round2 = (value: number): number => Math.round(value * 100) / 100;

const cloneTree = <T>(list: T[]): T[] => JSON.parse(JSON.stringify(list ?? []));

export const useLocalTaskTree = <T extends TaskTreeNode<T>>(source: LocalTaskTreeSource<T>, options: LocalTaskTreeOptions = {}) => {
    const tasks = ref<T[]>([]) as Ref<T[]>;

    watch(
        source,
        (list) => {
            tasks.value = cloneTree(list ?? []);
        },
        { immediate: true, flush: 'sync' },
    );

    const findIn = (list: T[], id: string): T | null => {
        for (const item of list) {
            if (item.id === id) {
                return item;
            }
            const found = findIn(item.sub_task_recursive ?? [], id);
            if (found) {
                return found;
            }
        }
        return null;
    };

    const removeFrom = (list: T[], id: string): T | null => {
        for (let i = 0; i < list.length; i++) {
            if (list[i].id === id) {
                return list.splice(i, 1)[0];
            }
            const removed = removeFrom(list[i].sub_task_recursive ?? [], id);
            if (removed) {
                return removed;
            }
        }
        return null;
    };

    const insert = (item: T, parentId: string | null): void => {
        if (!parentId) {
            tasks.value.push(item);
            return;
        }
        const parent = findIn(tasks.value, parentId);
        if (parent) {
            parent.sub_task_recursive.push(item);
        } else {
            tasks.value.push(item);
        }
    };

    const recalcNode = (item: T): number => {
        const children = item.sub_task_recursive ?? [];
        const value =
            children.length === 0
                ? (item.status?.score ?? item.progress ?? 0)
                : round2(children.reduce((sum, child) => sum + recalcNode(child), 0) / children.length);
        item.progress = value;
        return value;
    };

    const recalc = (): void => {
        const rootScores = tasks.value.map((item) => recalcNode(item));
        if (options.onProjectProgress) {
            const projectProgress = rootScores.length === 0 ? 0 : round2(rootScores.reduce((s, v) => s + v, 0) / rootScores.length);
            options.onProjectProgress(projectProgress);
        }
    };

    const applySaved = (payload: SavedTaskPayload, adapter: NodeAdapter<T>): void => {
        if (payload.mode === 'create') {
            insert(adapter.build(payload), payload.parentId);
        } else {
            const node = findIn(tasks.value, payload.id);

            if (!node) return;

            const previousParentId = node.parent_id;
            adapter.patch(node, payload);

            const nextParentId = payload.parentId ?? null;

            if ((previousParentId ?? null) !== nextParentId) {
                const detached = removeFrom(tasks.value, payload.id);

                if (detached) {
                    insert(detached, nextParentId);
                }
            }
        }

        recalc();
        tasks.value = [...tasks.value];
    };

    return {
        tasks,
        applySaved,
        recalc,
        findNode: (id: string): T | null => findIn(tasks.value, id),
    };
};
