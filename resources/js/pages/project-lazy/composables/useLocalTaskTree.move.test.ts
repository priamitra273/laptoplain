import type { ListTask } from '@/pages/project-lazy';
import { computed } from 'vue';
import { describe, expect, it } from 'vitest';
import { useLocalTaskTree } from './useLocalTaskTree';

const task = (id: string, parentId: string | null, children: ListTask[] = []): ListTask => ({
    id,
    parent_id: parentId,
    title: id,
    progress: 0,
    is_overdue: false,
    users: [],
    sub_task_recursive: children,
});

const seed = (): ListTask[] => [task('A', null), task('B', null), task('C', null)];

const ids = (list: ListTask[]): string[] => list.map((t) => t.id);

describe('useLocalTaskTree.moveNode', () => {
    it('reorders a root sibling to a new index', () => {
        const { tasks, moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));

        const prev = moveNode('A', null, 2);

        expect(ids(tasks.value)).toEqual(['B', 'C', 'A']);
        expect(prev).toEqual({ parentId: null, index: 0 });
    });

    it('nests a node under a new parent and updates parent_id', () => {
        const { tasks, moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));

        moveNode('A', 'B', 0);

        const b = tasks.value.find((t) => t.id === 'B')!;
        expect(ids(tasks.value)).toEqual(['B', 'C']);
        expect(ids(b.sub_task_recursive)).toEqual(['A']);
        expect(b.sub_task_recursive[0].parent_id).toBe('B');
    });

    it('rolls back to the original location using the returned snapshot', () => {
        const { tasks, moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));

        const prev = moveNode('A', 'B', 0)!;
        moveNode('A', prev.parentId, prev.index);

        expect(ids(tasks.value)).toEqual(['A', 'B', 'C']);
        expect(tasks.value[0].parent_id).toBeNull();
    });

    it('returns null for an unknown id', () => {
        const { moveNode } = useLocalTaskTree<ListTask>(computed(() => seed()));
        expect(moveNode('ZZZ', null, 0)).toBeNull();
    });
});
