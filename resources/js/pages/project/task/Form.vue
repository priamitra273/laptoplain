<script setup lang="ts">
import { InertiaForm, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Swal from 'sweetalert2';
import { computed } from 'vue';

import type { Task, TaskPriority, TaskStatus, TaskType } from '..';

interface Props {
    parentId: string | null
    projectId: string;
    task: Task | null;
    taskTypes?: TaskType[];
    taskStatuses?: TaskStatus[];
    taskPriorities?: TaskPriority[];
    editTask?: Task | null;
}

interface Form {
    _method: 'POST' | 'PUT';
    title: string;
    description: string;
    project_id: string;
    type_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    parent_id: string | null;
    start_date: Date | null;
    due_date: Date | null;
    is_archived: boolean;
    progress_value: number;
    [key: string]: any;
}

const toDate = (value?: string | null): Date | null => (value ? new Date(value) : null);

const minDueDate = computed(() => (form.start_date ? form.start_date : undefined));

const props = defineProps<Props>();
const emit = defineEmits(['close', 'saved']);

const form: InertiaForm<Form> = useForm({
    _method: props?.task ? 'PUT' : 'POST',

    project_id: props.projectId,
    title: props?.task?.title ?? '',
    description: props?.task?.description ?? '',

    type_id: props?.task?.type?.id ?? null,
    status_id: props?.task?.status?.id ?? null,
    priority_id: props?.task?.priority?.id ?? null,
    parent_id: props?.parentId ?? null,

    start_date: toDate(props?.task?.start_date),
    due_date: toDate(props?.task?.due_date),

    is_archived: props?.task?.is_archived ?? false,
    progress_value: props?.task?.progress ?? 0,
});

const isEdit = computed(() => !!props.task);

const routeName = computed(() => (isEdit.value ? 'project.tasks.update' : 'project.tasks.store'));

const submit = () => {
    const param: any = { projectEncoded: props.projectId };

    if (isEdit.value) {
        param.taskEncoded = props.task?.id;
        form.put(route(routeName.value, param), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                form.reset();
                Swal.fire('Success', 'Task updated', 'success')
            },
        });
    } else {
        form.post(route(routeName.value, param), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                form.reset();
                Swal.fire('Success', 'Task added', 'success')
            },
        });
    }
};

const hasChild = computed(() => {
    return Boolean(
        props.task &&
        Array.isArray(props.task.children) &&
        props.task.children.length > 0
    );
});
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
                <InputNumber v-model="form.progress_value" class="w-full" placeholder="0 - 100" :disabled="hasChild" />
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="emit('close')" />
            <Button v-if="!isEdit" label="Create Task" @click="submit" />
            <Button v-else label="Update Task" severity="warning" @click="submit" />
        </div>
    </div>
</template>
