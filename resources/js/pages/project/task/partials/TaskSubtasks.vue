<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Tag from 'primevue/tag';
import { computed } from 'vue';
import type { Task } from '../../index.d.ts';
import SectionPanel from './SectionPanel.vue';

interface Props {
    task: Task;
}

const props = defineProps<Props>();

const subtasks = computed(() => props.task.sub_task_recursive ?? []);

const goToSubTask = (subTaskId: string) => {
    if (subTaskId) {
        router.visit(route('task.show', subTaskId));
    }
};
</script>

<template>
    <SectionPanel title="Subtasks" icon="pi pi-list-check" body-class="!p-0" toggleable>
        <template #actions>
            <span
                v-if="subtasks.length"
                class="rounded-full bg-surface-100 px-2 py-0.5 text-xs font-medium tabular-nums text-surface-600 dark:bg-surface-800 dark:text-surface-300"
            >
                {{ subtasks.length }}
            </span>
        </template>

        <div v-if="subtasks.length" class="max-h-96 divide-y divide-surface-200 overflow-y-auto overscroll-contain dark:divide-surface-700">
            <button
                v-for="subTask in subtasks"
                :key="subTask.id"
                type="button"
                :aria-label="`${subTask.title} — ${subTask.status?.name ?? 'No status'}`"
                class="group flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-surface-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500 dark:hover:bg-surface-800"
                @click="goToSubTask(subTask.id)"
            >
                <span
                    :title="subTask.title"
                    class="min-w-0 flex-1 truncate text-sm font-medium text-surface-800 transition-colors group-hover:text-primary-600 dark:text-surface-100 dark:group-hover:text-primary-400"
                >
                    {{ subTask.title }}
                </span>
                <Tag v-if="subTask.status?.name" :value="subTask.status.name" :severity="subTask.status.severity" class="shrink-0" />
                <i class="pi pi-chevron-right text-xs text-surface-400 dark:text-surface-500" />
            </button>
        </div>

        <div v-else class="flex flex-col items-center justify-center gap-2 px-4 py-8 text-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-surface-100 dark:bg-surface-800">
                <i class="pi pi-list-check text-lg text-surface-400 dark:text-surface-500" />
            </div>
            <p class="text-sm text-surface-500 dark:text-surface-400">No subtasks yet</p>
        </div>
    </SectionPanel>
</template>
