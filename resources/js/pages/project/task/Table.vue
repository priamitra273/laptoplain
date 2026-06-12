<script setup lang="ts">
import TaskActivityLogModal from '@/components/TaskActivityLogModal.vue';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { useSeverityColor } from '@/composables/useSeverityColor';
import { ProjectPolicyKey } from '@/types/type';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useSessionStorage } from '@vueuse/core';
import axios from 'axios';
import moment from 'moment';
import { TreeTableFilterMeta } from 'primevue/treetable';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { ProjectTask, ProjectTaskTableEmits, ProjectTaskTableFilter, ProjectTaskTableProps, TaskCategory, TaskFormatted } from '..';
import TaskTableFilters from './partials/TaskTableFilters.vue';
import TaskTableToolbar from './partials/TaskTableToolbar.vue';

const props = defineProps<ProjectTaskTableProps>();
const emit = defineEmits<ProjectTaskTableEmits>();

const policy = inject(ProjectPolicyKey, null);

const { getSeverityColorLight } = useSeverityColor();

const deleteLoading = ref(false);
const currentUser = usePage().props.auth.user;
const selectedKey = ref<{ [key: string]: any }>({});
const expandedKeys = ref<{ [key: string]: boolean }>({});

const filters = useSessionStorage<ProjectTaskTableFilter>('task-table-filters-' + currentUser.id, {
    global: '',
    'status.name': null,
    'type.name': null,
});

const { canAction } = useProjectPermissions(policy);

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

const openActivityLog = (task: ProjectTask) => {
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

// ─── Pure function, tidak ada side effects ────────────────────────────────────
const formatTasks = (list?: ProjectTask[], level: number = 0): TaskFormatted[] => {
    if (!list || !Array.isArray(list)) return [];
    return list.map((t) => ({
        key: t.id,
        original: t,
        data: {
            id: t.id,
            parent_id: t.parent_id ?? null,
            title: t.title,
            status: t.status ?? undefined,
            priority: t.priority ?? undefined,
            type: t.type ?? undefined,
            category: t.category ?? undefined,
            progress: Number(t.progress) || 0,
            users: t.users || [],
            start_date: t.start_date ?? null,
            due_date: t.due_date ?? null,
            created_by: t.created_by ?? null,
            completed_at: t.completed_at ?? null,
            is_overdue: t.is_overdue ?? false,
            level,
        },
        children: t.sub_task_recursive ? formatTasks(t.sub_task_recursive, level + 1) : [],
    }));
};

// ─── FIX: Gunakan ref + watch instead of computed untuk menghindari recursive update ───
const formattedTasks = ref<TaskFormatted[]>([]);

watch(
    () => props.tasks,
    (tasks) => {
        if (!tasks || !Array.isArray(tasks)) {
            formattedTasks.value = [];
            return;
        }

        const sorted = [...formatTasks(tasks)].sort((a, b) => {
            const dateA = new Date(a.original.updated_at || a.original.created_at || 0).getTime();
            const dateB = new Date(b.original.updated_at || b.original.created_at || 0).getTime();
            return dateB - dateA;
        });

        formattedTasks.value = sorted;
    },
    { immediate: true, deep: false },
);

const isAllSelected = computed(() => {
    if (!formattedTasks.value.length) return false;
    const allKeys: string[] = [];
    const collectKeys = (node: TaskFormatted) => {
        allKeys.push(node.key);
        if (node.children) node.children.forEach(collectKeys);
    };
    formattedTasks.value.forEach(collectKeys);
    return allKeys.every((key) => selectedKey.value[key]?.checked);
});

const hasSelectedTasks = computed(() => Object.keys(selectedKey.value).length > 0);

const confirm = useConfirm();
const toast = useToast();

const remove = (t: ProjectTask) => {
    confirm.require({
        message: `Remove task ${t.title}? This action cannot be undone.`,
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
                    task: t.id,
                }),
                {
                    preserveScroll: true,
                    onError: () => {
                        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete task', life: 3000 });
                    },
                    onFinish: () => {
                        deleteLoading.value = false;
                    },
                },
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
    formattedTasks.value.forEach(mark);
    selectedKey.value = { ...keys };
};

const clearSelection = () => {
    selectedKey.value = {};
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
            toast.add({ severity: 'success', summary: 'Success', detail: `${ids.length} tasks deleted successfully`, life: 3000 });
        },
    });
};

