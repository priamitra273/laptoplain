<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Highcharts from 'highcharts';
import A11yInit from 'highcharts/modules/accessibility';
import exportingInit from 'highcharts/modules/exporting';
import { Chart } from 'highcharts-vue';
import { computed } from 'vue';
import type { BurndownPoint } from './types';
import { useReportResource } from './useReportResource';

A11yInit(Highcharts);
exportingInit(Highcharts);

interface Props {
    projectId: string;
    sprintId: string;
}

const props = defineProps<Props>();

const {
    data: burndown,
    loading,
    error,
} = useReportResource<BurndownPoint[]>(() => route('sprints.burndown', { project: props.projectId, projectSprint: props.sprintId }), []);

const options = computed<Highcharts.Options>(() => ({
    chart: { type: 'line', backgroundColor: 'transparent' },
    title: { text: 'Burndown Chart', align: 'center', style: { fontSize: '16px', fontWeight: '600' } },
    credits: { enabled: false },
    xAxis: {
        type: 'datetime',
        labels: { format: '{value:%d %b}' },
        title: { text: 'Date' },
        categories: burndown.value.map((point) => point.date),
    },
    series: [
        { type: 'line', name: 'Planned', data: burndown.value.map((point) => point.total_plan), color: '#64748b' },
        { type: 'line', name: 'Actual', data: burndown.value.map((point) => point.total_actual), color: '#ef4444' },
    ],
    legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom', itemStyle: { fontWeight: '500' } },
}));
</script>

<template>
    <div v-if="loading" class="flex h-90 items-center justify-center">
        <UIcon name="i-lucide-loader-2" class="size-6 animate-spin text-muted" />
    </div>
    <p v-else-if="error" class="py-16 text-center text-sm text-error">{{ error }}</p>
    <EmptyState v-else-if="!burndown.length" icon="i-lucide-chart-line" title="No burndown data for this sprint" />
    <Chart v-else :options="options" class="mx-auto h-[500px] w-3/4" />
</template>
