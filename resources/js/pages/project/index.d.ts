export interface ProjectMember {
    id: string;
    user: { id: string; name: string; email: string };
    role: { id: string; name: string };
    project_role_id: string;
    is_active: boolean;
}

export interface ProjectMembersData {
    members: ProjectMember[]
    roles: { id: string; name: string }[]
    users: { id: string; name: string }[]
}

export interface CellEditEvent<T> {
    data: T
    field: keyof T
    newValue: any
}
