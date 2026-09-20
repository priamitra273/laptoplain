import type { TaskReportFilterOptions } from './types';

export function normalizeTaskReportFilterOptions(
    options?: Partial<TaskReportFilterOptions> | null,
): TaskReportFilterOptions {
    return {
        creators: options?.creators ?? [],
        project_statuses: options?.project_statuses ?? [],
        statuses: options?.statuses ?? [],
        priorities: options?.priorities ?? [],
        types: options?.types ?? [],
    };
}