<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, watch as vueWatch, watch } from 'vue';

interface Props {
    visible: boolean;
    projects: { id: number; title: string }[];
    statuses: { id: number; name: string }[];
    priorities: { id: number; name: string }[];
    types: { id: number; name: string }[];
    tasks: { id: number; title: string }[];
    taskId: number | null;
}

const props = defineProps<Props>();
const emits = defineEmits<{
    (e: 'update:visible', value: boolean): void;
}>();

const toast = useToast();

const visible = computed({
    get: () => props.visible,
    set: (val) => emits('update:visible', val),
});

const form = useForm({
    project_id: null,
    parent_id: null,
    title: '',
    description: '',
    start_date: null,
    due_date: null,
    status_id: null,
    priority_id: null,
    type_id: null,
    is_archived: false,
    progress: 0,
});

watch(
    () => props.taskId,
    (val) => {
        if (val) {
            const task = props.tasks.find((x) => x.id === val);
            if (task) {
                form.project_id = task.project_id;
                form.parent_id = task.parent_id;
                form.title = task.title;
                form.description = task.description;
                form.start_date = task.start_date ? new Date(task.start_date) : null;
                form.due_date = task.due_date ? new Date(task.due_date) : null;
                form.status_id = task.status_id;
                form.priority_id = task.priority_id;
                form.type_id = task.type_id;
                form.progress = task.progress;
                form.is_archived = task.is_archived == 1;
            }
        } else {
            form.reset();
        }
    },
    { immediate: true },
);

const save = () => {
    form.transform((data) => ({
        ...data,
        is_archived: data.is_archived ? 1 : 0,
        progress: Number(data.progress),
        start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
        due_date: data.due_date ? moment(data.due_date).format('YYYY-MM-DD') : null,
    }));

    const isUpdate = !!props.taskId;

    const url = isUpdate ? route('task.update', props.taskId) : route('task.store');

    form.submit(isUpdate ? 'put' : 'post', url, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            form.reset();
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: isUpdate ? 'Task updated successfully.' : 'Task created successfully.',
                life: 3000,
            });
        },
    });
};

vueWatch(
    () => form.data(),
    () => {
        Object.keys(form.errors).forEach((key) => {
            if (form[key] !== undefined) delete form.errors[key];
        });
    },
    { deep: true },
);
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="props.taskId ? 'Update Task' : 'Create Task'">
        <form class="grid gap-8 md:grid-cols-2" @submit.prevent="save">
            <!-- Project -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label>Project</Label>
                <Dropdown v-model="form.project_id" :options="projects" optionLabel="title" optionValue="id" placeholder="Select project" fluid />
                <small v-if="form.errors.project_id" class="text-red-500">
                    {{ form.errors.project_id }}
                </small>
            </div>

            <!-- Parent Task -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label>Parent Task</Label>
                <Dropdown
                    v-model="form.parent_id"
                    :options="props.tasks ?? []"
                    optionLabel="title"
                    optionValue="id"
                    placeholder="No Parent (Root Task)"
                    filter
                    fluid
                />
                <small v-if="form.errors.parent_id" class="text-red-500">
                    {{ form.errors.parent_id }}
                </small>
            </div>

            <!-- Title -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label>Title</Label>
                <InputText v-model="form.title" placeholder="Task title" fluid />
                <small v-if="form.errors.title" class="text-red-500">
                    {{ form.errors.title }}
                </small>
            </div>

            <!-- Status -->
            <div class="flex flex-col gap-2">
                <Label>Status</Label>
                <Dropdown v-model="form.status_id" :options="statuses" optionLabel="name" optionValue="id" fluid />
                <small v-if="form.errors.status_id" class="text-red-500">
                    {{ form.errors.status_id }}
                </small>
            </div>

            <!-- Priority -->
            <div class="flex flex-col gap-2">
                <Label>Priority</Label>
                <Dropdown v-model="form.priority_id" :options="priorities" optionLabel="name" optionValue="id" fluid />
                <small v-if="form.errors.priority_id" class="text-red-500">
                    {{ form.errors.priority_id }}
                </small>
            </div>

            <!-- Type -->
            <div class="flex flex-col gap-2">
                <Label>Type</Label>
                <Dropdown v-model="form.type_id" :options="types" optionLabel="name" optionValue="id" fluid />
                <small v-if="form.errors.type_id" class="text-red-500">
                    {{ form.errors.type_id }}
                </small>
            </div>

            <!-- Dates -->
            <div class="flex flex-col gap-2">
                <Label>Start Date</Label>
                <DatePicker v-model="form.start_date" date-format="yy-mm-dd" fluid />
            </div>

            <div class="flex flex-col gap-2">
                <Label>Due Date</Label>
                <DatePicker v-model="form.due_date" date-format="yy-mm-dd" :min-date="form.start_date ?? undefined" fluid />
            </div>

            <!-- Archived -->
            <div class="flex items-center gap-2">
                <Checkbox v-model="form.is_archived" binary />
                <Label>Archived</Label>
            </div>

            <!-- Progress -->
            <div class="flex flex-col gap-2">
                <Label>Progress (%)</Label>
                <InputText v-model.number="form.progress" type="number" min="0" max="100" placeholder="0 - 100" fluid />
                <small v-if="form.errors.progress" class="text-red-500">
                    {{ form.errors.progress }}
                </small>
            </div>

            <!-- Description -->
            <div class="col-span-2 flex flex-col gap-2">
                <Label>Description</Label>
                <Editor v-model="form.description" editorStyle="height: 200px" />
                <small v-if="form.errors.description" class="text-red-500">
                    {{ form.errors.description }}
                </small>
            </div>

            <!-- Buttons -->
            <div class="col-span-2 flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" type="submit" :loading="form.processing" />
            </div>
        </form>
    </Drawer>
</template>
