<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import Chip from 'primevue/chip';
import DatePicker from 'primevue/datepicker';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import SectionPanel from './SectionPanel.vue';

import type { Project, Task, TaskFormField, TaskFormInput, TaskPriority, TaskStatus, TaskType, User } from '../../index.d.ts';

interface Props {
    task: Task;
    project: Project;
    statuses: TaskStatus[];
    priorities: TaskPriority[];
    types: TaskType[];
    isTaskMember: boolean;
    creator?: User;
    assignedUsers?: User[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'showInProgressDialog', pendingStatusId: string): void;
}>();

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

const canEdit = computed(() => props.isTaskMember || hasPermission() || isOwner.value);

const assignees = computed<User[]>(() => props.assignedUsers ?? []);

const statusOption = computed(() => {
    return isDeveloper.value ? props.statuses.filter((status) => ['In Progress', 'In Review'].includes(status.name)) : props.statuses;
});
const requiresDueDateForStatus = (statusName?: string) => statusName === 'In Progress';

const formatDate = (date?: string) => (date ? moment(date).format('DD MMM YYYY') : '-');

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
            onError: (errors) => {
                let errorMessage = `Failed to update ${getFieldLabel(field as string)}. Please try again.`;

                if (Object.keys(errors).length) {
                    errorMessage = errors[Object.keys(errors)[0]];
                }

                toast.add({
                    severity: 'error',
                    summary: 'Update Failed',
                    detail: errorMessage,
                    life: 5000,
                });
            },
        },
    );
};

