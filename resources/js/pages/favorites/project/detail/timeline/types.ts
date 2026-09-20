import type { PrimeSeverity } from '@/types';

export interface TimelineTask {
    id: string;
    title: string;
    start_date: string | null;
    due_date: string | null;
    progress: number | null;
    status: { name: string; severity: PrimeSeverity | null } | null;
    sub_task_recursive: TimelineTask[];
}
