import type { InjectionKey } from 'vue';
import type { BacklogTask, Epic, TaskPriorityOption, TaskStatusOption } from '../../index';

export interface BacklogContext {
    projectId: string;
    epics: Epic[];
    taskPriorities: TaskPriorityOption[];
    taskStatuses: TaskStatusOption[];
    canAct: boolean;
    canSprintCreate: boolean;
    canSprintUpdate: boolean;
    canSprintDelete: boolean;

    // Actions
    editTask: (task: BacklogTask) => void;
    addEpic: (task: BacklogTask, epicId: string | null) => Promise<void>;
    updatePriority: (task: BacklogTask, priorityId: string) => Promise<void>;
    viewEpic: (epicId: string) => void;
    toggleSelect: (task: BacklogTask, checked: boolean) => void;
    openTaskMenu: (event: MouseEvent, task: BacklogTask, sprintId: string | null) => void;
    addTask: (task: BacklogTask | null, sprintId?: string | null) => void;
    addParent: (epicId: string, sprintId?: string | null) => void;
}

export const BacklogKey: InjectionKey<BacklogContext> = Symbol('BacklogKey');
