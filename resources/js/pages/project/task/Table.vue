<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import TreeTable from 'primevue/treetable';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';
import { Task, TaskFormatted } from '..';

interface Props {
    projectId: string;
    tasks: Task[];
    isPM: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: Task): void;
}>();

// State untuk paginasi
const currentPage = ref(1);
const itemsPerPage = ref(10);

// State untuk search
const searchQuery = ref<string>('');

// Format data untuk TreeTable
const formatTasks = (list?: Task[]): TaskFormatted[] => {
    if (!list || !Array.isArray(list)) return [];
    return list.map((t) => ({
        key: t.id,
        original: t,
        data: {
            id: t.id,
            title: t.title,
            status: t.status,
            priority: t.priority,
            type: t.type,
        },
        children: t.sub_task_recursive ? formatTasks(t.sub_task_recursive) : [],
    }));
};

// Search tasks
const filteredTasks = computed(() => {
    let tasks = formatTasks(props.tasks);

    // Search by title
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        tasks = tasks.filter(
            (task) =>
                task.data.title.toLowerCase().includes(query) ||
                (task.children && task.children.some((child) => child.data.title.toLowerCase().includes(query))),
        );
    }

    return tasks;
});

// Data yang ditampilkan di halaman saat ini (hanya parent)
const paginatedTasks = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTasks.value.slice(start, end);
});

// Total halaman (berdasarkan parent yang sudah difilter)
const totalPages = computed(() => {
    return Math.ceil(filteredTasks.value.length / itemsPerPage.value);
});

// Reset ke halaman 1 saat search berubah
watch([searchQuery], () => {
    currentPage.value = 1;
});

// Fungsi untuk mengubah halaman atau jumlah item per halaman
const onPageChange = (event: { page: number; rows: number }) => {
    currentPage.value = event.page + 1;
    itemsPerPage.value = event.rows;
};

// Setup confirm & toast
const confirm = useConfirm();
const toast = useToast();

// Fungsi untuk menghapus task
const remove = (t: Task) => {
    const message =
        t.sub_task_recursive && t.sub_task_recursive.length > 0
            ? 'This task has children. Removing it will remove its children.'
            : 'This action cannot be undone.';
    confirm.require({
        message: `Remove ${t.title}? ${message}`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Yes, remove',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancel',
        accept: () => {
            router.delete(
                route('project.tasks.destroy', {
                    projectEncoded: props.projectId,
                    taskEncoded: t.id,
                }),
                {
                    onSuccess: () => {
                        toast.add({
                            severity: 'success',
                            summary: 'Success',
                            detail: 'Task removed successfully',
                            life: 3000,
                        });
                    },
                    preserveScroll: true,
                },
            );
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-row justify-between">
            <h3 class="mb-4 text-lg font-semibold">Tasks</h3>
            <Button label="Add Task" icon="pi pi-plus" @click="emit('add', null)" />
        </div>

        <!-- Search Section -->
        <div class="mb-4 flex-1">
            <label for="search" class="mb-2 block text-sm font-medium">Search</label>
            <InputText id="search" v-model="searchQuery" placeholder="Search by title..." class="w-full" />
        </div>

        <!-- TreeTable -->
        <TreeTable :value="paginatedTasks" tableStyle="min-width: 50rem">
            <Column field="title" header="Title" expander />
            <Column header="Status">
                <template #body="{ node }">
                    <Tag :value="node.data.status?.name" :severity="node.data.status?.severity" />
                </template>
            </Column>
            <Column header="Priority">
                <template #body="{ node }">
                    <Tag :value="node.data.priority?.name" :severity="node.data.priority?.severity" />
                </template>
            </Column>
            <Column header="Type">
                <template #body="{ node }">
                    <Tag :value="node.data.type?.name" :severity="node.data.type?.severity" />
                </template>
            </Column>
            <Column header="Actions">
                <template #body="{ node }">
                    <Button icon="pi pi-plus" severity="help" size="small" @click="emit('add', node.data.id)" />
                    <Button icon="pi pi-pencil" severity="warning" size="small" @click="emit('edit', node.original)" />
                    <Button icon="pi pi-trash" severity="danger" size="small" @click="remove(node.original)" />
                    <Button label="Detail" @click="router.visit(route('task.show', node.original))" />
                </template>
            </Column>
            <template #empty>
                <p class="text-center">No Data Available</p>
            </template>
        </TreeTable>

        <!-- Paginator -->
        <Paginator :rows="itemsPerPage" :totalRecords="filteredTasks.length" :rowsPerPageOptions="[10, 25, 50]" @page="onPageChange" />
    </div>
</template>
