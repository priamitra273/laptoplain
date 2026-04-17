import { PrimeSeverity, ProjectRoleOption } from '@/types';

export interface User {
    id: string | number;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

export interface ProjectMember {
    id: string;
    user: User;
    role: ProjectRoleOption;
    project_role_id: string;
    is_active: boolean;
}

export interface MemberWithAvatar extends ProjectMember {
    user: User;
}

export interface ProjectMembersData {
    members: ProjectMember[];
    roles: ProjectRoleOption[];
    users: User[];
}

export interface Project {
    id: string;
    project_no: string;
    title: string;
    description?: string;
    emoji: string;
    progress: number;
    start_date?: string;
    due_date?: string;
    status?: ProjectStatus;
    priority?: ProjectPriority;
    status_id?: string;
    priority_id?: string;
    created_at?: string;
    updated_at?: string;
    project_members: ProjectMember[];
}

export interface Epic {
    id: string;
    title: string;
    story_points?: number | null;
}

export interface SprintStatus {
    id: string;
    name: string; // 'Planning' | 'Active' | 'Completed'
    severity?: number;
}

export interface Sprint {
    id: string;
    name: string;
    goal?: string;
    duration?: string;
    start_date?: string;
    end_date?: string;
    order?: number;
    retrospective?: string;
    status?: SprintStatus;
    tasks?: Task[];
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

    project?: ProjectOptions;
    category?: TaskCategory;
    creator?: User;
    is_assigned?: boolean;
    is_created_by_me?: boolean;
}

export interface ProjectOptions {
    id: string;
    title: string;
}

export interface TaskCategory {
    id: string;
    name: string; // 'Epic' | 'Story' | 'Issue'
    icon?: string;
    severity?: PrimeSeverity;
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

export interface TaskUser extends User {
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
    parent_id: string | null;
    title: string;
    status?: TaskStatus;
    priority?: TaskPriority;
    type?: TaskType;
    category?: TaskCategory;
    users: TaskUser[];
    progress: number;
    start_date: string | null;
    due_date: string | null;
    created_by: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    level?: number;
}

export interface Comment {
    id: string;
    body: string;
    reactions: CommentReaction[];
    current_user_reaction: string | null;

    created_at: string;
    updated_at: string | null;

    user: User;
    replies: Comment[];
}

export interface CommentReaction {
    reaction: string;
    count: number;
}

export interface ProjectStatus {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

export interface ProjectPriority {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

export interface ProjectTableProps {
    projects?: Project[];
    statuses: ProjectStatus[];
    priorities: ProjectPriority[];
    progresses?: number;
}

export interface ProjectFormProps {
    value?: any;
    visible: boolean;
    statuses: ProjectStatus[];
    priorities: ProjectPriority[];
}

export interface ProjectForm {
    title: string;
    start_date: Date | null;
    due_date: Date | null;
    description: string;
    emoji: string | null;
    status_id: string | null;
    priority_id: string | null;
    owner_id?: number | null;
    owned_id?: number | null;
    [key: string]: any;
}

export interface TabListItem {
    label: string;
    icon: string;
}

export interface ProjectDetailProps {
    project: Project;
    members: MemberWithAvatar[];
    roles: ProjectRoleOption[];
    users: User[];
    tasks: Task[];
    taskTypes: TaskType[];
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    tags: Tag[];
    assignableUsers: User[];
    statuses?: ProjectStatus[];
    priorities?: ProjectPriority[];
    sprints: Sprint[];
    backlog: Task[];
    taskCategories: TaskCategory[];
    epics: Epic[];
}

export interface ProjectDetailHeaderProps {
    project: Project;
    members: MemberWithAvatar[];
    canEdit: boolean;
    isMember: boolean;
}

export interface TaskFormInput {
    status_id: string;
    priority_id: string;
    type_id: string;
    start_date: string | null;
    due_date: string | null;
    progress_value: number;

    [key: string]: any;
}

export type TaskFormField = keyof TaskFormInput;

export interface TaskDetailProps {
    task: Task;
    project: Project;
    assignedUsers: User[];
    comments: Comment[];
    statuses: TaskStatus[];
    priorities: TaskPriority[];
    types: TaskType[];

    isTaskMember: boolean;
    creator?: User;
}
