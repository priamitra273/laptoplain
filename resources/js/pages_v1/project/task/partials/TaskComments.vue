<script setup lang="ts">
import MentionEditor from '@/components/Mentioneditor.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import CommentItem from '@/components/ui/comment/CommentItem.vue';
import { ProjectUserOption } from '@/types/task-comment';
import { router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import type { Comment, Task } from '../../index.d.ts';
import SectionPanel from './SectionPanel.vue';

interface Props {
    task: Task;
    comments: Comment[];
    mentionMembers: ProjectUserOption[];
    currentUserId: number;
}

const props = defineProps<Props>();

const toast = useToast();

const currentUser = usePage().props.auth.user;

const commentLoading = ref(false);
const newComment = ref('');

const submitComment = () => {
    commentLoading.value = true;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = newComment.value;
    const textContent = tempDiv.textContent || tempDiv.innerText || '';

    if (!textContent.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Empty Comment',
            detail: 'Please write a comment before posting.',
            life: 3000,
        });
        commentLoading.value = false;
        return;
    }

    router.post(
        route('comments.store'),
        {
            body: newComment.value,
            commentable_type: 'App\\Models\\Task',
            commentable_id: props.task.id,
            parent_id: null,
        },
        {
            onSuccess: () => {
                newComment.value = '';
                router.reload({ only: ['comments'] });
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Comment posted successfully.',
                    life: 3000,
                });
                commentLoading.value = false;
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Failed',
                    detail: 'Failed to post comment. Please try again.',
                    life: 3000,
                });
                commentLoading.value = false;
            },
            onFinish: () => (commentLoading.value = false),
        },
    );
};
</script>

<template>
    <SectionPanel title="Comments" icon="pi pi-comments" toggleable>
        <template #actions>
            <span
                v-if="props.comments?.length"
                class="rounded-full bg-surface-100 px-2 py-0.5 text-xs font-medium tabular-nums text-surface-600 dark:bg-surface-800 dark:text-surface-300"
            >
                {{ props.comments.length }}
            </span>
        </template>

        <!-- Composer: avatar + unified editor/action bar mirrors the thread items -->
        <div class="flex gap-3">
            <UserAvatar
                :user="currentUser"
                size="!h-9 !w-9"
                fontSize=".8rem"
                class="mt-0.5 hidden shrink-0 ring-2 ring-surface-0 sm:block dark:ring-surface-900"
            />
            <div
                class="comment-composer min-w-0 flex-1 overflow-hidden rounded-xl border border-surface-300 bg-surface-0 shadow-sm transition-colors focus-within:border-primary-400 dark:border-surface-600 dark:bg-surface-900"
            >
                <MentionEditor
                    v-model="newComment"
                    :projectMembers="props.mentionMembers"
                    height="140px"
                    placeholder="Write a comment... Use @ to mention someone"
                />
                <div class="flex items-center gap-2 border-t border-surface-200 px-3 py-2 dark:border-surface-700">
                    <span class="hidden items-center gap-1.5 text-xs text-surface-500 sm:flex dark:text-surface-400">
                        <i class="pi pi-at text-[10px]" />
                        Mention teammates with @
                    </span>
                    <Button
                        label="Post Comment"
                        icon="pi pi-send"
                        size="small"
                        class="ml-auto"
                        :disabled="!newComment.trim() || commentLoading"
                        :loading="commentLoading"
                        @click="submitComment"
                    />
                </div>
            </div>
        </div>

        <!-- Thread -->
        <div v-if="props.comments?.length" class="mt-6 space-y-6 border-t border-surface-200 pt-6 dark:border-surface-700">
            <CommentItem
                v-for="comment in props.comments"
                :key="comment.id"
                :currentUserId="props.currentUserId"
                :comment="comment"
                :taskId="props.task.id"
                :projectMembers="props.mentionMembers"
            />
        </div>
        <div
            v-else
            class="mt-6 flex flex-col items-center justify-center gap-2 border-t border-surface-200 py-10 text-center dark:border-surface-700"
        >
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-surface-100 dark:bg-surface-800">
                <i class="pi pi-comments text-lg text-surface-400 dark:text-surface-500" />
            </div>
            <p class="text-sm text-surface-500 dark:text-surface-400">No comments yet — start the conversation.</p>
        </div>
    </SectionPanel>
</template>

<style scoped>
.comment-composer :deep(.ql-toolbar) {
    border: 0 !important;
    border-bottom: 1px solid var(--p-content-border-color) !important;
    border-radius: 0 !important;
    background: transparent !important;
}

.comment-composer :deep(.ql-container) {
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
}
</style>
