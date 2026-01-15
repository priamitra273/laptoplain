<script setup lang="ts">
import { applyHighchartsTheme, watchThemeChanges } from '@/useHighchartsTheme';
import Highcharts from 'highcharts';
import GanttModule from 'highcharts/modules/gantt';
import { onMounted, ref, watch } from 'vue';

GanttModule(Highcharts);

const props = defineProps<{
    tasks: Array<{
        id: string | number;
        title: string;
        start_date: string;
        due_date: string;
        progress?: number;
    }>;
}>();

const chartRef = ref<HTMLDivElement | null>(null);

// transform data ke bentuk Highcharts
const transformTasks = (tasks: any[]) =>
    tasks.map((t) => ({
        id: t.id,
        name: t.title,
        start: Date.parse(t.start_date),
        end: Date.parse(t.due_date),
        completed: t.progress !== undefined ? { amount: t.progress / 100 } : undefined,
    }));

const renderChart = () => {
    if (!chartRef.value) return;

    Highcharts.ganttChart(chartRef.value, {
        title: { text: 'Project Timeline' },
        series: [
            {
                name: 'Tasks',
                data: transformTasks(props.tasks),
            },
        ],
    });
};

onMounted(() => {
    // apply theme init
    applyHighchartsTheme();
    renderChart();

    // re-apply saat theme berubah
    watchThemeChanges(() => {
        applyHighchartsTheme();
        renderChart();
    });
});

// re-render saat data tasks berubah
watch(
    () => props.tasks,
    () => {
        applyHighchartsTheme();
        renderChart();
    },
    { deep: true },
);
</script>

<template>
    <div ref="chartRef" style="width: 100%; height: 400px"></div>
</template>