const canTaskCreate = computed(() => canAction('task', 'create'));
const canTaskUpdate = computed(() => canAction('task', 'update'));
const canTaskDelete = computed(() => canAction('task', 'delete'));
const canMoveTask = computed(() => canAction('task', 'update'));

const hasAccessToEditAndDelete = (): boolean => canTaskUpdate.value || canTaskDelete.value;

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

const findTaskById = (list: ProjectTask[], taskId: string): ProjectTask | null => {
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
    const walk = (nodes: ProjectTask[]): boolean => {
        for (const n of nodes) {
            if (n.id === targetId) return true;
            if (walk(n.sub_task_recursive || [])) return true;
        }
        return false;
    };
    return walk(source.sub_task_recursive || []);
};

const onHandleDragStart = (event: DragEvent, node: TaskFormatted) => {
    const canMove = canMoveTask.value;

    if (!canMove || dragArmedTaskId.value !== node.key) {
        event.preventDefault();
        return;
    }

    onPointerDragStart(node);
    draggedTaskId.value = node.key;
    dropTargetTaskId.value = null;
    if (event.dataTransfer) {
        event.dataTransfer.setData(TASK_DRAG_TEXT_MIME, node.key);
        event.dataTransfer.setData(TASK_DRAG_LEGACY_TEXT_MIME, node.key);
        try {
            event.dataTransfer.setData(TASK_DRAG_MIME, node.key);
        } catch {}
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
        } catch {}
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
    if (!canMoveTask.value) return;
    if (!hasTaskDragPayload(event)) return;
    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    if (sourceTaskId && targetNode.key === sourceTaskId) return;
    event.preventDefault();
    if (!draggedTaskId.value) draggedTaskId.value = sourceTaskId;
    pointerOnRootDropzone.value = false;
    dropTargetTaskId.value = targetNode.key;
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    scheduleAutoExpand(targetNode);
};

const updateTaskParent = async (taskId: string, parentId: string | null) => {
    if (updateParentLoading.value) return;
    updateParentLoading.value = true;
    try {
        await axios.put(
            route('project.tasks.parent.update', {
                projectEncoded: props.projectId,
                task: taskId,
            }),
            { parent_id: parentId },
        );
        toast.add({ severity: 'success', summary: 'Success', detail: 'Task parent updated successfully.', life: 2200 });
        router.reload();
    } catch (error: any) {
        const message = error?.response?.data?.message || 'Failed to update task parent.';
        toast.add({ severity: 'error', summary: 'Error', detail: message, life: 3000 });
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
        toast.add({ severity: 'warn', summary: 'Invalid move', detail: 'Cannot move task under its own descendant.', life: 2500 });
        resetDragState();
        return;
    }
    await updateTaskParent(sourceTaskId, targetTaskId);
};

const onRowDrop = async (event: DragEvent, targetNode: TaskFormatted) => {
    if (!canMoveTask.value) return;
    event.preventDefault();
    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    await moveTaskWithValidation(sourceTaskId, targetNode.key);
};

const onRootDragOver = (event: DragEvent) => {
    if (!canMoveTask.value) return;
    if (!hasTaskDragPayload(event)) return;
    const target = event.target as Element | null;
    const insideTaskRow = !!target?.closest('[data-task-drop-row="true"]');
    if (insideTaskRow) return;
    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    event.preventDefault();
    if (!draggedTaskId.value && sourceTaskId) draggedTaskId.value = sourceTaskId;
    pointerOnRootDropzone.value = true;
    dropTargetTaskId.value = null;
    clearAutoExpandSchedule();
};

const onRootDrop = async (event: DragEvent) => {
    if (!canMoveTask.value) return;
    event.preventDefault();
    const sourceTaskId = getDraggedTaskIdFromEvent(event);
    await moveTaskWithValidation(sourceTaskId, null);
};

