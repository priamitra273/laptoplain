<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import TreeTable from 'primevue/treetable';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, ComputedRef, ref, watch } from 'vue';
import { Task, TaskFormatted, TaskFormattedData, TaskUser } from '..';

interface Props {
    projectId: string;
    tasks: Task[];
    isPM: boolean;
    isMember: boolean;
    isOwner: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: Task): void;
}>();

const currentUser = usePage().props.auth.user;

const currentPage = ref(1);
const itemsPerPage = ref(10);
const searchQuery = ref<string>('');
const selectedKey = ref<{ [key: string]: any }>({});

// Format date helper
const formatDate = (date: string | null | undefined): string => {
    if (!date) return '-';
    return moment(date).format('DD MMM YYYY');
};

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
            progress: t.progress ?? 0,
            users: t.users || [],
            start_date: t.start_date,
            due_date: t.due_date,
        },
        children: t.sub_task_recursive ? formatTasks(t.sub_task_recursive) : [],
    }));
};

// Filter and sort tasks based on search query (newest first)
const filteredTasks: ComputedRef<TaskFormatted[]> = computed(() => {
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
const paginatedTasks: ComputedRef<TaskFormatted[]> = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTasks.value.slice(start, end);
});

// Check if all tasks are selected
const isAllSelected = computed(() => {
    if (!filteredTasks.value.length) return false;

    const allKeys: string[] = [];
    const collectKeys = (node: TaskFormatted) => {
        allKeys.push(node.key);
        if (node.children) node.children.forEach(collectKeys);
    };
    filteredTasks.value.forEach(collectKeys);

    return allKeys.every((key) => selectedKey.value[key]?.checked);
});

// Check if any task is selected
const hasSelectedTasks = computed(() => {
    return Object.keys(selectedKey.value).length > 0;
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

// Toggle Select All
const toggleSelectAll = () => {
    if (isAllSelected.value) {
        clearSelection();
    } else {
        selectAll();
    }
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

const hasAccessToEditAndDelete = (task: TaskFormattedData): boolean => {
    if (props.isOwner) return true;

    const taskUsers: TaskUser[] = task.users || [];
    const isMember = taskUsers.some((tu) => tu.id === currentUser.id);

    return isMember;
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
                    v-if="hasSelectedTasks"
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
            <TreeTable :value="paginatedTasks" class="min-w-full" scrollable scrollHeight="600px">
                <!-- Select All Checkbox Column - FROZEN LEFT -->
                <Column :expander="false" style="width: 3rem" v-if="isMember" frozen alignFrozen="left">
                    <template #header>
                        <Checkbox :modelValue="isAllSelected" @update:modelValue="toggleSelectAll" binary />
                    </template>
                    <template #body="{ node }">
                        <Checkbox
                            :modelValue="selectedKey[node.key]?.checked"
                            @update:modelValue="
                                (value) => {
                                    if (value) {
                                        selectedKey[node.key] = { checked: true, partialChecked: false };
                                    } else {
                                        delete selectedKey[node.key];
                                    }
                                    selectedKey = { ...selectedKey };
                                }
                            "
                            binary
                        />
                    </template>
                </Column>

                <!-- Expander Column - FROZEN LEFT -->
                <Column :expander="true" style="width: 3rem" frozen alignFrozen="left" />

                <!-- Title Column -->
                <Column field="title" header="Title" style="min-width: 200px" />

                <Column header="Status" style="min-width: 120px">
                    <template #body="{ node }">
                        <Tag :value="node.data.status?.name" :severity="node.data.status?.severity" />
                    </template>
                </Column>

                <Column header="Priority" style="min-width: 120px">
                    <template #body="{ node }">
                        <Tag :value="node.data.priority?.name" :severity="node.data.priority?.severity" />
                    </template>
                </Column>

                <Column header="Type" style="min-width: 120px">
                    <template #body="{ node }">
                        <Tag :value="node.data.type?.name" :severity="node.data.type?.severity" />
                    </template>
                </Column>

                <!-- Start Date Column -->
                <Column header="Start Date" style="min-width: 120px">
                    <template #body="{ node }">
                        <span>{{ formatDate(node.data.start_date) }}</span>
                    </template>
                </Column>

                <!-- Due Date Column -->
                <Column header="Due Date" style="min-width: 120px">
                    <template #body="{ node }">
                        <span>{{ formatDate(node.data.due_date) }}</span>
                    </template>
                </Column>

                <Column header="Progress" style="min-width: 150px">
                    <template #body="{ node }">
                        <div class="flex min-w-[120px] items-center gap-2">
                            <ProgressBar :value="node.data.progress" :showValue="false" class="h-2 flex-1" />
                            <span class="text-xs">{{ node.data.progress }}%</span>
                        </div>
                    </template>
                </Column>

                <!-- Actions Column - FROZEN RIGHT -->
                <Column header="Actions" frozen alignFrozen="right" style="min-width: 200px">
                    <template #body="{ node }">
                        <div class="flex gap-1">
                            <Link :href="route('task.show', node.original)">
                                <Button icon="pi pi-eye" size="small" severity="secondary" />
                            </Link>
                            <Button icon="pi pi-plus" size="small" severity="info" @click="emit('add', node.data.id)" v-if="isMember" />
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                severity="warning"
                                @click="emit('edit', node.original)"
                                v-if="isMember && hasAccessToEditAndDelete(node.data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                @click="remove(node.original)"
                                v-if="isMember && hasAccessToEditAndDelete(node.data)"
                            />
                        </div>
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
