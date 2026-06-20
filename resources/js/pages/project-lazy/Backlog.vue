<script lang="ts">
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';

export default { layout: ProjectShellLayout };
</script>

<script setup lang="ts">
import { ProjectPolicyKey } from '@/types/type';
import { Deferred, Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { inject, ref } from 'vue';
import { useBacklogBoard } from './composables/useBacklogBoard';
import type { BacklogProps } from './index';
import BacklogSection from './partials/backlog/BacklogSection.vue';
import BacklogSkeleton from './partials/backlog/BacklogSkeleton.vue';
import BacklogTaskDrawer from './partials/backlog/BacklogTaskDrawer.vue';
import CompleteSprintDialog from './partials/backlog/CompleteSprintDialog.vue';
import EditSprintDialog from './partials/backlog/EditSprintDialog.vue';
import SprintSection from './partials/backlog/SprintSection.vue';
import StartSprintDialog from './partials/backlog/StartSprintDialog.vue';

const props = defineProps<BacklogProps>();

const policy = inject(ProjectPolicyKey, null);

const drawer = ref<InstanceType<typeof BacklogTaskDrawer> | null>(null);

const {
    localSprints,
    localBacklog,
    loading,
    selectedTaskIds,
    selectedCount,
    isAllSelected,
    toggleSelectAll,
    toggleMany,
    clearSelection,
    showStartDialog,
    startingSprint,
    showEditDialog,
    editingSprint,
    showCompleteDialog,
    completingSprint,
    openStartDialog,
    openCompleteDialog,
    createSprint,
    onTaskMoved,
    onSprintMenu,
    refresh,
    sprintMenu,
    taskMenu,
    sprintMenuItems,
    taskMenuItems,
    openCreateTask,
} = useBacklogBoard(props, policy, {
    openCreate: (options) => drawer.value?.openCreate(options),
    openEdit: (task) => drawer.value?.openEdit(task),
});
</script>

<template>
    <Head :title="`Backlog - ${props.project.title}`" />

    <Deferred :data="['sprints', 'backlog', 'epics', 'assignableUsers']">
        <template #fallback>
            <BacklogSkeleton />
        </template>

        <div class="flex flex-col gap-3">
            <!-- Sprints -->
            <SprintSection
                v-for="sprint in localSprints"
                :key="sprint.id"
                :sprint="sprint"
                :selectedIds="selectedTaskIds"
                @sprintMenu="onSprintMenu"
                @start="openStartDialog"
                @complete="openCompleteDialog"
                @addIssue="openCreateTask"
                @taskMoved="onTaskMoved"
                @toggleSelectAll="toggleMany"
            />

            <!-- Backlog -->
            <BacklogSection
                :tasks="localBacklog"
                :selectedIds="selectedTaskIds"
                :loading="loading.backlog"
                @addIssue="openCreateTask"
                @createSprint="createSprint"
                @taskMoved="onTaskMoved"
                @toggleSelectAll="toggleMany"
            />
        </div>

        <!-- Bulk selection toolbar -->
        <div
            v-if="selectedCount > 0"
            class="fixed bottom-4 left-1/2 z-40 flex -translate-x-1/2 items-center gap-2 rounded-md border border-surface-200 bg-white px-3 py-2 text-sm shadow-lg dark:border-surface-700 dark:bg-surface-900"
        >
            <span class="rounded bg-surface-100 px-2 py-0.5 text-xs font-semibold text-surface-700 dark:bg-surface-800 dark:text-surface-200">
                {{ selectedCount }} selected
            </span>
            <Button :label="isAllSelected ? 'Unselect all' : 'Select all'" text size="small" class="!px-2" @click="toggleSelectAll" />
            <Button label="Clear" text size="small" class="!px-2" @click="clearSelection" />
        </div>

        <StartSprintDialog v-model:visible="showStartDialog" :sprint="startingSprint" :projectId="props.project.id" @saved="refresh" />
        <EditSprintDialog v-model:visible="showEditDialog" :sprint="editingSprint" :projectId="props.project.id" @saved="refresh" />
        <CompleteSprintDialog
            v-model:visible="showCompleteDialog"
            :sprint="completingSprint"
            :sprints="localSprints"
            :projectId="props.project.id"
            @saved="refresh"
        />

        <Menu ref="sprintMenu" :model="sprintMenuItems" popup />
        <Menu ref="taskMenu" :model="taskMenuItems" popup />

        <BacklogTaskDrawer
            ref="drawer"
            :projectId="props.project.id"
            :taskStatuses="props.taskStatuses"
            :taskPriorities="props.taskPriorities"
            :taskTypes="props.taskTypes"
            :taskCategories="props.taskCategories"
            :tags="props.tags"
            :assignableUsers="props.assignableUsers"
            @saved="refresh"
        />
    </Deferred>
</template>
