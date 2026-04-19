export interface User {
    id: string | number;
    name: string;
    email?: string;
    avatar_url?: string | null;
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
