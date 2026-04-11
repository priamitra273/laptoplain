<script setup lang="ts">
import { ProjectUserOption } from '@/types/task-comment';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import { Comment } from '..';
import CommentAvatar from './partials/comment/CommentAvatar.vue';
import CommentEditor from './partials/comment/CommentEditor.vue';
import CommentHeader from './partials/comment/CommentHeader.vue';
import CommentItemSkeleton from './partials/comment/CommentItemSkeleton.vue';
import CommentReactions from './partials/comment/CommentReactions.vue';
import CommentReplies from './partials/comment/CommentReplies.vue';

interface Props {
    comment: Comment;
    taskId: string;
    level?: number;
    currentUserId?: number;
    projectMembers?: ProjectUserOption[];
}

const props = defineProps<Props>();

const CurrentUser = usePage().props.auth.user;

const currentLevel = props.level ?? 0;
const replyTarget = ref<string | null>(null);
const replyText = ref('');
const editingCommentId = ref<string | null>(null);

// ===== Loading states =====
const replyLoading = ref(false);
const editLoading = ref(false);
const deleteLoadingId = ref<string | null>(null);

const confirm = useConfirm();
const toast = useToast();

const normalizeReactions = (reactions: any) => {
    if (!reactions) return {};
    const normalized: { [key: string]: string } = {};
    for (const [key, value] of Object.entries(reactions)) {
        normalized[String(key)] = value as string;
    }
    return normalized;
};

const localReactions = ref<{ [key: string]: string }>(normalizeReactions(props.comment.reaction));

const getCurrentUserId = () => String(CurrentUser.id);

const reactToComment = async (reaction: string) => {
    try {
        const userId = getCurrentUserId();

        if (localReactions.value[userId] === reaction) {
            delete localReactions.value[userId];
        } else {
            localReactions.value[userId] = reaction;
        }

        const response = await axios.post(route('comments.react', props.comment.id), { reaction });

        if (response.data.success) {
            localReactions.value = normalizeReactions(response.data.reactions);
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update reaction', life: 3000 });
        console.error('Failed to react:', error);
    }
};

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
        route('comments.update', editingCommentId.value),
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
            router.delete(route('comments.destroy', id), {
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

watch(
    () => props.comment.reaction,
    (newReactions) => {
        localReactions.value = normalizeReactions(newReactions);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div class="w-full">
        <CommentItemSkeleton v-if="deleteLoadingId === comment.id" :comment="comment" />

        <div
            v-else
            class="group rounded-lg border border-gray-200 bg-white p-2 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600"
        >
            <div class="flex gap-2">
                <CommentAvatar :user="comment.user" />

                <div class="min-w-0 flex-1">
                    <div v-if="editingCommentId === comment.id">
                        <CommentEditor
                            v-model="replyText"
                            :projectMembers="props.projectMembers"
                            :loading="editLoading"
                            placeholder="Edit your comment..."
                            @submit="updateComment"
                            @cancel="cancelEdit"
                        />
                    </div>

                    <div v-else class="space-y-1">
                        <CommentHeader
                            :comment="comment"
                            :currentUserId="CurrentUser.id"
                            :currentLevel="currentLevel"
                            @reply="setReply"
                            @edit="startEdit"
                            @delete="deleteComment"
                        />

                        <div
                            class="prose prose-sm dark:prose-invert overflow-wrap-anywhere max-w-none break-words text-xs leading-relaxed text-gray-700 dark:text-gray-300"
                            v-html="comment.body"
                        ></div>

                        <CommentReactions :reactions="localReactions" :currentUserId="getCurrentUserId()" @react="reactToComment" />

                        <div v-if="replyTarget === comment.id && currentLevel < 1" class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                            <CommentEditor
                                v-model="replyText"
                                :projectMembers="props.projectMembers"
                                :loading="replyLoading"
                                placeholder="Write a reply... Use @ to mention someone"
                                submitLabel="Reply"
                                submitIcon="pi pi-send"
                                @submit="submitReply(comment.id)"
                                @cancel="cancelReply"
                            />
                        </div>

                        <CommentReplies
                            :comment="comment"
                            :taskId="props.taskId"
                            :currentLevel="currentLevel"
                            :currentUserId="props.currentUserId"
                            :projectMembers="props.projectMembers"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
