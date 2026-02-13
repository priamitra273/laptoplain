export interface ProjectMember {
    id: string;
    user: User;
    role: { id: string; name: string };
    project_role_id: string;
    is_active: boolean;
}

export interface ProjectMembersData {
    members: ProjectMember[];
    roles: { id: string; name: string }[];
    users: { id: string; name: string }[];
}

export interface CellEditEvent<T> {
    data: T;
    field: keyof T;
    newValue: any;
}

export interface Task {
    id: string;
    owned_id: string | null;
    parent_id: string | null;
    status_id: string;
    priority_id: string;
    type_id: string;
    created_by: string | null;
    updated_by: string | null;
    deleted_by: string | null;

    emoji: string | null;
    title: string;
    description: string;

    start_date: string;
    due_date: string;

    progress: number;
    sequence_number: number | null;
    is_archived: boolean;

    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    completed_at: string | null;
    is_overdue: boolean;

    project_id: string;

    status: TaskStatus;
    priority: TaskPriority;
    type: TaskType;

    users: TaskUser[];

    sub_task: Task[];
    sub_task_recursive: Task[];
    tags: Tag[];
}

export interface TaskStatus {
    id: string;
    name: string;
    severity: string;
    score: number;
}

export interface TaskPriority {
    id: string;
    name: string;
    severity: string;
}

export interface TaskType {
    id: string;
    name: string;
    severity: string;
}

export interface Tag {
    id: string;
    name: string;
    severity: string;
}

export interface TaskUser {
    id: string;
    name: string;
    pivot: TaskPivot;
}

export interface TaskPivot {
    task_id: string;
    user_id: string;

    created_at: string;
    updated_at: string;

    owned_id: string | null;
    created_by: string | null;
    updated_by: string | null;
    deleted_by: string | null;
}

export interface TaskFormatted {
    key: string;
    data: TaskFormattedData;
    children: TaskFormatted[];
    original: Task;
}

export interface TaskFormattedData {
    id: string;
    title: string;
    status?: TaskStatus;
    priority?: TaskPriority;
    type?: TaskType;
    users: TaskUser[];
    progress: number;
    start_date: string;
    due_date: string;
    created_by: string | null;
    completed_at: string | null;
    is_overdue: boolean;
}

export interface Comment {
    id: string;
    commentable_type: string;
    commentable_id: string;
    user_id: string;
    body: string;
    reaction?: Record<string, string>;
    owned_id: string | null;

    created_by: string | null;
    updated_by: string | null;
    deleted_by: string | null;

    created_at: string;
    updated_at: string | null;
    deleted_at: string | null;

    parent_id: string | null;

    user: CommentUser;
    replies: Comment[];
}

export interface CommentUser {
    id: string;
    uuid: string;
    name: string;
    email: string;
    avatar_url: string | null;

    email_verified_at: string | null;
    is_active: boolean;

    created_by: number | string;
    updated_by: number | string;
    deleted_by: number | string | null;

    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}
