<script setup lang="ts">
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import type { Task } from '../../index.d.ts';

interface Props {
    task: Task;
}

const props = defineProps<Props>();
</script>

<template>
    <Card class="rounded-2xl border-0 shadow-lg transition-shadow hover:shadow-xl">
        <template #title>
            <div class="flex items-center gap-2">
                <i class="pi pi-align-left text-blue-500"></i>
                <h2 class="text-lg font-bold">Description</h2>
            </div>
        </template>
        <template #content>
            <Divider class="my-3" />
            <div
                class="prose prose-sm max-h-60 overflow-auto break-words text-gray-700 dark:text-gray-300"
                v-html="props.task.description || '<p class=\'text-gray-400 italic\'>No description provided</p>'"
            />
        </template>
    </Card>
</template>
