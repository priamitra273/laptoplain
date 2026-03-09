<script setup lang="ts">
import TaskActivityLogModal from '@/components/TaskActivityLogModal.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import moment from 'moment';
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
import { computed, ComputedRef, onBeforeUnmount, onMounted, ref } from 'vue';
import { Task, TaskFormatted, TaskFormattedData, TaskPriority, TaskStatus, TaskType, TaskUser } from '..';

interface Props {
    projectId: string;
    tasks: Task[];
    isMember: boolean;
    hasPermission: boolean;
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    isDeveloper: boolean
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
const expandedKeys = ref<{ [key: string]: boolean }>({});

// Filter refs
const selectedStatuses = ref<string[]>([]);
// const selectedPriorities = ref<string[]>([]);
const selectedTypes = ref<string[]>([]);

// Activity modal state
const activityModal = ref({
    visible: false,
    taskId: '',
    taskTitle: '',
});

const draggedTaskId = ref<string | null>(null);
const dropTargetTaskId = ref<string | null>(null);
const updateParentLoading = ref(false);
const pointerDraggedTaskId = ref<string | null>(null);
const pointerOnRootDropzone = ref(false);
const dragArmedTaskId = ref<string | null>(null);
const holdCandidateTaskId = ref<string | null>(null);
const holdTimerId = ref<ReturnType<typeof setTimeout> | null>(null);
const autoExpandTargetKey = ref<string | null>(null);
const autoExpandTimerId = ref<ReturnType<typeof setTimeout> | null>(null);
const DRAG_HOLD_MS = 900;
const AUTO_EXPAND_DELAY_MS = 500;
const TASK_DRAG_MIME = 'application/x-task-id';
const TASK_DRAG_TEXT_MIME = 'text/plain';
const TASK_DRAG_LEGACY_TEXT_MIME = 'text';
const activeDragTaskId = computed(() => pointerDraggedTaskId.value || draggedTaskId.value || dragArmedTaskId.value);
const isDraggingTask = computed(() => !!activeDragTaskId.value);
const isRootDropActive = computed(() => isDraggingTask.value && pointerOnRootDropzone.value && !dropTargetTaskId.value);

const openActivityLog = (task: Task) => {
    activityModal.value = {
        visible: true,
        taskId: task.id,
        taskTitle: task.title,
    };
};

const formatDate = (date: string | null | undefined): string => {
    if (!date) return '-';
    return moment(date).format('DD MMM YYYY');
};

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getUserColor = (index: number) => `hsl(${index * 60}, 70%, 60%)`;

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

const statusOptions = computed(() => props.taskStatuses ?? []);
// const priorityOptions = computed(() => props.taskPriorities ?? []);
const typeOptions = computed(() => props.taskTypes ?? []);

const filterTaskRecursive = (task: TaskFormatted, query: string): boolean => {
    const matchesSearch = !query || task.data.title.toLowerCase().includes(query);
    const matchesStatus =
        !selectedStatuses.value ||
        selectedStatuses.value.length === 0 ||
        (task.data.status?.name && selectedStatuses.value.includes(task.data.status.name));
    // const matchesPriority =
    //     !selectedPriorities.value ||
    //     selectedPriorities.value.length === 0 ||
    //     (task.data.priority?.name && selectedPriorities.value.includes(task.data.priority.name));
    const matchesType =
        !selectedTypes.value || selectedTypes.value.length === 0 || (task.data.type?.name && selectedTypes.value.includes(task.data.type.name));

    const currentMatches = matchesSearch && matchesStatus && /* matchesPriority && */ matchesType;
    const hasMatchingChildren = task.children && task.children.some((child) => filterTaskRecursive(child, query));
    return currentMatches || hasMatchingChildren;
};

const filteredTasks: ComputedRef<TaskFormatted[]> = computed(() => {
    if (!props.tasks || !Array.isArray(props.tasks)) return [];
    let tasks = formatTasks(props.tasks);
    tasks = tasks.sort((a, b) => {
        const dateA = new Date(a.original.updated_at || a.original.created_at).getTime();
        const dateB = new Date(b.original.updated_at || b.original.created_at).getTime();
        return dateB - dateA;
    });
    const query = searchQuery.value.toLowerCase();
    tasks = tasks.filter((task) => filterTaskRecursive(task, query));
    return tasks;
});

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

const hasSelectedTasks = computed(() => Object.keys(selectedKey.value).length > 0);

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value !== '' ||
        (selectedStatuses.value && selectedStatuses.value.length > 0) ||
        // (selectedPriorities.value && selectedPriorities.value.length > 0) ||
        (selectedTypes.value && selectedTypes.value.length > 0)
    );
});

