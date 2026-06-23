<script setup lang="ts">
import { useLayout } from '@/composables/useLayouts.js';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { ProjectUserOption } from '@/types/task-comment';
import { Head, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, ref, useTemplateRef } from 'vue';
import type { TaskDetailProps } from '../index.d.ts';
import TaskAttachments from './partials/TaskAttachments.vue';
import TaskComments from './partials/TaskComments.vue';
import TaskDescription from './partials/TaskDescription.vue';
import TaskDetails from './partials/TaskDetails.vue';
import TaskHeader from './partials/TaskHeader.vue';
import TaskInProgressDialog from './partials/TaskInProgressDialog.vue';
import TaskSubtasks from './partials/TaskSubtasks.vue';

const props = defineProps<TaskDetailProps>();
const page = usePage();
const toast = useToast();

const { isHorizontal } = useLayout();

const currentUserId = computed(() => Number(page.props.auth.user.id));

const mentionMembers = computed<ProjectUserOption[]>(() =>
    props.project.project_members.map((m) => {
        return {
            id: m.user.id,
            name: m.user.name,
        } as ProjectUserOption;
    }),
);

const inProgressDialogVisible = ref(false);
const inProgressDueDate = ref<Date | null>(null);
const pendingStatusId = ref<string | null>(null);
const taskDetailsRef = useTemplateRef('taskDetailsRef');

const onShowInProgressDialog = (statusId: string) => {
    pendingStatusId.value = statusId;
    inProgressDueDate.value = null;
    inProgressDialogVisible.value = true;
};

const submitInProgressDialog = () => {
    if (!inProgressDueDate.value) {
        toast.add({
            severity: 'warn',
            summary: 'Due Date Required',
            detail: 'Please select a due date to set the task as In Progress.',
            life: 3000,
        });
        return;
    }

    const formattedDueDate = moment(inProgressDueDate.value).format('YYYY-MM-DD');

    if (taskDetailsRef.value) {
        taskDetailsRef.value.autoSave('status_id', pendingStatusId.value, { due_date: formattedDueDate });
    }

    inProgressDialogVisible.value = false;
    pendingStatusId.value = null;
    inProgressDueDate.value = null;
};

const cancelInProgressDialog = () => {
    inProgressDialogVisible.value = false;
    pendingStatusId.value = null;
    inProgressDueDate.value = null;

    if (taskDetailsRef.value) {
        taskDetailsRef.value.editValue = props.task.status_id;
        taskDetailsRef.value.cancelEdit();
    }
};
</script>

<template>
    <Head :title="`Task Detail - ${props.task.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-4 pb-10">
            <TaskHeader :project="props.project" :task="props.task" />

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
                <!-- Main content -->
                <div class="order-last min-w-0 space-y-6 lg:order-none lg:col-span-2">
                    <TaskDescription :task="props.task" :creator="props.creator" />

                    <TaskSubtasks :task="props.task" />

                    <TaskAttachments :task="props.task" />

                    <TaskComments :task="props.task" :comments="props.comments" :currentUserId="currentUserId" :mentionMembers="mentionMembers" />
                </div>

                <!-- Sidebar: properties -->
                <aside class="order-first min-w-0 space-y-6 lg:sticky lg:order-none" :class="[isHorizontal ? 'lg:top-32' : 'lg:top-20']">
                    <TaskDetails
                        ref="taskDetailsRef"
                        :task="props.task"
                        :project="props.project"
                        :isTaskMember="props.isTaskMember"
                        :creator="props.creator"
                        :assignedUsers="props.assignedUsers"
                        :priorities="props.priorities"
                        :statuses="props.statuses"
                        :types="props.types"
                        @showInProgressDialog="onShowInProgressDialog"
                    />
                </aside>
            </div>
        </div>

        <TaskInProgressDialog
            v-model:visible="inProgressDialogVisible"
            v-model:dueDate="inProgressDueDate"
            @confirm="submitInProgressDialog"
            @cancel="cancelInProgressDialog"
        />
    </AppLayout>
</template>
