<script setup lang="ts">
import { getInitials } from '@/lib/utils';
import { CommentReaction, User } from '@/pages/project';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import { Comment } from '..';
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
    projectMembers?: User[];
    showReply?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showReply: () => true,
});

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

const currentUserReaction = ref(props.comment.current_user_reaction);
const localReactions = ref<CommentReaction[]>(props.comment.reactions);

const reactToComment = async (reaction: string) => {
    try {
        toggleReaction(reaction);

        const response = await axios.post(route('comments.react', props.comment.id), { reaction });

        if (response.data.success) {
            localReactions.value = response.data.data;
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update reaction', life: 3000 });
        console.error('Failed to react:', error);
    }
};

const toggleReaction = (reaction: string) => {
    if (currentUserReaction.value === reaction) {
        decreaseReactionCount(reaction);
        currentUserReaction.value = null;
    } else {
        decreaseReactionCount(currentUserReaction.value);

        const newReaction: CommentReaction = {
            reaction,
            count: 1,
        };

        const index = localReactions.value.findIndex((r) => r.reaction === reaction);

        if (index !== -1) {
            localReactions.value[index].count++;
        } else {
            localReactions.value.push(newReaction);
        }

        currentUserReaction.value = reaction;
    }

    // remote empty reaction
    localReactions.value = localReactions.value.filter((r) => r.count > 0);
};

const decreaseReactionCount = (reaction: string | null) => {
    const index = localReactions.value.findIndex((r) => r.reaction === reaction);

    if (index !== -1) {
        localReactions.value[index].count--;
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
</script>

<template>
    <div class="w-full">
        <CommentItemSkeleton v-if="deleteLoadingId === comment.id" :comment="comment" />

        <div v-else class="group">
            <div class="">
                <div class="flex w-full gap-2">
                    <Avatar :image="comment.user?.avatar_url ?? undefined" :label="getInitials(comment.user?.name ?? '')" shape="circle" />

                    <div class="min-w-0 flex-1 space-y-3">
                        <CommentHeader
                            :comment="comment"
                            :currentUserId="CurrentUser.id"
                            :currentLevel="currentLevel"
                            class="flex-1"
                            @reply="setReply"
                            @edit="startEdit"
                            @delete="deleteComment"
                        />

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

                        <div v-else class="space-y-3">
                            <div
                                class="prose prose-sm dark:prose-invert overflow-wrap-anywhere max-w-none break-words leading-relaxed text-gray-700 dark:text-gray-300"
                                v-html="comment.body"
                            ></div>

                            <CommentReactions
                                :reactions="localReactions"
                                :current-user-reaction
                                :show-reply="props.showReply"
                                @react="reactToComment"
                                @reply="setReply(comment.id)"
                            />

                            <div
                                v-if="replyTarget === comment.id && currentLevel < 1"
                                class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-700"
                            >
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
    </div>
</template>
