<script setup lang="ts">
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import { computed, ref } from 'vue';
import TaskRow from './Taskrow.vue';

interface TaskListItem {
    id: string | number;
    title?: string;
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

const emit = defineEmits<{
    edit: [task: TaskListItem];
    add: [task: TaskListItem];
    addParent: [epicId: string];
    addEpic: [task: TaskListItem, epicId: string | null];
    updatePriority: [task: TaskListItem, priorityId: string];
    viewEpic: [epicId: string];
    toggleSelect: [task: TaskListItem, checked: boolean];
    taskMenu: [event: MouseEvent, task: TaskListItem];
    addIssue: [];
    createSprint: [];
    toggleSelectAll: [taskIds: string[], checked: boolean];
}>();

const collapsed = ref(false);
const props = defineProps<{
    tasks: TaskListItem[];
    canAct: boolean;
    epics: EpicOption[];
    taskStatuses: OptionItem[];
    taskPriorities: OptionItem[];
    selectedIds: string[];
}>();
const sectionTaskIds = computed(() => props.tasks.map((task) => String(task.id)));
const allSelected = computed(() => sectionTaskIds.value.length > 0 && sectionTaskIds.value.every((id) => props.selectedIds.includes(id)));
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700">
        <!-- Header -->
        <div class="flex cursor-pointer select-none items-center gap-2 bg-surface-50 px-3 py-2 dark:bg-surface-800" @click="collapsed = !collapsed">
            <div v-if="sectionTaskIds.length > 0" class="flex items-center gap-1 text-xs text-surface-500" @click.stop>
                <Checkbox :modelValue="allSelected" binary @update:modelValue="emit('toggleSelectAll', sectionTaskIds, !!$event)" />
            </div>
            <i :class="collapsed ? 'pi pi-chevron-right' : 'pi pi-chevron-down'" class="text-xs text-surface-500" />
            <span class="flex-1 text-sm font-semibold">Backlog</span>
            <span class="text-xs text-surface-500">{{ tasks.length }} issues</span>
            <Button
                v-if="canAct"
                label="Create Sprint"
                icon="pi pi-plus"
                size="small"
                outlined
                class="!py-1 text-xs"
                @click.stop="emit('createSprint')"
            />
        </div>

        <!-- Body -->
        <div v-if="!collapsed">
            <div v-if="!tasks.length" class="flex flex-col items-center justify-center gap-2 py-8 text-surface-400">
                <i class="pi pi-inbox text-2xl" />
                <span class="text-sm">Backlog is empty</span>
            </div>

            <TaskRow
                v-for="task in tasks"
                :key="task.id"
                :task="task"
                :epics="epics"
                :taskStatuses="taskStatuses"
                :taskPriorities="taskPriorities"
                :canAct="canAct"
                :showChecklist="true"
                :selected="selectedIds.includes(String(task.id))"
                @edit="emit('edit', $event)"
                @add="emit('add', $event)"
                @addParent="(epicId) => emit('addParent', epicId)"
                @addEpic="(task, epicId) => emit('addEpic', task, epicId)"
                @updatePriority="(task, priorityId) => emit('updatePriority', task, priorityId)"
                @viewEpic="(epicId) => emit('viewEpic', epicId)"
                @toggleSelect="(task, checked) => emit('toggleSelect', task, checked)"
                @menu="(event, task) => emit('taskMenu', event, task)"
            />

            <div
                v-if="canAct"
                class="flex cursor-pointer items-center gap-2 border-t border-surface-100 px-4 py-2 text-surface-400 hover:bg-surface-50 hover:text-primary-500 dark:border-surface-700 dark:hover:bg-surface-800/50"
                @click="emit('addIssue')"
            >
                <i class="pi pi-plus text-xs" />
                <span class="text-sm">Create</span>
            </div>
        </div>
    </div>
</template>
