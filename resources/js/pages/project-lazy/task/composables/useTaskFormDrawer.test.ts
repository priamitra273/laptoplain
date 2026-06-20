import type { ParentTaskOption } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { buildParentTree } from './useTaskFormDrawer';

describe('buildParentTree', () => {
    it('nests children under their parent', () => {
        const flat: ParentTaskOption[] = [
            { id: '1', parent_id: null, title: 'root' },
            { id: '2', parent_id: '1', title: 'child' },
        ];
        const tree = buildParentTree(flat);
        expect(tree).toHaveLength(1);
        expect(tree[0].id).toBe('1');
        expect(tree[0].sub_task_recursive).toHaveLength(1);
        expect(tree[0].sub_task_recursive[0].id).toBe('2');
    });

    it('treats an unknown parent as a root', () => {
        const tree = buildParentTree([{ id: '5', parent_id: '99', title: 'x' }]);
        expect(tree).toHaveLength(1);
        expect(tree[0].id).toBe('5');
    });

    it('preserves category and coerces ids to strings', () => {
        const flat = [{ id: 7 as unknown as string, parent_id: null, title: 'n', category: { id: '3', name: 'Epic' } }];
        const tree = buildParentTree(flat as ParentTaskOption[]);
        expect(tree[0].id).toBe('7');
        expect(tree[0].category?.name).toBe('Epic');
    });

    it('returns an empty array for an empty list', () => {
        expect(buildParentTree([])).toEqual([]);
    });
});
