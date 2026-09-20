export interface TeamMemberUser {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
}

export interface TeamMemberRole {
    id: string;
    name: string;
}

export interface TeamMember {
    id: string;
    is_active: boolean;
    user: TeamMemberUser;
    role: TeamMemberRole;
}

export interface RoleOption {
    id: string;
    name: string;
}

export interface AssignableUser {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
}
