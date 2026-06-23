<script setup lang="ts">
import { applyHighchartsTheme, watchThemeChanges } from '@/useHighchartsTheme';
import Highcharts from 'highcharts';
import GanttModule from 'highcharts/modules/gantt';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

GanttModule(Highcharts);

interface TaskNode {
    id: string | number;
    title: string;
    start_date?: string | null;
    due_date?: string | null;
    progress?: number;
    dependency_id?: string | number;
    sub_task_recursive?: TaskNode[];
}

const props = defineProps<{
    tasks: TaskNode[];
}>();

const MAX_TITLE_LENGTH = 28;

const truncateTitle = (title: string, maxLength = MAX_TITLE_LENGTH): string => {
    if (!title) return 'Untitled Task';
    return title.length > maxLength ? title.slice(0, maxLength).trimEnd() + '…' : title;
};

const chartRef = ref<HTMLDivElement | null>(null);
const chartInstance = ref<Highcharts.Chart | null>(null);

const transformTasks = (tasks: TaskNode[], parentId: string | null = null): any[] => {
    if (!tasks || tasks.length === 0) return [];

    let result: any[] = [];

    tasks.forEach((t) => {
        const fullTitle = t.title || 'Untitled Task';
        const node: any = {
            id: String(t.id),
            name: truncateTitle(fullTitle),
            fullName: fullTitle, // simpan versi asli untuk tooltip
        };

        if (parentId) {
            node.parent = parentId;
        }

        if (t.start_date) {
            node.start = new Date(t.start_date).getTime();
        }
        if (t.due_date) {
            const endDate = new Date(t.due_date);
            endDate.setDate(endDate.getDate() + 1);
            node.end = endDate.getTime();
        }

        if (t.progress !== undefined && t.progress !== null) {
            const progressValue = Math.max(0, Math.min(100, t.progress)) / 100;
            node.completed = { amount: progressValue };
        }

        if (t.dependency_id) {
            node.dependency = String(t.dependency_id);
        }

        result.push(node);

        if (t.sub_task_recursive && t.sub_task_recursive.length > 0) {
            result = result.concat(transformTasks(t.sub_task_recursive, String(t.id)));
        }
    });

    return result;
};

const ganttData = computed(() => transformTasks(props.tasks));

const hasTasks = computed(() => props.tasks && props.tasks.length > 0);

const renderChart = () => {
    if (!chartRef.value) return;

    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }

    if (!hasTasks.value) return;

    try {
        chartInstance.value = Highcharts.ganttChart(chartRef.value, {
            title: {
                text: 'Project Timeline',
            },
            xAxis: {
                currentDateIndicator: true,
                type: 'datetime',
            },
            yAxis: {
                type: 'treegrid',
                labels: {
                    // Pastikan lebar kolom label cukup agar ellipsis tidak terpotong paksa
                    style: {
                        textOverflow: 'ellipsis',
                        overflow: 'hidden',
                        whiteSpace: 'nowrap',
                    },
                },
            },
            navigator: {
                enabled: true,
            },
            scrollbar: {
                enabled: true,
            },
            rangeSelector: {
                enabled: true,
                selected: 0,
            },
            tooltip: {
                useHTML: true,
                pointFormatter: function () {
                    const point: any = this;
                    // Gunakan fullName untuk tooltip agar judul lengkap tetap tampil
                    const displayName = point.fullName || point.name;
                    let tooltip = `<b style="display:block;max-width:260px;white-space:normal;word-break:break-word;">${displayName}</b>`;

                    if (point.start) {
                        const startDate = new Date(point.start);
                        tooltip += `<br/>Start: ${startDate.toISOString().split('T')[0]}`;
                    }

                    if (point.end) {
                        const endDate = new Date(point.end);
                        endDate.setDate(endDate.getDate() - 1);
                        tooltip += `<br/>End: ${endDate.toISOString().split('T')[0]}`;
                    }

                    if (point.completed && point.completed.amount !== undefined) {
                        tooltip += `<br/>Progress: ${(point.completed.amount * 100).toFixed(0)}%`;
                    }

                    return tooltip;
                },
            },
            credits: {
                enabled: false,
            },
            series: [
                {
                    type: 'gantt',
                    name: 'Tasks',
                    data: ganttData.value,
                },
            ],
        });
    } catch (error) {}
};

let themeCleanup: (() => void) | null = null;

onMounted(() => {
    applyHighchartsTheme();
    setTimeout(() => renderChart(), 100);
    themeCleanup = watchThemeChanges(() => {
        applyHighchartsTheme();
        renderChart();
    });
});

onUnmounted(() => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
    if (themeCleanup) themeCleanup();
});

watch(
    () => props.tasks,
    () => renderChart(),
    { deep: true },
);
</script>

<template>
    <div v-if="hasTasks" ref="chartRef" style="width: 100%; min-height: 600px" />
    <div v-else class="no-tasks-message">
        <p>Tidak ada task untuk ditampilkan</p>
    </div>
</template>

<style scoped>
.no-tasks-message {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 400px;
    color: #999;
    font-size: 14px;
}
</style>
