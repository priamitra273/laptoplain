<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';

import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Breadcrumb from 'primevue/breadcrumb';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import Slider from 'primevue/slider';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';

const emojiIndex = new EmojiIndex(emojiData);

import { computed, ref } from 'vue';
import CommentItem from './CommentItem.vue';

const props = defineProps<{
    task: any;
    project: any;
    subTasks: any[];
    assignedUsers: any[];
    comments: any[];
    statuses: any[];
    priorities: any[];
    types: any[];
    isMember: boolean;
}>();

const currentUserId = usePage().props.auth.user.id;
const toast = useToast();

const breadcrumbItems = computed(() => [
    {
        label: 'Projects',
        icon: 'pi pi-folder',
        command: () => router.visit(route('project.index')),
    },
    {
        label: props.project.title,
        icon: 'pi pi-folder-open',
        command: () => router.visit(route('project.show', { encoded: props.project.id })),
    },
    {
        label: props.task.title,
        icon: 'pi pi-file',
    },
]);

const breadcrumbHome = {
    icon: 'pi pi-home',
    command: () => router.visit(route('dashboard')),
};

const formatDate = (date?: string) => (date ? moment(date).format('DD MMM YYYY') : '-');

const goToProject = () => {
    if (props.project?.id) {
        router.visit(route('project.show', { encoded: props.project.id }));
    }
};

const goToSubTask = (subTaskId: string) => {
    if (subTaskId) {
        router.visit(route('task.show', { encoded: subTaskId }));
    }
};

const editingField = ref<string | null>(null);
const editValue = ref<any>(null);

const startEdit = (field: string, currentValue: any) => {
    if (!props.isMember) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You must be a project member to edit this task',
            life: 3000,
        });
        return;
    }

    editingField.value = field;

    if (field === 'start_date' || field === 'due_date') {
        editValue.value = currentValue ? new Date(currentValue) : null;
    } else if (field === 'progress') {
        editValue.value = currentValue || 0;
    } else {
        editValue.value = currentValue;
    }
};

const getFieldLabel = (field: string): string => {
    const labels: Record<string, string> = {
        status_id: 'Status',
        priority_id: 'Priority',
        type_id: 'Type',
        progress: 'Progress',
        start_date: 'Start Date',
        due_date: 'Due Date',
    };
    return labels[field] || field;
};

const autoSave = (field: string, value: any) => {
    let valueToSave = value;

    if (field === 'start_date' || field === 'due_date') {
        valueToSave = value ? moment(value).format('YYYY-MM-DD') : null;
    }

    const updateData: any = {
        ...props.task,
        [field]: valueToSave,
        progress_value: field === 'progress' ? valueToSave : props.task.progress,
    };

    router.put(
        route('project.tasks.update', {
            projectEncoded: props.project.id,
            taskEncoded: props.task.id,
        }),
        updateData,
        {
            onSuccess: () => {
                editingField.value = null;
                editValue.value = null;
                toast.add({
                    severity: 'success',
                    summary: 'Update Successful',
                    detail: `${getFieldLabel(field)} has been updated successfully`,
                    life: 3000,
                });
            },
            onError: (errors) => {
                editingField.value = null;
                editValue.value = null;
                toast.add({
                    severity: 'error',
                    summary: 'Update Failed',
                    detail: 'Failed to update task. Please try again.',
                    life: 3000,
                });
            },
        },
    );
};
const handleSelectChange = (field: string, value: any) => {
    autoSave(field, value);
};
const handleSliderChange = (field: string, value: any) => {
    autoSave(field, value);
};

const newComment = ref('');

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
                toast.add({
                    severity: 'success',
                    summary: 'Comment Posted',
                    detail: 'Your comment has been added successfully',
                    life: 3000,
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Failed',
                    detail: 'Failed to post comment. Please try again.',
                    life: 3000,
                });
            },
        },
    );
};
</script>

