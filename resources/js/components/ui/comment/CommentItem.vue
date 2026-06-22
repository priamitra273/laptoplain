<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import CommentEditor from './CommentEditor.vue';
import CommentHeader from './CommentHeader.vue';
import CommentItemSkeleton from './CommentItemSkeleton.vue';
import CommentReactions from './CommentReactions.vue';
import CommentReplies from './CommentReplies.vue';
import { Comment, CommentReaction, User } from './type';

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
            <div class="flex w-full gap-3">
                <UserAvatar
                    :user="comment.user"
                    :size="currentLevel > 0 ? '!h-7 !w-7' : '!h-9 !w-9'"
                    :fontSize="currentLevel > 0 ? '.7rem' : '.8rem'"
                    class="mt-0.5 shrink-0 ring-2 ring-surface-0 dark:ring-surface-900"
                />

                <div class="min-w-0 flex-1">
                    <div
                        class="-mx-2 rounded-lg px-2 py-1.5 transition-colors duration-200 group-hover:bg-surface-50 motion-reduce:transition-none dark:group-hover:bg-surface-800/40"
                    >
                        <CommentHeader
                            :comment="comment"
                            :currentUserId="CurrentUser.id"
                            :currentLevel="currentLevel"
                            class="flex-1"
                            @reply="setReply"
                            @edit="startEdit"
                            @delete="deleteComment"
                        />

                        <div v-if="editingCommentId === comment.id" class="mt-2">
                            <CommentEditor
                                v-model="replyText"
                                :projectMembers="props.projectMembers"
                                :loading="editLoading"
                                placeholder="Edit your comment..."
                                @submit="updateComment"
                                @cancel="cancelEdit"
                            />
                        </div>

                        <div v-else class="mt-1 space-y-2.5">
                            <div
                                class="prose prose-sm dark:prose-invert overflow-wrap-anywhere max-w-none break-words leading-relaxed text-surface-700 dark:text-surface-300"
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
                                class="mt-3 space-y-2 border-t border-surface-200 pt-3 dark:border-surface-700"
                            >
                                <p class="flex items-center gap-1.5 text-xs font-medium text-surface-500 dark:text-surface-400">
                                    <i class="pi pi-reply text-[10px]" />
                                    Membalas
                                    <span class="text-surface-700 dark:text-surface-200">{{ comment.user?.name }}</span>
                                </p>
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
                        </div>
                    </div>

                    <CommentReplies
                        v-if="editingCommentId !== comment.id"
                        :comment="comment"
                        :taskId="props.taskId"
                        :currentLevel="currentLevel"
                        :currentUserId="props.currentUserId"
                        :projectMembers="props.projectMembers"
                        class="mt-1"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
