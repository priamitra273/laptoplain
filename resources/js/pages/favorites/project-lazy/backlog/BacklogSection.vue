<script setup lang="ts">
import { ref, watch } from 'vue';
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus';
import type { KanbanBadge } from '../kanban/types';
import TaskRow from './TaskRow.vue';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

interface Props {
    tasks: BacklogTask[];
    sprints: BacklogSprint[];
    epics: BacklogEpic[];
    priorities: KanbanBadge[];
    canAct: boolean;
    creatingSprint: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    addIssue: [];
    createSprint: [];
    taskMoved: [taskId: string, fromSprintId: string | null, toSprintId: string | null];
    editTask: [task: BacklogTask];
    updatePriority: [task: BacklogTask, priorityId: string];
    moveTask: [task: BacklogTask, fromSprintId: string | null, toSprintId: string | null];
}>();

const collapsed = ref(false);
const localTasks = ref<BacklogTask[]>([...props.tasks]);

watch(
    () => props.tasks,
    (tasks) => {
        localTasks.value = [...tasks];
    },
    { deep: true },
);

const onAdd = (event: DraggableEvent<BacklogTask>) => {
    const fromEl = event.from as HTMLElement;
    if (event.data) {
        emit('taskMoved', String(event.data.id), fromEl.dataset.sprintId || null, null);
    }
};
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-default">
        <div class="flex cursor-pointer items-center gap-2 bg-elevated/50 px-3 py-2 select-none" @click="collapsed = !collapsed">
            <UIcon :name="collapsed ? 'i-lucide-chevron-right' : 'i-lucide-chevron-down'" class="size-3.5 shrink-0 text-muted" />
            <span class="flex-1 text-sm font-semibold">Backlog</span>
            <span class="text-xs text-muted">{{ localTasks.length }} issues</span>
            <UButton
                v-if="canAct"
                label="Create Sprint"
                icon="i-lucide-plus"
                color="neutral"
                variant="outline"
                size="xs"
                :loading="creatingSprint"
                :disabled="creatingSprint"
                @click.stop="emit('createSprint')"
            />
        </div>

        <div v-if="!collapsed">
            <div v-if="!localTasks.length" class="flex flex-col items-center justify-center gap-2 py-8 text-muted">
                <UIcon name="i-lucide-inbox" class="size-6" />
                <span class="text-sm">Backlog is empty</span>
            </div>

            <VueDraggable
                v-model="localTasks"
                group="backlog-tasks"
                handle=".drag-handle"
                :animation="150"
                ghost-class="opacity-40"
                class="min-h-8"
                data-sprint-id=""
                @add="onAdd"
            >
                <TaskRow
                    v-for="task in localTasks"
                    :key="task.id"
                    :task="task"
                    :epics="epics"
                    :priorities="priorities"
                    :sprints="sprints"
                    :current-sprint-id="null"
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
                @click="emit('addIssue')"
            >
                <UIcon name="i-lucide-plus" class="size-3.5" />
                <span class="text-sm">Create</span>
            </button>
        </div>
    </div>
</template>
