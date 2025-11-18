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

const showModal = ref(false);
const editId = ref<number | null>(null);

const openAdd = () => {
    editId.value = null;
    showModal.value = true;
};

const openEdit = (id: number) => {
    editId.value = id;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};
</script>

<template>
    <AppLayout>
        <Head title="My Tasks" />

        <div class="mb-4 flex items-center justify-between">
            <Heading title="My Tasks" />
        </div>

        <TaskTable :tasks="props.tasks" @add="openAdd" @edit="openEdit" />

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
