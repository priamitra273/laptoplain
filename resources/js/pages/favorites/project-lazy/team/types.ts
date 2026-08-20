export interface TeamRole {
    id: string;
    name: string;
}

export interface TeamMemberUser {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
}

export interface TeamMember {
    id: string;
    is_active: boolean;
    user: TeamMemberUser;
    role: TeamRole;
}

export interface TeamUserOption {
    id: string;
    name: string;
    email: string;
    avatar_url: string | null;
}
