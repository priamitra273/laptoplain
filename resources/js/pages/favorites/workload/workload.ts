import { severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import type { DistributionSegment, WorkloadSortColumn, WorkloadStatusOption, WorkloadSummary, WorkloadUser } from './types';

/**
 * Id status di enum WorkloadStatus dipetakan ke kunci hitungannya di summary.
 * Backend memakai nama lain untuk dua di antaranya (`light`, `moderate`).
 */
const countKeyByStatusId: Record<number, 'free' | 'light' | 'moderate' | 'busy'> = {
    1: 'free',
    2: 'light',
    3: 'moderate',
    4: 'busy',
};

type ToneName = ReturnType<typeof severityColor>;

/**
 * Tailwind memindai kelas sebagai teks utuh, jadi setiap nada harus ditulis lengkap
 * di sini alih-alih dirangkai dari potongan string saat runtime.
 */
const toneClasses: Record<ToneName, { text: string; fill: string; soft: string; ring: string }> = {
    primary: { text: 'text-primary', fill: 'bg-primary', soft: 'bg-primary/10', ring: 'ring-primary/30' },
    secondary: { text: 'text-secondary', fill: 'bg-secondary', soft: 'bg-secondary/10', ring: 'ring-secondary/30' },
    success: { text: 'text-success', fill: 'bg-success', soft: 'bg-success/10', ring: 'ring-success/30' },
    info: { text: 'text-info', fill: 'bg-info', soft: 'bg-info/10', ring: 'ring-info/30' },
    warning: { text: 'text-warning', fill: 'bg-warning', soft: 'bg-warning/10', ring: 'ring-warning/30' },
    error: { text: 'text-error', fill: 'bg-error', soft: 'bg-error/10', ring: 'ring-error/30' },
    neutral: { text: 'text-muted', fill: 'bg-accented', soft: 'bg-elevated', ring: 'ring-default' },
};

export const toneOf = (severity: PrimeSeverity | null | undefined) => toneClasses[severityColor(severity)];

/** Persentase anggota yang saat ini berada pada bucket Overloaded. */
export function workloadUtilization(summary: WorkloadSummary): number {
    if (summary.total_users === 0) {
        return 0;
    }

    return Math.round((summary.busy / summary.total_users) * 100);
}

export type WorkloadStatusTab = 'all' | 'free' | 'ongoing' | 'overloaded';

const statusIdsByTab: Record<WorkloadStatusTab, number[]> = {
    all: [],
    free: [1],
    ongoing: [2, 3],
    overloaded: [4],
};

export function statusIdsForWorkloadTab(tab: WorkloadStatusTab): number[] {
    return [...statusIdsByTab[tab]];
}

export function workloadTabForStatusIds(statusIds: number[]): WorkloadStatusTab | undefined {
    if (statusIds.length === 0) {
        return 'all';
    }

    return (Object.entries(statusIdsByTab) as [WorkloadStatusTab, number[]][]).find(
        ([, ids]) => ids.length === statusIds.length && ids.every((id) => statusIds.includes(id)),
    )?.[0];
}

export function workloadDonutSegments(summary: WorkloadSummary) {
    return [
        { label: 'Overloaded', value: summary.busy, color: '#ef4444' },
        { label: 'Other members', value: Math.max(summary.total_users - summary.busy, 0), color: 'rgba(113, 113, 122, 0.2)' },
    ];
}

export function sortWorkloadUsers(users: WorkloadUser[], column: WorkloadSortColumn, direction: 'asc' | 'desc'): WorkloadUser[] {
    const directionMultiplier = direction === 'asc' ? 1 : -1;

    return [...users].sort((firstUser, secondUser) => {
        if (column === 'name') {
            return firstUser.name.localeCompare(secondUser.name) * directionMultiplier;
        }

        const firstValue = column === 'total_tasks' ? firstUser.total_tasks : firstUser.remaining_work_percent;
        const secondValue = column === 'total_tasks' ? secondUser.total_tasks : secondUser.remaining_work_percent;
        const difference = firstValue - secondValue;

        return difference === 0 ? firstUser.name.localeCompare(secondUser.name) : difference * directionMultiplier;
    });
}

/**
 * Segmen batang distribusi. Persentase dihitung terhadap populasi yang lolos filter,
 * bukan terhadap satu halaman — `total_users` di summary sudah bersifat pasca-filter.
 */
export function buildSegments(summary: WorkloadSummary, statusOptions: WorkloadStatusOption[], activeStatusIds: number[]): DistributionSegment[] {
    return statusOptions
        .filter((option) => option.id in countKeyByStatusId)
        .map((option) => {
            const count = summary[countKeyByStatusId[option.id]];

            return {
                id: option.id,
                label: option.name,
                count,
                percent: summary.total_users ? Math.round((count / summary.total_users) * 100) : 0,
                severity: option.severity,
                active: activeStatusIds.length === 0 || activeStatusIds.includes(option.id),
            };
        });
}
