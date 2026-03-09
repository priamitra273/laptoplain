<script setup lang="ts">
import moment from 'moment';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import { computed } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { Sprint, Task, TaskCategory, TaskPriority, TaskStatus, TaskType, User } from '../type';
import BacklogQuickAdd from './BacklogQuickAdd.vue';
import BacklogTaskRow from './BacklogTaskRow.vue';

interface QuickForm {
    title: string;
    type_id: TaskType | null;
    priority_id: TaskPriority | null;
    category_id: TaskCategory | null;
    assign_users: User[];
    due_date: Date | null;
}

const props = defineProps<{
    sprint: Sprint;
    projectId: string;
    canAct: boolean;
    taskStatuses: TaskStatus[];
    taskPriorities: TaskPriority[];
    taskTypes: TaskType[];
    taskCategories: TaskCategory[];
    assignableUsers: User[];
    filteredTasks: Task[];
    quickAddSection: string | null;
    quickAddLoading: boolean;
    quickForm: QuickForm;
    quickErrors: Record<string, string>;
    collapsed: boolean;
}>();

const emit = defineEmits<{
    toggleCollapse: [id: string];
    startQuickAdd: [section: string];
    cancelQuickAdd: [];
    submitQuickAdd: [];
    'update:quickForm': [form: QuickForm];
    startSprint: [sprint: Sprint];
    editSprint: [sprint: Sprint];
    completeSprint: [sprint: Sprint];
    deleteSprint: [sprint: Sprint];
    editTask: [task: Task];
    deleteTask: [task: Task];
    taskMovedToBacklog: [task: Task];
    taskMovedToSprint: [task: Task, sprintId: string];
}>();

const isActive = computed(() => props.sprint.status?.name === 'Active');
const isPlanning = computed(() => props.sprint.status?.name === 'Planning');

const progress = computed(() => {
    const tasks = props.sprint.tasks ?? [];
    if (!tasks.length) return 0;
    const done = tasks.filter((t) => t.status?.name?.toLowerCase().match(/complete|done/)).length;
    return Math.round((done / tasks.length) * 100);
});

const daysLeft = computed(() => {
    if (!props.sprint.end_date) return null;
    return moment(props.sprint.end_date).diff(moment(), 'days');
});

const dateRange = computed(() => {
    const s = props.sprint.start_date ? moment(props.sprint.start_date).format('DD MMM') : null;
    const e = props.sprint.end_date ? moment(props.sprint.end_date).format('DD MMM YYYY') : null;
    if (s && e) return `${s} – ${e}`;
    if (e) return `Until ${e}`;
    return null;
});

const STATUS_BADGE: Record<string, string> = {
    Active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    Planning: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    Completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
};
const statusBadge = computed(() => STATUS_BADGE[props.sprint.status?.name ?? ''] ?? STATUS_BADGE.Planning);

const borderClass = computed(() => (isActive.value ? 'border-emerald-300 dark:border-emerald-700/60' : 'border-surface-200 dark:border-surface-700'));
</script>

