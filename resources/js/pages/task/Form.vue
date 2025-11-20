<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { computed, watch } from 'vue';

import type { Task, TaskPriority, TaskStatus, TaskType } from '@/types/index';

interface Props {
    projectId: string;
    tasks: Task[];
    taskTypes?: TaskType[];
    taskStatuses?: TaskStatus[];
    taskPriorities?: TaskPriority[];
    editTask?: Task | null; // untuk mode edit
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'saved']);

const form = useForm({
    project_id: props.projectId,
    title: '',
    description: '',
    type_id: null,
    status_id: null,
    priority_id: null,
    parent_id: null,
    start_date: null,
    due_date: null,
    is_archived: false,
    progress: 0,
});

function formatDate(date: any) {
    if (!date) return null;

    // Jika sudah string, kirim apa adanya
    if (typeof date === 'string') return date;

    // Format ke YYYY-MM-DD
    return date.toISOString().split('T')[0];
}

const minDueDate = computed<Date | undefined>(() => {
    if (!form.start_date) return undefined;

    return typeof form.start_date === 'string' ? new Date(form.start_date) : form.start_date;
});

// Jika edit mode → isi form otomatis
watch(
    () => props.editTask,
    (task) => {
        if (task) {
            form.title = task.title;
            form.description = task.description ?? '';
            form.type_id = task.type?.id ?? null;
            form.status_id = task.status?.id ?? null;
            form.priority_id = task.priority?.id ?? null;
            form.parent_id = task.parent_id ?? null;
            form.start_date = task.start_date ?? null;
            form.due_date = task.due_date ?? null;
            form.is_archived = task.is_archived;
            form.progress = task.progress ?? 0;
        }
    },
    { immediate: true },
);

// Submit ADD
const save = () => {
    form.start_date = formatDate(form.start_date);
    form.due_date = formatDate(form.due_date);
    form.project_id = props.projectId;

    form.post(route('project.tasks.store', props.projectId), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            form.reset();
        },
    });
};

// Submit EDIT
const update = () => {
    if (!props.editTask) return;
    form.start_date = formatDate(form.start_date);
    form.due_date = formatDate(form.due_date);
    form.project_id = props.projectId;

    form.put(
        route('project.tasks.update', {
            projectEncoded: props.projectId,
            taskEncoded: props.editTask.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
            },
        },
    );
};

// Mode formulir
const isEdit = computed(() => !!props.editTask);
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- TITLE -->
        <div>
            <label class="font-semibold">Title</label>
            <InputText v-model="form.title" class="w-full" placeholder="Task title" />
        </div>

        <!-- DESCRIPTION -->
        <div>
            <label class="font-semibold">Description</label>
            <Textarea v-model="form.description" rows="4" class="w-full" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="font-semibold">Start Date</label>
                <Calendar class="w-full" v-model="form.start_date" dateFormat="yy-mm-dd" showIcon />
            </div>

            <div>
                <label class="font-semibold">Due Date</label>
                <Calendar class="w-full" v-model="form.due_date" dateFormat="yy-mm-dd" showIcon :minDate="minDueDate" />
            </div>
        </div>

        <!-- TYPE / STATUS / PRIORITY -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="font-semibold">Type</label>
                <Dropdown
                    class="w-full"
                    v-model="form.type_id"
                    :options="props.taskTypes"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Type"
                />
            </div>

            <div>
                <label class="font-semibold">Status</label>
                <Dropdown
                    class="w-full"
                    v-model="form.status_id"
                    :options="props.taskStatuses"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Status"
                />
            </div>

            <div>
                <label class="font-semibold">Priority</label>
                <Dropdown
                    class="w-full"
                    v-model="form.priority_id"
                    :options="props.taskPriorities"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Priority"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="font-semibold">Archived</label>
                <Dropdown
                    class="w-full"
                    v-model="form.is_archived"
                    :options="[
                        { label: 'No', value: false },
                        { label: 'Yes', value: true },
                    ]"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Archived Status"
                />
            </div>

            <div>
                <label class="font-semibold">Progress (%)</label>
                <InputNumber v-model="form.progress" class="w-full" placeholder="0 - 100" />
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" />
            <Button v-if="!isEdit" label="Create Task" @click="save" />
            <Button v-else label="Update Task" severity="warning" @click="update" />
        </div>
    </div>
</template>