const clearFilters = () => {
    searchQuery.value = '';
    selectedStatuses.value = [];
    // selectedPriorities.value = [];
    selectedTypes.value = [];
};

const handleClearStatuses = () => {
    selectedStatuses.value = [];
};
// const handleClearPriorities = () => {
//     selectedPriorities.value = [];
// };
const handleClearTypes = () => {
    selectedTypes.value = [];
};

const confirm = useConfirm();
const toast = useToast();

const remove = (t: Task) => {
    confirm.require({
        message: `Remove ${t.title}? This action cannot be undone.`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Yes, remove',
        acceptClass: 'p-button-danger',
        rejectLabel: 'Cancel',
        accept: () => {
            deleteLoading.value = true;

            router.delete(
                route('project.tasks.destroy', {
                    projectEncoded: props.projectId,
                    taskEncoded: t.id
                }),
                {
                    preserveScroll: true,
                    onError: () => {
                        toast.add({
                            severity: 'error',
                            summary: 'Error',
                            detail: 'Failed to delete task',
                            life: 3000
                        });
                    },
                    onFinish: () => {
                        deleteLoading.value = false;
                    },
                }
            );
        },
    });
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        clearSelection();
    } else {
        selectAll();
    }
};

const selectAll = () => {
    const keys: { [key: string]: any } = {};
    const mark = (node: TaskFormatted) => {
        keys[node.key] = { checked: true, partialChecked: false };
        if (node.children) node.children.forEach(mark);
    };
    filteredTasks.value.forEach(mark);
    selectedKey.value = { ...keys };
};

const clearSelection = () => {
    selectedKey.value = {};
    selectedKey.value = { ...selectedKey.value };
};

const removeSelected = () => {
    const ids = Object.keys(selectedKey.value);
    if (!ids.length) {
        toast.add({ severity: 'warn', summary: 'Warning', detail: 'No tasks selected to delete.', life: 3000 });
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
            toast.add({ severity: 'success', summary: 'Success', detail: `${ids.length} tasks deleted successfully`, life: 3000 });
        },
    });
};

const hasAccessToEditAndDelete = (task: TaskFormattedData): boolean => {
    if (props.hasPermission) return true;
    const taskUsers: TaskUser[] = task.users || [];
    const isMember = taskUsers.some((tu) => tu.id === currentUser.id);
    return isMember;
};

const clearAutoExpandSchedule = () => {
    if (autoExpandTimerId.value) {
        clearTimeout(autoExpandTimerId.value);
        autoExpandTimerId.value = null;
    }
    autoExpandTargetKey.value = null;
};

const scheduleAutoExpand = (node: TaskFormatted) => {
    const hasChildren = Array.isArray(node.children) && node.children.length > 0;
    if (!hasChildren || expandedKeys.value[node.key]) {
        clearAutoExpandSchedule();
        return;
    }

    if (autoExpandTargetKey.value === node.key && autoExpandTimerId.value) return;
    clearAutoExpandSchedule();
    autoExpandTargetKey.value = node.key;
    autoExpandTimerId.value = setTimeout(() => {
        expandedKeys.value = { ...expandedKeys.value, [node.key]: true };
        clearAutoExpandSchedule();
    }, AUTO_EXPAND_DELAY_MS);
};

const resetDragState = () => {
    if (holdTimerId.value) {
        clearTimeout(holdTimerId.value);
        holdTimerId.value = null;
    }
    clearAutoExpandSchedule();
    draggedTaskId.value = null;
    dropTargetTaskId.value = null;
    pointerDraggedTaskId.value = null;
    pointerOnRootDropzone.value = false;
    dragArmedTaskId.value = null;
    holdCandidateTaskId.value = null;
};

const findTaskById = (list: Task[], taskId: string): Task | null => {
    for (const item of list) {
        if (item.id === taskId) return item;
        const found = findTaskById(item.sub_task_recursive || [], taskId);
        if (found) return found;
    }
    return null;
};

const isDescendant = (sourceId: string, targetId: string): boolean => {
    const source = findTaskById(props.tasks, sourceId);
    if (!source) return false;

    const walk = (nodes: Task[]): boolean => {
        for (const n of nodes) {
            if (n.id === targetId) return true;
            if (walk(n.sub_task_recursive || [])) return true;
        }
        return false;
    };

    return walk(source.sub_task_recursive || []);
};

