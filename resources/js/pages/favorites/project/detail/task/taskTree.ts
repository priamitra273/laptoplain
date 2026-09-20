import { isTaskStatusDone } from '@/lib/utils';
import type { ExpandedState } from '@tanstack/vue-table';
import type { DropMode, ListTask, TaskFilters, TaskMove } from './types';

export const flattenTasks = (tasks: ListTask[]): ListTask[] => tasks.flatMap((task) => [task, ...flattenTasks(task.sub_task_recursive)]);

export const hasTaskFilters = (filters: TaskFilters): boolean => !!(filters.search.trim() || filters.status);

/** Sudah pindah ke `@/lib/taskTree` karena dipakai komponen bersama; di-ekspor ulang supaya pemakai lama tidak perlu berubah. */
export { parentOptionTree } from '@/lib/taskTree';

/** Induk yang tidak cocok tetap hadir sebagai konteks, bukan dihitung sebagai hasil. */
export function filterTaskTree(tasks: ListTask[], filters: TaskFilters): { tree: ListTask[]; matchedIds: Set<string> } {
    const query = filters.search.trim().toLocaleLowerCase();
    const matchedIds = new Set<string>();
    const walk = (nodes: ListTask[]): ListTask[] =>
        nodes.flatMap((task) => {
            const children = walk(task.sub_task_recursive);
            const matches =
                (!query || task.title.toLocaleLowerCase().includes(query)) &&
                (!filters.status || task.status?.id === filters.status);
            if (matches) matchedIds.add(task.id);
            return matches || children.length ? [{ ...task, sub_task_recursive: children }] : [];
        });
    return { tree: walk(tasks), matchedIds };
}

export const branchIds = (tasks: ListTask[]): string[] =>
    flattenTasks(tasks)
        .filter((task) => task.sub_task_recursive.length)
        .map((task) => task.id);

export function visibleTaskCount(tasks: ListTask[], expanded: ExpandedState): number {
    return tasks.reduce(
        (count, task) => count + 1 + (expanded === true || expanded[task.id] ? visibleTaskCount(task.sub_task_recursive, expanded) : 0),
        0,
    );
}

export function computeTaskMove(tasks: ListTask[], sourceId: string, targetId: string | null, mode: DropMode): TaskMove | null {
    const flat = flattenTasks(tasks);
    const source = flat.find((task) => task.id === sourceId);
    const target = flat.find((task) => task.id === targetId);
    if (!source || (mode !== 'root' && (!target || target.id === sourceId))) return null;
    const parentId = mode === 'root' ? null : mode === 'inside' ? target!.id : target!.parent_id;
    if (parentId === source.id || flattenTasks(source.sub_task_recursive).some((task) => task.id === parentId)) return null;
    const siblings = parentId === null ? tasks : flat.find((task) => task.id === parentId)?.sub_task_recursive;
    if (!siblings) return null;
    const remaining = siblings.filter((task) => task.id !== sourceId);
    const targetIndex = remaining.findIndex((task) => task.id === targetId);
    if (mode !== 'root' && mode !== 'inside' && targetIndex < 0) return null;
    const position = mode === 'root' || mode === 'inside' ? remaining.length : targetIndex + (mode === 'after' ? 1 : 0);
    if (source.parent_id === parentId && siblings.findIndex((task) => task.id === sourceId) === position) return null;
    return { taskId: sourceId, parentId, position };
}

/** completed_at adalah timestamp; kolom ini menampilkan tanggal kalender yang dikirim server. */
export const completionDate = (value: string | null): string | null => (value ? value.slice(0, 10) : null);

export function taskTotals(tasks: ListTask[]) {
    const flat = flattenTasks(tasks);
    return {
        total: flat.length,
        completed: flat.filter((task) => isTaskStatusDone(task.status?.name)).length,
        inProgress: flat.filter((task) => task.status?.name.toLowerCase().replace(/\s/g, '') === 'inprogress').length,
        overdue: flat.filter((task) => task.is_overdue).length,
    };
}
