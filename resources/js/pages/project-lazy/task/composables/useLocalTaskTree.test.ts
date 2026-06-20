import { ref } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import { useLocalTaskTree } from './useLocalTaskTree';

interface Node {
    id: string;
    parent_id: string | null;
    progress: number;
    status?: { score?: number } | null;
    sub_task_recursive: Node[];
}

const node = (over: Partial<Node> & { id: string }): Node => ({
    parent_id: null,
    progress: 0,
    status: null,
    sub_task_recursive: [],
    ...over,
});

const leafAdapter = {
    build: (p: { id: string; parentId: string | null; status?: { score?: number } | null }): Node =>
        node({ id: p.id, parent_id: p.parentId, status: p.status ?? null, progress: p.status?.score ?? 0 }),
    patch: (n: Node, p: { parentId: string | null; status?: { score?: number } | null }): void => {
        n.parent_id = p.parentId;
        n.status = p.status ?? null;
    },
};

describe('useLocalTaskTree.recalc', () => {
    it('sets a leaf progress from its status score and a parent to the average of children', () => {
        const source = ref<Node[]>([
            node({
                id: 'p',
                sub_task_recursive: [
                    node({ id: 'a', parent_id: 'p', status: { score: 40 }, progress: 0 }),
                    node({ id: 'b', parent_id: 'p', status: { score: 60 }, progress: 0 }),
                ],
            }),
        ]);
        const onProjectProgress = vi.fn();
        const { tasks, recalc } = useLocalTaskTree(source, { onProjectProgress });

        recalc();

        expect(tasks.value[0].sub_task_recursive[0].progress).toBe(40);
        expect(tasks.value[0].sub_task_recursive[1].progress).toBe(60);
        expect(tasks.value[0].progress).toBe(50);
        expect(onProjectProgress).toHaveBeenLastCalledWith(50);
    });
});

describe('useLocalTaskTree.applySaved', () => {
    it('inserts a created child under its parent and recomputes ancestor + project progress', () => {
        const source = ref<Node[]>([node({ id: 'p', status: { score: 0 }, progress: 0 })]);
        const onProjectProgress = vi.fn();
        const { tasks, applySaved } = useLocalTaskTree(source, { onProjectProgress });

        applySaved({ mode: 'create', id: 'c', parentId: 'p', status: { score: 80 } } as never, leafAdapter as never);

        expect(tasks.value[0].sub_task_recursive).toHaveLength(1);
        expect(tasks.value[0].sub_task_recursive[0].id).toBe('c');
        expect(tasks.value[0].progress).toBe(80);
        expect(onProjectProgress).toHaveBeenLastCalledWith(80);
    });

    it('patches an existing node and re-parents it when parentId changes', () => {
        const source = ref<Node[]>([
            node({ id: 'p1', sub_task_recursive: [node({ id: 'c', parent_id: 'p1', status: { score: 30 }, progress: 30 })] }),
            node({ id: 'p2', status: { score: 0 }, progress: 0 }),
        ]);
        const { tasks, applySaved, findNode } = useLocalTaskTree(source, {});

        applySaved({ mode: 'edit', id: 'c', parentId: 'p2', status: { score: 90 } } as never, leafAdapter as never);

        expect(tasks.value[0].sub_task_recursive).toHaveLength(0);
        expect(findNode('p2')?.sub_task_recursive[0].id).toBe('c');
        expect(findNode('p2')?.progress).toBe(90);
    });

    it('re-seeds local tasks when the source reference changes', () => {
        const source = ref<Node[]>([node({ id: 'old' })]);
        const { tasks } = useLocalTaskTree(source, {});
        expect(tasks.value.map((t) => t.id)).toEqual(['old']);

        source.value = [node({ id: 'new' })];
        expect(tasks.value.map((t) => t.id)).toEqual(['new']);
    });
});
