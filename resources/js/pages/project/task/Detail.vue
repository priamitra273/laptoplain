<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';
import { Comment } from '..';
import CommentItem from './CommentItem.vue';

const props = defineProps<{
    currentUserId: string;
    task: any;
    project: any;
    subTasks: any[];
    assignedUsers: any[];
    assignableUsers: any[];
    statuses: any[];
    priorities: any[];
    types: any[];
    isPM: boolean;
    comments: Comment[];
}>();

const newComment = ref('');

const currentUserId = usePage().props.auth.user.id;

const goBack = () => {
    router.visit(route('project.show', { encoded: props.project.id }));
};

const goToProject = () => props.project?.id && router.visit(route('project.show', { encoded: props.project.id }));
const formatDate = (date: string | undefined) => (date ? moment(date).format('DD MMM YYYY') : '-');

const submitComment = () => {
    if (!newComment.value.trim()) return;

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
            },
        },
    );
};
</script>

<template>
    <Head :title="`Task Detail - ${props.task.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <!-- HEADER -->
            <Card class="rounded-xl border shadow-md">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex cursor-pointer items-center gap-3" @click="goToProject">
                            <span class="text-4xl">{{ props.project.emoji }}</span>
                            <div>
                                <h1 class="text-2xl font-bold">{{ props.task.title }}</h1>
                                <p class="text-sm text-gray-500">
                                    Project: <span class="font-semibold">{{ props.project.title }}</span>
                                </p>
                            </div>
                        </div>
                        <Button label="Back" icon="pi pi-arrow-left" severity="secondary" @click="goBack" />
                    </div>
                </template>
            </Card>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <!-- LEFT COLUMN -->
                <div class="space-y-6">
                    <Card class="rounded-xl shadow-md">
                        <template #title><h2 class="font-semibold">Description</h2></template>
                        <template #content>
                            <div
                                class="prose prose-sm max-h-60 overflow-auto break-words"
                                v-html="props.task.description || '<p>No description</p>'"
                            />
                        </template>
                    </Card>

                    <Card class="rounded-xl shadow-md">
                        <template #title><h2 class="font-semibold">Details</h2></template>
                        <template #content>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="font-medium">Status</p>
                                    <Tag :value="props.task.status?.name" :severity="props.task.status?.severity" />
                                </div>
                                <div>
                                    <p class="font-medium">Priority</p>
                                    <Tag :value="props.task.priority?.name" :severity="props.task.priority?.severity" />
                                </div>
                                <div>
                                    <p class="font-medium">Type</p>
                                    <Tag :value="props.task.type?.name" :severity="props.task.type?.severity" />
                                </div>
                                <div>
                                    <p class="font-medium">Progress</p>
                                    <Tag :value="`${props.task.progress}%`" severity="success" />
                                </div>
                                <div>
                                    <p class="font-medium">Start Date</p>
                                    <p>{{ formatDate(props.task.start_date) }}</p>
                                </div>
                                <div>
                                    <p class="font-medium">Due Date</p>
                                    <p>{{ formatDate(props.task.due_date) }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #title><h2 class="font-semibold">Assigned Users</h2></template>
                        <template #content>
                            <div class="flex flex-wrap gap-2">
                                <Avatar v-for="user in props.assignedUsers" :key="user.id" :label="user.name.charAt(0)" shape="circle" />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="space-y-6 xl:col-span-2">
                    <Card>
                        <template #title>
                            <h2 class="font-semibold">Subtasks</h2>
                        </template>
                        <template #content>
                            <div v-if="props.subTasks.length" class="space-y-4">
                                <div v-for="subTask in props.subTasks" :key="subTask.id" class="rounded-lg border p-4">
                                    <div class="mb-2 flex items-center justify-between">
                                        <p class="max-w-[80%] truncate text-lg font-semibold">{{ subTask.title }}</p>
                                        <Tag :value="subTask.status?.name" :severity="subTask.status?.severity" />
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <p class="mb-1 font-medium">Description</p>
                                        <p
                                            class="max-h-40 overflow-auto break-words"
                                            v-html="subTask.description || '<span class=\'text-gray-400\'>No description</span>'"
                                        ></p>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="italic text-gray-500">No subtasks available</p>
                        </template>
                    </Card>

                    <!-- COMMENTS -->
                    <Card class="mt-6">
                        <template #title><h2 class="font-semibold">Comments</h2></template>
                        <template #content>
                            <div class="mb-6 flex flex-col gap-3">
                                <Textarea v-model="newComment" rows="3" placeholder="Write a comment..." />
                                <Button label="Submit" icon="pi pi-send" @click="submitComment" />
                            </div>

                            <div v-if="props.comments?.length">
                                <CommentItem
                                    class="mt-2"
                                    v-for="comment in props.comments"
                                    :currentUserId="currentUserId"
                                    :key="comment.id"
                                    :comment="comment"
                                    :taskId="props.task.id"
                                />
                            </div>
                            <p v-else class="italic text-gray-500">No comments yet</p>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
