import type { SavedTaskPayload } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { buildListTaskNode, patchListTaskNode } from './listTaskNode';

const payload = (over: Partial<SavedTaskPayload> = {}): SavedTaskPayload => ({
    mode: 'create',
    id: 'X',
    parentId: null,
    title: 'T',
    startDate: null,
    dueDate: null,
    isArchived: false,
    status: { id: 'S1', name: 'In Progress', severity: 'info', score: 50 },
    type: { id: 'T1', name: 'Bug', severity: 'danger' },
    category: { id: 'C1', name: 'Task' },
    priority: { id: 'P1', name: 'High', severity: 'warning' },
    users: [],
    ...over,
});

describe('buildListTaskNode', () => {
    it('builds a leaf with progress from the status score and an empty children array', () => {
        const node = buildListTaskNode(payload({ id: 'N', parentId: 'P', title: 'New' }));
        expect(node.id).toBe('N');
        expect(node.parent_id).toBe('P');
        expect(node.progress).toBe(50);
        expect(node.sub_task_recursive).toEqual([]);
        expect(node.status?.id).toBe('S1');
    });

    it('sets completed_at and clears overdue when the status is Completed', () => {
        const node = buildListTaskNode(
            payload({ status: { id: 'S2', name: 'Completed', severity: 'success', score: 100 }, dueDate: '2000-01-01' }),
        );
        expect(node.completed_at).not.toBeNull();
        expect(node.is_overdue).toBe(false);
    });

    it('flags overdue for a past due date on a non-completed status', () => {
        const node = buildListTaskNode(payload({ dueDate: '2000-01-01' }));
        expect(node.is_overdue).toBe(true);
    });
});

describe('patchListTaskNode', () => {
    it('updates fields in place and keeps the existing children', () => {
        const node = buildListTaskNode(payload({ id: 'N' }));
        node.sub_task_recursive = [buildListTaskNode(payload({ id: 'child', parentId: 'N' }))];
        patchListTaskNode(node, payload({ id: 'N', title: 'Renamed', parentId: 'P2' }));
        expect(node.title).toBe('Renamed');
        expect(node.parent_id).toBe('P2');
        expect(node.sub_task_recursive).toHaveLength(1);
    });

    it('updates progress when status score changes', () => {
        const node = buildListTaskNode(payload());
        expect(node.progress).toBe(50);
        patchListTaskNode(node, payload({ status: { id: 'S2', name: 'Completed', severity: 'success', score: 100 } }));
        expect(node.progress).toBe(100);
    });
});
