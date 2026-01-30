<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Editor from 'primevue/editor';
import Menu from 'primevue/menu';
import { useConfirm } from 'primevue/useconfirm';
import { ref } from 'vue';
import { Comment } from '..';

const props = defineProps<{
    comment: Comment;
    taskId: string;
    level?: number;
    currentUserId?: number;
}>();

// Current logged-in user
const CurrentUser = usePage().props.auth.user;

const currentLevel = props.level ?? 0;
const replyTarget = ref<string | null>(null);
const replyText = ref('');
const editingCommentId = ref<string | null>(null);
const showAllReplies = ref<{ [key: string]: boolean }>({});
const REPLY_LIMIT = 0;
const menu = ref<any>(null);

const confirm = useConfirm();

// Helper functions for avatar
const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getUserColor = (userId: number) => `hsl(${(userId * 60) % 360}, 70%, 60%)`;

// Available reactions
const availableReactions = {
    like: '👍',
    love: '❤️',
    laugh: '😂',
    sad: '😢',
    angry: '😡',
};

// Count reactions
const countReactions = (reactionType: string) => {
    if (!props.comment.reaction) return 0;
    return Object.values(props.comment.reaction).filter((r) => r === reactionType).length;
};

// Check if current user reacted
const hasReacted = (reactionType: string) => {
    if (!props.comment.reaction) return false;
    return props.comment.reaction[CurrentUser.id] === reactionType;
};

// React to comment
const reactToComment = (reaction: string) => {
    router.post(route('comments.react', { id: props.comment.id }), { reaction }, { onSuccess: () => router.reload({ only: ['comments'] }) });
};

// Reply methods
const setReply = (id: string) => {
    replyTarget.value = id;
    replyText.value = '';
};

const submitReply = (parentId: string) => {
    // Check if comment is empty or only contains whitespace/empty HTML tags
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = replyText.value;
    const textContent = tempDiv.textContent || tempDiv.innerText || '';

    if (!textContent.trim()) return;

    router.post(
        route('comments.store'),
        {
            body: replyText.value,
            commentable_type: 'App\\Models\\Task',
            commentable_id: props.taskId,
            parent_id: parentId,
        },
        {
            onSuccess: () => {
                replyText.value = '';
                replyTarget.value = null;
                router.reload({ only: ['comments'] });
            },
        },
    );
};

// Edit comment
const startEdit = (comment: any) => {
    editingCommentId.value = comment.id;
    replyText.value = comment.body;
};

const updateComment = () => {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = replyText.value;
    const textContent = tempDiv.textContent || tempDiv.innerText || '';

    if (!textContent.trim() || editingCommentId.value === null) return;

    router.put(
        route('comments.update', { id: editingCommentId.value }),
        { body: replyText.value },
        {
            onSuccess: () => {
                editingCommentId.value = null;
                replyText.value = '';
                router.reload({ only: ['comments'] });
            },
        },
    );
};

const cancelEdit = () => {
    editingCommentId.value = null;
    replyText.value = '';
};

const cancelReply = () => {
    replyTarget.value = null;
    replyText.value = '';
};

// Delete comment
const deleteComment = (id: string) => {
    confirm.require({
        message: 'Are you sure you want to delete this comment?',
        header: 'Delete Comment',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Yes, delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('comments.destroy', { id }), {
                onSuccess: () => router.reload({ only: ['comments'] }),
            });
        },
    });
};

// Displayed replies
const displayedReplies = (comment: any) => {
    const showAll = showAllReplies.value[comment.id] ?? false;
    if (showAll) return comment.replies;
    return comment.replies?.slice(0, REPLY_LIMIT) || [];
};

const remainingReplies = (comment: any) => {
    return (comment.replies?.length || 0) - REPLY_LIMIT;
};

const toggleShowAllReplies = (commentId: string) => {
    showAllReplies.value[commentId] = !showAllReplies.value[commentId];
};

// Menu items
const getMenuItems = (comment: any) => {
    const items: any[] = [];
    if (currentLevel < 1) {
        items.push({ label: 'Reply', icon: 'pi pi-reply', command: () => setReply(comment.id) });
    }
    if (comment.user.id === CurrentUser.id) {
        items.push(
            { label: 'Edit', icon: 'pi pi-pencil', command: () => startEdit(comment) },
            { label: 'Delete', icon: 'pi pi-trash', command: () => deleteComment(comment.id) },
        );
    }
    return items;
};
</script>

