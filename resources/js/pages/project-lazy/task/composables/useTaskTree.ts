import type { LazyTaskFormatted, ListTask } from '@/pages/project-lazy';
import { ref, watch, type ComputedRef, type Ref } from 'vue';

export const formatTasks = (list?: ListTask[], level: number = 0): LazyTaskFormatted[] => {
    if (!list || !Array.isArray(list)) {
        return [];
    }

    return list.map((t) => ({
        key: t.id,
        original: t,
        data: {
            id: t.id,
            parent_id: t.parent_id ?? null,
            title: t.title,
            status: t.status ?? undefined,
            type: t.type ?? undefined,
            category: t.category ?? undefined,
            progress: Number(t.progress) || 0,
            users: t.users || [],
            start_date: t.start_date ?? null,
            due_date: t.due_date ?? null,
            completed_at: t.completed_at ?? null,
            is_overdue: t.is_overdue ?? false,
            level,
        },
        children: t.sub_task_recursive ? formatTasks(t.sub_task_recursive, level + 1) : [],
    }));
};

export const sortByRecency = (rows: LazyTaskFormatted[]): LazyTaskFormatted[] => {
    return [...rows].sort((a, b) => {
        const dateA = new Date(a.original.updated_at || a.original.created_at || 0).getTime();
        const dateB = new Date(b.original.updated_at || b.original.created_at || 0).getTime();
        return dateB - dateA;
    });
};

export const findTaskById = (list: ListTask[], taskId: string): ListTask | null => {
    for (const item of list) {
        if (item.id === taskId) {
            return item;
        }
        const found = findTaskById(item.sub_task_recursive || [], taskId);
        if (found) {
            return found;
        }
    }
    return null;
};

export const isDescendant = (rootList: ListTask[], sourceId: string, targetId: string): boolean => {
    const source = findTaskById(rootList, sourceId);
    if (!source) {
        return false;
    }
    const walk = (nodes: ListTask[]): boolean => {
        for (const n of nodes) {
            if (n.id === targetId) {
                return true;
            }
            if (walk(n.sub_task_recursive || [])) {
                return true;
            }
        }
        return false;
    };
    return walk(source.sub_task_recursive || []);
};

export const useTaskTree = (tasks: Ref<ListTask[]> | ComputedRef<ListTask[]>) => {
    const formattedTasks = ref<LazyTaskFormatted[]>([]);

    watch(
        tasks,
        (list) => {
            if (!list || !Array.isArray(list)) {
                formattedTasks.value = [];
                return;
            }

            // temporary disabled
            // formattedTasks.value = sortByRecency(formatTasks(list));

            formattedTasks.value = formatTasks(list);
        },
        { immediate: true, deep: false },
    );

    return {
        formattedTasks,
        findTaskById: (id: string): ListTask | null => findTaskById(tasks.value, id),
        isDescendant: (sourceId: string, targetId: string): boolean => isDescendant(tasks.value, sourceId, targetId),
    };
};
