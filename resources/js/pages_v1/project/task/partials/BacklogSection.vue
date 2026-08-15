<script setup lang="ts">
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import { computed, inject, ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { ProjectTask } from '../..';
import { BacklogKey } from '../types';
import TaskRow from './Taskrow.vue';

interface Props {
    tasks: ProjectTask[];
    selectedIds: string[];
    loading: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    addIssue: [];
    createSprint: [];
    taskMoved: [taskId: string, fromSprintId: string | null, toSprintId: string | null];
    toggleSelectAll: [taskIds: string[], checked: boolean];
}>();

const context = inject(BacklogKey);

const collapsed = ref(false);
const localTasks = ref([...props.tasks]);

watch(
    () => props.tasks,
    (tasks) => {
        localTasks.value = [...tasks];
    },
    { deep: true },
);

const sectionTaskIds = computed(() => localTasks.value.map((task) => String(task.id)));
const allSelected = computed(() => sectionTaskIds.value.length > 0 && sectionTaskIds.value.every((id) => props.selectedIds.includes(id)));

const onAdd = (e: any) => {
    const taskId = String(e.data?.id || e.item?._value?.id);
    if (taskId) {
        emit('taskMoved', taskId, e.from.dataset.sprintId || null, null);
    }
};
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
            <span class="text-xs text-surface-500">{{ localTasks.length }} issues</span>
            <Button
                v-if="context?.canAct"
                label="Create Sprint"
                icon="pi pi-plus"
                size="small"
                outlined
                class="!py-1 text-xs"
                :loading="loading"
                :disabled="!context?.canAct || loading"
                @click.stop="emit('createSprint')"
            />
        </div>

        <!-- Body -->
        <div v-if="!collapsed">
            <div v-if="!localTasks.length" class="flex flex-col items-center justify-center gap-2 py-8 text-surface-400">
                <i class="pi pi-inbox text-2xl" />
                <span class="text-sm">Backlog is empty</span>
            </div>

            <VueDraggable
                v-model="localTasks"
                group="tasks"
                handle=".drag-handle"
                :animation="150"
                class="min-h-[2rem]"
                data-sprint-id=""
                @add="onAdd"
            >
                <TaskRow
                    v-for="task in localTasks"
                    :key="task.id"
                    :task="task"
                    :draggable="context?.canAct"
                    :showChecklist="true"
                    :selected="selectedIds.includes(String(task.id))"
                />
            </VueDraggable>

            <div
                v-if="context?.canAct"
                class="flex cursor-pointer items-center gap-2 border-t border-surface-100 px-4 py-2 text-surface-400 hover:bg-surface-50 hover:text-primary-500 dark:border-surface-700 dark:hover:bg-surface-800/50"
                @click="emit('addIssue')"
            >
                <i class="pi pi-plus text-xs" />
                <span class="text-sm">Create</span>
            </div>
        </div>
    </div>
</template>
