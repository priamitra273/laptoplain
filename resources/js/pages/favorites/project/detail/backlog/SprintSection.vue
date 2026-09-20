<script setup lang="ts">
import ProgressWithLabel from '@/components/ProgressWithLabel.vue';
import { formatDateRange } from '@/lib/date';
import { isTaskStatusDone, plural } from '@/lib/utils';
import type { DropdownMenuItem } from '@nuxt/ui';
import { computed } from 'vue';
import BacklogGroup from './BacklogGroup.vue';
import BacklogTaskTable from './BacklogTaskTable.vue';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

const props = withDefaults(
    defineProps<{
        sprint: BacklogSprint;
        epics: BacklogEpic[];
        sprints: Pick<BacklogSprint, 'id' | 'name'>[];
        canAct?: boolean;
        canSprintUpdate?: boolean;
        canSprintDelete?: boolean;
    }>(),
    { canAct: false, canSprintUpdate: false, canSprintDelete: false },
);

const emit = defineEmits<{
    edit: [task: BacklogTask];
    move: [task: BacklogTask, fromSprintId: string | null, toSprintId: string | null];
    assignEpic: [task: BacklogTask, epicId: string | null];
    bulkMove: [tasks: BacklogTask[], fromSprintId: string | null, toSprintId: string | null];
    startSprint: [sprint: BacklogSprint];
    completeSprint: [sprint: BacklogSprint];
    editSprint: [sprint: BacklogSprint];
    deleteSprint: [sprint: BacklogSprint];
}>();

const statusName = computed(() => props.sprint.status?.name ?? 'Planning');
const active = computed(() => statusName.value === 'Active');
const started = computed(() => active.value || statusName.value === 'Completed');

/**
 * `ms_sprint_statuses.severity` menyimpan peringkat 1/2/3, bukan kosakata PrimeVue
 * seperti tabel status lain, jadi `severityColor()` tidak berlaku di sini.
 */
const statusColor = computed(() => {
    switch (statusName.value) {
        case 'Active':
            return 'success' as const;
        case 'Completed':
            return 'info' as const;
        default:
            return 'neutral' as const;
    }
});

const scheduleLabel = computed(() => formatDateRange(props.sprint.start_date, props.sprint.end_date));

const progress = computed(() => {
    const tasks = props.sprint.tasks;

    if (!tasks.length) {
        return 0;
    }

    return Math.round((tasks.filter((task) => isTaskStatusDone(task.status?.name)).length / tasks.length) * 100);
});

const sprintActions = computed<DropdownMenuItem[][]>(() => {
    const primary: DropdownMenuItem[] = [
        { label: 'Edit Sprint', icon: 'i-lucide-pencil', disabled: !props.canSprintUpdate, onSelect: () => emit('editSprint', props.sprint) },
    ];

    if (active.value) {
        primary.push({
            label: 'Complete Sprint',
            icon: 'i-lucide-flag',
            disabled: !props.canSprintUpdate,
            onSelect: () => emit('completeSprint', props.sprint),
        });
    }

    return [
        primary,
        [
            {
                label: 'Delete Sprint',
                icon: 'i-lucide-trash',
                color: 'error',
                disabled: !props.canSprintDelete,
                onSelect: () => emit('deleteSprint', props.sprint),
            },
        ],
    ];
});
</script>

<template>
    <BacklogGroup
        :icon="active ? 'i-lucide-zap' : 'i-lucide-calendar-range'"
        :active="active"
        :content-id="`sprint-tasks-${sprint.id}`"
    >
        <template #header>
            <span class="flex flex-wrap items-center gap-2">
                <span class="text-highlighted text-sm font-semibold">{{ sprint.name }}</span>
                <span class="text-dimmed text-xs">{{ plural(sprint.tasks.length, 'task') }}</span>
            </span>

            <span v-if="sprint.goal" class="text-muted text-xs">{{ sprint.goal }}</span>
        </template>

        <template #actions>
            <UBadge :color="statusColor" variant="subtle" size="sm">{{ statusName }}</UBadge>

            <span v-if="started" class="text-muted text-xs whitespace-nowrap">{{ scheduleLabel }}</span>

            <ProgressWithLabel compact :value="progress" :bar-aria-label="`Progress ${sprint.name}`" class="w-32 shrink-0" />

            <UButton
                v-if="canSprintUpdate && !started"
                label="Start Sprint"
                icon="i-lucide-play"
                color="success"
                size="xs"
                @click="emit('startSprint', sprint)"
            />
            <UButton
                v-else-if="canSprintUpdate && active"
                label="Complete Sprint"
                icon="i-lucide-flag"
                color="neutral"
                variant="subtle"
                size="xs"
                @click="emit('completeSprint', sprint)"
            />

            <UDropdownMenu :items="sprintActions" :content="{ align: 'end', side: 'bottom' }">
                <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="xs" :aria-label="`Actions for ${sprint.name}`" />
            </UDropdownMenu>
        </template>

        <BacklogTaskTable
            :tasks="sprint.tasks"
            :epics="epics"
            :sprints="sprints"
            :current-sprint-id="sprint.id"
            :can-act="canAct"
            empty-message="No tasks in this sprint yet."
            empty-description="Tasks added to this sprint will appear here."
            :label="sprint.name"
            @edit="(task) => emit('edit', task)"
            @move="(task, fromSprintId, toSprintId) => emit('move', task, fromSprintId, toSprintId)"
            @assign-epic="(task, epicId) => emit('assignEpic', task, epicId)"
            @bulk-move="(tasks, fromSprintId, toSprintId) => emit('bulkMove', tasks, fromSprintId, toSprintId)"
        />
    </BacklogGroup>
</template>
