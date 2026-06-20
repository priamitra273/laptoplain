import type { ParentTaskOption } from '@/pages/project-lazy';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { buildParentTree, useTaskFormDrawer } from './useTaskFormDrawer';

const { getMock } = vi.hoisted(() => ({ getMock: vi.fn() }));

vi.mock('axios', () => ({ default: { get: getMock } }));
vi.mock('primevue/usetoast', () => ({ useToast: () => ({ add: vi.fn() }) }));

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

describe('useTaskFormDrawer.openCreate', () => {
    beforeEach(() => {
        getMock.mockReset();
        (globalThis as unknown as { route: unknown }).route = vi.fn(() => '/parent-options');
    });

    it('keeps loading until parent options are fetched so the form mounts with populated options', async () => {
        let resolveFetch!: (value: { data: { data: ParentTaskOption[] } }) => void;
        getMock.mockReturnValue(
            new Promise((resolve) => {
                resolveFetch = resolve;
            }),
        );

        const drawer = useTaskFormDrawer('proj-1', vi.fn());
        const opening = drawer.openCreate('parent-1');

        expect(drawer.visible.value).toBe(true);
        expect(drawer.loading.value).toBe(true);
        expect(drawer.parentTree.value).toEqual([]);

        resolveFetch({ data: { data: [{ id: '1', parent_id: null, title: 'root' }] } });
        await opening;

        expect(drawer.loading.value).toBe(false);
        expect(drawer.parentTree.value).toHaveLength(1);
        expect(drawer.parentTree.value[0].id).toBe('1');
    });

    it('clears loading even when fetching parent options fails', async () => {
        getMock.mockRejectedValue(new Error('network'));

        const drawer = useTaskFormDrawer('proj-1', vi.fn());
        await drawer.openCreate('parent-1');

        expect(drawer.loading.value).toBe(false);
        expect(drawer.parentTree.value).toEqual([]);
    });
});
