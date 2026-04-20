<script setup lang="ts">
import TaskPriorityIcon from '@/components/TaskPriorityIcon.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { Link } from '@inertiajs/vue3';
import moment from 'moment';
import type { Task } from '../../..';

const props = defineProps<{
    task: Task;
    dragging: boolean;
    isOverdue: boolean;
    subtaskCount: number;
    doneSubtaskCount: number;
    canAct: boolean;
}>();

const emit = defineEmits<{
    detail: [task: Task];
    menu: [e: MouseEvent, task: Task];
    add: [taskId: string];
    edit: [task: Task, parentId: string | null];
    delete: [task: Task];
}>();
</script>

<template>
    <div
        :data-task-id="task.id"
        class="group relative cursor-grab rounded-lg border bg-white shadow-sm transition-all duration-150 active:cursor-grabbing active:shadow-lg dark:bg-surface-800"
        :class="[
            isOverdue ? 'border-l-[3px] border-surface-200 border-l-rose-400 dark:border-surface-700' : 'border-surface-200 dark:border-surface-700',
            dragging ? 'opacity-50' : 'hover:border-blue-300 hover:shadow-md dark:hover:border-blue-600',
        ]"
        @click="emit('detail', task)"
        @contextmenu="emit('menu', $event, task)"
    >
        <div class="p-2.5">
            <!-- Row 1: type + priority + overdue + kebab -->
            <div class="mb-1.5 flex items-center gap-1">
                <span
                    v-if="task.type"
                    class="inline-flex items-center rounded bg-surface-100 px-1.5 py-0.5 text-[10px] font-semibold text-surface-600 dark:bg-surface-700 dark:text-surface-300"
                >
                    {{ task.type.name }}
                </span>
                <TaskPriorityIcon v-if="task.priority" :priority="task.priority" />
                <span v-if="isOverdue" class="ml-auto flex items-center gap-0.5 text-[10px] font-semibold text-rose-500">
                    <i class="pi pi-clock text-[10px]" /> Overdue
                </span>
                <button
                    class="ml-auto hidden rounded p-0.5 text-surface-400 hover:bg-surface-100 hover:text-surface-700 group-hover:block dark:hover:bg-surface-700"
                    @click.stop="emit('menu', $event, task)"
                >
                    <i class="pi pi-ellipsis-h text-xs" />
                </button>
            </div>

            <!-- Title -->
            <p class="mb-2 line-clamp-3 text-[13px] font-medium leading-snug text-surface-800 dark:text-surface-100">
                {{ task.title }}
            </p>

            <!-- Due date -->
            <div v-if="task.due_date" class="mb-2 flex items-center gap-1 text-[11px]" :class="isOverdue ? 'text-rose-500' : 'text-surface-700'">
                <i class="pi pi-calendar text-[10px]" />
                {{ moment(task.due_date).format('DD MMM') }}
                <span class="text-surface-500 dark:text-surface-300">· {{ moment(task.due_date).fromNow() }}</span>
            </div>

            <!-- Subtask progress -->
            <div v-if="subtaskCount > 0" class="mb-2">
                <div class="mb-0.5 flex items-center justify-between text-[10px] text-surface-400">
                    <span>Subtasks</span>
                    <span>{{ doneSubtaskCount }}/{{ subtaskCount }}</span>
                </div>
                <div class="h-1 w-full overflow-hidden rounded-full bg-surface-100 dark:bg-surface-700">
                    <div
                        class="h-full rounded-full bg-emerald-400 transition-all"
                        :style="`width:${Math.round((doneSubtaskCount / subtaskCount) * 100)}%`"
                    />
                </div>
            </div>

            <!-- Bottom: subtask chip + avatars -->
            <div class="flex items-center justify-between gap-1">
                <button
                    v-if="subtaskCount > 0"
                    class="flex items-center gap-0.5 rounded px-1 py-0.5 text-[10px] text-surface-500 hover:bg-surface-100 dark:hover:bg-surface-700"
                    @click.stop="emit('detail', task)"
                >
                    <i class="pi pi-sitemap text-[10px]" /> {{ subtaskCount }}
                </button>
                <div v-else class="flex-1" />
                <div class="flex -space-x-1.5">
                    <UserAvatar
                        v-for="u in (task.users || []).slice(0, 3)"
                        :key="u.id"
                        :user="u"
                        size="!h-5 !w-5 border border-white dark:border-surface-800"
                        fontSize=".6rem"
                    />
                    <span
                        v-if="(task.users || []).length > 3"
                        class="flex h-5 w-5 items-center justify-center rounded-full border border-white bg-surface-200 text-[9px] font-bold text-surface-600 dark:border-surface-800 dark:bg-surface-700"
                    >
                        +{{ (task.users || []).length - 3 }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Hover action strip -->
        <div
            class="hidden items-center justify-end gap-0.5 rounded-b-lg border-t border-surface-100 bg-surface-50 px-2 py-1 group-hover:flex dark:border-surface-700 dark:bg-surface-800/60"
        >
            <Link :href="route('task.show', task.id)" @click.stop>
                <button class="rounded p-1 text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700" title="Open">
                    <i class="pi pi-eye text-[11px]" />
                </button>
            </Link>
            <button
                v-if="canAct"
                class="rounded p-1 text-surface-400 hover:bg-surface-200 hover:text-surface-700 dark:hover:bg-surface-700"
                title="Add subtask"
                @click.stop="emit('add', task.id)"
            >
                <i class="pi pi-sitemap text-[11px]" />
            </button>
            <button
                v-if="canAct"
                class="rounded p-1 text-surface-400 hover:bg-amber-100 hover:text-amber-600 dark:hover:bg-amber-900/30"
                title="Edit"
                @click.stop="emit('edit', task, task.parent_id)"
            >
                <i class="pi pi-pencil text-[11px]" />
            </button>
            <button
                v-if="canAct"
                class="rounded p-1 text-surface-400 hover:bg-rose-100 hover:text-rose-500 dark:hover:bg-rose-900/30"
                title="Delete"
                @click.stop="emit('delete', task)"
            >
                <i class="pi pi-trash text-[11px]" />
            </button>
        </div>
    </div>
</template>
