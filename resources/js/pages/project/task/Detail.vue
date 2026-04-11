<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import moment from 'moment';

import Breadcrumb from 'primevue/breadcrumb';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';

import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';

const emojiIndex = new EmojiIndex(emojiData);

import MentionEditor from '@/components/Mentioneditor.vue';
import { ProjectUserOption } from '@/types/task-comment';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Comment, ProjectMember, Task, TaskPriority, TaskStatus, TaskType } from '..';
import CommentItem from './CommentItem.vue';
import MemberCard from './partials/MemberCard.vue';

interface User {
    id: number;
    name: string;
    avatar_url?: string | null;
}

interface TaskForm {
    status_id: string;
    priority_id: string;
    type_id: string;
    start_date: string | null;
    due_date: string | null;
    progress_value: number;

    [key: string]: any;
}

type TaskFormField = keyof TaskForm;

interface Props {
    task: Task;
    project: {
        id: string;
        title: string;
        description?: string;
        emoji: string;
        progress: number;
        start_date?: string;
        due_date?: string;
        status?: { id: string; name: string; severity?: string };
        priority?: { id: string; name: string; severity?: string };
        status_id?: string;
        priority_id?: string;
        created_at?: string;
        updated_at?: string;
        project_members: ProjectMember[];
    };
    assignedUsers: User[];
    comments: Comment[];
    statuses: TaskStatus[];
    priorities: TaskPriority[];
    types: TaskType[];

    isTaskMember: boolean;
    creator?: User;
}

const props = defineProps<Props>();
const page = usePage();
const commentLoading = ref(false);
const currentUserId = computed(() => Number(page.props.auth.user.id));
const isDeveloper = computed(() => page.props.auth?.role?.startsWith('developer-'));

const toast = useToast();

const statusOption = computed(() => {
    return isDeveloper.value ? props.statuses.filter((status) => ['In Progress', 'In Review'].includes(status.name)) : props.statuses;
});

// Compute flat list of project members for mention
const mentionMembers = computed(() =>
    props.project.project_members.map((m) => {
        return {
            id: m.user.id,
            name: m.user.name,
        } as ProjectUserOption;
    }),
);

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

const authUser = computed(() => page.props.auth?.user);
const hasPermission = (): boolean => {
    const role = page.props.auth.role;
    return role ? role.startsWith('super-admin-') || role.startsWith('admin-') : false;
};
const isOwner = computed(() => {
    if (!authUser.value) return false;

    return props.project.project_members.some((member) => member.user.id === authUser.value.id && member.role.name === 'Owner');
});

const formatDate = (date?: string) => (date ? moment(date).format('DD MMM YYYY') : '-');
const formatDateTime = (date?: string) => (date ? moment(date).format('DD MMM YYYY HH:mm') : '-');

const goToProject = () => {
    if (props.project?.id) {
        router.visit(route('project.show', { encoded: props.project.id }));
    }
};

const goToSubTask = (subTaskId: string) => {
    if (subTaskId) {
        router.visit(route('task.show', subTaskId));
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

// --- In Progress Dialog ---
const inProgressDialogVisible = ref(false);
const inProgressDueDate = ref<Date | null>(null);
const pendingStatusId = ref<string | null>(null);

const startEdit = (field: string, currentValue: any, event?: Event) => {
    if (
        isDeveloper.value
        // && field !== 'status_id'
        // && field !== 'due_date'
    ) {
        toast.add({
            severity: 'warn',
            summary: 'Access Denied',
            detail: 'You must be a project member and assigned to this task to edit it',
            life: 3000,
        });
        return;
    }

    if (!props.isTaskMember && !hasPermission() && !isOwner.value) {
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
    } else {
        editValue.value = currentValue;
    }
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
        start_date: 'Start Date',
        due_date: 'Due Date',
    };
    return labels[field] || field;
};

const form = useForm<TaskForm>({
    status_id: props.task.status_id,
    priority_id: props.task.priority_id,
    type_id: props.task.type_id,
    start_date: props.task.start_date,
    due_date: props.task.due_date,
    progress_value: props.task.progress,
});

const autoSave = (field: TaskFormField, value: any, extraFields?: Partial<TaskForm>) => {
    let valueToSave = value;

    if (field === 'start_date' || field === 'due_date') {
        valueToSave = value ? moment(value).format('YYYY-MM-DD') : null;
    }

    form[field] = valueToSave;

    // Apply extra fields (e.g. due_date when saving status)
    if (extraFields) {
        for (const [key, val] of Object.entries(extraFields)) {
            form[key as TaskFormField] = val;
        }
    }

    form.put(
        route('project.tasks.update', {
            projectEncoded: props.project.id,
            taskEncoded: props.task.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                cancelEdit();
                handleClickOutside(new MouseEvent('click'));
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Update Failed',
                    detail: `Failed to update ${getFieldLabel(field as string)}. Please try again.`,
                    life: 3000,
                });
            },
        },
    );
};

