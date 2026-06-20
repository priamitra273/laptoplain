<script setup lang="ts">
import TaskActivityLogModal from '@/components/TaskActivityLogModal.vue';
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import type { LazyTaskTableEmits, LazyTaskTableFilter, LazyTaskTableProps, ListTask } from '@/pages/project-lazy';
import { ProjectPolicyKey } from '@/types/type';
import { Link, usePage } from '@inertiajs/vue3';
import { useSessionStorage } from '@vueuse/core';
import moment from 'moment';
import { TreeTableFilterMeta } from 'primevue/treetable';
import { computed, inject, ref } from 'vue';
import { useTaskActions } from './composables/useTaskActions';
import { useTaskCategoryStyle } from './composables/useTaskCategoryStyle';
import { useTaskDragDrop } from './composables/useTaskDragDrop';
import { useTaskSelection } from './composables/useTaskSelection';
import { useTaskTree } from './composables/useTaskTree';
import TaskTableFilters from './partials/TaskTableFilters.vue';
import TaskTableToolbar from './partials/TaskTableToolbar.vue';

const props = defineProps<LazyTaskTableProps>();
const emit = defineEmits<LazyTaskTableEmits>();

const policy = inject(ProjectPolicyKey, null);
const { canAction } = useProjectPermissions(policy);

const currentUser = usePage().props.auth.user;

const canTaskCreate = computed(() => canAction('task', 'create'));
const canTaskUpdate = computed(() => canAction('task', 'update'));
const canTaskDelete = computed(() => canAction('task', 'delete'));
const canMoveTask = computed(() => canAction('task', 'update'));

const hasAccessToEditAndDelete = (): boolean => canTaskUpdate.value || canTaskDelete.value;

const tasksRef = computed(() => props.tasks);

const { formattedTasks, isDescendant } = useTaskTree(tasksRef);

const { selectedKey, expandedKeys, isAllSelected, hasSelectedTasks, selectedIds, toggleSelectAll, setSelected } = useTaskSelection(formattedTasks);

const { deleteLoading, remove, removeSelected } = useTaskActions(props.projectId);

const {
    dropTargetTaskId,
    dragArmedTaskId,
    activeDragTaskId,
    isDraggingTask,
    isRootDropActive,
    onHandleDragStart,
    onHandleDragEnd,
    onRowDragOver,
    onRowDrop,
    onRootDragOver,
    onRootDrop,
    onPointerHoldStart,
    cancelPointerHold,
    onPointerRowEnter,
    onPointerRootLeave,
    onPointerContainerMove,
} = useTaskDragDrop({ projectId: props.projectId, expandedKeys, canMove: canMoveTask, isDescendant });

const { getCategoryIcon, getCategoryColor } = useTaskCategoryStyle();

const filters = useSessionStorage<LazyTaskTableFilter>('task-table-filters-' + currentUser.id, {
    global: '',
    'status.name': null,
    'type.name': null,
});

const activityModal = ref({
    visible: false,
    taskId: '',
    taskTitle: '',
});

const openActivityLog = (task: ListTask) => {
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

const onRemoveSelected = () => removeSelected(selectedIds.value, () => setSelected({}));
</script>

<template>
    <div class="flex flex-col gap-4">
        <TaskTableToolbar :hasSelectedTasks="hasSelectedTasks" @add="(parentId) => emit('add', parentId)" @removeSelected="onRemoveSelected" />

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
