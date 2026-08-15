import type { ListTask } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { computeMoveTarget } from './computeMoveTarget';

const task = (id: string, parentId: string | null, children: ListTask[] = []): ListTask => ({
    id,
    parent_id: parentId,
    title: id,
    progress: 0,
    is_overdue: false,
    users: [],
    sub_task_recursive: children,
});

// Tree: A, B, C at root; B has children B1, B2.
const tree = (): ListTask[] => [
    task('A', null),
    task('B', null, [task('B1', 'B'), task('B2', 'B')]),
    task('C', null),
];

describe('computeMoveTarget', () => {
    it('returns null when dragging onto itself', () => {
        expect(computeMoveTarget(tree(), 'A', 'A', 'before')).toBeNull();
    });

    it('computes inside as append to target children (excluding dragged)', () => {
        expect(computeMoveTarget(tree(), 'A', 'B', 'inside')).toEqual({ parentId: 'B', position: 2 });
    });

    it('computes before a root sibling', () => {
        // siblings excl A = [B, C]; before C -> index 1
        expect(computeMoveTarget(tree(), 'A', 'C', 'before')).toEqual({ parentId: null, position: 1 });
    });

    it('computes after a root sibling', () => {
        // siblings excl A = [B, C]; after B -> index 0 + 1 = 1
        expect(computeMoveTarget(tree(), 'A', 'B', 'after')).toEqual({ parentId: null, position: 1 });
    });

    it('computes before within a nested sibling group', () => {
        // siblings of B's children excl A (not present) = [B1, B2]; before B2 -> index 1
        expect(computeMoveTarget(tree(), 'A', 'B2', 'before')).toEqual({ parentId: 'B', position: 1 });
    });

    it('accounts for the dragged node when it is already a sibling of the target', () => {
        // dragging B1 after B2: siblings of parent B excl B1 = [B2]; after B2 -> index 1
        expect(computeMoveTarget(tree(), 'B1', 'B2', 'after')).toEqual({ parentId: 'B', position: 1 });
    });

    it('returns null when the target does not exist', () => {
        expect(computeMoveTarget(tree(), 'A', 'ZZZ', 'inside')).toBeNull();
    });
});
