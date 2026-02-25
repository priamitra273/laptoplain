<script setup lang="ts">
import MentionEditor from '@/components/Mentioneditor.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import { Comment } from '..';

const props = defineProps<{
    comment: Comment;
    taskId: string;
    level?: number;
    currentUserId?: number;
    projectMembers?: { id: number | string; name: string }[];
}>();

const CurrentUser = usePage().props.auth.user;

const currentLevel = props.level ?? 0;
const replyTarget = ref<string | null>(null);
const replyText = ref('');
const editingCommentId = ref<string | null>(null);
const showAllReplies = ref<{ [key: string]: boolean }>({});
const REPLY_LIMIT = 0;
const menu = ref<any>(null);

// ===== Loading states =====
const replyLoading = ref(false);
const editLoading = ref(false);
const deleteLoadingId = ref<string | null>(null);

const confirm = useConfirm();
const toast = useToast();

// Normalize reactions
const normalizeReactions = (reactions: any) => {
    if (!reactions) return {};
    const normalized: { [key: string]: string } = {};
    for (const [key, value] of Object.entries(reactions)) {
        normalized[String(key)] = value as string;
    }
    return normalized;
};

const localReactions = ref<{ [key: string]: string }>(normalizeReactions(props.comment.reaction));

watch(
    () => props.comment.reaction,
    (newReactions) => {
        localReactions.value = normalizeReactions(newReactions);
    },
    { deep: true, immediate: true },
);

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getUserColor = (userId: number) => `hsl(${(userId * 60) % 360}, 70%, 60%)`;

const availableReactions = {
    like: '👍',
    love: '❤️',
    laugh: '😂',
    sad: '😢',
    angry: '😡',
};

const getCurrentUserId = () => String(CurrentUser.id);

const countReactions = (reactionType: string) => {
    if (!localReactions.value) return 0;
    return Object.values(localReactions.value).filter((r) => r === reactionType).length;
};

const hasReacted = (reactionType: string) => {
    if (!localReactions.value) return false;
    return localReactions.value[getCurrentUserId()] === reactionType;
};

const reactToComment = async (reaction: string) => {
    try {
        const userId = getCurrentUserId();
        if (localReactions.value[userId] === reaction) {
            delete localReactions.value[userId];
        } else {
            localReactions.value[userId] = reaction;
        }

        const response = await axios.post(route('comments.react', { id: props.comment.id }), { reaction });
        if (response.data.success) {
            localReactions.value = normalizeReactions(response.data.reactions);
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update reaction', life: 3000 });
        console.error('Failed to react:', error);
    }
};

// ===== Reply =====
const setReply = (id: string) => {
    replyTarget.value = id;
    replyText.value = '';
};

const submitReply = (parentId: string) => {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = replyText.value;
    const textContent = tempDiv.textContent || tempDiv.innerText || '';
    if (!textContent.trim()) return;

    replyLoading.value = true;

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
            onError: () => {
                toast.add({ severity: 'error', summary: 'Failed', detail: 'Failed to post reply.', life: 3000 });
            },
            onFinish: () => {
                replyLoading.value = false;
            },
        },
    );
};

// ===== Edit =====
const startEdit = (comment: any) => {
    editingCommentId.value = comment.id;
    replyText.value = comment.body;
};

const updateComment = () => {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = replyText.value;
    const textContent = tempDiv.textContent || tempDiv.innerText || '';
    if (!textContent.trim() || editingCommentId.value === null) return;

    editLoading.value = true;

    router.put(
        route('comments.update', { id: editingCommentId.value }),
        { body: replyText.value },
        {
            onSuccess: () => {
                editingCommentId.value = null;
                replyText.value = '';
                router.reload({ only: ['comments'] });
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Failed', detail: 'Failed to update comment.', life: 3000 });
            },
            onFinish: () => {
                editLoading.value = false;
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

// ===== Delete =====
const deleteComment = (id: string) => {
    confirm.require({
        message: 'Are you sure you want to delete this comment?',
        header: 'Delete Comment',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Yes, delete',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => {
            deleteLoadingId.value = id;
            router.delete(route('comments.destroy', { id }), {
                onSuccess: () => {
                    router.reload({ only: ['comments'] });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Failed', detail: 'Failed to delete comment.', life: 3000 });
                },
                onFinish: () => {
                    deleteLoadingId.value = null;
                },
            });
        },
    });
};

// ===== Replies =====
const displayedReplies = (comment: any) => {
    const showAll = showAllReplies.value[comment.id] ?? false;
    if (showAll) return comment.replies;
    return comment.replies?.slice(0, REPLY_LIMIT) || [];
};

const remainingReplies = (comment: any) => (comment.replies?.length || 0) - REPLY_LIMIT;

const toggleShowAllReplies = (commentId: string) => {
    showAllReplies.value[commentId] = !showAllReplies.value[commentId];
};

// ===== Menu =====
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
        <!-- Skeleton saat delete loading -->
        <div
            v-if="deleteLoadingId === comment.id"
            class="animate-pulse rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="flex gap-2">
                <div class="h-8 w-8 flex-shrink-0 rounded-full bg-gray-200 dark:bg-gray-700" />
                <div class="flex-1 space-y-2 pt-1">
                    <div class="h-3 w-1/4 rounded bg-gray-200 dark:bg-gray-700" />
                    <div class="h-3 w-3/4 rounded bg-gray-200 dark:bg-gray-700" />
                </div>
            </div>
        </div>

        <div
            v-else
            class="group rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600"
        >
            <div class="flex gap-2">
                <!-- Avatar -->
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
                        <MentionEditor v-model="replyText" :projectMembers="props.projectMembers" height="120px" placeholder="Edit your comment..." />
                        <div class="flex gap-1">
                            <Button
                                label="Save"
                                icon="pi pi-check"
                                size="small"
                                severity="success"
                                :loading="editLoading"
                                :disabled="editLoading"
                                @click="updateComment"
                                class="shadow-sm hover:shadow"
                            />
                            <Button
                                label="Cancel"
                                icon="pi pi-times"
                                size="small"
                                severity="secondary"
                                text
                                :disabled="editLoading"
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
                        <div
                            class="prose prose-sm dark:prose-invert overflow-wrap-anywhere max-w-none break-words text-xs leading-relaxed text-gray-700 dark:text-gray-300"
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
                            <MentionEditor
                                v-model="replyText"
                                :projectMembers="props.projectMembers"
                                height="120px"
                                placeholder="Write a reply... Use @ to mention someone"
                            />
                            <div class="flex gap-1">
                                <Button
                                    label="Reply"
                                    icon="pi pi-send"
                                    size="small"
                                    :loading="replyLoading"
                                    :disabled="replyLoading"
                                    @click="submitReply(comment.id)"
                                    class="shadow-sm hover:shadow"
                                />
                                <Button
                                    label="Cancel"
                                    size="small"
                                    severity="secondary"
                                    text
                                    :disabled="replyLoading"
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
                                :projectMembers="props.projectMembers"
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
