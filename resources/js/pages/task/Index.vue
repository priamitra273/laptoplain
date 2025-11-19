<script setup>
import { router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { ref } from 'vue';
import Form from './Form.vue';
import Table from './Table.vue';

const page = usePage();

const tasks = page.props.tasks;
const statuses = page.props.statuses;
const priorities = page.props.priorities;
const types = page.props.types;

const showForm = ref(false);
const selectedTask = ref(null);

// Create new
const createTask = () => {
    selectedTask.value = {};
    showForm.value = true;
};

// Edit
const editTask = (task) => {
    selectedTask.value = task;
    showForm.value = true;
};

// Delete
const deleteTask = (id) => {
    if (!confirm('Delete this task?')) return;

    router.delete(
        route('task.destroy', {
            encoded: page.props.project_id,
            taskEncoded: id,
        }),
    );
};

// Submit form (create/update)
const submitForm = (form) => {
    const routeName = selectedTask.value?.id ? 'task.update' : 'task.store';

    router.post(
        route(routeName, {
            encoded: page.props.project_id,
            taskEncoded: selectedTask.value?.id,
        }),
        form,
        { onSuccess: () => (showForm.value = false) },
    );
};
</script>

<template>
    <div class="p-4">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold">Tasks</h2>

            <Button label="Add Task" icon="pi pi-plus" @click="createTask" />
        </div>

        <Table :tasks="tasks" :statuses="statuses" :priorities="priorities" :types="types" @edit="editTask" @delete="deleteTask" />

        <Form v-model="showForm" :task="selectedTask" :statuses="statuses" :priorities="priorities" :types="types" @submit="submitForm" />
    </div>
</template>