const onHandleDragStart = (event: DragEvent, node: TaskFormatted) => {
    const canMove = hasAccessToEditAndDelete(node.data) && !props.isDeveloper;
    if (!canMove || dragArmedTaskId.value !== node.key) {
        event.preventDefault();
        return;
    }

    onPointerDragStart(node);
    draggedTaskId.value = node.key;
    dropTargetTaskId.value = null;
    if (event.dataTransfer) {
        // Use multiple MIME keys because browsers handle drag payload types differently.
        event.dataTransfer.setData(TASK_DRAG_TEXT_MIME, node.key);
        event.dataTransfer.setData(TASK_DRAG_LEGACY_TEXT_MIME, node.key);
        try {
            event.dataTransfer.setData(TASK_DRAG_MIME, node.key);
        } catch {
            // Some browsers reject custom MIME types, built-in text keys are still enough.
        }
        event.dataTransfer.effectAllowed = 'move';
    }
};

const onHandleDragEnd = () => {
    resetDragState();
};

const getDraggedTaskIdFromEvent = (event: DragEvent): string | null => {
    const transfer = event.dataTransfer;
    if (!transfer) return draggedTaskId.value;

    const candidates = [TASK_DRAG_MIME, TASK_DRAG_TEXT_MIME, TASK_DRAG_LEGACY_TEXT_MIME];
    for (const mime of candidates) {
        try {
            const value = transfer.getData(mime)?.trim();
            if (value) return value;
        } catch {
            // Ignore unsupported MIME reads and continue with next fallback.
        }
    }

    return draggedTaskId.value;
};

const hasTaskDragPayload = (event: DragEvent): boolean => {
    if (draggedTaskId.value) return true;
    const transfer = event.dataTransfer;
    if (!transfer?.types) return false;
    const types = Array.from(transfer.types);
    return [TASK_DRAG_MIME, TASK_DRAG_TEXT_MIME, TASK_DRAG_LEGACY_TEXT_MIME].some((mime) => types.includes(mime));
};

const onRowDragOver = (event: DragEvent, targetNode: TaskFormatted) => {
    if (props.isDeveloper) return;
    if (!hasTaskDragPayload(event)) return;
    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    if (sourceTaskId && targetNode.key === sourceTaskId) return;

    event.preventDefault();
    if (!draggedTaskId.value) {
        draggedTaskId.value = sourceTaskId;
    }
    pointerOnRootDropzone.value = false;
    dropTargetTaskId.value = targetNode.key;
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
    scheduleAutoExpand(targetNode);
};

const updateTaskParent = async (taskId: string, parentId: string | null) => {
    if (updateParentLoading.value) return;
    updateParentLoading.value = true;

    try {
        await axios.put(
            route('project.tasks.parent.update', {
                projectEncoded: props.projectId,
                taskEncoded: taskId,
            }),
            { parent_id: parentId },
        );

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Task parent updated successfully.',
            life: 2200,
        });

        router.reload();
    } catch (error: any) {
        const message = error?.response?.data?.message || 'Failed to update task parent.';
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: message,
            life: 3000,
        });
    } finally {
        updateParentLoading.value = false;
        resetDragState();
    }
};

const moveTaskWithValidation = async (sourceTaskId: string | null, targetTaskId: string | null) => {
    if (!sourceTaskId) {
        resetDragState();
        return;
    }

    if (targetTaskId && sourceTaskId === targetTaskId) {
        resetDragState();
        return;
    }

    if (targetTaskId && isDescendant(sourceTaskId, targetTaskId)) {
        toast.add({
            severity: 'warn',
            summary: 'Invalid move',
            detail: 'Cannot move task under its own descendant.',
            life: 2500,
        });
        resetDragState();
        return;
    }

    await updateTaskParent(sourceTaskId, targetTaskId);
};

const onRowDrop = async (event: DragEvent, targetNode: TaskFormatted) => {
    if (props.isDeveloper) return;
    event.preventDefault();
    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    await moveTaskWithValidation(sourceTaskId, targetNode.key);
};

const onRootDragOver = (event: DragEvent) => {
    if (props.isDeveloper) return;
    if (!hasTaskDragPayload(event)) return;
    const target = event.target as Element | null;
    const insideTaskRow = !!target?.closest('[data-task-drop-row="true"]');
    if (insideTaskRow) return;

    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    event.preventDefault();
    if (!draggedTaskId.value && sourceTaskId) {
        draggedTaskId.value = sourceTaskId;
    }
    pointerOnRootDropzone.value = true;
    dropTargetTaskId.value = null;
    clearAutoExpandSchedule();
};

