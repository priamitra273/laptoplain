import type { Epic, Sprint, Task, TaskPriority, TaskStatus, User } from '..';
import type { InjectionKey } from 'vue';

export interface BacklogContext {
    projectId: string;
    epics: Epic[];
    taskPriorities: TaskPriority[];
    taskStatuses: TaskStatus[];
    canAct: boolean;
    
    // Actions
    editTask: (task: any) => void;
    addEpic: (task: any, epicId: string | null) => Promise<void>;
    updatePriority: (task: any, priorityId: string) => Promise<void>;
    viewEpic: (epicId: string) => void;
    toggleSelect: (task: any, checked: boolean) => void;
    openTaskMenu: (event: MouseEvent, task: any, sprintId: string | null) => void;
    addTask: (task: any, sprintId?: string) => void;
    addParent: (epicId: string, sprintId?: string) => void;
}

export const BacklogKey: InjectionKey<BacklogContext> = Symbol('BacklogKey');
