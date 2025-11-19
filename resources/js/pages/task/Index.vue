<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import TaskForm from './Form.vue';
import TaskTable from './Table.vue';

interface Props {
    tasks: any[];
    statuses: { id: number; name: string; severity: string }[];
    priorities: { id: number; name: string; severity: string }[];
    types: { id: number; name: string; severity: string }[];
    projects: { id: number; title: string }[];
}

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
    statuses: () => [],
    priorities: () => [],
    types: () => [],
    projects: () => [],
});

const showForm = ref(false);
const selectedTask = ref<any | null>(null);

// NOTE:
// Task index (halaman semua task) tidak punya "projectEncoded".
// Jadi project akan dipilih langsung di Form (dropdown) → opsional.
const selectedProject = ref(null);

function newTask() {
    selectedTask.value = null;
    showForm.value = true;
}

function editTask(task: any) {
    selectedTask.value = task;
    showForm.value = true;
}
</script>

<template>
    <Head title="Tasks" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Task List" description="Manage all your tasks" />

            <!-- CARD WRAPPER -->
            <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Tasks</h2>
                    <Button label="Add Task" icon="pi pi-plus" @click="newTask" />
                </div>

                <TaskTable :tasks="props.tasks" @edit="editTask" />
            </div>
        </div>

        <!-- FORM MODAL -->
        <TaskForm
            v-model="showForm"
            :task="selectedTask"
            :statuses="props.statuses"
            :priorities="props.priorities"
            :types="props.types"
            :projects="props.projects"
        />
    </AppLayout>
</template>
