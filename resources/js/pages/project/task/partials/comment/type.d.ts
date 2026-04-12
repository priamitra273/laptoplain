import { Comment, User } from '@/pages/project';

export interface EditorProps {
    modelValue: string;
    projectMembers?: User[];
    placeholder?: string;
    submitLabel?: string;
    submitIcon?: string;
    loading?: boolean;
    height?: string;
}

export interface EditorEmits {
    (e: 'update:modelValue', value: string): void;
    (e: 'submit'): void;
    (e: 'cancel'): void;
}

export interface HeaderProps {
    comment: Comment;
    currentUserId: string | number;
    currentLevel: number;
}

export interface HeaederEmits {
    (e: 'reply', id: string): void;
    (e: 'edit', comment: any): void;
    (e: 'delete', id: string): void;
}

export interface ReplyProps {
    comment: Comment;
    taskId: string;
    currentLevel: number;
    currentUserId?: number;
    projectMembers?: User[];
}
