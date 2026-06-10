<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import DatePicker from 'primevue/datepicker';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, onUnmounted, ref } from 'vue';

import type { Project, Task, TaskFormField, TaskFormInput, TaskPriority, TaskStatus, TaskType, User } from '../../index.d.ts';

interface Props {
    task: Task;
    project: Project;
    statuses: TaskStatus[];
    priorities: TaskPriority[];
    types: TaskType[];
    isTaskMember: boolean;
    creator?: User;
}

const props = defineProps<Props>();

const page = usePage();
const toast = useToast();

const isDeveloper = computed(() => page.props.auth?.role?.startsWith('developer-'));
const authUser = computed(() => page.props.auth?.user);

const hasPermission = (): boolean => {
    const role = page.props.auth.role;
    return role ? role.startsWith('super-admin-') || role.startsWith('admin-') : false;
};
const isOwner = computed(() => {
    if (!authUser.value) return false;
    return props.project.project_members.some((member: any) => member.user.id === authUser.value.id && member.role.name === 'Owner');
});

const statusOption = computed(() => {
    return isDeveloper.value ? props.statuses.filter((status) => ['In Progress', 'In Review'].includes(status.name)) : props.statuses;
});

const formatDate = (date?: string) => (date ? moment(date).format('DD MMM YYYY') : '-');
const formatDateTime = (date?: string) => (date ? moment(date).format('DD MMM YYYY HH:mm') : '-');

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
    if (isDeveloper.value) {
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

const form = useForm<TaskFormInput>({
    status_id: props.task.status_id,
    priority_id: props.task.priority_id,
    type_id: props.task.type_id,
    start_date: props.task.start_date,
    due_date: props.task.due_date,
    progress_value: props.task.progress,
});

const autoSave = (field: TaskFormField, value: any, extraFields?: Partial<TaskFormInput>) => {
    let valueToSave = value;

    if (field === 'start_date' || field === 'due_date') {
        valueToSave = value ? moment(value).format('YYYY-MM-DD') : null;
    }

    form[field] = valueToSave;

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
                handleClickOutside(new MouseEvent('click') as any);
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

const handleSelectChange = (field: string, value: any) => {
    autoSave(field as TaskFormField, value);
};

// Expose these methods to access from parent (Detail.vue) when dialog confirmed
defineExpose({
    autoSave,
    cancelEdit,
    editValue,
});

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
</script>

<template>
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
                    <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400"><i class="pi pi-user mr-1 text-indigo-500"></i>CREATED BY</p>
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
                                <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white drop-shadow">
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
                    <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400"><i class="pi pi-tags mr-1 text-orange-500"></i>TAGS</p>
                    <div class="flex flex-wrap gap-2">
                        <Chip v-for="tag in props.task.tags" :key="tag.id ?? tag.name" :label="tag.name" class="text-xs" />
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