const onRootDrop = async (event: DragEvent) => {
    if (props.isDeveloper) return;
    event.preventDefault();
    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    await moveTaskWithValidation(sourceTaskId, null);
};

const onPointerDragStart = (node: TaskFormatted) => {
    if (!hasAccessToEditAndDelete(node.data) || props.isDeveloper) return;
    pointerDraggedTaskId.value = node.key;
    pointerOnRootDropzone.value = false;
    draggedTaskId.value = node.key;
    dropTargetTaskId.value = null;
};

const cancelPointerHold = () => {
    if (holdTimerId.value) {
        clearTimeout(holdTimerId.value);
        holdTimerId.value = null;
    }
    holdCandidateTaskId.value = null;
    if (!pointerDraggedTaskId.value) {
        dragArmedTaskId.value = null;
    }
};

const onPointerHoldStart = (node: TaskFormatted) => {
    if (!hasAccessToEditAndDelete(node.data) || props.isDeveloper) return;
    cancelPointerHold();
    holdCandidateTaskId.value = node.key;
    holdTimerId.value = setTimeout(() => {
        if (holdCandidateTaskId.value !== node.key) return;
        dragArmedTaskId.value = node.key;
        onPointerDragStart(node);
    }, DRAG_HOLD_MS);
};

const onPointerRowEnter = (node: TaskFormatted) => {
    if (!pointerDraggedTaskId.value) return;
    pointerOnRootDropzone.value = false;
    dropTargetTaskId.value = node.key === pointerDraggedTaskId.value ? null : node.key;
    scheduleAutoExpand(node);
};

const onPointerRootEnter = () => {
    if (!pointerDraggedTaskId.value) return;
    pointerOnRootDropzone.value = true;
    dropTargetTaskId.value = null;
    clearAutoExpandSchedule();
};

const onPointerRootLeave = () => {
    if (!pointerDraggedTaskId.value) return;
    pointerOnRootDropzone.value = false;
};

const onPointerContainerMove = (event: MouseEvent) => {
    if (!pointerDraggedTaskId.value) return;
    const target = event.target as Element | null;
    const insideTaskRow = !!target?.closest('[data-task-drop-row="true"]');
    if (insideTaskRow) {
        pointerOnRootDropzone.value = false;
        return;
    }
    onPointerRootEnter();
};

const finalizePointerDrag = async () => {
    if (!pointerDraggedTaskId.value) return;
    const sourceTaskId = pointerDraggedTaskId.value;
    const targetTaskId = pointerOnRootDropzone.value ? null : dropTargetTaskId.value;
    await moveTaskWithValidation(sourceTaskId, targetTaskId);
};

const onGlobalMouseUp = () => {
    void finalizePointerDrag();
    cancelPointerHold();
};

onMounted(() => {
    window.addEventListener('mouseup', onGlobalMouseUp);
});