<template>
    <ConfirmDialog />
    <div class="w-full">
        <div
            class="group rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600"
        >
            <div class="flex gap-2">
                <!-- Avatar with image support -->
                <Avatar
                    v-if="comment.user?.avatar_url && comment.user.avatar_url !== '/images/default-avatar.png'"
                    :image="comment.user.avatar_url"
                    size="normal"
                    shape="circle"
                    class="flex-shrink-0 border-2 border-white shadow-sm dark:border-gray-800"
                    style="width: 32px; height: 32px"
                />
                <Avatar
                    v-else
                    :label="getInitials(comment.user?.name || 'U')"
                    size="normal"
                    shape="circle"
                    class="flex-shrink-0 border-2 border-white text-white shadow-sm dark:border-gray-800"
                    :style="{
                        backgroundColor: getUserColor(Number(comment.user?.id) || 0),
                        color: 'white',
                        fontWeight: '600',
                        width: '32px',
                        height: '32px',
                        fontSize: '0.75rem',
                    }"
                />

                <div class="min-w-0 flex-1">
                    <!-- Edit Mode -->
                    <div v-if="editingCommentId === comment.id" class="space-y-2">
                        <Editor v-model="replyText" editorStyle="height: 120px" class="dark:border-gray-600">
                            <template v-slot:toolbar>
                                <span class="ql-formats">
                                    <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                                    <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                                    <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                                </span>
                            </template>
                        </Editor>
                        <div class="flex gap-1">
                            <Button
                                label="Save"
                                icon="pi pi-check"
                                size="small"
                                severity="success"
                                @click="updateComment"
                                class="shadow-sm hover:shadow"
                            />
                            <Button
                                label="Cancel"
                                icon="pi pi-times"
                                size="small"
                                severity="secondary"
                                text
                                @click="cancelEdit"
                                class="dark:text-gray-300 dark:hover:bg-gray-700"
                            />
                        </div>
                    </div>

                    <!-- View Mode -->
                    <div v-else class="space-y-1">
                        <!-- Header -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-semibold text-gray-900 dark:text-gray-100">
                                    {{ comment.user?.name }}
                                </span>
                                <span class="ml-1.5 text-xs text-gray-400 dark:text-gray-500">
                                    {{ moment(comment.created_at).fromNow() }}
                                </span>
                            </div>

                            <Button
                                v-if="getMenuItems(comment).length > 0"
                                icon="pi pi-ellipsis-v"
                                text
                                rounded
                                size="small"
                                class="h-6 w-6 text-gray-400 opacity-0 transition-opacity group-hover:opacity-100 dark:text-gray-500 dark:hover:bg-gray-700"
                                @click="menu?.toggle($event)"
                            />
                            <Menu :model="getMenuItems(comment)" :popup="true" ref="menu" />
                        </div>

                        <!-- Body with HTML support -->
                        <div
                            class="prose prose-sm dark:prose-invert max-w-none text-xs leading-relaxed text-gray-700 dark:text-gray-300"
                            v-html="comment.body"
                        ></div>

                        <!-- Reactions -->
                        <div class="flex items-center gap-1 pt-0.5">
                            <button
                                v-for="(icon, reaction) in availableReactions"
                                :key="reaction"
                                @click="reactToComment(reaction)"
                                class="flex items-center gap-0.5 rounded-md px-1.5 py-0.5 text-xs transition-all duration-150 hover:scale-105 hover:bg-gray-100 dark:hover:bg-gray-700"
                                :class="{
                                    'bg-blue-50 ring-1 ring-blue-200 dark:bg-blue-900/30 dark:ring-blue-800': hasReacted(reaction),
                                    'hover:shadow-sm': countReactions(reaction) > 0,
                                }"
                            >
                                <span class="text-sm">{{ icon }}</span>
                                <span
                                    v-if="countReactions(reaction) > 0"
                                    class="text-xs font-medium text-gray-600 dark:text-gray-400"
                                    :class="{ 'text-blue-600 dark:text-blue-400': hasReacted(reaction) }"
                                >
                                    {{ countReactions(reaction) }}
                                </span>
                            </button>
                        </div>

                        <!-- Reply Box -->
                        <div
                            v-if="replyTarget === comment.id && currentLevel < 1"
                            class="mt-2 space-y-1.5 border-t border-gray-200 pt-2 dark:border-gray-700"
                        >
                            <Editor v-model="replyText" editorStyle="height: 120px" class="dark:border-gray-600">
                                <template v-slot:toolbar>
                                    <span class="ql-formats">
                                        <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                                        <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                                        <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                                    </span>
                                </template>
                            </Editor>
                            <div class="flex gap-1">
                                <Button
                                    label="Reply"
                                    icon="pi pi-send"
                                    size="small"
                                    @click="submitReply(comment.id)"
                                    class="shadow-sm hover:shadow"
                                />
                                <Button
                                    label="Cancel"
                                    size="small"
                                    severity="secondary"
                                    text
                                    @click="cancelReply"
                                    class="dark:text-gray-300 dark:hover:bg-gray-700"
                                />
                            </div>
                        </div>

                        <!-- Nested Replies -->
                        <div
                            v-if="comment.replies?.length && currentLevel < 1"
                            class="mt-2 space-y-1.5 border-l-2 border-gray-200 pl-2 dark:border-gray-700"
                        >
                            <CommentItem
                                v-for="reply in displayedReplies(comment)"
                                :key="reply.id"
                                :comment="reply"
                                :taskId="props.taskId"
                                :level="currentLevel + 1"
                                :currentUserId="props.currentUserId"
                            />
                            <button
                                v-if="!showAllReplies[comment.id] && remainingReplies(comment) > 0"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs font-medium text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                View {{ remainingReplies(comment) }} more {{ remainingReplies(comment) === 1 ? 'reply' : 'replies' }}
                            </button>
                            <button
                                v-else-if="showAllReplies[comment.id]"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs font-medium text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                Show less
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
