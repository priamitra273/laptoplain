import type { TaskReportFilters } from './types';

export function normalizeTaskReportFilters(filters?: TaskReportFilters) {
    return {
        names: [...(filters?.names ?? [])],
        statuses: [...(filters?.statuses ?? [])],
        project_statuses: [...(filters?.project_statuses ?? [])],
        priorities: [...(filters?.priorities ?? [])],
        types: [...(filters?.types ?? [])],
        start_date_from: filters?.start_date_from ?? '',
        start_date_to: filters?.start_date_to ?? '',
        due_date_from: filters?.due_date_from ?? '',
        due_date_to: filters?.due_date_to ?? '',
        search: filters?.search ?? '',
    };
}

export function taskReportFiltersEqual(
    first: TaskReportFilters,
    second: TaskReportFilters,
) {
    return (
        JSON.stringify(normalizeTaskReportFilters(first)) ===
        JSON.stringify(normalizeTaskReportFilters(second))
    );
}

export function resetTaskReportFilters() {
    return normalizeTaskReportFilters();
}
