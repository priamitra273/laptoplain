<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Chip from 'primevue/chip';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';
import type { Task } from '../../index.d.ts';

interface Props {
    task: Task;
}

const props = defineProps<Props>();

const goToSubTask = (subTaskId: string) => {
    if (subTaskId) {
        router.visit(route('task.show', subTaskId));
    }
};
</script>

<template>
    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
        <template #title>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="pi pi-list text-indigo-500"></i>
                    <h2 class="text-lg font-bold">Subtasks</h2>
                </div>
                <Chip
                    v-if="props.task.sub_task_recursive.length"
                    :label="`${props.task.sub_task_recursive.length}`"
                    class="bg-indigo-100 text-indigo-700"
                />
            </div>
        </template>
        <template #content>
            <Divider class="my-3" />
            <div v-if="props.task.sub_task_recursive.length" class="space-y-3">
                <div
                    v-for="subTask in props.task.sub_task_recursive"
                    :key="subTask.id"
                    @click="goToSubTask(subTask.id)"
                    class="group cursor-pointer rounded-xl border-2 border-gray-100 bg-white p-4 transition-all hover:border-indigo-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600"
                >
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <p
                                :title="subTask.title"
                                class="max-w-full truncate break-words text-base font-semibold text-gray-800 transition-colors group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400"
                            >
                                {{ subTask.title }}
                            </p>
                        </div>
                        <Tag :value="subTask.status?.name" :severity="subTask.status?.severity" class="shrink-0" />
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                        <div
                            class="prose prose-sm dark:prose-invert max-h-32 overflow-auto break-words"
                            v-html="subTask.description || '<span class=\'text-gray-400 italic\'>No description</span>'"
                        ></div>
                    </div>
                </div>
            </div>
            <div v-else class="flex flex-col items-center justify-center py-8 text-gray-400">
                <i class="pi pi-inbox mb-3 text-4xl opacity-50"></i>
                <p class="italic">No subtasks available</p>
            </div>
        </template>
    </Card>
</template>
