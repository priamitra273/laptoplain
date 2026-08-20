<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import ReportTaskList from './ReportTaskList.vue';
import type { SprintStatusReport } from './types';

interface Props {
    projectId: string;
    sprintId: string;
}

const props = defineProps<Props>();

const loading = ref(false);
const report = ref<SprintStatusReport>({ completed_tasks: [], incomplete_tasks: [] });

const fetchReport = async () => {
    loading.value = true;
    try {
        const response = await fetch(route('sprints.status-report', { project: props.projectId, projectSprint: props.sprintId }), {
            headers: { Accept: 'application/json' },
        });
        const body = await response.json();
        report.value = body.data ?? { completed_tasks: [], incomplete_tasks: [] };
    } finally {
        loading.value = false;
    }
};

watch(() => props.sprintId, fetchReport);
onMounted(fetchReport);
</script>

<template>
    <div class="flex flex-col gap-6">
        <ReportTaskList
            title="Completed Tasks"
            :tasks="report.completed_tasks"
            :loading="loading"
            empty-message="No completed tasks in this sprint."
        />

        <ReportTaskList
            title="Incomplete Tasks"
            description="Tasks moved out when this sprint was closed without being finished."
            :tasks="report.incomplete_tasks"
            :loading="loading"
            empty-message="No incomplete tasks for this sprint."
        />
    </div>
</template>
