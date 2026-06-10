<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { ProjectUserOption } from '@/types/task-comment';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { TaskDetailProps } from '../index.d.ts';
import TaskComments from './partials/TaskComments.vue';
import TaskDescription from './partials/TaskDescription.vue';
import TaskDetails from './partials/TaskDetails.vue';
import TaskHeader from './partials/TaskHeader.vue';
import TaskMembers from './partials/TaskMembers.vue';
import TaskSubtasks from './partials/TaskSubtasks.vue';

const props = defineProps<TaskDetailProps>();
const page = usePage();

const currentUserId = computed(() => Number(page.props.auth.user.id));

const mentionMembers = computed<ProjectUserOption[]>(() =>
    props.project.project_members.map((m) => {
        return {
            id: m.user.id,
            name: m.user.name,
        } as ProjectUserOption;
    }),
);
</script>

<template>
    <Head :title="`Task Detail - ${props.task.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-6 pb-8">
            <TaskHeader :project="props.project" :task="props.task" />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column -->
                <div class="space-y-6">
                    <TaskSubtasks :task="props.task" />

                    <TaskDetails
                        :task="props.task"
                        :project="props.project"
                        :isTaskMember="props.isTaskMember"
                        :creator="props.creator"
                        :priorities="props.priorities"
                        :statuses="props.statuses"
                        :types="props.types"
                    />

                    <TaskMembers :users="props.assignedUsers" />
                </div>

                <!-- Right Column -->
                <div class="lg:col-span-2">
                    <TaskDescription :task="props.task" :creator="props.creator" />

                    <TaskComments :task="props.task" :comments="props.comments" :currentUserId="currentUserId" :mentionMembers="mentionMembers" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
