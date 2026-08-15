<script setup lang="ts">
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import { inject } from 'vue';
import type { BacklogTask } from '@/pages/project-lazy';
import TaskCategoryIcon from './TaskCategoryIcon.vue';
import TaskEpicPicker from './TaskEpicPicker.vue';
import TaskMembers from './TaskMembers.vue';
import TaskPriorityPicker from './TaskPriorityPicker.vue';
import { BacklogKey } from './types';

interface Props {
    task: BacklogTask;
    draggable?: boolean;
    showChecklist?: boolean;
    selected?: boolean;
}

const context = inject(BacklogKey);

const props = defineProps<Props>();

const isEpic = (task: BacklogTask) => task.category?.name?.toLowerCase() === 'epic';
</script>

<template>
    <div
        class="group flex items-center gap-2 border-t border-surface-100 px-3 py-2 hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-800/50"
    >
        <i
            v-if="draggable"
            class="drag-handle pi pi-bars flex-shrink-0 cursor-grab text-xs text-surface-300 opacity-0 active:cursor-grabbing group-hover:opacity-100 dark:text-surface-600"
            @click.stop
        ></i>

        <Checkbox
            v-if="showChecklist"
            :modelValue="!!selected"
            binary
            class="flex-shrink-0"
            @update:modelValue="context?.toggleSelect(task, !!$event)"
            @click.stop
        />

        <TaskCategoryIcon :category="task.category" />

        <span class="min-w-0 flex-1 truncate text-sm text-surface-800 dark:text-surface-100" :title="task.title" @click="context?.editTask(task)">
            {{ task.title }}
        </span>

        <span
            v-if="task.story_points != null"
            class="hidden h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-surface-100 text-xs font-semibold text-surface-600 sm:inline-flex dark:bg-surface-700 dark:text-surface-300"
            title="Story points"
        >
            {{ task.story_points }}
        </span>

        <div class="ml-auto flex min-w-0 shrink-0 items-center gap-1 sm:gap-2">
            <!-- Epic -->
            <TaskEpicPicker v-if="!isEpic(task)" :task="task" />
            <div v-else class="flex min-w-[6.5rem] max-w-[9rem] flex-1 shrink-0 items-center justify-start gap-0.5 sm:min-w-[7rem]"></div>

            <!-- Priority -->
            <TaskPriorityPicker :task="task" />

            <!-- Status -->
            <Tag
                v-if="task.status"
                :value="task.status.name"
                :severity="task.status.severity ?? undefined"
                class="w-24 min-w-[5.5rem] shrink-0 justify-center truncate text-xs"
            />
            <span v-else class="w-24 min-w-[5.5rem] shrink-0"></span>

            <!-- Members -->
            <TaskMembers :users="task.users ?? []" />
        </div>

        <Button
            v-if="isEpic(task)"
            icon="pi pi-plus"
            size="small"
            text
            rounded
            severity="secondary"
            class="flex-shrink-0 opacity-0 group-hover:opacity-100"
            @click.stop="context?.addTask(task)"
        />

        <Button
            icon="pi pi-ellipsis-v"
            size="small"
            text
            rounded
            severity="secondary"
            class="flex-shrink-0 opacity-0 group-hover:opacity-100"
            @click.stop="context?.openTaskMenu($event, task, null)"
        />
    </div>
</template>
