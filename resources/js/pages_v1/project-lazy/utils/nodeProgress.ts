import type { TaskStatusOption } from '@/pages/project-lazy';

export const isCompletedStatus = (status: TaskStatusOption | null): boolean => (status?.name ?? '').toLowerCase() === 'completed';

export const computeOverdue = (dueDate: string | null, status: TaskStatusOption | null): boolean => {
    if (!dueDate || isCompletedStatus(status)) {
        return false;
    }
    const today = new Date(new Date().toDateString());
    return new Date(dueDate) < today;
};
