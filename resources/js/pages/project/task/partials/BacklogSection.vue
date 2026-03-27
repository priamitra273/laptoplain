<script setup lang="ts">
import Button from 'primevue/button';
import { ref } from 'vue';
import type { Task } from '..';
import TaskRow from './TaskRow.vue';

defineProps<{ tasks: Task[]; canAct: boolean }>();
const emit = defineEmits<{
    edit: [task: Task];
    taskMenu: [event: MouseEvent, task: Task];
    addIssue: [];
}>();

const collapsed = ref(false);
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-surface-200 dark:border-surface-700">
        <!-- Header -->
        <div class="flex cursor-pointer select-none items-center gap-2 bg-surface-50 px-3 py-2 dark:bg-surface-800" @click="collapsed = !collapsed">
            <i :class="collapsed ? 'pi pi-chevron-right' : 'pi pi-chevron-down'" class="text-xs text-surface-500" />
            <span class="flex-1 text-sm font-semibold">Backlog</span>
            <span class="text-xs text-surface-500">{{ tasks.length }} issues</span>
            <Button v-if="canAct" label="Create Issue" icon="pi pi-plus" size="small" text class="!py-1 text-xs" @click.stop="emit('addIssue')" />
        </div>

        <!-- Body -->
        <div v-if="!collapsed">
            <div v-if="!tasks.length" class="flex flex-col items-center justify-center gap-2 py-8 text-surface-400">
                <i class="pi pi-inbox text-2xl" />
                <span class="text-sm">Backlog is empty</span>
            </div>

            <TaskRow v-for="task in tasks" :key="task.id" :task="task" @edit="emit('edit', $event)" @menu="emit('taskMenu', $event, $event)" />

            <div
                v-if="canAct"
                class="flex cursor-pointer items-center gap-2 border-t border-surface-100 px-4 py-2 text-surface-400 hover:bg-surface-50 hover:text-primary-500 dark:border-surface-700 dark:hover:bg-surface-800/50"
                @click="emit('addIssue')"
            >
                <i class="pi pi-plus text-xs" />
                <span class="text-sm">Add issue</span>
            </div>
        </div>
    </div>
</template>
