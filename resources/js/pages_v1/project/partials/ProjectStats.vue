<script setup lang="ts">
import moment from 'moment';
import { ref, watch } from 'vue';
import type { Project, ProjectPriority, ProjectStatus } from '../index';

interface Props {
    project: Project;
    statuses: ProjectStatus[];
    priorities: ProjectPriority[];
    canEdit: boolean;
}

interface Emits {
    (e: 'update', value: any, field: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const editMode = ref({
    status: false,
    priority: false,
    startDate: false,
    dueDate: false,
});

const localProject = ref({ ...props.project });

watch(
    () => props.project,
    (newVal) => {
        localProject.value = { ...newVal };
    },
    { deep: true },
);

const enableEdit = (field: keyof typeof editMode.value) => {
    if (props.canEdit) {
        editMode.value[field] = true;
    }
};

const onStatusChange = (event: any) => {
    emit('update', event.value.id, 'status_id');
    editMode.value.status = false;
};

const onPriorityChange = (event: any) => {
    emit('update', event.value.id, 'priority_id');
    editMode.value.priority = false;
};

const onStartDateChange = (value: Date | Date[] | (Date | null)[] | null | undefined) => {
    if (value instanceof Date) {
        emit('update', value, 'start_date');
    }
    editMode.value.startDate = false;
};

const onDueDateChange = (value: Date | Date[] | (Date | null)[] | null | undefined) => {
    if (value instanceof Date) {
        emit('update', value, 'due_date');
    }
    editMode.value.dueDate = false;
};
</script>

<template>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        <!-- Status Card -->
        <Card class="shadow-sm">
            <template #content>
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Status</span>
                    <div v-if="!editMode.status" @click="enableEdit('status')" :class="canEdit ? 'cursor-pointer' : ''">
                        <Tag :value="project.status?.name || 'In Progress'" :severity="project.status?.severity || 'info'" class="w-fit" />
                    </div>
                    <div v-else @click.stop>
                        <Select
                            v-model="localProject.status"
                            :options="statuses"
                            optionLabel="name"
                            placeholder="Select Status"
                            @change="onStatusChange"
                            @hide="editMode.status = false"
                            class="w-full"
                            autofocus
                        >
                            <template #value="slotProps">
                                <Tag v-if="slotProps.value" :value="slotProps.value.name" :severity="slotProps.value.severity || 'info'" />
                            </template>
                            <template #option="slotProps">
                                <Tag :value="slotProps.option.name" :severity="slotProps.option.severity || 'info'" />
                            </template>
                        </Select>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Priority Card -->
        <Card class="shadow-sm">
            <template #content>
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Priority</span>
                    <div v-if="!editMode.priority" @click="enableEdit('priority')" :class="canEdit ? 'cursor-pointer' : ''">
                        <Tag :value="project.priority?.name || 'Medium'" :severity="project.priority?.severity || 'warning'" class="w-fit" />
                    </div>

                    <div v-else @click.stop>
                        <Select
                            v-model="localProject.priority"
                            :options="priorities"
                            optionLabel="name"
                            placeholder="Select Priority"
                            @change="onPriorityChange"
                            @hide="editMode.priority = false"
                            class="w-full"
                            autofocus
                        >
                            <template #value="{ value }">
                                <Tag v-if="value" :value="value.name" :severity="value.severity || 'warning'" />
                            </template>

                            <template #option="{ option }">
                                <Tag :value="option.name" :severity="option.severity || 'warning'" />
                            </template>
                        </Select>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Timeline Card -->
        <Card class="shadow-sm">
            <template #content>
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Timeline</span>
                    <div v-if="!editMode.startDate && !editMode.dueDate" class="flex flex-col gap-2">
                        <div
                            @click="enableEdit('startDate')"
                            :class="canEdit ? 'cursor-pointer rounded px-2 py-1 hover:bg-surface-50 dark:hover:bg-surface-800' : ''"
                            class="text-sm text-surface-700 dark:text-surface-300"
                        >
                            Start: {{ project.start_date ? moment(project.start_date).format('MMM DD, YYYY') : '-' }}
                        </div>
                        <div
                            @click="enableEdit('dueDate')"
                            :class="canEdit ? 'cursor-pointer rounded px-2 py-1 hover:bg-surface-50 dark:hover:bg-surface-800' : ''"
                            class="text-sm text-surface-700 dark:text-surface-300"
                        >
                            Due: {{ project.due_date ? moment(project.due_date).format('MMM DD, YYYY') : '-' }}
                        </div>
                    </div>
                    <div v-else class="flex flex-col gap-2">
                        <div v-if="editMode.startDate" @click.stop>
                            <DatePicker
                                :modelValue="localProject.start_date ? new Date(localProject.start_date) : null"
                                dateFormat="dd M yy"
                                placeholder="Start Date"
                                class="w-full text-sm"
                                autofocus
                                @update:modelValue="onStartDateChange"
                                @hide="editMode.startDate = false"
                            />
                        </div>
                        <div v-else class="px-2 py-1 text-sm text-surface-700 dark:text-surface-300">
                            Start: {{ moment(project.start_date).format('MMM DD, YYYY') }}
                        </div>
                        <div v-if="editMode.dueDate" @click.stop>
                            <DatePicker
                                :modelValue="localProject.due_date ? new Date(localProject.due_date) : null"
                                dateFormat="dd M yy"
                                placeholder="Due Date"
                                class="w-full text-sm"
                                autofocus
                                @update:modelValue="onDueDateChange"
                                @hide="editMode.dueDate = false"
                            />
                        </div>
                        <div v-else class="px-2 py-1 text-sm text-surface-700 dark:text-surface-300">
                            Due: {{ moment(project.due_date).format('MMM DD, YYYY') }}
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Progress Card -->
        <Card class="shadow-sm">
            <template #content>
                <div class="space-y-2">
                    <span class="text-xs font-semibold uppercase text-surface-500 dark:text-surface-400">Progress</span>
                    <ProgressBar :value="project.progress" class="flex-1" :showValue="true" />
                </div>
            </template>
        </Card>
    </div>
</template>
