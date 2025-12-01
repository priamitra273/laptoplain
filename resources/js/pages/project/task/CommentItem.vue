<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Textarea from 'primevue/textarea';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import { Comment } from '..';

const props = defineProps<{
    currentUserId: string;
    comment: Comment;
    taskId: number;
    level?: number;
}>();

const currentLevel = props.level ?? 0;
const replyTarget = ref<string | null>(null);
const replyText = ref('');
const editingCommentId = ref<string | null>(null);
const showAllReplies = ref<{ [key: string]: boolean }>({});
const REPLY_LIMIT = 0;
const menu = ref<any>(null);

// Reaksi yang tersedia
const availableReactions = {
    like: '👍',
    love: '❤️',
    laugh: '😂',
    sad: '😢',
    angry: '😡',
};

// Fungsi untuk menghitung total reaksi per emoticon
const countReactions = (reactionType: string) => {
    if (!props.comment.reaction) return 0;
    return Object.values(props.comment.reaction).filter((r) => r === reactionType).length;
};

// Fungsi untuk mengecek apakah user sudah memberi reaksi tertentu
const hasReacted = (reactionType: string) => {
    if (!props.comment.reaction) return false;
    return props.comment.reaction[props.currentUserId] === reactionType;
};

// Metode untuk mereaksi komentar
const reactToComment = (reaction: string) => {
    router.post(
        route('comments.react', { id: props.comment.id }),
        { reaction },
        {
            onSuccess: () => {
                router.reload({ only: ['comments'] });
            },
        },
    );
};

// Metode lainnya tetap sama
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

const getMenuItems = (comment: any) => {
    const items: any[] = [];
    if (currentLevel < 1) {
        items.push({ label: 'Reply', icon: 'pi pi-reply', command: () => setReply(comment.id) });
    }
    if (comment.user.id === props.currentUserId) {
        items.push(
            { label: 'Edit', icon: 'pi pi-pencil', command: () => startEdit(comment) },
            { label: 'Delete', icon: 'pi pi-trash', command: () => deleteComment(comment.id) },
        );
    }
    return items;
};
</script>

<template>
    <div class="w-full space-y-2">
        <div class="relative w-full max-w-full break-words rounded-lg bg-white p-3 shadow transition-shadow hover:shadow-md">
            <div class="flex w-full gap-2">
                <Avatar :label="comment.user?.name[0]" size="small" class="flex-shrink-0 bg-blue-500 text-xs text-white" />
                <div class="w-full flex-1 overflow-hidden">
                    <!-- Edit Mode -->
                    <div v-if="editingCommentId === comment.id">
                        <Textarea v-model="replyText" rows="2" class="w-full break-words rounded border p-2 text-sm" />
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Button
                                label="Save"
                                icon="pi pi-check"
                                size="small"
                                class="bg-green-500 text-xs hover:bg-green-600"
                                @click="updateComment"
                            />
                            <Button label="Cancel" icon="pi pi-times" size="small" severity="secondary" class="text-xs" @click="cancelEdit" />
                        </div>
                    </div>
                    <!-- View Mode -->
                    <div v-else>
                        <div class="mb-1 flex w-full items-center justify-between">
                            <h4 :class="['truncate font-semibold text-gray-800', currentLevel === 0 ? 'text-sm' : 'text-xs']">
                                {{ comment.user?.name }}
                            </h4>
                            <div class="flex items-center gap-1">
                                <span class="text-xs text-gray-400">{{ moment(comment.created_at).fromNow() }}</span>
                                <template v-if="getMenuItems(comment).length > 0">
                                    <Button
                                        icon="pi pi-ellipsis-v"
                                        class="p-button-text p-button-rounded text-gray-500"
                                        @click="menu?.toggle($event)"
                                    />
                                    <Menu :model="getMenuItems(comment)" :popup="true" ref="menu" />
                                </template>
                            </div>
                        </div>
                        <p :class="['break-words text-gray-700', currentLevel === 0 ? 'text-sm' : 'text-xs']">
                            {{ comment.body }}
                        </p>

                        <!-- Tombol reaksi dengan total reaksi -->
                        <div class="mt-1 flex items-center gap-2">
                            <button
                                v-for="(icon, reaction) in availableReactions"
                                :key="reaction"
                                @click="reactToComment(reaction)"
                                class="flex items-center gap-1 rounded p-1 text-xs hover:bg-gray-100"
                                :class="{ 'bg-gray-200': hasReacted(reaction) }"
                            >
                                {{ icon }}
                                <span class="text-xs text-gray-500">
                                    {{ countReactions(reaction) }}
                                </span>
                            </button>
                        </div>

                        <!-- Reply Box -->
                        <div
                            v-if="replyTarget === comment.id && currentLevel < 1"
                            class="mt-2 flex w-full flex-col gap-2"
                            :class="currentLevel === 0 ? 'pl-3' : 'pl-2'"
                        >
                            <Textarea
                                v-model="replyText"
                                rows="2"
                                placeholder="Write a reply..."
                                class="w-full break-words rounded border p-2 text-sm"
                            />
                            <div class="flex flex-row gap-2">
                                <Button
                                    label="Submit"
                                    icon="pi pi-send"
                                    size="small"
                                    class="bg-blue-500 text-xs hover:bg-blue-600"
                                    @click="submitReply(comment.id)"
                                />
                                <Button label="Cancel" icon="pi pi-times" size="small" severity="secondary" class="text-xs" @click="cancelReply" />
                            </div>
                        </div>

                        <!-- Nested Replies -->
                        <div
                            v-if="comment.replies?.length && currentLevel < 1"
                            class="mt-2 w-full space-y-2 overflow-hidden"
                            :class="currentLevel === 0 ? 'pl-3' : 'pl-2'"
                        >
                            <CommentItem
                                v-for="reply in displayedReplies(comment)"
                                :currentUserId="props.currentUserId"
                                :key="reply.id"
                                :comment="reply"
                                :taskId="props.taskId"
                                :level="currentLevel + 1"
                            />
                            <button
                                v-if="!showAllReplies[comment.id] && remainingReplies(comment) > 0"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs text-gray-500 hover:underline"
                            >
                                View {{ remainingReplies(comment) }} more replies
                            </button>
                            <button
                                v-else-if="showAllReplies[comment.id] && remainingReplies(comment) > 0"
                                @click="toggleShowAllReplies(comment.id)"
                                class="text-xs text-gray-500 hover:underline"
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
