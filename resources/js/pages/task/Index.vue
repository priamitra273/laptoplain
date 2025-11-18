<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import TaskForm from './Form.vue';
import TaskTable from './Table.vue';

interface Props {
    tasks: any[];
    statuses: any[];
    priorities: any[];
    types: any[];
    projects: any[];
}

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
});

// --- MODAL STATE ---
const showModal = ref(false);
const editId = ref<number | null>(null);

// --- OPEN ADD ---
const openAdd = () => {
    editId.value = null;
    showModal.value = true;
};

// --- OPEN EDIT ---
const openEdit = (id: number) => {
    editId.value = id;
    showModal.value = true;
};

// --- CLOSE MODAL ---
const closeModal = () => {
    showModal.value = false;
};
</script>

<template>
    <AppLayout>
        <Head title="My Tasks" />

        <div class="mb-4 flex items-center justify-between">
            <Heading title="My Tasks" />

            <!-- manual add button (opsional) -->
            <button class="rounded-lg bg-primary px-4 py-2 text-white" @click="openAdd">Add Task</button>
        </div>

        <!-- TABLE -->
        <TaskTable :tasks="props.tasks" @add="openAdd" @edit="openEdit" />

        <!-- MODAL -->

        <TaskForm
            :visible="showModal"
            @update:visible="(val) => (showModal = val)"
            :taskId="editId"
            :statuses="props.statuses"
            :priorities="props.priorities"
            :types="props.types"
            :projects="props.projects"
            :tasks="props.tasks"
            @saved="closeModal"
            @cancel="closeModal"
        />
    </AppLayout>
</template>
