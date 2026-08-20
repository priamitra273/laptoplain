<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import Highcharts from 'highcharts';
import GanttModule from 'highcharts/modules/gantt';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type { TimelineTask } from './types';

GanttModule(Highcharts);

interface Props {
    tasks: TimelineTask[];
}

const props = defineProps<Props>();

const { appearance } = useAppearance();

const MAX_TITLE_LENGTH = 28;

const truncateTitle = (title: string) => (title.length > MAX_TITLE_LENGTH ? title.slice(0, MAX_TITLE_LENGTH).trimEnd() + '…' : title);

const chartRef = ref<HTMLDivElement | null>(null);
let chartInstance: Highcharts.Chart | null = null;

interface GanttPoint {
    id: string;
    name: string;
    fullName: string;
    parent?: string;
    start?: number;
    end?: number;
    completed?: { amount: number };
}

const transformTasks = (tasks: TimelineTask[], parentId: string | null = null): GanttPoint[] => {
    let result: GanttPoint[] = [];

    for (const task of tasks) {
        const fullTitle = task.title || 'Untitled Task';
        const point: GanttPoint = { id: task.id, name: truncateTitle(fullTitle), fullName: fullTitle };

        if (parentId) point.parent = parentId;
        if (task.start_date) point.start = new Date(task.start_date).getTime();
        if (task.due_date) {
            const end = new Date(task.due_date);
            end.setDate(end.getDate() + 1);
            point.end = end.getTime();
        }
        if (task.progress !== null && task.progress !== undefined) {
            point.completed = { amount: Math.max(0, Math.min(100, task.progress)) / 100 };
        }

        result.push(point);

        if (task.sub_task_recursive.length) {
            result = result.concat(transformTasks(task.sub_task_recursive, task.id));
        }
    }

    return result;
};

const ganttData = computed(() => transformTasks(props.tasks));
const hasTasks = computed(() => props.tasks.length > 0);

const renderChart = () => {
    if (!chartRef.value) return;

    chartInstance?.destroy();
    chartInstance = null;

    if (!hasTasks.value) return;

    const isDark = appearance.value === 'dark';
    const textColor = isDark ? '#e5e7eb' : '#1f2937';
    const gridColor = isDark ? '#374151' : '#e5e7eb';

    chartInstance = Highcharts.ganttChart(chartRef.value, {
        chart: { backgroundColor: 'transparent', style: { color: textColor } },
        title: { text: undefined },
        xAxis: {
            currentDateIndicator: true,
            type: 'datetime',
            tickInterval: 24 * 3600 * 1000,
            gridLineColor: gridColor,
            lineColor: gridColor,
            dateTimeLabelFormats: { day: '%A' },
            labels: {
                useHTML: true,
                style: {
                    color: textColor,
                    writingMode: 'vertical-lr',
                    textOrientation: 'mixed',
                    whiteSpace: 'nowrap',
                },
            },
        },
        yAxis: {
            type: 'treegrid',
            gridLineColor: gridColor,
            lineColor: gridColor,
            labels: {
                style: { color: textColor, textOverflow: 'ellipsis', overflow: 'hidden', whiteSpace: 'nowrap' },
            },
        },
        navigator: { enabled: true },
        scrollbar: { enabled: true },
        rangeSelector: { enabled: true, selected: 0, inputEnabled: false },
        tooltip: {
            useHTML: true,
            pointFormatter(this: any) {
                const displayName = this.fullName || this.name;
                let tooltip = `<b style="display:block;max-width:260px;white-space:normal;word-break:break-word;">${displayName}</b>`;

                if (this.start) tooltip += `<br/>Start: ${new Date(this.start).toISOString().split('T')[0]}`;
                if (this.end) {
                    const end = new Date(this.end);
                    end.setDate(end.getDate() - 1);
                    tooltip += `<br/>End: ${end.toISOString().split('T')[0]}`;
                }
                if (this.completed?.amount !== undefined) tooltip += `<br/>Progress: ${(this.completed.amount * 100).toFixed(0)}%`;

                return tooltip;
            },
        },
        credits: { enabled: false },
        series: [{ type: 'gantt', name: 'Tasks', data: ganttData.value }],
    });
};

onMounted(renderChart);

onUnmounted(() => {
    chartInstance?.destroy();
    chartInstance = null;
});

watch(() => props.tasks, renderChart, { deep: true });
watch(appearance, renderChart);
</script>

<template>
    <div v-if="hasTasks" ref="chartRef" style="width: 100%; min-height: 600px" />
    <div v-else class="flex h-96 flex-col items-center justify-center gap-3 text-center">
        <UIcon name="i-lucide-calendar-range" class="size-10 text-muted" />
        <p class="text-sm text-muted">No tasks to display on the timeline.</p>
    </div>
</template>
