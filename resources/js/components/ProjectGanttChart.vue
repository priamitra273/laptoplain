<script setup lang="ts">
import { applyHighchartsTheme, watchThemeChanges } from '@/useHighchartsTheme';
import Highcharts from 'highcharts';
import GanttModule from 'highcharts/modules/gantt';
import { computed, onMounted, ref, watch } from 'vue';

GanttModule(Highcharts);

interface TaskNode {
    id: string | number;
    title: string;
    start_date?: string;
    due_date?: string;
    progress?: number;
    dependency_id?: string | number;
    sub_task_recursive?: TaskNode[];
}

const props = defineProps<{
    tasks: TaskNode[];
}>();

const chartRef = ref<HTMLDivElement | null>(null);

/**
 * Recursive transformer ke format Highcharts Gantt (TreeGrid)
 */
const transformTasks = (tasks: TaskNode[], parentId: string | null = null): any[] => {
    let result: any[] = [];

    tasks.forEach((t) => {
        const start = t.start_date ? Date.parse(t.start_date) : undefined;
        const end = t.due_date ? Date.parse(t.due_date) : undefined;

        const node: any = {
            id: String(t.id),
            name: t.title,
            parent: parentId ?? undefined,
        };

        // Milestone: start == end
        if (start && end && start === end) {
            node.milestone = true;
            node.start = start;
        } else {
            if (start) node.start = start;
            if (end) node.end = end;
        }

        // Progress
        if (t.progress !== undefined) {
            node.completed = { amount: t.progress / 100 };
        }

        // Dependency
        if (t.dependency_id) {
            node.dependency = String(t.dependency_id);
        }

        result.push(node);

        // Recursive children
        if (t.sub_task_recursive?.length) {
            result = result.concat(transformTasks(t.sub_task_recursive, String(t.id)));
        }
    });

    return result;
};

const ganttData = computed(() => transformTasks(props.tasks));

const renderChart = () => {
    if (!chartRef.value) return;

    Highcharts.ganttChart(chartRef.value, {
        title: { text: 'Project Timeline' },
        xAxis: { type: 'datetime' },
        yAxis: {
            type: 'treegrid',
            uniqueNames: true,
            grid: { enabled: true },
        },
        series: [
            {
                name: 'Tasks',
                data: ganttData.value,
            },
        ],
    });
};

onMounted(() => {
    applyHighchartsTheme();
    renderChart();
    watchThemeChanges(() => {
        applyHighchartsTheme();
        renderChart();
    });
});

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
