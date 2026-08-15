<script setup lang="ts">
import moment from 'moment';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { computed, inject, ref, watch } from 'vue';
import { DraggableEvent, VueDraggable } from 'vue-draggable-plus';
import type { ProjectTask, Sprint } from '../type.d';
import { BacklogKey } from '../types';
import TaskRow from './Taskrow.vue';

const props = defineProps<{
    sprint: Sprint;
    selectedIds: string[];
}>();

const emit = defineEmits<{
    sprintMenu: [event: MouseEvent, sprint: Sprint];
    start: [sprint: Sprint];
    complete: [sprint: Sprint];
    addIssue: [sprintId: string];
    taskMoved: [taskId: string, fromSprintId: string | null, toSprintId: string | null];
    toggleSelectAll: [taskIds: string[], checked: boolean];
}>();

const context = inject(BacklogKey);

const collapsed = ref(false);
const localTasks = ref([...(props.sprint.tasks ?? [])]);

watch(
    () => props.sprint.tasks,
    (tasks) => {
        localTasks.value = [...(tasks ?? [])];
    },
    { deep: true },
);

const visibleSprintTasks = computed(() => localTasks.value.filter((task) => task.category?.name?.toLowerCase() !== 'epic'));
const sprintTaskIds = computed(() => visibleSprintTasks.value.map((task) => String(task.id)));
const allSelected = computed(() => sprintTaskIds.value.length > 0 && sprintTaskIds.value.every((id) => props.selectedIds.includes(id)));

const sprintStatusSeverity = (name?: string): any => ({ Planning: 'secondary', Active: 'info', Completed: 'success' })[name ?? ''] ?? 'secondary';

const sprintProgress = (sprint: Sprint) => {
    if (!sprint.tasks?.length) return 0;
    const done = sprint.tasks.filter((t) => ['Done', 'Completed'].includes(t.status?.name ?? '')).length;
    return Math.round((done / sprint.tasks.length) * 100);
};

const formatDate = (d?: string) => (d ? moment(d).format('D MMM') : '');

const onSort = (e: any) => {
    if (e.from === e.to && e.oldIndex === e.newIndex) return;
    const taskId = String(e.item._value?.id || e.clone?._value?.id || localTasks.value[e.newIndex]?.id);
    if (!taskId) return;

    // We need to know if it came from another list or just reordered within
    // But for now, we just emit taskMoved and let Backlog.vue handle the logic
    // Actually, VueDraggablePlus 'add' and 'remove' events are better for cross-list
};

const onAdd = (e: DraggableEvent<ProjectTask>) => {
    if (e.data) {
        emit('taskMoved', e.data.id, e.from.dataset.sprintId ?? null, props.sprint.id);
    }
};
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
            <div v-if="context?.canAct" class="ml-auto flex shrink-0 gap-1" @click.stop>
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
            <div v-if="!localTasks.length" class="flex flex-col items-center justify-center gap-2 py-8 text-surface-400">
                <i class="pi pi-inbox text-2xl" />
                <span class="text-sm">No issues in this sprint</span>
            </div>

            <VueDraggable
                v-model="localTasks"
                group="tasks"
                handle=".drag-handle"
                :animation="150"
                class="min-h-[2rem]"
                :data-sprint-id="sprint.id"
                @add="onAdd"
            >
                <TaskRow
                    v-for="task in visibleSprintTasks"
                    :key="task.id"
                    :task="task"
                    :draggable="context?.canAct"
                    :showChecklist="true"
                    :selected="props.selectedIds.includes(String(task.id))"
                />
            </VueDraggable>

            <div
                v-if="context?.canAct"
                class="flex cursor-pointer items-center gap-2 border-t border-surface-100 px-4 py-2 text-surface-400 hover:bg-surface-50 hover:text-primary-500 dark:border-surface-700 dark:hover:bg-surface-800/50"
                @click="emit('addIssue', sprint.id)"
            >
                <i class="pi pi-plus text-xs" />
                <span class="text-sm">Create</span>
            </div>
        </div>
    </div>
</template>
