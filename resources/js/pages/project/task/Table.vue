<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import TreeTable from 'primevue/treetable';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, ComputedRef, ref } from 'vue';
import { Task, TaskFormatted, TaskFormattedData, TaskPriority, TaskStatus, TaskType, TaskUser } from '..';

interface Props {
    projectId: string;
    tasks: Task[];
    isMember: boolean;
    hasPermission: boolean;
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'add', parentId: string | null): void;
    (e: 'edit', task: Task, parentId: string | null): void;
}>();

const deleteLoading = ref(false);

const currentUser = usePage().props.auth.user;

const searchQuery = ref<string>('');
const selectedKey = ref<{ [key: string]: any }>({});

// Filter refs
const selectedStatuses = ref<string[]>([]);
const selectedPriorities = ref<string[]>([]);
const selectedTypes = ref<string[]>([]);

// Format date helper
const formatDate = (date: string | null | undefined): string => {
    if (!date) return '-';
    return moment(date).format('DD MMM YYYY');
};

// Get initials for avatar
const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

// Get color for avatar
const getUserColor = (index: number) => `hsl(${index * 60}, 70%, 60%)`;

// Format tasks for TreeTable — now accepts a `level` parameter for indentation
const formatTasks = (list?: Task[], level: number = 0): TaskFormatted[] => {
    if (!list || !Array.isArray(list)) return [];
    return list.map((t) => ({
        key: t.id,
        original: t,
        data: {
            id: t.id,
            parent_id: t.parent_id,
            title: t.title,
            status: t.status,
            priority: t.priority,
            type: t.type,
            progress: Number(t.progress) ?? 0,
            users: t.users || [],
            start_date: t.start_date,
            due_date: t.due_date,
            created_by: t.created_by,
            completed_at: t.completed_at,
            is_overdue: t.is_overdue,
            level,
        },
        children: t.sub_task_recursive ? formatTasks(t.sub_task_recursive, level + 1) : [],
    }));
};

// Get filter options from master data (props) - show all available options
const statusOptions = computed(() => props.taskStatuses ?? []);
const priorityOptions = computed(() => props.taskPriorities ?? []);
const typeOptions = computed(() => props.taskTypes ?? []);

// Filter tasks recursively
const filterTaskRecursive = (task: TaskFormatted, query: string): boolean => {
    // Check if current task matches
    const matchesSearch = !query || task.data.title.toLowerCase().includes(query);
    const matchesStatus =
        !selectedStatuses.value ||
        selectedStatuses.value.length === 0 ||
        (task.data.status?.name && selectedStatuses.value.includes(task.data.status.name));

    const matchesPriority =
        !selectedPriorities.value ||
        selectedPriorities.value.length === 0 ||
        (task.data.priority?.name && selectedPriorities.value.includes(task.data.priority.name));

    const matchesType =
        !selectedTypes.value || selectedTypes.value.length === 0 || (task.data.type?.name && selectedTypes.value.includes(task.data.type.name));

    const currentMatches = matchesSearch && matchesStatus && matchesPriority && matchesType;

    // Check if any children match
    const hasMatchingChildren = task.children && task.children.some((child) => filterTaskRecursive(child, query));

    return currentMatches || hasMatchingChildren;
};

