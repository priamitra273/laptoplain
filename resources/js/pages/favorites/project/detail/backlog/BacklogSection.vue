<script setup lang="ts">
import { plural } from '@/lib/utils';
import BacklogGroup from './BacklogGroup.vue';
import BacklogTaskTable from './BacklogTaskTable.vue';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

withDefaults(
    defineProps<{
        tasks: BacklogTask[];
        epics: BacklogEpic[];
        sprints: Pick<BacklogSprint, 'id' | 'name'>[];
        canAct?: boolean;
        canSprintCreate?: boolean;
        creatingSprint?: boolean;
    }>(),
    { canAct: false, canSprintCreate: false, creatingSprint: false },
);

const emit = defineEmits<{
    edit: [task: BacklogTask];
    move: [task: BacklogTask, fromSprintId: string | null, toSprintId: string | null];
    assignEpic: [task: BacklogTask, epicId: string | null];
    bulkMove: [tasks: BacklogTask[], fromSprintId: string | null, toSprintId: string | null];
    createSprint: [];
}>();
</script>

<template>
    <BacklogGroup icon="i-lucide-inbox" content-id="backlog-tasks">
        <template #header>
            <span class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-highlighted">Backlog</span>
                <UBadge color="neutral" variant="soft" size="xs">{{ plural(tasks.length, 'task') }}</UBadge>
            </span>

            <span class="text-xs text-muted">Work not yet scheduled into a sprint</span>
        </template>

        <template #actions>
            <UButton
                v-if="canSprintCreate"
                label="Create Sprint"
                icon="i-lucide-plus"
                color="neutral"
                variant="subtle"
                size="xs"
                :loading="creatingSprint"
                :disabled="creatingSprint"
                @click="emit('createSprint')"
            />
        </template>

        <BacklogTaskTable
            :tasks="tasks"
            :epics="epics"
            :sprints="sprints"
            :current-sprint-id="null"
            :can-act="canAct"
            empty-message="Backlog is empty."
            empty-description="Tasks that are not in a sprint will appear here."
            label="Backlog"
            @edit="(task) => emit('edit', task)"
            @move="(task, fromSprintId, toSprintId) => emit('move', task, fromSprintId, toSprintId)"
            @assign-epic="(task, epicId) => emit('assignEpic', task, epicId)"
            @bulk-move="(tasks, fromSprintId, toSprintId) => emit('bulkMove', tasks, fromSprintId, toSprintId)"
        />
    </BacklogGroup>
</template>
