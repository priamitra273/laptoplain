<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import type { KanbanBadge, KanbanStatusOption, KanbanUser } from '../kanban/types';
import BacklogBoard from './BacklogBoard.vue';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

interface Props extends ShellProps {
    taskStatuses?: KanbanStatusOption[];
    taskPriorities?: KanbanBadge[];
    taskTypes?: KanbanBadge[];
    taskCategories?: KanbanBadge[];
    tags?: KanbanBadge[];
    sprints?: BacklogSprint[];
    backlog?: BacklogTask[];
    epics?: BacklogEpic[];
    assignableUsers?: KanbanUser[];
}

withDefaults(defineProps<Props>(), {
    taskStatuses: () => [],
    taskPriorities: () => [],
    taskTypes: () => [],
    taskCategories: () => [],
    tags: () => [],
    sprints: () => [],
    backlog: () => [],
    epics: () => [],
    assignableUsers: () => [],
});
</script>

<template>
    <Head :title="`Backlog - ${project.title}`" />

    <ProjectShellLayout>
        <Deferred :data="['sprints', 'backlog', 'epics', 'assignableUsers']">
            <template #fallback>
                <div class="flex flex-col gap-3">
                    <USkeleton class="h-10 w-full rounded-lg" />
                    <USkeleton class="h-48 w-full rounded-lg" />
                    <USkeleton class="h-32 w-full rounded-lg" />
                </div>
            </template>

            <BacklogBoard
                :project-id="project.id"
                :sprints="sprints"
                :backlog-tasks="backlog"
                :epics="epics"
                :statuses="taskStatuses"
                :priorities="taskPriorities"
                :types="taskTypes"
                :categories="taskCategories"
                :tags="tags"
                :assignable-users="assignableUsers"
            />
        </Deferred>
    </ProjectShellLayout>
</template>
