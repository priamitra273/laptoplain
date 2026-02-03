<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import moment from 'moment';

import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Breadcrumb from 'primevue/breadcrumb';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Editor from 'primevue/editor';
import Select from 'primevue/select';
import Slider from 'primevue/slider';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';

import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';

const emojiIndex = new EmojiIndex(emojiData);

import { computed, onMounted, onUnmounted, ref } from 'vue';
import CommentItem from './CommentItem.vue';

interface User {
    id: number;
    name: string;
    avatar_url?: string | null;
}

const props = defineProps<{
    task: any;
    project: any;
    subTasks: any[];
    assignedUsers: User[];
    comments: any[];
    statuses: any[];
    priorities: any[];
    types: any[];
    isMember: boolean;
    isTaskMember: boolean;
}>();

const currentUserId = computed(() => Number(usePage().props.auth.user.id));

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

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getUserColor = (index: number) => `hsl(${index * 60}, 70%, 60%)`;

const editingField = ref<string | null>(null);
const editValue = ref<any>(null);
const editingElement = ref<HTMLElement | null>(null);

const startEdit = (field: string, currentValue: any, event?: Event) => {
    if (!props.isMember || !props.isTaskMember) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You must be a project member and assigned to this task to edit it',
            life: 3000,
        });
        return;
    }

    editingField.value = field;

    if (event) {
        editingElement.value = (event.target as HTMLElement).closest('[data-editable]') as HTMLElement;
    }

    if (field === 'start_date' || field === 'due_date') {
        editValue.value = currentValue ? new Date(currentValue) : null;
    } else if (field === 'progress') {
        editValue.value = currentValue || 0;
    } else {
        editValue.value = currentValue;
    }
};

const startEditProgress = (event?: Event) => {
    if (!canEditProgress.value) {
        toast.add({
            severity: 'info',
            summary: 'Progress Locked',
            detail: 'Progress is locked when subtasks exist',
            life: 3000,
        });
        return;
    }

    startEdit('progress', props.task.progress, event);
};

