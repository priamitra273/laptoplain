<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import moment from 'moment';
import { computed, ref, watch } from 'vue';
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus';
import type { KanbanBadge } from '../kanban/types';
import TaskRow from './TaskRow.vue';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

interface Props {
    sprint: BacklogSprint;
    allSprints: BacklogSprint[];
    epics: BacklogEpic[];
    priorities: KanbanBadge[];
    canAct: boolean;
    canSprintUpdate: boolean;
    canSprintDelete: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    editSprint: [sprint: BacklogSprint];
    start: [sprint: BacklogSprint];
    complete: [sprint: BacklogSprint];
    deleteSprint: [sprint: BacklogSprint];
    addIssue: [sprintId: string];
    taskMoved: [taskId: string, fromSprintId: string | null, toSprintId: string | null];
    editTask: [task: BacklogTask];
    updatePriority: [task: BacklogTask, priorityId: string];
    moveTask: [task: BacklogTask, fromSprintId: string | null, toSprintId: string | null];
}>();

const sprintMenuItems = computed(() => {
    const items: { label: string; icon: string; color?: 'error'; disabled?: boolean; onSelect: () => void }[][] = [
        [{ label: 'Edit Sprint', icon: 'i-lucide-pencil', disabled: !props.canSprintUpdate, onSelect: () => emit('editSprint', props.sprint) }],
    ];

    if (props.sprint.status?.name === 'Active') {
        items[0].push({
            label: 'Complete Sprint',
            icon: 'i-lucide-flag',
            disabled: !props.canSprintUpdate,
            onSelect: () => emit('complete', props.sprint),
        });
    }

    items.push([
        {
            label: 'Delete Sprint',
            icon: 'i-lucide-trash',
            color: 'error',
            disabled: !props.canSprintDelete,
            onSelect: () => emit('deleteSprint', props.sprint),
        },
    ]);

    return items;
});

const collapsed = ref(false);
const localTasks = ref<BacklogTask[]>([...(props.sprint.tasks ?? [])]);

watch(
    () => props.sprint.tasks,
    (tasks) => {
        localTasks.value = [...(tasks ?? [])];
    },
    { deep: true },
);

const sprintStatusColor = computed(() => severityColor(props.sprint.status?.severity));

const sprintProgress = computed(() => {
    const tasks = props.sprint.tasks ?? [];
    if (!tasks.length) return 0;
    const done = tasks.filter((task) => ['Done', 'Completed'].includes(task.status?.name ?? '')).length;
    return Math.round((done / tasks.length) * 100);
});

const formatDate = (date?: string | null) => (date ? moment(date).format('D MMM') : '');

const onAdd = (event: DraggableEvent<BacklogTask>) => {
    const fromEl = event.from as HTMLElement;
    if (event.data) {
        emit('taskMoved', String(event.data.id), fromEl.dataset.sprintId || null, props.sprint.id);
    }
};
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-default">
        <div class="flex cursor-pointer items-center gap-2 bg-elevated/50 px-3 py-2 select-none" @click="collapsed = !collapsed">
            <UIcon :name="collapsed ? 'i-lucide-chevron-right' : 'i-lucide-chevron-down'" class="size-3.5 shrink-0 text-muted" />
            <span class="min-w-0 flex-1 truncate text-sm font-semibold" :title="sprint.name">{{ sprint.name }}</span>

            <UBadge :color="sprintStatusColor" variant="subtle" size="sm" class="shrink-0">{{ sprint.status?.name ?? 'Planning' }}</UBadge>

            <span v-if="sprint.start_date && sprint.end_date" class="hidden shrink-0 text-xs text-muted sm:inline">
                {{ formatDate(sprint.start_date) }} – {{ formatDate(sprint.end_date) }}
            </span>

            <span class="shrink-0 text-xs text-muted">{{ (sprint.tasks ?? []).length }} issues</span>

            <div class="hidden w-24 shrink-0 items-center gap-1 sm:flex" @click.stop>
                <UProgress :model-value="sprintProgress" size="xs" class="flex-1" />
                <span class="w-8 text-right text-xs text-muted">{{ sprintProgress }}%</span>
            </div>

            <div v-if="canAct" class="ml-auto flex shrink-0 items-center gap-1" @click.stop>
                <UButton
                    v-if="sprint.status?.name === 'Planning'"
                    label="Start Sprint"
                    icon="i-lucide-play"
                    color="success"
                    size="xs"
                    @click="emit('start', sprint)"
                />
                <UButton
                    v-if="sprint.status?.name === 'Active'"
                    label="Complete"
                    icon="i-lucide-check"
                    color="info"
                    size="xs"
                    @click="emit('complete', sprint)"
                />
                <UDropdownMenu :items="sprintMenuItems" :content="{ align: 'end' }">
                    <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="xs" aria-label="Sprint menu" />
                </UDropdownMenu>
            </div>
        </div>

        <div v-if="sprint.goal && !collapsed" class="border-t border-default bg-elevated/30 px-4 py-1 text-xs text-muted italic">
            Goal: {{ sprint.goal }}
        </div>

        <div v-if="!collapsed">
            <div v-if="!localTasks.length" class="flex flex-col items-center justify-center gap-2 py-8 text-muted">
                <UIcon name="i-lucide-inbox" class="size-6" />
                <span class="text-sm">No issues in this sprint</span>
            </div>

            <VueDraggable
                v-model="localTasks"
                group="backlog-tasks"
                handle=".drag-handle"
                :animation="150"
                ghost-class="opacity-40"
                class="min-h-8"
                :data-sprint-id="sprint.id"
                @add="onAdd"
            >
                <TaskRow
                    v-for="task in localTasks"
                    :key="task.id"
                    :task="task"
                    :epics="epics"
                    :priorities="priorities"
                    :sprints="allSprints"
                    :current-sprint-id="sprint.id"
                    :draggable="canAct"
                    :can-act="canAct"
                    @edit="emit('editTask', task)"
                    @update-priority="(t, priorityId) => emit('updatePriority', t, priorityId)"
                    @move-to="(t, fromId, toId) => emit('moveTask', t, fromId, toId)"
                />
            </VueDraggable>

            <button
                v-if="canAct"
                type="button"
                class="flex w-full cursor-pointer items-center gap-2 border-t border-default px-4 py-2 text-muted hover:bg-elevated/50 hover:text-primary"
                @click="emit('addIssue', sprint.id)"
            >
                <UIcon name="i-lucide-plus" class="size-3.5" />
                <span class="text-sm">Create</span>
            </button>
        </div>
    </div>
</template>
