<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Textarea from 'primevue/textarea';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import { Comment } from '..';

const props = defineProps<{
    comment: Comment;
    taskId: number;
    level?: number;
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
    if (!replyText.value.trim()) return;
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
    if (!replyText.value.trim() || editingCommentId.value === null) return;
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

const cancelEdit = () => (editingCommentId.value = null);
const cancelReply = () => {
    replyTarget.value = null;
    replyText.value = '';
};

// Delete comment
const deleteComment = (id: string) => {
    Swal.fire({
        title: 'Delete Comment?',
        text: 'Are you sure you want to delete this comment?',
        icon: 'warning',
        showCancelButton: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('comments.destroy', { id }), {
                onSuccess: () => router.reload({ only: ['comments'] }),
            });
        }
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
    <div class="w-full">
        <div
            class="group rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600"
        >
            <div class="flex gap-2">
                <!-- Avatar -->
                <Avatar
                    :label="comment.user?.name[0]"
                    size="normal"
                    class="flex-shrink-0 bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm dark:from-blue-600 dark:to-blue-700"
                    style="width: 28px; height: 28px; font-size: 0.75rem"
                />

                <div class="min-w-0 flex-1">
                    <!-- Edit Mode -->
                    <div v-if="editingCommentId === comment.id" class="space-y-2">
                        <Textarea
                            v-model="replyText"
                            rows="2"
                            class="w-full text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                            auto-resize
                        />
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

                        <!-- Body -->
                        <p class="text-xs leading-relaxed text-gray-700 dark:text-gray-300">
                            {{ comment.body }}
                        </p>

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
                            <Textarea
                                v-model="replyText"
                                rows="2"
                                placeholder="Write a reply..."
                                class="w-full text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"
                                auto-resize
                            />
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
                            />
                            <button
                                v-if="!showAllReplies[comment.id] && remainingReplies(comment) > 0"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs font-medium transition-colors hover:text-slate-800 dark:text-blue-400 dark:hover:text-blue-300"
                            >
                                View {{ remainingReplies(comment) }} more {{ remainingReplies(comment) === 1 ? 'reply' : 'replies' }}
                            </button>
                            <button
                                v-else-if="showAllReplies[comment.id]"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs font-medium transition-colors hover:text-slate-800 dark:text-blue-400 dark:hover:text-blue-300"
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
