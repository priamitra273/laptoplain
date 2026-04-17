<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { ProjectUserOption } from '@/types/task-comment';
import { Head, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, ref, useTemplateRef } from 'vue';
import type { TaskDetailProps } from '../index.d.ts';
import TaskComments from './partials/TaskComments.vue';
import TaskDescription from './partials/TaskDescription.vue';
import TaskDetails from './partials/TaskDetails.vue';
import TaskHeader from './partials/TaskHeader.vue';
import TaskInProgressDialog from './partials/TaskInProgressDialog.vue';
import TaskMembers from './partials/TaskMembers.vue';
import TaskSubtasks from './partials/TaskSubtasks.vue';

const props = defineProps<TaskDetailProps>();
const page = usePage();
const toast = useToast();

const currentUserId = computed(() => Number(page.props.auth.user.id));

const mentionMembers = computed<ProjectUserOption[]>(() =>
    props.project.project_members.map((m) => {
        return {
            id: m.user.id,
            name: m.user.name,
        } as ProjectUserOption;
    }),
);

// --- In Progress Dialog ---
const inProgressDialogVisible = ref(false);
const inProgressDueDate = ref<Date | null>(null);
const pendingStatusId = ref<string | null>(null);
// const taskDetailsRef = ref<InstanceType<typeof TaskDetails> | null>(null);
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

    // Reset select back to original value in child component
    if (taskDetailsRef.value) {
        taskDetailsRef.value.editValue = props.task.status_id;
        taskDetailsRef.value.cancelEdit();
    }
};
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
                        ref="taskDetailsRef"
                        :task="props.task"
                        :project="props.project"
                        :isTaskMember="props.isTaskMember"
                        :creator="props.creator"
                        :priorities="props.priorities"
                        :statuses="props.statuses"
                        :types="props.types"
                        @showInProgressDialog="onShowInProgressDialog"
                    />

                    <TaskMembers :values="props.assignedUsers" />
                </div>

                <!-- Right Column -->
                <div class="lg:col-span-2">
                    <TaskDescription :task="props.task" :creator="props.creator" />

                    <TaskComments :task="props.task" :comments="props.comments" :currentUserId="currentUserId" :mentionMembers="mentionMembers" />
                </div>
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