<template>
    <div class="overflow-hidden rounded-xl border bg-white dark:bg-surface-900" :class="borderClass">
        <!-- ── Sprint Header ─────────────────────────────────────────────── -->
        <div
            class="flex cursor-pointer items-center gap-3 px-4 py-3 transition hover:bg-surface-50 dark:hover:bg-surface-800/50"
            :class="isActive ? 'bg-emerald-50/30 dark:bg-emerald-950/10' : ''"
            @click="emit('toggleCollapse', sprint.id)"
        >
            <!-- Chevron -->
            <i :class="`pi ${collapsed ? 'pi-chevron-right' : 'pi-chevron-down'} shrink-0 text-xs text-surface-400`" />

            <!-- Name + status badge + goal -->
            <div class="flex min-w-0 flex-1 items-center gap-2">
                <span class="truncate text-sm font-semibold text-surface-700 dark:text-surface-200">
                    {{ sprint.name }}
                </span>
                <span :class="['shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium', statusBadge]">
                    {{ sprint.status?.name }}
                </span>
                <span v-if="sprint.goal" class="hidden truncate text-xs text-surface-400 lg:block" style="max-width: 200px">
                    · {{ sprint.goal }}
                </span>
            </div>

            <!-- Meta row (right side) -->
            <div class="flex shrink-0 items-center gap-3 text-xs text-surface-400" @click.stop>
                <!-- Date range -->
                <span v-if="dateRange" class="hidden sm:block">{{ dateRange }}</span>

                <!-- Days left / overdue -->
                <span
                    v-if="isActive && daysLeft !== null"
                    :class="{
                        'font-semibold text-rose-500': daysLeft < 0,
                        'font-semibold text-amber-500': daysLeft >= 0 && daysLeft <= 2,
                    }"
                >
                    {{ daysLeft < 0 ? `${Math.abs(daysLeft)}d overdue` : daysLeft === 0 ? 'Due today' : `${daysLeft}d left` }}
                </span>

                <!-- Task count -->
                <span
                    class="rounded-full bg-surface-100 px-2 py-0.5 text-xs font-semibold text-surface-500 dark:bg-surface-700 dark:text-surface-300"
                >
                    {{ (sprint.tasks ?? []).length }}
                </span>

                <!-- Progress (active only) -->
                <div v-if="isActive" class="flex items-center gap-1.5">
                    <ProgressBar :value="progress" style="width: 64px; height: 4px" :showValue="false" />
                    <span class="w-7 text-right">{{ progress }}%</span>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-0.5">
                    <Button
                        v-if="canAct"
                        icon="pi pi-plus"
                        size="small"
                        text
                        severity="secondary"
                        class="!h-7 !w-7"
                        v-tooltip.top="'Add task'"
                        @click.stop="emit('startQuickAdd', sprint.id)"
                    />
                    <Button
                        v-if="canAct && isPlanning"
                        label="Start Sprint"
                        icon="pi pi-play"
                        size="small"
                        severity="success"
                        outlined
                        class="!py-1 !text-xs"
                        @click.stop="emit('startSprint', sprint)"
                    />
                    <Button
                        v-if="canAct && isActive"
                        label="Complete"
                        icon="pi pi-flag-fill"
                        size="small"
                        severity="secondary"
                        outlined
                        class="!py-1 !text-xs"
                        @click.stop="emit('completeSprint', sprint)"
                    />
                    <Button
                        v-if="canAct"
                        icon="pi pi-pencil"
                        size="small"
                        text
                        severity="secondary"
                        class="!h-7 !w-7"
                        v-tooltip.top="'Edit sprint'"
                        @click.stop="emit('editSprint', sprint)"
                    />
                    <Button
                        v-if="canAct && isPlanning"
                        icon="pi pi-trash"
                        size="small"
                        text
                        severity="danger"
                        class="!h-7 !w-7"
                        v-tooltip.top="'Delete sprint'"
                        @click.stop="emit('deleteSprint', sprint)"
                    />
                </div>
            </div>
        </div>

        <!-- ── Sprint Body ───────────────────────────────────────────────── -->
        <div v-show="!collapsed">
            <!-- Quick add form -->
            <div v-if="quickAddSection === sprint.id" class="border-t border-surface-100 p-3 dark:border-surface-700">
                <BacklogQuickAdd
                    :task-types="taskTypes"
                    :task-priorities="taskPriorities"
                    :task-categories="taskCategories"
                    :assignable-users="assignableUsers"
                    :form="quickForm"
                    :errors="quickErrors"
                    :loading="quickAddLoading"
                    @update:form="emit('update:quickForm', $event)"
                    @submit="emit('submitQuickAdd')"
                    @cancel="emit('cancelQuickAdd')"
                />
            </div>

            <!-- Task rows (draggable) -->
            <VueDraggable
                :model-value="filteredTasks"
                @update:model-value="() => {}"
                :animation="160"
                ghost-class="sprint-ghost"
                group="sprint-backlog"
                class="divide-y divide-surface-100 dark:divide-surface-700/60"
                style="min-height: 40px"
                @add="(e: any) => emit('taskMovedToSprint', e.data, sprint.id)"
                @remove="(e: any) => emit('taskMovedToBacklog', e.data)"
            >
                <BacklogTaskRow
                    v-for="task in filteredTasks"
                    :key="task.id"
                    :task="task"
                    :can-act="canAct"
                    :project-id="projectId"
                    :planning-sprints="[]"
                    :show-move-to-backlog="true"
                    @edit="emit('editTask', $event)"
                    @delete="emit('deleteTask', $event)"
                    @move-to-backlog="emit('taskMovedToBacklog', task)"
                />
            </VueDraggable>

            <!-- Empty drop zone -->
            <div
                v-if="filteredTasks.length === 0 && quickAddSection !== sprint.id"
                class="flex flex-col items-center justify-center py-8 text-surface-300 dark:text-surface-600"
            >
                <i class="pi pi-inbox mb-1.5 text-2xl" />
                <p class="text-xs">Drag tasks here or click + to add</p>
            </div>

            <!-- Footer add button -->
            <button
                v-if="canAct && quickAddSection !== sprint.id"
                class="flex w-full items-center gap-1.5 px-4 py-2.5 text-xs text-surface-400 transition hover:bg-surface-50 hover:text-surface-600 dark:hover:bg-surface-800/50"
                @click="emit('startQuickAdd', sprint.id)"
            >
                <i class="pi pi-plus text-xs" /> Create task
            </button>
        </div>
    </div>
</template>

<style scoped>
.sprint-ghost {
    opacity: 0.25;
    background: #d1fae5;
    border: 1.5px dashed #6ee7b7;
    border-radius: 6px;
}
</style>
