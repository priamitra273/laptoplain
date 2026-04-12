<script setup lang="ts">
import UserAvatar from '@/components/UserAvatar.vue';
import { Link } from '@inertiajs/vue3';
import moment from 'moment';
import type { Task } from '../../..';
import KanbanRow from './KanbanRow.vue';

const props = defineProps<{
    task: Task | null;
    canAct: boolean;
}>();

const visible = defineModel<boolean>('visible', { default: false });

const emit = defineEmits<{
    edit: [task: Task, parentId: string | null];
    delete: [task: Task];
    add: [taskId: string];
}>();

const isOverdue = (task: Task) =>
    !!task.due_date && moment(task.due_date).isBefore(moment(), 'day') && !task.status?.name?.toLowerCase().includes('done');

const subtaskCount = (task: Task) => task.sub_task_recursive?.length || 0;
const doneSubtaskCount = (task: Task) => {
    return (task.sub_task_recursive || []).filter((s) => s.status?.name?.toLowerCase().includes('done')).length;
};
</script>

<template>
    <Drawer v-model:visible="visible" modal dismissable position="right" class="!w-full md:!w-1/2 lg:!w-[40%]">
        <template #container="{ closeCallback }">
            <div v-if="task" class="flex h-full flex-col overflow-hidden">
                <!-- Header -->
                <div class="flex items-start justify-end border-b border-surface-100 p-4 dark:border-surface-700">
                    <div class="flex shrink-0 items-center gap-1">
                        <Link :href="route('task.show', task.id)">
                            <Button icon="pi pi-external-link" text rounded severity="secondary" v-tooltip.top="'Open full page'" />
                        </Link>

                        <Button icon="pi pi-times" outlined severity="secondary" @click="closeCallback" />
                    </div>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <h2 class="mb-6 text-2xl font-semibold leading-snug text-surface-800 dark:text-surface-100">
                        {{ task.title }}
                    </h2>

                    <div class="mb-4 space-y-3">
                        <KanbanRow label="Assignee" icon="UsersRound">
                            <Chip v-for="user in task.users" :label="user.name" :key="user.id" class="!py-1 !pl-1 !pr-2 text-sm">
                                <template #icon>
                                    <UserAvatar :user="user" fontSize=".75rem" />
                                </template>
                            </Chip>
                        </KanbanRow>

                        <KanbanRow label="Status" icon="Loader">
                            <Tag v-if="task.status" :value="task.status?.name" :severity="task.status?.severity" />
                        </KanbanRow>

                        <KanbanRow label="Start Date" icon="Calendar">
                            <span>{{ task.start_date ? moment(task.start_date).format('DD MMM YYYY') : '—' }}</span>
                        </KanbanRow>

                        <KanbanRow label="Due Date" icon="CalendarCheck">
                            <span>{{ task.due_date ? moment(task.due_date).format('DD MMM YYYY') : '—' }}</span>
                        </KanbanRow>

                        <KanbanRow label="Priority" icon="Target">
                            <Tag v-if="task.priority" :value="task.priority?.name" :severity="task.priority?.severity" />
                        </KanbanRow>

                        <KanbanRow label="Type" icon="Tag">
                            <Tag v-if="task.type" :value="task.type?.name" :severity="task.type?.severity" />
                        </KanbanRow>

                        <KanbanRow label="Progress" icon="ClipboardCheck">
                            <div class="flex h-full items-center gap-2">
                                <ProgressBar :value="Number(task.progress) || 0" class="flex-1" style="height: 10px" :showValue="false" />
                                <span class="text-xs text-surface-500">{{ task.progress || 0 }}%</span>
                            </div>
                        </KanbanRow>
                    </div>

                    <div class="mb-4 py-4">
                        <h3 class="mb-2 text-lg font-semibold text-surface-600">Deskripsi</h3>
                        <div v-html="task.description"></div>
                    </div>

                    <!-- Subtasks -->
                    <div class="mb-4">
                        <div class="mb-1.5 flex items-center justify-between">
                            <p class="text-[10px] font-semibold uppercase text-surface-400">
                                Subtasks
                                <span v-if="subtaskCount(task)">({{ doneSubtaskCount(task) }}/{{ subtaskCount(task) }})</span>
                            </p>
                            <Button
                                label="Add subtask"
                                icon="pi pi-plus"
                                size="small"
                                text
                                :disabled="!canAct"
                                class="!text-xs"
                                @click="
                                    emit('add', task.id);
                                    visible = false;
                                "
                            />
                        </div>
                        <div v-if="subtaskCount(task) > 0" class="flex flex-col gap-1.5">
                            <div
                                v-for="sub in task.sub_task_recursive"
                                :key="sub.id"
                                class="flex items-center justify-between rounded-lg border border-surface-100 bg-surface-50 px-3 py-2 dark:border-surface-700 dark:bg-surface-800"
                            >
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <div
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :class="sub.status?.name?.toLowerCase().includes('done') ? 'bg-emerald-400' : 'bg-surface-300'"
                                    />
                                    <span class="truncate text-xs text-surface-700 dark:text-surface-200">{{ sub.title }}</span>
                                </div>
                                <div class="ml-2 flex shrink-0 items-center gap-1">
                                    <Tag v-if="sub.status" :value="sub.status?.name" :severity="sub.status?.severity" class="!text-[10px]" />
                                    <button
                                        v-if="canAct"
                                        class="rounded p-0.5 text-surface-400 hover:text-amber-500"
                                        @click="
                                            emit('edit', sub, sub.parent_id);
                                            visible = false;
                                        "
                                    >
                                        <i class="pi pi-pencil text-[10px]" />
                                    </button>
                                    <Link :href="route('task.show', sub.id)">
                                        <button class="rounded p-0.5 text-surface-400 hover:text-surface-700">
                                            <i class="pi pi-external-link text-[10px]" />
                                        </button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <p v-else class="mt-1 text-xs text-surface-400">No subtasks yet.</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between border-t border-surface-100 px-4 py-3 dark:border-surface-700">
                    <Button
                        v-if="canAct"
                        label="Edit Task"
                        icon="pi pi-pencil"
                        size="small"
                        severity="secondary"
                        outlined
                        @click="
                            emit('edit', task, task.parent_id);
                            visible = false;
                        "
                    />
                    <Button
                        v-if="canAct"
                        label="Delete"
                        icon="pi pi-trash"
                        size="small"
                        severity="danger"
                        text
                        @click="
                            emit('delete', task);
                            visible = false;
                        "
                    />
                </div>
            </div>
        </template>
    </Drawer>
</template>
