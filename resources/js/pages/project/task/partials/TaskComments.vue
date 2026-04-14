<script setup lang="ts">
import MentionEditor from '@/components/Mentioneditor.vue';
import { ProjectUserOption } from '@/types/task-comment';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import Divider from 'primevue/divider';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import type { Comment, Task } from '../../index.d.ts';
import CommentItem from '../CommentItem.vue';

interface Props {
    task: Task;
    comments: Comment[];
    mentionMembers: ProjectUserOption[];
    currentUserId: number;
}

const props = defineProps<Props>();

const toast = useToast();
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
    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
        <template #title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="pi pi-comments text-teal-500"></i>
                    <h2 class="text-lg font-bold">Comments</h2>
                </div>
                <Chip v-if="props.comments?.length" :label="`${props.comments.length}`" class="bg-teal-100 text-teal-700" />
            </div>
        </template>

        <template #content>
            <Divider class="my-3" />
            <div class="mb-6 rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                <MentionEditor
                    v-model="newComment"
                    :projectMembers="props.mentionMembers"
                    height="200px"
                    placeholder="Write a comment... Use @ to mention someone"
                    class="mb-3"
                />
                <div class="mt-3 flex justify-end">
                    <Button
                        label="Post Comment"
                        icon="pi pi-send"
                        @click="submitComment"
                        :disabled="!newComment.trim() || commentLoading"
                        :loading="commentLoading"
                        class="shadow-md"
                    />
                </div>
            </div>
            <div v-if="props.comments?.length" class="space-y-4">
                <CommentItem
                    v-for="comment in props.comments"
                    :currentUserId="props.currentUserId"
                    :key="comment.id"
                    :comment="comment"
                    :taskId="props.task.id"
                    :projectMembers="props.mentionMembers"
                    class="rounded-lg border border-gray-100 p-4 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                />
            </div>
            <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                <i class="pi pi-comment mb-3 text-4xl opacity-50"></i>
                <p class="italic">No comments yet. Be the first to comment!</p>
            </div>
        </template>
    </Card>
</template>