const cancelEdit = () => {
    editingField.value = null;
    editValue.value = null;
    editingElement.value = null;
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

const form = useForm({
    ...props.task,
    progress_value: props.task.progress,
});

const autoSave = (field: string, value: any) => {
    let valueToSave = value;

    if (field === 'start_date' || field === 'due_date') {
        valueToSave = value ? moment(value).format('YYYY-MM-DD') : null;
    }

    form[field] = valueToSave;

    if (field === 'progress') {
        form.progress_value = valueToSave;
    }

    form.put(
        route('project.tasks.update', {
            projectEncoded: props.project.id,
            taskEncoded: props.task.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Update Failed',
                    detail: `Failed to update ${getFieldLabel(field)}. Please try again.`,
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

const handleClickOutside = (event: MouseEvent) => {
    if (!editingField.value) return;

    const target = event.target as HTMLElement;

    const isInsideOverlay =
        target.closest('.p-select-overlay') ||
        target.closest('.p-select-panel') ||
        target.closest('.p-datepicker') ||
        target.closest('.p-datepicker-panel') ||
        target.closest('.p-overlay') ||
        target.closest('.p-component-overlay');

    if (isInsideOverlay) {
        return;
    }

    if (editingElement.value && editingElement.value.contains(target)) {
        return;
    }

    cancelEdit();
};

const handleEscapeKey = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && editingField.value) {
        cancelEdit();
    }
};

onMounted(() => {
    setTimeout(() => {
        document.addEventListener('click', handleClickOutside, true);
        document.addEventListener('keydown', handleEscapeKey);
    }, 0);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside, true);
    document.removeEventListener('keydown', handleEscapeKey);
});

const hasSubTasks = computed(() => {
    return props.subTasks && props.subTasks.length > 0;
});

const canEditProgress = computed(() => {
    return props.isMember && props.isTaskMember && !hasSubTasks.value;
});

const handleProgressChange = (value: number) => {
    if (!canEditProgress.value) return;
    autoSave('progress', value);
};

const newComment = ref('');

const submitComment = () => {
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
                                <h1 class="mb-1 text-3xl font-bold text-gray-800 dark:text-white">
                                    {{ props.task.title }}
                                </h1>
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <i class="pi pi-folder text-blue-500"></i>
                                    <span>Project:</span>
                                    <span class="font-semibold text-blue-600 dark:text-blue-400">
                                        {{ props.project.title }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Subtasks Card -->
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

                    <!-- Details Card -->
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
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" data-editable>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">STATUS</p>
                                        <div
                                            v-if="editingField !== 'status_id'"
                                            @click="startEdit('status_id', props.task.status_id, $event)"
                                            :class="[
                                                props.isMember && props.isTaskMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <Tag :value="props.task.status?.name" :severity="props.task.status?.severity" class="w-full" />
                                        </div>
                                        <div v-else @click.stop>
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
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" data-editable>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">PRIORITY</p>
                                        <div
                                            v-if="editingField !== 'priority_id'"
                                            @click="startEdit('priority_id', props.task.priority_id, $event)"
                                            :class="[
                                                props.isMember && props.isTaskMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <Tag :value="props.task.priority?.name" :severity="props.task.priority?.severity" class="w-full" />
                                        </div>
                                        <div v-else @click.stop>
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
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" data-editable>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">TYPE</p>
                                        <div
                                            v-if="editingField !== 'type_id'"
                                            @click="startEdit('type_id', props.task.type_id, $event)"
                                            :class="[
                                                props.isMember && props.isTaskMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <Tag :value="props.task.type?.name" :severity="props.task.type?.severity" class="w-full" />
                                        </div>
                                        <div v-else @click.stop>
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
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" data-editable>
                                        <p class="mb-2 flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            PROGRESS
                                            <Tag v-if="hasSubTasks" value="Auto" severity="info" class="text-[10px]" />
                                        </p>

                                        <!-- DISPLAY MODE -->
                                        <div
                                            v-if="editingField !== 'progress'"
                                            @click="startEditProgress($event)"
                                            :class="[
                                                canEditProgress
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-60',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                    <div
                                                        class="h-full bg-gradient-to-r from-green-400 to-green-600 transition-all"
                                                        :style="{ width: `${props.task.progress}%` }"
                                                    />
                                                </div>

                                                <span
                                                    class="text-sm font-semibold"
                                                    :class="hasSubTasks ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400'"
                                                >
                                                    {{ props.task.progress }}%
                                                </span>
                                            </div>
                                        </div>

                                        <!-- EDIT MODE -->
                                        <div v-else @click.stop>
                                            <div class="flex items-center gap-3">
                                                <Slider
                                                    v-model="editValue"
                                                    class="flex-1"
                                                    :min="0"
                                                    :max="100"
                                                    :disabled="hasSubTasks"
                                                    @slideend="handleProgressChange(editValue)"
                                                />
                                                <span class="w-12 text-right text-sm font-semibold"> {{ editValue }}% </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" data-editable>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <i class="pi pi-calendar mr-1 text-blue-500"></i>START DATE
                                        </p>
                                        <div
                                            v-if="editingField !== 'start_date'"
                                            @click="startEdit('start_date', props.task.start_date, $event)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <p class="text-sm font-semibold">{{ formatDate(props.task.start_date) }}</p>
                                        </div>
                                        <div v-else @click.stop>
                                            <DatePicker
                                                v-model="editValue"
                                                dateFormat="dd M yy"
                                                class="w-full"
                                                showIcon
                                                @date-select="handleSelectChange('start_date', editValue)"
                                            />
                                        </div>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" data-editable>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            <i class="pi pi-calendar-times mr-1 text-red-500"></i>DUE DATE
                                        </p>
                                        <div
                                            v-if="editingField !== 'due_date'"
                                            @click="startEdit('due_date', props.task.due_date, $event)"
                                            :class="[
                                                props.isMember
                                                    ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed opacity-75',
                                                'rounded p-1 transition-all',
                                            ]"
                                        >
                                            <p class="text-sm font-semibold">{{ formatDate(props.task.due_date) }}</p>
                                        </div>
                                        <div v-else @click.stop>
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

                    <!-- Team Members Card -->
                    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
                        <template #title>
                            <div class="flex items-center gap-2">
                                <i class="pi pi-users text-green-500"></i>
                                <h2 class="text-lg font-bold">Team Members</h2>
                            </div>
                        </template>
                        <template #content>
                            <Divider class="my-3" />
                            <div v-if="props.assignedUsers?.length" class="space-y-4">
                                <AvatarGroup>
                                    <Avatar
                                        v-for="(user, idx) in props.assignedUsers.slice(0, 5)"
                                        :key="user.id"
                                        :image="user.avatar_url && user.avatar_url !== '/images/default-avatar.png' ? user.avatar_url : undefined"
                                        :label="
                                            !user.avatar_url || user.avatar_url === '/images/default-avatar.png' ? getInitials(user.name) : undefined
                                        "
                                        shape="circle"
                                        size="large"
                                        :style="
                                            !user.avatar_url || user.avatar_url === '/images/default-avatar.png'
                                                ? { backgroundColor: getUserColor(idx), color: 'white', fontWeight: '600' }
                                                : {}
                                        "
                                        :title="user.name"
                                        class="border-2 border-white shadow-md dark:border-gray-800"
                                    />

                                    <Avatar
                                        v-if="props.assignedUsers.length > 5"
                                        :label="`+${props.assignedUsers.length - 5}`"
                                        shape="circle"
                                        size="large"
                                        class="border-2 border-white bg-gray-300 shadow-md dark:border-gray-800"
                                    />
                                </AvatarGroup>
                                <div class="space-y-2">
                                    <div
                                        v-for="(user, idx) in props.assignedUsers"
                                        :key="user.id"
                                        class="flex items-center gap-3 rounded-lg bg-gray-50 p-2 dark:bg-gray-800"
                                    >
                                        <Avatar
                                            :image="user.avatar_url && user.avatar_url !== '/images/default-avatar.png' ? user.avatar_url : undefined"
                                            :label="
                                                !user.avatar_url || user.avatar_url === '/images/default-avatar.png'
                                                    ? getInitials(user.name)
                                                    : undefined
                                            "
                                            shape="circle"
                                            :style="
                                                !user.avatar_url || user.avatar_url === '/images/default-avatar.png'
                                                    ? { backgroundColor: getUserColor(idx), color: 'white', fontWeight: '600' }
                                                    : {}
                                            "
                                        />
                                        <span class="text-sm font-medium">{{ user.name }}</span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm italic text-gray-400">No members assigned</p>
                        </template>
                    </Card>
                </div>

                <!-- Right Column -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Description Card -->
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

                    <!-- Comments Card -->
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
                                <Editor v-model="newComment" editorStyle="height: 200px" class="mb-3">
                                    <template v-slot:toolbar>
                                        <span class="ql-formats">
                                            <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                                            <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                                            <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                                        </span>
                                    </template>
                                </Editor>
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
