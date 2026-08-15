import { Role, Team, UserList } from '@/types';

export interface UserFormProps {
    pageTitle?: string;
    user?: UserList;
    teams: Team[];
    roles: Role[];
}

export interface UserForm {
    _method: 'POST' | 'PUT';
    name: string | null;
    email: string | null;
    team_uuid: string | null;
    role_id: number | null;
    is_active: boolean;
    password?: string;
    password_confirmation?: string;
    [key: string]: any;
}
