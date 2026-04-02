<script setup lang="ts">
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import type { Sprint } from '../type';
import TaskRow from './Taskrow.vue';

interface SprintTask {
    id: string | number;
    title?: string;
    status?: { name?: string } | null;
    [key: string]: any;
}

interface EpicOption {
    id: string;
    title: string;
}
interface OptionItem {
    id: string;
    name: string;
    severity?: string;
}

const props = defineProps<{
    sprint: Sprint;
    canAct: boolean;
    epics: EpicOption[];
    taskStatuses: OptionItem[];
    taskPriorities: OptionItem[];
    selectedIds: string[];
}>();

const emit = defineEmits<{
    edit: [task: SprintTask];
    add: [task: SprintTask, sprintId: string];
    addParent: [epicId: string, sprintId: string];
    addEpic: [task: SprintTask, epicId: string | null, sprintId: string];
    updatePriority: [task: SprintTask, priorityId: string, sprintId: string];
    viewEpic: [epicId: string];
    toggleSelect: [task: SprintTask, checked: boolean];
    toggleSelectAll: [taskIds: string[], checked: boolean];
    taskMenu: [event: MouseEvent, task: SprintTask, sprintId: string];
    sprintMenu: [event: MouseEvent, sprint: Sprint];
    start: [sprint: Sprint];
    complete: [sprint: Sprint];
    addIssue: [sprintId: string];
}>();

const collapsed = ref(false);
const visibleSprintTasks = computed(() => (props.sprint.tasks ?? []).filter((task) => task.category?.name?.toLowerCase() !== 'epic'));
const sprintTaskIds = computed(() => visibleSprintTasks.value.map((task) => String(task.id)));
const allSelected = computed(() => sprintTaskIds.value.length > 0 && sprintTaskIds.value.every((id) => props.selectedIds.includes(id)));

const sprintStatusSeverity = (name?: string): any => ({ Planning: 'secondary', Active: 'info', Completed: 'success' })[name ?? ''] ?? 'secondary';

const sprintProgress = (sprint: Sprint) => {
    if (!sprint.tasks?.length) return 0;
    const done = sprint.tasks.filter((t) => ['Done', 'Completed'].includes(t.status?.name ?? '')).length;
    return Math.round((done / sprint.tasks.length) * 100);
};

const formatDate = (d?: string) => (d ? new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : '');
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700">
        <!-- Header -->
        <div class="flex cursor-pointer select-none items-center gap-2 bg-surface-50 px-3 py-2 dark:bg-surface-800" @click="collapsed = !collapsed">
            <div v-if="sprintTaskIds.length > 0" class="flex shrink-0 items-center gap-1 text-xs text-surface-500" @click.stop>
                <Checkbox :modelValue="allSelected" binary @update:modelValue="emit('toggleSelectAll', sprintTaskIds, !!$event)" />
            </div>
            <i :class="collapsed ? 'pi pi-chevron-right' : 'pi pi-chevron-down'" class="shrink-0 text-xs text-surface-500" />
            <span class="min-w-0 flex-1 truncate text-sm font-semibold" :title="sprint.name">{{ sprint.name }}</span>

            <Tag :severity="sprintStatusSeverity(sprint.status?.name)" :value="sprint.status?.name ?? 'Planning'" class="shrink-0 text-xs" />

            <span v-if="sprint.start_date && sprint.end_date" class="hidden text-xs text-surface-500 sm:inline">
                {{ formatDate(sprint.start_date) }} – {{ formatDate(sprint.end_date) }}
            </span>

            <span class="shrink-0 text-xs text-surface-500">{{ visibleSprintTasks.length }} issues</span>

            <div class="hidden w-24 items-center gap-1 sm:flex" @click.stop>
                <ProgressBar :value="sprintProgress(sprint)" class="h-1.5 flex-1" :showValue="false" />
                <span class="w-8 text-right text-xs text-surface-500">{{ sprintProgress(sprint) }}%</span>
            </div>

            <!-- Actions -->
            <div v-if="canAct" class="ml-auto flex shrink-0 gap-1" @click.stop>
                <Button
                    v-if="sprint.status?.name === 'Planning'"
                    label="Start Sprint"
                    icon="pi pi-play"
                    size="small"
                    severity="success"
                    class="!py-1 text-xs"
                    @click="emit('start', sprint)"
                />
                <Button
                    v-if="sprint.status?.name === 'Active'"
                    label="Complete"
                    icon="pi pi-check"
                    size="small"
                    severity="info"
                    class="!py-1 text-xs"
                    @click="emit('complete', sprint)"
                />
                <Button icon="pi pi-ellipsis-v" size="small" text rounded severity="secondary" @click="emit('sprintMenu', $event, sprint)" />
            </div>
        </div>

        <!-- Goal -->
        <div
            v-if="sprint.goal && !collapsed"
            class="border-t border-surface-100 bg-surface-50 px-4 py-1 text-xs italic text-surface-500 dark:border-surface-700 dark:bg-surface-800"
        >
            Goal: {{ sprint.goal }}
        </div>

        <!-- Body -->
        <div v-if="!collapsed">
            <div v-if="!visibleSprintTasks.length" class="flex flex-col items-center justify-center gap-2 py-8 text-surface-400">
                <i class="pi pi-inbox text-2xl" />
                <span class="text-sm">No issues in this sprint</span>
            </div>

            <TaskRow
                v-for="task in visibleSprintTasks"
                :key="task.id"
                :task="task"
                :epics="epics"
                :taskStatuses="taskStatuses"
                :taskPriorities="taskPriorities"
                :canAct="canAct"
                :showChecklist="true"
                :selected="props.selectedIds.includes(String(task.id))"
                @edit="emit('edit', $event)"
                @add="(task) => emit('add', task, sprint.id)"
                @addParent="(epicId) => emit('addParent', epicId, sprint.id)"
                @addEpic="(task, epicId) => emit('addEpic', task, epicId, sprint.id)"
                @updatePriority="(task, priorityId) => emit('updatePriority', task, priorityId, sprint.id)"
                @viewEpic="(epicId) => emit('viewEpic', epicId)"
                @toggleSelect="(task, checked) => emit('toggleSelect', task, checked)"
                @menu="(event, task) => emit('taskMenu', event, task, sprint.id)"
            />

            <div
                v-if="canAct"
                class="flex cursor-pointer items-center gap-2 border-t border-surface-100 px-4 py-2 text-surface-400 hover:bg-surface-50 hover:text-primary-500 dark:border-surface-700 dark:hover:bg-surface-800/50"
                @click="emit('addIssue', sprint.id)"
            >
                <i class="pi pi-plus text-xs" />
                <span class="text-sm">Create</span>
            </div>
        </div>
    </div>
</template>