const onPointerDragStart = (node: TaskFormatted) => {
    if (!canMoveTask.value) return;
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
    if (!pointerDraggedTaskId.value) dragArmedTaskId.value = null;
};

const onPointerHoldStart = (node: TaskFormatted) => {
    if (!canMoveTask.value) return;
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

// ─── Category icon style (same as backlog) ───────────────────────────────────
const getCategoryIcon = (category?: TaskCategory | null) => {
    const byName: Record<string, string> = {
        Epic: 'pi pi-bolt',
        Issue: 'pi pi-exclamation-circle',
        Story: 'pi pi-book',
        Task: 'pi pi-check-square',
    };
    const name = category?.name ?? '';
    return category?.icon ?? byName[name] ?? 'pi pi-tag';
};

const getCategoryColor = (category?: TaskCategory | null): string => {
    const severity = category?.severity;
    if (severity) {
        return getSeverityColorLight(severity, 0.2);
    }

    const byName: Record<string, string> = {
        Epic: '#7c3aed',
        Issue: '#dc2626',
        Story: '#16a34a',
        Task: '#3b82f6',
        Bug: '#dc2626',
    };
    const name = category?.name ?? '';
    return byName[name] ?? '#64748b';
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
        <TaskTableToolbar :hasSelectedTasks="hasSelectedTasks" @add="(parentId) => emit('add', parentId)" @removeSelected="removeSelected" />

        <TaskTableFilters v-model:filters="filters" :statusOptions="props.taskStatuses" :typeOptions="props.taskTypes" />

        <div
            class="overflow-x-auto"
            :class="
                isRootDropActive
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
            <TreeTable
                v-model:expandedKeys="expandedKeys"
                :value="formattedTasks"
                :filters="filters as unknown as TreeTableFilterMeta"
                filterMode="lenient"
                class="min-w-full"
                scrollable
                scrollHeight="600px"
                removableSort
            >
                <Column :expander="false" style="width: 3rem" v-if="canTaskCreate || canTaskUpdate || canTaskDelete" frozen align-frozen="left">
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
                            <i
                                v-if="node.data.category?.id"
                                v-tooltip.top="node.data.category.name"
                                :class="getCategoryIcon(node.data.category)"
                                :style="{ color: getCategoryColor(node.data.category) }"
                                class="shrink-0 cursor-default text-sm"
                            />

                            <div
                                :title="node.data.title"
                                class="max-w-[150px] select-none truncate text-ellipsis rounded px-1 py-0.5"
                                :class="[
                                    hasAccessToEditAndDelete() ? 'cursor-grab active:cursor-grabbing' : 'cursor-not-allowed opacity-50',
                                    activeDragTaskId === node.key
                                        ? 'bg-blue-100/80 text-blue-800 ring-1 ring-blue-300 dark:bg-blue-900/35 dark:text-blue-100 dark:ring-blue-600/60'
                                        : '',
                                ]"
                                :draggable="canMoveTask && dragArmedTaskId === node.key"
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

                <Column field="status.name" header="Status" filterMatchMode="in" style="min-width: 120px" sortable>
                    <template #body="{ node }">
                        <Tag :value="node.data.status?.name" :severity="node.data.status?.severity" />
                    </template>
                </Column>

                <Column field="type.name" header="Type" filterMatchMode="in" style="min-width: 120px" sortable>
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
                                :disabled="deleteLoading || !canTaskCreate"
                                @click="emit('add', node.data.id)"
                            />
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                severity="warning"
                                v-tooltip.top="'Edit Task'"
                                :disabled="deleteLoading || !canTaskUpdate || !hasAccessToEditAndDelete()"
                                @click="emit('edit', node.original, node.data.parent_id)"
                            />
                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                v-tooltip.top="'Delete'"
                                :disabled="deleteLoading || !canTaskDelete || !hasAccessToEditAndDelete()"
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

        <TaskActivityLogModal v-model:visible="activityModal.visible" :taskId="activityModal.taskId" :taskTitle="activityModal.taskTitle" />
    </div>
</template>