/**
 * Handle status select change.
 * If selected status is "In Progress" AND due_date is empty, show the dialog.
 * Otherwise proceed normally.
 */
const handleSelectChange = (field: string, value: any) => {
    if (field === 'status_id') {
        const selectedStatus = props.statuses.find((s) => s.id === value);
        const isInProgress = selectedStatus?.name === 'In Progress';
        const dueDateMissing = !props.task.due_date;

        if (isInProgress && dueDateMissing) {
            pendingStatusId.value = value;
            inProgressDueDate.value = null;
            inProgressDialogVisible.value = true;
            // Don't save yet — wait for dialog submit
            return;
        }
    }

    autoSave(field, value);
};

/** Called when user submits the In Progress dialog */
const submitInProgressDialog = () => {
    if (!inProgressDueDate.value) {
        toast.add({
            severity: 'warn',
            summary: 'Due Date Required',
            detail: 'Please select a due date to set the task as In Progress.',
            life: 3000,
        });
        return;
    }

    const formattedDueDate = moment(inProgressDueDate.value).format('YYYY-MM-DD');

    // Save status + due_date together
    autoSave('status_id', pendingStatusId.value, { due_date: formattedDueDate });

    inProgressDialogVisible.value = false;
    pendingStatusId.value = null;
    inProgressDueDate.value = null;
};

