<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import moment from 'moment';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

const props = defineProps<{
    task: any;
    project: any;
    subTasks: any[];
    assignedUsers: any[];
    assignableUsers: any[];
    statuses: any[];
    priorities: any[];
    types: any[];
    isPM: boolean;
}>();

// Back to previous page
const goBack = () => {
    window.history.back();
};

// Go to project page
const goToProject = () => {
    if (props.project?.id) {
        router.visit(route('project.show', { encoded: props.project.id }));
    }
};

const formatDate = (date: string | undefined) => {
    return date ? moment(date).format('DD MMM YYYY') : '-';
};
</script>

<template>
    <Head :title="`Task Detail - ${props.task.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <!-- HEADER -->
            <Card class="rounded-xl border border-surface-200 shadow-md dark:border-surface-700">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex min-w-0 cursor-pointer items-center gap-3" @click="goToProject">
                            <span class="text-4xl">{{ props.project.emoji }}</span>

                            <div class="min-w-0">
                                <h1 class="whitespace-normal break-words text-2xl font-bold leading-none">
                                    {{ props.task.title }}
                                </h1>
                                <p class="mt-1 break-words text-sm text-gray-500">
                                    Project:
                                    <span class="break-words font-semibold">{{ props.project.title }}</span>
                                </p>
                            </div>
                        </div>

                        <Button label="Back" icon="pi pi-arrow-left" severity="secondary" class="px-4" @click="goBack" />
                    </div>
                </template>
            </Card>

            <!-- CONTENT GRID -->
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <!-- LEFT COLUMN -->
                <div class="space-y-6">
                    <!-- DESCRIPTION -->
                    <Card class="rounded-xl border border-surface-200 shadow-sm dark:border-surface-700">
                        <template #title>
                            <h2 class="font-semibold">Description</h2>
                        </template>

                        <template #content>
                            <div
                                class="prose dark:prose-invert max-w-none whitespace-normal break-words text-[15px]"
                                v-html="props.task.description || '<p class=`text-gray-500 italic`>No description</p>'"
                            />
                        </template>
                    </Card>

                    <!-- DETAILS -->
                    <Card class="rounded-xl border border-surface-200 shadow-sm dark:border-surface-700">
                        <template #title>
                            <h2 class="font-semibold">Details</h2>
                        </template>

                        <template #content>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="mb-1 font-medium">Status</p>
                                    <Tag :value="props.task.status?.name || '-'" :severity="props.task.status?.severity" />
                                </div>

                                <div>
                                    <p class="mb-1 font-medium">Priority</p>
                                    <Tag :value="props.task.priority?.name || '-'" :severity="props.task.priority?.severity" />
                                </div>

                                <div>
                                    <p class="mb-1 font-medium">Type</p>
                                    <Tag :value="props.task.type?.name || '-'" :severity="props.task.type?.severity" />
                                </div>

                                <div>
                                    <p class="mb-1 font-medium">Progress</p>
                                    <Tag :value="`${props.task.progress}%`" severity="success" />
                                </div>

                                <div>
                                    <p class="mb-1 font-medium">Start Date</p>
                                    <p class="break-words text-gray-600">{{ formatDate(props.task.start_date) }}</p>
                                </div>

                                <div>
                                    <p class="mb-1 font-medium">Due Date</p>
                                    <p class="break-words text-gray-600">{{ formatDate(props.task.due_date) }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- TIMESTAMPS -->
                    <Card class="rounded-xl border border-surface-200 shadow-sm dark:border-surface-700">
                        <template #title>
                            <h2 class="font-semibold">Timestamps</h2>
                        </template>

                        <template #content>
                            <div class="space-y-3 text-sm">
                                <div>
                                    <p class="font-medium">Created At</p>
                                    <p class="break-words text-gray-600">{{ formatDate(props.task.created_at) }}</p>
                                </div>

                                <div>
                                    <p class="font-medium">Updated At</p>
                                    <p class="break-words text-gray-600">{{ formatDate(props.task.updated_at) }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <!-- ASSIGNED USERS -->
                    <Card class="rounded-xl border border-surface-200 shadow-sm dark:border-surface-700">
                        <template #title>
                            <h2 class="font-semibold">Assigned Users</h2>
                        </template>

                        <template #content>
                            <div class="flex flex-wrap gap-2">
                                <Tag
                                    v-for="user in props.assignedUsers"
                                    :key="user.id"
                                    :value="user.name"
                                    severity="info"
                                    class="max-w-full break-words"
                                />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="space-y-6 xl:col-span-2">
                    <!-- SUBTASKS -->
                    <Card class="rounded-xl border border-surface-200 shadow-sm dark:border-surface-700">
                        <template #title>
                            <h2 class="font-semibold">Subtasks</h2>
                        </template>

                        <template #content>
                            <div v-if="props.subTasks.length" class="space-y-4">
                                <Card
                                    v-for="subTask in props.subTasks"
                                    :key="subTask.id"
                                    class="rounded-lg border border-surface-200 shadow-sm dark:border-surface-700"
                                >
                                    <template #content>
                                        <div class="flex min-w-0 items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="break-words text-[15px] font-semibold">
                                                    {{ subTask.title }}
                                                </p>

                                                <p class="mt-1 whitespace-normal break-words text-sm text-gray-500">
                                                    {{ subTask.description || '-' }}
                                                </p>
                                            </div>

                                            <Tag :value="subTask.status?.name" :severity="subTask.status?.severity" />
                                        </div>
                                    </template>
                                </Card>
                            </div>

                            <p v-else class="italic text-gray-500">No subtasks available</p>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
