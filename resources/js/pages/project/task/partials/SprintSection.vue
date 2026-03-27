<script setup lang="ts">
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { ref } from 'vue';
import type { Task } from '..';
import type { Sprint } from '../type';
import TaskRow from './TaskRow.vue';

const props = defineProps<{
    sprint: Sprint;
    canAct: boolean;
}>();

const emit = defineEmits<{
    edit: [task: Task];
    taskMenu: [event: MouseEvent, task: Task, sprintId: string];
    sprintMenu: [event: MouseEvent, sprint: Sprint];
    start: [sprint: Sprint];
    complete: [sprint: Sprint];
    addIssue: [];
}>();

const collapsed = ref(false);

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
            <i :class="collapsed ? 'pi pi-chevron-right' : 'pi pi-chevron-down'" class="text-xs text-surface-500" />
            <span class="flex-1 text-sm font-semibold">{{ sprint.name }}</span>

            <Tag :severity="sprintStatusSeverity(sprint.status?.name)" :value="sprint.status?.name ?? 'Planning'" class="text-xs" />

            <span v-if="sprint.start_date && sprint.end_date" class="hidden text-xs text-surface-500 sm:inline">
                {{ formatDate(sprint.start_date) }} – {{ formatDate(sprint.end_date) }}
            </span>

            <span class="text-xs text-surface-500">{{ sprint.tasks?.length ?? 0 }} issues</span>

            <div class="hidden w-24 items-center gap-1 sm:flex" @click.stop>
                <ProgressBar :value="sprintProgress(sprint)" class="h-1.5 flex-1" :showValue="false" />
                <span class="w-8 text-right text-xs text-surface-500">{{ sprintProgress(sprint) }}%</span>
            </div>

            <!-- Actions -->
            <div v-if="canAct" class="flex gap-1" @click.stop>
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
            <div v-if="!sprint.tasks?.length" class="flex flex-col items-center justify-center gap-2 py-8 text-surface-400">
                <i class="pi pi-inbox text-2xl" />
                <span class="text-sm">No issues in this sprint</span>
            </div>

            <TaskRow
                v-for="task in sprint.tasks"
                :key="task.id"
                :task="task"
                @edit="emit('edit', $event)"
                @menu="emit('taskMenu', $event, $event, sprint.id)"
            />

            <div
                v-if="canAct"
                class="flex cursor-pointer items-center gap-2 border-t border-surface-100 px-4 py-2 text-surface-400 hover:bg-surface-50 hover:text-primary-500 dark:border-surface-700 dark:hover:bg-surface-800/50"
                @click="emit('addIssue')"
            >
                <i class="pi pi-plus text-xs" />
                <span class="text-sm">Add issue</span>
            </div>
        </div>
    </div>
</template>