onBeforeUnmount(() => {
    window.removeEventListener('mouseup', onGlobalMouseUp);
});
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
                    :disabled="(!isMember && !hasPermission) || isDeveloper"
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
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Search</label>
                <InputText v-model="searchQuery" placeholder="Search by title..." class="w-full" />
            </div>
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
            <!-- Priority Filter (commented out)
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
            -->
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

        <!-- TreeTable -->
        <div class="overflow-x-auto" :class="isRootDropActive
                ? 'rounded-lg border-2 border-dashed border-emerald-400 bg-emerald-50/60 p-1 transition-colors dark:border-emerald-500/80 dark:bg-emerald-950/35'
                : isDraggingTask
                    ? 'rounded-lg border border-dashed border-blue-300/80 bg-blue-50/40 p-1 transition-colors dark:border-blue-700/70 dark:bg-blue-950/20'
                    : 'transition-colors'
            "
            @dragover.prevent="onRootDragOver"
            @dragenter.prevent="onRootDragOver"
            @drop.stop.prevent="onRootDrop"
            @mousemove="onPointerContainerMove"
            @mouseleave="onPointerRootLeave"
        >
            <TreeTable v-model:expandedKeys="expandedKeys" :value="filteredTasks" class="min-w-full" scrollable scrollHeight="600px" removableSort>
                <!-- Checkbox Column -->
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

                <!-- Title Column -->
                <Column field="title" header="Title" sortable frozen expander align-frozen="left">
                    <template #body="{ node }">
                        <div
                            data-task-drop-row="true"
                            class="flex items-center gap-2 rounded px-1 py-1 transition-colors"
                            :class="dropTargetTaskId === node.key ? 'bg-blue-100 ring-1 ring-blue-300 dark:bg-blue-900/40 dark:ring-blue-600/70' : ''"
                            @dragover.prevent="onRowDragOver($event, node)"
                            @dragenter.prevent="onRowDragOver($event, node)"
                            @drop.stop.prevent="onRowDrop($event, node)"
                            @mouseenter="onPointerRowEnter(node)"
                        >
                            <div
                                :title="node.data.title"
                                class="max-w-[150px] select-none truncate text-ellipsis rounded px-1 py-0.5"
                                :class="[
                                    hasAccessToEditAndDelete(node.data) ? 'cursor-grab active:cursor-grabbing' : 'cursor-not-allowed opacity-50',
                                    activeDragTaskId === node.key
                                        ? 'bg-blue-100/80 text-blue-800 ring-1 ring-blue-300 dark:bg-blue-900/35 dark:text-blue-100 dark:ring-blue-600/60'
                                        : '',
                                ]"
                                :draggable="hasAccessToEditAndDelete(node.data) && !isDeveloper && dragArmedTaskId === node.key"
                                style="-webkit-user-drag: element"
                                @mousedown.left.stop.prevent="onPointerHoldStart(node)"
                                @mouseup.left="cancelPointerHold"
                                @mouseleave="cancelPointerHold"
                                @dragstart.stop="onHandleDragStart($event, node)"
                                @dragend="onHandleDragEnd"
                            >
                                {{ node.data.title }}
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="status.name" header="Status" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <Tag :value="node.data.status?.name" :severity="node.data.status?.severity" />
                    </template>
                </Column>

                <!-- Priority Column (commented out)
                <Column field="priority.name" header="Priority" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <Tag :value="node.data.priority?.name" :severity="node.data.priority?.severity" />
                    </template>
                </Column>
                -->

                <Column field="type.name" header="Type" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <Tag :value="node.data.type?.name" :severity="node.data.type?.severity" />
                    </template>
                </Column>

                <Column field="start_date" header="Start Date" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <span>{{ formatDate(node.data.start_date) }}</span>
                    </template>
                </Column>

                <Column field="due_date" header="Due Date" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <span :class="{ 'text-red-500': node.data.is_overdue }">{{ formatDate(node.data.due_date) }}</span>
                    </template>
                </Column>

                <Column field="completed_at" header="Complete Date" style="min-width: 160px" sortable>
                    <template #body="{ node }">
                        <span>{{ formatDate(node.data.completed_at) }}</span>
                    </template>
                </Column>

                <Column field="progress" header="Progress" style="min-width: 150px" sortable>
                    <template #body="{ node }">
                        <ProgressBar :value="node.data.progress" :showValue="true" class="min-w-[120px]" />
                    </template>
                </Column>

                <!-- Created Column
                <Column header="Created" style="min-width: 90px">
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
                </Column> -->

                <!-- Actions Column -->
                <Column header="Actions" frozen alignFrozen="right">
                    <template #body="{ node }">
                        <div class="flex gap-1">
                            <Link :href="route('task.show', node.original)">
                                <Button icon="pi pi-eye" size="small" severity="secondary" v-tooltip.top="'View Task'" />
                            </Link>
                            <Button
                                icon="pi pi-plus"
                                size="small"
                                severity="info"
                                v-tooltip.top="'Add Subtask'"
                                :disabled="(deleteLoading || (!isMember && !hasPermission)) || isDeveloper"
                                @click="emit('add', node.data.id)"
                            />
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                severity="warning"
                                v-tooltip.top="'Edit Task'"
                                :disabled="deleteLoading || !hasAccessToEditAndDelete(node.data)"
                                @click="emit('edit', node.original, node.data.parent_id)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                v-tooltip.top="'Deleted'"
                                :disabled="deleteLoading || !hasAccessToEditAndDelete(node.data)"
                                @click="remove(node.original)"
                            />
                            <Button
                                icon="pi pi-history"
                                size="small"
                                severity="secondary"
                                v-tooltip.top="'History Log'"
                                @click="openActivityLog(node.original)"
                            />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data Available</p>
                </template>
            </TreeTable>
        </div>

        <!-- Activity Log Modal — milik TaskTable, di sini tempatnya -->
        <TaskActivityLogModal v-model:visible="activityModal.visible" :taskId="activityModal.taskId" :taskTitle="activityModal.taskTitle" />
    </div>
</template>
