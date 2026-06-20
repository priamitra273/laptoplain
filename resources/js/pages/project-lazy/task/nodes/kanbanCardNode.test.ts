import type { SavedTaskPayload } from '@/pages/project-lazy';
import { describe, expect, it } from 'vitest';
import { buildKanbanCardNode, patchKanbanCardNode } from './kanbanCardNode';

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

describe('buildKanbanCardNode', () => {
    it('builds a leaf card with progress from the status score and a priority', () => {
        const card = buildKanbanCardNode(payload({ id: 'N', parentId: 'P' }));
        expect(card.id).toBe('N');
        expect(card.parent_id).toBe('P');
        expect(card.progress).toBe(50);
        expect(card.priority?.id).toBe('P1');
        expect(card.sub_task_recursive).toEqual([]);
    });
});

describe('patchKanbanCardNode', () => {
    it('updates fields in place and keeps children', () => {
        const card = buildKanbanCardNode(payload({ id: 'N' }));
        card.sub_task_recursive = [buildKanbanCardNode(payload({ id: 'c', parentId: 'N' }))];
        patchKanbanCardNode(card, payload({ id: 'N', title: 'Renamed', parentId: 'P2' }));
        expect(card.title).toBe('Renamed');
        expect(card.parent_id).toBe('P2');
        expect(card.sub_task_recursive).toHaveLength(1);
    });

    it('updates progress when status score changes', () => {
        const card = buildKanbanCardNode(payload({ status: { id: 'S1', name: 'In Progress', severity: 'info', score: 50 } }));
        expect(card.progress).toBe(50);
        patchKanbanCardNode(card, payload({ status: { id: 'S2', name: 'Completed', severity: 'success', score: 100 } }));
        expect(card.progress).toBe(100);
    });
});