<template>
    <Head :title="`Task Detail - ${props.task.title}`" />

    <AppLayout>
        <Toast />

        <div class="flex flex-col gap-6 pb-8">
            <Card class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <Breadcrumb :home="breadcrumbHome" :model="breadcrumbItems" class="border-none bg-transparent p-0 text-sm">
                        <template #item="{ item, props }">
                            <a
                                v-bind="props.action"
                                class="flex cursor-pointer items-center gap-1.5 text-gray-500 transition-colors hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                            >
                                <i :class="item.icon" class="text-xs"></i>
                                <span class="font-medium">{{ item.label }}</span>
                            </a>
                        </template>
                    </Breadcrumb>
                </template>
            </Card>
            <Card
                class="overflow-hidden rounded-2xl border-0 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-lg dark:from-gray-800 dark:to-gray-900"
            >
                <template #content>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex cursor-pointer items-start gap-4 transition-transform hover:scale-[1.02]" @click="goToProject">
                            <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white shadow-md dark:bg-gray-800">
                                <Emoji
                                    v-if="props.project?.emoji?.startsWith(':')"
                                    :data="emojiIndex"
                                    :emoji="props.project.emoji"
                                    set="google"
                                    :size="36"
                                />
                                <span v-else class="text-4xl">
                                    {{ props.project.emoji }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <h1 class="mb-1 text-3xl font-bold text-gray-800 dark:text-white">{{ props.task.title }}</h1>
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <i class="pi pi-folder text-blue-500"></i>
                                    <span>Project:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ props.project.title }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6">
                    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-align-left text-blue-500"></i>
                                <h2 class="text-lg font-bold">Description</h2>
                            </div>
                        </template>
                        <template #content>
                            <Divider class="my-3" />
                            <div
                                class="prose prose-sm max-h-60 overflow-auto break-words text-gray-700 dark:text-gray-300"
                                v-html="props.task.description || '<p class=\'text-gray-400 italic\'>No description provided</p>'"
                            />
                        </template>
                    </Card>
                    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-info-circle text-purple-500"></i>
                                <h2 class="text-lg font-bold">Details</h2>
                            </div>
                        </template>
                        <template #content>
                            <Divider class="my-3" />
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">STATUS</p>
                                        <div
                                            v-if="editingField !== 'status_id'"
                                            @click="startEdit('status_id', props.task.status_id)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <Tag :value="props.task.status?.name" :severity="props.task.status?.severity" class="w-full" />
                                        </div>
                                        <div v-else>
                                            <Select
                                                v-model="editValue"
                                                :options="props.statuses"
                                                optionLabel="name"
                                                optionValue="id"
                                                placeholder="Select Status"
                                                class="w-full"
                                                @change="handleSelectChange('status_id', editValue)"
                                            />
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">PRIORITY</p>
                                        <div
                                            v-if="editingField !== 'priority_id'"
                                            @click="startEdit('priority_id', props.task.priority_id)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <Tag :value="props.task.priority?.name" :severity="props.task.priority?.severity" class="w-full" />
                                        </div>
                                        <div v-else>
                                            <Select
                                                v-model="editValue"
                                                :options="props.priorities"
                                                optionLabel="name"
                                                optionValue="id"
                                                placeholder="Select Priority"
                                                class="w-full"
                                                @change="handleSelectChange('priority_id', editValue)"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">TYPE</p>
                                        <div
                                            v-if="editingField !== 'type_id'"
                                            @click="startEdit('type_id', props.task.type_id)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <Tag :value="props.task.type?.name" :severity="props.task.type?.severity" class="w-full" />
                                        </div>
                                        <div v-else>
                                            <Select
                                                v-model="editValue"
                                                :options="props.types"
                                                optionLabel="name"
                                                optionValue="id"
                                                placeholder="Select Type"
                                                class="w-full"
                                                @change="handleSelectChange('type_id', editValue)"
                                            />
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">PROGRESS</p>
                                        <div
                                            v-if="editingField !== 'progress'"
                                            @click="startEdit('progress', props.task.progress)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                    <div
                                                        class="h-full bg-gradient-to-r from-green-400 to-green-600 transition-all"
                                                        :style="{ width: `${props.task.progress}%` }"
                                                    ></div>
                                                </div>
                                                <span class="text-sm font-semibold text-green-600 dark:text-green-400"
                                                    >{{ props.task.progress }}%</span
                                                >
                                            </div>
                                        </div>
                                        <div v-else>
                                            <div class="flex items-center gap-2">
                                                <Slider
                                                    v-model="editValue"
                                                    class="flex-1"
                                                    :min="0"
                                                    :max="100"
                                                    @slideend="handleSliderChange('progress', editValue)"
                                                />
                                                <span class="w-12 text-right text-sm font-semibold">{{ editValue }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <i class="pi pi-calendar mr-1 text-blue-500"></i>START DATE
                                        </p>
                                        <div
                                            v-if="editingField !== 'start_date'"
                                            @click="startEdit('start_date', props.task.start_date)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <p class="text-sm font-semibold">{{ formatDate(props.task.start_date) }}</p>
                                        </div>
                                        <div v-else>
                                            <DatePicker
                                                v-model="editValue"
                                                dateFormat="dd M yy"
                                                class="w-full"
                                                showIcon
                                                @date-select="handleSelectChange('start_date', editValue)"
                                            />
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <i class="pi pi-calendar-times mr-1 text-red-500"></i>DUE DATE
                                        </p>
                                        <div
                                            v-if="editingField !== 'due_date'"
                                            @click="startEdit('due_date', props.task.due_date)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <p class="text-sm font-semibold">{{ formatDate(props.task.due_date) }}</p>
                                        </div>
                                        <div v-else>
                                            <DatePicker
                                                v-model="editValue"
                                                dateFormat="dd M yy"
                                                class="w-full"
                                                showIcon
                                                @date-select="handleSelectChange('due_date', editValue)"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div v-if="props.task.tags?.length" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                        <i class="pi pi-tags mr-1 text-orange-500"></i>TAGS
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <Chip v-for="tag in props.task.tags" :key="tag.id ?? tag.name" :label="tag.name" class="text-xs" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users text-green-500"></i>
                                <h2 class="text-lg font-bold">Team Members</h2>
                            </div>
                        </template>
                        <template #content>
                            <Divider class="my-3" />
                            <div v-if="props.assignedUsers?.length" class="flex flex-col gap-3">
                                <AvatarGroup>
                                    <Avatar
                                        v-for="(user, idx) in props.assignedUsers.slice(0, 5)"
                                        :key="user.id"
                                        :label="user.name.charAt(0).toUpperCase()"
                                        shape="circle"
                                        size="large"
                                        class="border-2 border-white shadow-md"
                                        :style="{ backgroundColor: `hsl(${idx * 60}, 70%, 60%)` }"
                                    />
                                    <Avatar
                                        v-if="props.assignedUsers.length > 5"
                                        :label="`+${props.assignedUsers.length - 5}`"
                                        shape="circle"
                                        size="large"
                                        class="border-2 border-white bg-gray-300 shadow-md"
                                    />
                                </AvatarGroup>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ props.assignedUsers.length }} member{{ props.assignedUsers.length > 1 ? 's' : '' }} assigned
                                </div>
                            </div>
                            <p v-else class="text-sm italic text-gray-400">No members assigned</p>
                        </template>
                    </Card>
                </div>
                <div class="space-y-6 lg:col-span-2">
                    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
                        <template #title>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-list text-indigo-500"></i>
                                    <h2 class="text-lg font-bold">Subtasks</h2>
                                </div>
                                <Chip v-if="props.subTasks.length" :label="`${props.subTasks.length}`" class="bg-indigo-100 text-indigo-700" />
                            </div>
                        </template>
                        <template #content>
                            <Divider class="my-3" />
                            <div v-if="props.subTasks.length" class="space-y-3">
                                <div
                                    v-for="subTask in props.subTasks"
                                    :key="subTask.id"
                                    @click="goToSubTask(subTask.id)"
                                    class="group cursor-pointer rounded-xl border-2 border-gray-100 bg-white p-4 transition-all hover:border-indigo-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600"
                                >
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 flex-1 items-start gap-3">
                                            <p
                                                class="break-words text-base font-semibold text-gray-800 transition-colors group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
                                            >
                                                {{ subTask.title }}
                                            </p>
                                        </div>
                                        <Tag :value="subTask.status?.name" :severity="subTask.status?.severity" class="shrink-0" />
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        <div
                                            class="prose prose-sm dark:prose-invert max-h-32 overflow-auto break-words"
                                            v-html="subTask.description || '<span class=\'text-gray-400 italic\'>No description</span>'"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                                <i class="pi pi-inbox mb-3 text-4xl opacity-50"></i>
                                <p class="italic">No subtasks available</p>
                            </div>
                        </template>
                    </Card>
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
                                <Textarea v-model="newComment" rows="3" placeholder="Share your thoughts..." class="mb-3 w-full" :autoResize="true" />
                                <div class="flex justify-end">
                                    <Button
                                        label="Post Comment"
                                        icon="pi pi-send"
                                        @click="submitComment"
                                        :disabled="!newComment.trim()"
                                        class="shadow-md"
                                    />
                                </div>
                            </div>
                            <div v-if="props.comments?.length" class="space-y-4">
                                <CommentItem
                                    v-for="comment in props.comments"
                                    :currentUserId="currentUserId"
                                    :key="comment.id"
                                    :comment="comment"
                                    :taskId="props.task.id"
                                    class="rounded-lg border border-gray-100 p-4 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                                />
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                                <i class="pi pi-comment mb-3 text-4xl opacity-50"></i>
                                <p class="italic">No comments yet. Be the first to comment!</p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
