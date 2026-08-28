import { describe, expect, it } from 'vitest';
import type { WorkloadStatusOption, WorkloadSummary } from './types';
import { buildSegments } from './workload';

const statusOptions: WorkloadStatusOption[] = [
    { id: 1, name: 'Free', severity: 'success' },
    { id: 2, name: 'Almost Done', severity: 'info' },
    { id: 3, name: 'Ongoing', severity: 'warn' },
    { id: 4, name: 'Overloaded', severity: 'danger' },
];

const summary = (overrides: Partial<WorkloadSummary> = {}): WorkloadSummary => ({
    total_users: 10,
    free: 2,
    light: 2,
    moderate: 3,
    busy: 3,
    users_preview: [],
    overloaded_preview: [],
    ...overrides,
});

describe('buildSegments', () => {
    it('keeps the severity order coming from the enum', () => {
        expect(buildSegments(summary(), statusOptions, []).map((segment) => segment.label)).toEqual(['Free', 'Almost Done', 'Ongoing', 'Overloaded']);
    });

    it('reads each count from the key the backend uses for it', () => {
        expect(buildSegments(summary(), statusOptions, []).map((segment) => segment.count)).toEqual([2, 2, 3, 3]);
    });

    it('takes percentages from the filtered population, not from a page', () => {
        expect(buildSegments(summary({ total_users: 6, free: 0, light: 0, moderate: 3, busy: 3 }), statusOptions, []).map((s) => s.percent)).toEqual([
            0, 0, 50, 50,
        ]);
    });

    it('does not divide by zero when nothing matches', () => {
        const empty = summary({ total_users: 0, free: 0, light: 0, moderate: 0, busy: 0 });

        expect(buildSegments(empty, statusOptions, []).map((segment) => segment.percent)).toEqual([0, 0, 0, 0]);
    });

    it('treats every segment as active while no status filter is applied', () => {
        expect(buildSegments(summary(), statusOptions, []).every((segment) => segment.active)).toBe(true);
    });

    it('marks only the selected statuses as active', () => {
        expect(buildSegments(summary(), statusOptions, [3, 4]).map((segment) => segment.active)).toEqual([false, false, true, true]);
    });

    it('ignores status options that have no counter in the summary', () => {
        const withStranger = [...statusOptions, { id: 9, name: 'Unknown', severity: 'info' } as WorkloadStatusOption];

        expect(buildSegments(summary(), withStranger, [])).toHaveLength(4);
    });
});