// Filter and sort tasks based on search query and filters (newest first)
const filteredTasks: ComputedRef<TaskFormatted[]> = computed(() => {
    if (!props.tasks || !Array.isArray(props.tasks)) return [];

    let tasks = formatTasks(props.tasks);

    // Sort by created_at or updated_at (newest first)
    tasks = tasks.sort((a, b) => {
        const dateA = new Date(a.original.updated_at || a.original.created_at).getTime();
        const dateB = new Date(b.original.updated_at || b.original.created_at).getTime();
        return dateB - dateA; // Descending order (newest first)
    });

    const query = searchQuery.value.toLowerCase();

    // Apply filters
    tasks = tasks.filter((task) => filterTaskRecursive(task, query));

    return tasks;
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

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return (
        searchQuery.value !== '' ||
        (selectedStatuses.value && selectedStatuses.value.length > 0) ||
        (selectedPriorities.value && selectedPriorities.value.length > 0) ||
        (selectedTypes.value && selectedTypes.value.length > 0)
    );
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedStatuses.value = [];
    selectedPriorities.value = [];
    selectedTypes.value = [];
};

// Handle clear for individual filters
const handleClearStatuses = () => {
    selectedStatuses.value = [];
};

const handleClearPriorities = () => {
    selectedPriorities.value = [];
};

const handleClearTypes = () => {
    selectedTypes.value = [];
};

const confirm = useConfirm();
const toast = useToast();

// Remove single task
const remove = (t: Task) => {
    deleteLoading.value = true;
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
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete task', life: 3000 });
                },
                onFinish: () => (deleteLoading.value = false),
            });
        },
        reject: () => (deleteLoading.value = false),
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
    if (props.hasPermission) return true;

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
            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                <Button 
                    label="Add Task" 
                    icon="pi pi-plus" 
                    @click="emit('add', null)" 
                    class="w-full min-w-[120px] sm:w-auto sm:min-w-0" 
                    :disabled="!isMember && !hasPermission" 
                />
                <Button
                    v-if="hasSelectedTasks"
                    label="Delete Selected"
                    icon="pi pi-trash"
                    severity="danger"
                    @click="removeSelected"
                    class="w-full min-w-[120px] sm:w-auto sm:min-w-0"
                    variant="outlined"
                    :disabled="!isMember && !hasPermission" 
                />
            </div>
        </div>

        <!-- Filters Section -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <!-- Search input -->
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Search</label>
                <InputText v-model="searchQuery" placeholder="Search by title..." class="w-full" />
            </div>

            <!-- Status Filter -->
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Status</label>
                <MultiSelect
                    v-model="selectedStatuses"
                    :options="statusOptions"
                    optionLabel="name"
                    optionValue="name"
                    placeholder="Select Status"
                    class="w-full"
                    :maxSelectedLabels="2"
                    showClear
                    @clear="handleClearStatuses"
                >
                    <template #option="slotProps">
                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                    </template>
                    <template #header>
                        <div class="flex items-center gap-2 px-3 py-2">
                            <span class="font-semibold">Select All</span>
                        </div>
                    </template>
                </MultiSelect>
            </div>

            <!-- Priority Filter -->
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Priority</label>
                <MultiSelect
                    v-model="selectedPriorities"
                    :options="priorityOptions"
                    optionLabel="name"
                    optionValue="name"
                    placeholder="Select Priority"
                    class="w-full"
                    :maxSelectedLabels="2"
                    showClear
                    @clear="handleClearPriorities"
                >
                    <template #option="slotProps">
                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                    </template>
                    <template #header>
                        <div class="flex items-center gap-2 px-3 py-2">
                            <span class="font-semibold">Select All</span>
                        </div>
                    </template>
                </MultiSelect>
            </div>

            <!-- Type Filter -->
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Type</label>
                <MultiSelect
                    v-model="selectedTypes"
                    :options="typeOptions"
                    optionLabel="name"
                    optionValue="name"
                    placeholder="Select Type"
                    class="w-full"
                    :maxSelectedLabels="2"
                    showClear
                    @clear="handleClearTypes"
                >
                    <template #option="slotProps">
                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                    </template>
                    <template #header>
                        <div class="flex items-center gap-2 px-3 py-2">
                            <span class="font-semibold">Select All</span>
                        </div>
                    </template>
                </MultiSelect>
            </div>
        </div>

        <!-- Clear Filters Button -->
        <div v-if="hasActiveFilters" class="flex justify-end">
            <Button label="Clear Filters" icon="pi pi-filter-slash" @click="clearFilters" severity="secondary" size="small" text />
        </div>

        <!-- TreeTable container scrollable for mobile -->
        <div class="overflow-x-auto">
            <TreeTable :value="filteredTasks" class="min-w-full" scrollable scrollHeight="600px" removableSort>
                <!-- Select All Checkbox Column - FROZEN LEFT -->
                <Column :expander="false" style="width: 3rem" v-if="isMember || hasPermission" frozen alignFrozen="left">
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

                <!-- Title Column with indentation based on level -->
                <Column field="title" header="Title" sortable frozen expander align-frozen="left">
                    <template #body="{ node }">
                        <p class="max-w-[300px] truncate text-ellipsis">{{ node.data.title }}</p>
                    </template>
                </Column>

                <Column field="status.name" header="Status" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <Tag :value="node.data.status?.name" :severity="node.data.status?.severity" />
                    </template>
                </Column>

                <Column field="priority.name" header="Priority" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <Tag :value="node.data.priority?.name" :severity="node.data.priority?.severity" />
                    </template>
                </Column>

                <Column field="type.name" header="Type" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <Tag :value="node.data.type?.name" :severity="node.data.type?.severity" />
                    </template>
                </Column>

                <!-- Start Date Column -->
                <Column field="start_date" header="Start Date" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <span>{{ formatDate(node.data.start_date) }}</span>
                    </template>
                </Column>

                <!-- Due Date Column -->
                <Column field="due_date" header="Due Date" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <span :class="{ 'text-red-500': node.data.is_overdue }">{{ formatDate(node.data.due_date) }}</span>
                    </template>
                </Column>

                <Column field="completed_at" header="Complete Date" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <span>{{ formatDate(node.data.completed_at) }}</span>
                    </template>
                </Column>

                <Column field="progress" header="Progress" style="min-width: 150px" sortable>
                    <template #body="{ node }">
                        <ProgressBar :value="node.data.progress" :showValue="true" class="min-w-[120px]" />
                    </template>
                </Column>

                <!-- Created By Column -->
                <Column header="Created By" style="min-width: 150px;">
                    <template #body="{ node }">
                        <div v-if="node.original.creator" class="flex items-center gap-2">
                            <Avatar
                                :image="
                                    node.original.creator.avatar_url && node.original.creator.avatar_url !== '/images/default-avatar.png'
                                        ? node.original.creator.avatar_url
                                        : undefined
                                "
                                :label="
                                    !node.original.creator.avatar_url || node.original.creator.avatar_url === '/images/default-avatar.png'
                                        ? getInitials(node.original.creator.name)
                                        : undefined
                                "
                                shape="circle"
                                size="small"
                                :style="
                                    !node.original.creator.avatar_url || node.original.creator.avatar_url === '/images/default-avatar.png'
                                        ? { backgroundColor: getUserColor(0), color: 'white', fontWeight: '600' }
                                        : {}
                                "
                                v-tooltip.bottom="node.original.creator.name"
                            />
                        </div>
                        <span v-else class="text-sm text-gray-400">-</span>
                    </template>
                </Column>

                <!-- Actions Column - FROZEN RIGHT -->
                <Column header="Actions" frozen alignFrozen="right">
                    <template #body="{ node }">
                        <div class="flex gap-1">
                            <Link :href="route('task.show', node.original)">
                                <Button icon="pi pi-eye" size="small" severity="secondary" />
                            </Link>
                            <Button
                                icon="pi pi-plus"
                                size="small"
                                severity="info"
                                :disabled="deleteLoading || (!isMember && !hasPermission)"
                                @click="emit('add', node.data.id)"
                            />
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                severity="warning"
                                :disabled="deleteLoading || !hasAccessToEditAndDelete(node.data)"
                                @click="emit('edit', node.original, node.data.parent_id)""
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                :disabled="deleteLoading || !hasAccessToEditAndDelete(node.data)"
                                @click="remove(node.original)"
                            />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data Available</p>
                </template>
            </TreeTable>
        </div>
    </div>
</template>
