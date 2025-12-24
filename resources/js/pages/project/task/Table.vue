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
    isMember: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: Task): void;
}>();

const currentPage = ref(1);
const itemsPerPage = ref(10);
const searchQuery = ref<string>('');
const selectedKey = ref<{ [key: string]: any }>({});

// Format tasks for TreeTable
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

// Filter and sort tasks based on search query (newest first)
const filteredTasks = computed(() => {
    let tasks = formatTasks(props.tasks);

    // Sort by created_at or updated_at (newest first)
    tasks = tasks.sort((a, b) => {
        const dateA = new Date(a.original.updated_at || a.original.created_at).getTime();
        const dateB = new Date(b.original.updated_at || b.original.created_at).getTime();
        return dateB - dateA; // Descending order (newest first)
    });

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

// Paginate tasks
const paginatedTasks = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTasks.value.slice(start, end);
});

// Reset page when search query changes
watch([searchQuery], () => {
    currentPage.value = 1;
});

const onPageChange = (event: { page: number; rows: number }) => {
    currentPage.value = event.page + 1;
    itemsPerPage.value = event.rows;
};

const confirm = useConfirm();
const toast = useToast();

// Remove single task
const remove = (t: Task) => {
    confirm.require({
        message: `Remove ${t.title}? This action cannot be undone.`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Yes, remove',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancel',
        accept: () => {
            router.delete(route('project.tasks.destroy', { projectEncoded: props.projectId, taskEncoded: t.id }), {
                preserveScroll: true,
            });

            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Task removed successfully',
                life: 3000,
            });
        },
    });
};

// Select All (all filtered tasks, including children)
const selectAll = () => {
    const keys: { [key: string]: any } = {};

    const mark = (node: TaskFormatted) => {
        keys[node.key] = { checked: true, partialChecked: false };
        if (node.children) node.children.forEach(mark);
    };

    filteredTasks.value.forEach(mark);
    selectedKey.value = { ...keys };
};

// Clear selection
const clearSelection = () => {
    selectedKey.value = {};
    selectedKey.value = { ...selectedKey.value };
};

// Remove selected tasks
const removeSelected = () => {
    const ids = Object.keys(selectedKey.value);

    if (!ids.length) {
        toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'No tasks selected to delete.',
            life: 3000,
        });
        return;
    }

    confirm.require({
        message: `Delete ${ids.length} selected task(s)? This action cannot be undone.`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Yes, delete',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancel',
        accept: () => {
            ids.forEach((id) => {
                router.delete(route('project.tasks.destroy', { projectEncoded: props.projectId, taskEncoded: id }), {
                    preserveScroll: true,
                });
            });

            selectedKey.value = {};
            selectedKey.value = { ...selectedKey.value };
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: `${ids.length} tasks deleted successfully`,
                life: 3000,
            });
        },
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Header with buttons -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-lg font-semibold">Tasks</h3>
            <div class="flex w-full flex-wrap gap-2 sm:w-auto" v-if="isMember">
                <Button label="Add Task" icon="pi pi-plus" @click="emit('add', null)" class="w-full min-w-[120px] sm:w-auto sm:min-w-0" />
                <Button
                    label="Select All"
                    icon="pi pi-check-square"
                    @click="selectAll"
                    class="w-full min-w-[120px] sm:w-auto sm:min-w-0"
                    variant="outlined"
                />
                <Button
                    label="Clear"
                    icon="pi pi-times"
                    severity="secondary"
                    @click="clearSelection"
                    class="w-full min-w-[120px] sm:w-auto sm:min-w-0"
                />
                <Button
                    label="Delete Selected"
                    icon="pi pi-trash"
                    severity="danger"
                    @click="removeSelected"
                    class="w-full min-w-[120px] sm:w-auto sm:min-w-0"
                    variant="outlined"
                />
            </div>
        </div>

        <!-- Search input -->
        <div class="mb-4 w-full">
            <label class="mb-2 block text-sm font-medium">Search</label>
            <InputText v-model="searchQuery" placeholder="Search by title..." class="w-full" />
        </div>

        <!-- TreeTable container scrollable for mobile -->
        <div class="overflow-x-auto">
            <TreeTable
                v-model:selectionKeys="selectedKey"
                :value="paginatedTasks"
                selectionMode="checkbox"
                :propagateSelectionDown="true"
                :propagateSelectionUp="true"
                class="min-w-full"
            >
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
                        <Button icon="pi pi-eye" size="small" severity="secondary" @click="router.visit(route('task.show', node.original))" />
                        <Button icon="pi pi-plus" size="small" severity="info" @click="emit('add', node.data.id)" v-if="isMember" />
                        <Button icon="pi pi-pencil" size="small" severity="warning" @click="emit('edit', node.original)" v-if="isMember" />
                        <Button icon="pi pi-trash" size="small" severity="danger" @click="remove(node.original)" v-if="isMember" />
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data Available</p>
                </template>
            </TreeTable>
        </div>

        <!-- Pagination -->
        <Paginator :rows="itemsPerPage" :totalRecords="filteredTasks.length" :rowsPerPageOptions="[10, 25, 50]" @page="onPageChange" />
    </div>
</template>
