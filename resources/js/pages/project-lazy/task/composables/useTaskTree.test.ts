import type { ListTask } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { findTaskById, formatTasks, isDescendant, sortByRecency } from './useTaskTree';

const task = (over: Partial<ListTask> & { id: string }): ListTask =>
    ({
        parent_id: null,
        title: 't',
        progress: 0,
        is_overdue: false,
        users: [],
        sub_task_recursive: [],
        ...over,
    }) as ListTask;

describe('formatTasks', () => {
    it('maps fields and nests children with incremented level', () => {
        const rows = formatTasks([task({ id: '1', title: 'A', sub_task_recursive: [task({ id: '2', title: 'B' })] })]);
        expect(rows[0].key).toBe('1');
        expect(rows[0].data.id).toBe('1');
        expect(rows[0].data.level).toBe(0);
        expect(rows[0].children[0].key).toBe('2');
        expect(rows[0].children[0].data.level).toBe(1);
        expect(rows[0].original.id).toBe('1');
    });

    it('returns an empty array for undefined input', () => {
        expect(formatTasks(undefined)).toEqual([]);
    });
});

describe('sortByRecency', () => {
    it('orders by updated_at desc', () => {
        const rows = formatTasks([task({ id: '1', updated_at: '2024-01-01' }), task({ id: '2', updated_at: '2024-06-01' })]);
        expect(sortByRecency(rows).map((r) => r.key)).toEqual(['2', '1']);
    });

    it('falls back to created_at when updated_at is missing', () => {
        const rows = formatTasks([task({ id: '1', created_at: '2024-01-01' }), task({ id: '2', created_at: '2024-09-01' })]);
        expect(sortByRecency(rows).map((r) => r.key)).toEqual(['2', '1']);
    });
});

describe('findTaskById / isDescendant', () => {
    const tree = [task({ id: '1', sub_task_recursive: [task({ id: '2', sub_task_recursive: [task({ id: '3' })] })] })];

    it('finds a nested task', () => {
        expect(findTaskById(tree, '3')?.id).toBe('3');
    });

    it('returns null when the id is absent', () => {
        expect(findTaskById(tree, '99')).toBeNull();
    });

    it('detects a descendant', () => {
        expect(isDescendant(tree, '1', '3')).toBe(true);
    });

    it('rejects a non-descendant (parent under child)', () => {
        expect(isDescendant(tree, '2', '1')).toBe(false);
    });

    it('rejects self', () => {
        expect(isDescendant(tree, '1', '1')).toBe(false);
    });
});