/** Called when user cancels the In Progress dialog */
const cancelInProgressDialog = () => {
    inProgressDialogVisible.value = false;
    pendingStatusId.value = null;
    inProgressDueDate.value = null;
    // Reset select back to original value
    editValue.value = props.task.status_id;
    cancelEdit();
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
    return props.task.sub_task_recursive.length > 0;
});

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
                                <span class="max-w-prose truncate font-medium" :title="item.label as string">
                                    {{ item.label }}
                                </span>
                            </a>
                        </template>
                    </Breadcrumb>
                </template>
            </Card>

            <Card
                class="overflow-hidden rounded-2xl border-0 bg-gradient-to-br from-blue-50 to-indigo-50 shadow-lg dark:from-gray-800 dark:to-gray-900"
            >
                <template #content>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <Button
                            icon="pi pi-arrow-left"
                            text
                            rounded
                            severity="secondary"
                            @click="router.visit(route('project.show', { encoded: project.id }))"
                            class="hover:bg-surface-100 dark:hover:bg-surface-800"
                        />
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
                                <h1 class="mb-1 break-all text-3xl font-bold text-gray-800 dark:text-white">
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
                                <Chip
                                    v-if="props.task.sub_task_recursive.length"
                                    :label="`${props.task.sub_task_recursive.length}`"
                                    class="bg-indigo-100 text-indigo-700"
                                />
                            </div>
                        </template>
                        <template #content>
                            <Divider class="my-3" />
                            <div v-if="props.task.sub_task_recursive.length" class="space-y-3">
                                <div
                                    v-for="subTask in props.task.sub_task_recursive"
                                    :key="subTask.id"
                                    @click="goToSubTask(subTask.id)"
                                    class="group cursor-pointer rounded-xl border-2 border-gray-100 bg-white p-4 transition-all hover:border-indigo-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600"
                                >
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 flex-1 items-start gap-3">
                                            <p
                                                :title="subTask.title"
                                                class="max-w-full truncate break-words text-base font-semibold text-gray-800 transition-colors group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
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
                                <!-- Created By Section -->
                                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                        <i class="pi pi-user mr-1 text-indigo-500"></i>CREATED BY
                                    </p>
                                    <div v-if="props.creator" class="flex items-center gap-2">
                                        <Avatar
                                            :image="
                                                props.creator.avatar_url && props.creator.avatar_url !== '/images/default-avatar.png'
                                                    ? props.creator.avatar_url
                                                    : undefined
                                            "
                                            :label="
                                                !props.creator.avatar_url || props.creator.avatar_url === '/images/default-avatar.png'
                                                    ? getInitials(props.creator.name)
                                                    : undefined
                                            "
                                            shape="circle"
                                            size="normal"
                                            :style="
                                                !props.creator.avatar_url || props.creator.avatar_url === '/images/default-avatar.png'
                                                    ? { backgroundColor: getUserColor(0), color: 'white', fontWeight: '600' }
                                                    : {}
                                            "
                                        />
                                        <div>
                                            <p class="text-sm font-semibold">{{ props.creator.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDateTime(props.task.created_at) }}</p>
                                        </div>
                                    </div>
                                    <p v-else class="text-sm italic text-gray-400">Unknown</p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800" data-editable>
                                        <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">STATUS</p>
                                        <div
                                            v-if="editingField !== 'status_id'"
                                            @click="startEdit('status_id', props.task.status_id, $event)"
                                            :class="[
                                                props.isTaskMember || hasPermission() || isOwner
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
                                                :options="statusOption"
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
                                                props.isTaskMember || hasPermission() || isOwner
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
                                                props.isTaskMember || hasPermission() || isOwner
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
                                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <p class="mb-2 flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            PROGRESS
                                            <Tag v-if="hasSubTasks" value="Auto" severity="info" class="text-[10px]" />
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <div class="relative h-5 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                <div
                                                    class="h-full bg-gradient-to-r from-green-400 to-green-600 transition-all"
                                                    :style="{ width: `${props.task.progress}%` }"
                                                />
                                                <span
                                                    class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white drop-shadow"
                                                >
                                                    {{ props.task.progress }}%
                                                </span>
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
                                                props.isTaskMember || hasPermission() || isOwner
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
                                                props.isTaskMember || hasPermission() || isOwner
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
                    <MemberCard :values="props.assignedUsers" />
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
                                <!-- MentionEditor replaces PrimeVue Editor -->
                                <MentionEditor
                                    v-model="newComment"
                                    :projectMembers="mentionMembers"
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
                                    :currentUserId="currentUserId"
                                    :key="comment.id"
                                    :comment="comment"
                                    :taskId="props.task.id"
                                    :projectMembers="mentionMembers"
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

        <!-- In Progress: Due Date Required Dialog -->
        <Dialog v-model:visible="inProgressDialogVisible" modal :closable="false" :draggable="false" header="Set Due Date" class="w-full max-w-md">
            <template #header>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                        <i class="pi pi-calendar-clock text-blue-600 dark:text-blue-300"></i>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">Set Due Date</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Required to move task to In Progress</p>
                    </div>
                </div>
            </template>

            <div class="flex flex-col gap-4 py-2">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    This task doesn't have a due date yet. Please set a due date before marking it as
                    <span class="font-semibold text-blue-600 dark:text-blue-400">In Progress</span>.
                </p>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        <i class="pi pi-calendar-times mr-1 text-red-500"></i>DUE DATE <span class="text-red-500">*</span>
                    </label>
                    <DatePicker
                        v-model="inProgressDueDate"
                        dateFormat="dd M yy"
                        class="w-full"
                        showIcon
                        placeholder="Select due date"
                        :minDate="new Date()"
                    />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancel" severity="secondary" text @click="cancelInProgressDialog" />
                    <Button label="Confirm & Save" icon="pi pi-check" :disabled="!inProgressDueDate" @click="submitInProgressDialog" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
