<script setup lang="ts">
import EmptyState from '@/components/common/EmptyState.vue';
import { startCase } from 'lodash';
import { computed } from 'vue';
import ReportTaskList from './ReportTaskList.vue';
import type { SprintStatusReport } from './types';
import { useReportResource } from './useReportResource';

interface Props {
    projectId: string;
    sprintId: string;
}

const props = defineProps<Props>();

const {
    data: report,
    loading,
    error,
} = useReportResource<SprintStatusReport>(() => route('sprints.status-report', { project: props.projectId, projectSprint: props.sprintId }), {});

const hasSections = computed(() => Object.keys(report.value).length > 0);
</script>

<template>
    <div v-if="loading" class="flex h-40 items-center justify-center">
        <UIcon name="i-lucide-loader-2" class="size-6 animate-spin text-muted" />
    </div>
    <p v-else-if="error" class="py-16 text-center text-sm text-error">{{ error }}</p>
    <EmptyState v-else-if="!hasSections" icon="i-lucide-clipboard-list" title="No task report for this sprint" />
    <div v-else class="flex flex-col gap-6">
        <ReportTaskList
            v-for="(tasks, key) in report"
            :key="key"
            :title="startCase(key)"
            :tasks="tasks"
            :empty-message="`No ${startCase(key).toLowerCase()}.`"
        />
    </div>
</template>