const handleSelectChange = (field: string, value: any) => {
    if (field === 'status_id') {
        const selectedStatus = props.statuses.find((s) => s.id === value);
        const dueDateMissing = !props.task.due_date;

        if (requiresDueDateForStatus(selectedStatus?.name) && dueDateMissing) {
            emit('showInProgressDialog', value);
            return;
        }
    }

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
    <SectionPanel title="Details" icon="pi pi-sliders-h">
        <dl class="divide-y divide-surface-200 dark:divide-surface-700">
            <!-- Status -->
            <div class="flex items-center justify-between gap-3 py-3 first:pt-0">
                <dt class="text-sm text-surface-500 dark:text-surface-400">Status</dt>
                <dd data-editable class="flex min-w-0 flex-1 justify-end">
                    <button
                        v-if="editingField !== 'status_id'"
                        type="button"
                        class="-mr-1.5 rounded-md px-1.5 py-1 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        :class="canEdit ? 'cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800' : 'cursor-not-allowed opacity-70'"
                        @click="startEdit('status_id', props.task.status_id, $event)"
                    >
                        <Tag :value="props.task.status?.name" :severity="props.task.status?.severity" />
                    </button>
                    <div v-else class="flex w-full justify-end" @click.stop>
                        <Select
                            v-model="editValue"
                            :options="statusOption"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Status"
                            class="w-full max-w-[12rem]"
                            @change="handleSelectChange('status_id', editValue)"
                        />
                    </div>
                </dd>
            </div>

            <!-- Priority -->
            <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-sm text-surface-500 dark:text-surface-400">Priority</dt>
                <dd data-editable class="flex min-w-0 flex-1 justify-end">
                    <button
                        v-if="editingField !== 'priority_id'"
                        type="button"
                        class="-mr-1.5 rounded-md px-1.5 py-1 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        :class="canEdit ? 'cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800' : 'cursor-not-allowed opacity-70'"
                        @click="startEdit('priority_id', props.task.priority_id, $event)"
                    >
                        <Tag :value="props.task.priority?.name" :severity="props.task.priority?.severity" />
                    </button>
                    <div v-else class="flex w-full justify-end" @click.stop>
                        <Select
                            v-model="editValue"
                            :options="props.priorities"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Priority"
                            class="w-full max-w-[12rem]"
                            @change="handleSelectChange('priority_id', editValue)"
                        />
                    </div>
                </dd>
            </div>

            <!-- Type -->
            <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-sm text-surface-500 dark:text-surface-400">Type</dt>
                <dd data-editable class="flex min-w-0 flex-1 justify-end">
                    <button
                        v-if="editingField !== 'type_id'"
                        type="button"
                        class="-mr-1.5 rounded-md px-1.5 py-1 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        :class="canEdit ? 'cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800' : 'cursor-not-allowed opacity-70'"
                        @click="startEdit('type_id', props.task.type_id, $event)"
                    >
                        <Tag :value="props.task.type?.name" :severity="props.task.type?.severity" />
                    </button>
                    <div v-else class="flex w-full justify-end" @click.stop>
                        <Select
                            v-model="editValue"
                            :options="props.types"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select Type"
                            class="w-full max-w-[12rem]"
                            @change="handleSelectChange('type_id', editValue)"
                        />
                    </div>
                </dd>
            </div>

            <!-- Assignees -->
            <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-sm text-surface-500 dark:text-surface-400">Assignees</dt>
                <dd class="flex min-w-0 justify-end">
                    <div v-if="assignees.length" class="flex items-center -space-x-2">
                        <UserAvatar
                            v-for="user in assignees.slice(0, 5)"
                            :key="user.id"
                            :user="user"
                            size="!h-7 !w-7 ring-2 ring-surface-0 dark:ring-surface-900"
                        />
                        <span
                            v-if="assignees.length > 5"
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-surface-200 text-xs font-medium text-surface-700 ring-2 ring-surface-0 dark:bg-surface-700 dark:text-surface-200 dark:ring-surface-900"
                        >
                            +{{ assignees.length - 5 }}
                        </span>
                    </div>
                    <span v-else class="text-sm text-surface-500 dark:text-surface-400">Unassigned</span>
                </dd>
            </div>

            <!-- Progress -->
            <div class="flex items-center justify-between gap-3 py-3">
                <dt class="flex items-center gap-1.5 text-sm text-surface-500 dark:text-surface-400">
                    Progress
                    <Tag v-if="hasSubTasks" value="Auto" severity="secondary" class="!px-1.5 !py-0 !text-[10px] !font-medium" />
                </dt>
                <dd class="flex items-center gap-2">
                    <div class="h-1.5 w-24 overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700">
                        <div class="h-full rounded-full bg-primary-500 transition-all duration-300" :style="{ width: `${props.task.progress}%` }" />
                    </div>
                    <span class="w-9 text-right text-sm font-medium tabular-nums text-surface-700 dark:text-surface-200"
                        >{{ props.task.progress }}%</span
                    >
                </dd>
            </div>

            <!-- Start date -->
            <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-sm text-surface-500 dark:text-surface-400">Start date</dt>
                <dd data-editable class="flex min-w-0 flex-1 justify-end">
                    <button
                        v-if="editingField !== 'start_date'"
                        type="button"
                        class="-mr-1.5 rounded-md px-1.5 py-1 text-sm font-medium text-surface-700 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-surface-200"
                        :class="canEdit ? 'cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800' : 'cursor-not-allowed opacity-70'"
                        @click="startEdit('start_date', props.task.start_date, $event)"
                    >
                        {{ formatDate(props.task.start_date) }}
                    </button>
                    <div v-else class="flex w-full justify-end" @click.stop>
                        <DatePicker
                            v-model="editValue"
                            dateFormat="dd M yy"
                            class="w-full max-w-[12rem]"
                            showIcon
                            @date-select="handleSelectChange('start_date', editValue)"
                        />
                    </div>
                </dd>
            </div>

            <!-- Due date -->
            <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-sm text-surface-500 dark:text-surface-400">Due date</dt>
                <dd data-editable class="flex min-w-0 flex-1 justify-end">
                    <button
                        v-if="editingField !== 'due_date'"
                        type="button"
                        class="-mr-1.5 flex items-center gap-1.5 rounded-md px-1.5 py-1 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        :class="[
                            canEdit ? 'cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800' : 'cursor-not-allowed opacity-70',
                            props.task.is_overdue ? 'text-rose-600 dark:text-rose-400' : 'text-surface-700 dark:text-surface-200',
                        ]"
                        @click="startEdit('due_date', props.task.due_date, $event)"
                    >
                        <i v-if="props.task.is_overdue" class="pi pi-exclamation-circle text-xs" />
                        {{ formatDate(props.task.due_date) }}
                    </button>
                    <div v-else class="flex w-full justify-end" @click.stop>
                        <DatePicker
                            v-model="editValue"
                            dateFormat="dd M yy"
                            class="w-full max-w-[12rem]"
                            showIcon
                            @date-select="handleSelectChange('due_date', editValue)"
                        />
                    </div>
                </dd>
            </div>

            <!-- Tags -->
            <div v-if="props.task.tags?.length" class="flex items-start justify-between gap-3 py-3">
                <dt class="shrink-0 pt-1 text-sm text-surface-500 dark:text-surface-400">Tags</dt>
                <dd class="flex flex-wrap justify-end gap-1.5">
                    <Chip v-for="tag in props.task.tags" :key="tag.id ?? tag.name" :label="tag.name" class="!py-0.5 !text-xs" />
                </dd>
            </div>

            <!-- Created -->
            <div class="flex items-center justify-between gap-3 py-3">
                <dt class="text-sm text-surface-500 dark:text-surface-400">Created</dt>
                <dd class="text-sm font-medium text-surface-700 dark:text-surface-200">{{ formatDate(props.task.created_at) }}</dd>
            </div>
        </dl>
    </SectionPanel>
</template>
