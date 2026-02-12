<script setup lang="ts">
import { applyHighchartsTheme, watchThemeChanges } from '@/useHighchartsTheme';
import Highcharts from 'highcharts';
import GanttModule from 'highcharts/modules/gantt';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
const chartInstance = ref<Highcharts.Chart | null>(null);

/**
 * Recursive transformer ke format Highcharts Gantt
 */
const transformTasks = (tasks: TaskNode[], parentId: string | null = null): any[] => {
    if (!tasks || tasks.length === 0) return [];

    let result: any[] = [];

    tasks.forEach((t) => {
        const node: any = {
            id: String(t.id),
            name: t.title || 'Untitled Task',
        };

        // Set parent jika ada
        if (parentId) {
            node.parent = parentId;
        }

        // Set start and end dates - parse ke timestamp
        if (t.start_date) {
            node.start = new Date(t.start_date).getTime();
        }
        if (t.due_date) {
            // Tambahkan 1 hari ke end date agar inklusif
            const endDate = new Date(t.due_date);
            endDate.setDate(endDate.getDate() + 1);
            node.end = endDate.getTime();
        }

        // Check if milestone (start date == due date di input asli)
        if (t.start_date && t.due_date && t.start_date === t.due_date) {
            node.milestone = true;
            // Untuk milestone, gunakan start date saja
            node.start = new Date(t.start_date).getTime();
            delete node.end;
        }

        // Progress
        if (t.progress !== undefined && t.progress !== null) {
            const progressValue = Math.max(0, Math.min(100, t.progress)) / 100;
            node.completed = {
                amount: progressValue,
            };
        }

        // Dependency
        if (t.dependency_id) {
            node.dependency = String(t.dependency_id);
        }

        result.push(node);

        // Recursive children
        if (t.sub_task_recursive && t.sub_task_recursive.length > 0) {
            result = result.concat(transformTasks(t.sub_task_recursive, String(t.id)));
        }
    });

    return result;
};

const ganttData = computed(() => {
    const data = transformTasks(props.tasks);
    return data;
});

const hasTasks = computed(() => props.tasks && props.tasks.length > 0);

const renderChart = () => {
    if (!chartRef.value) {
        return;
    }

    // Destroy previous chart instance if exists
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }

    // Don't render chart if no tasks
    if (!hasTasks.value) {
        return;
    }

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
                // uniqueNames: true,
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
                pointFormatter: function () {
                    const point: any = this;
                    let tooltip = `<b>${point.name}</b><br/>`;

                    if (point.start) {
                        const startDate = new Date(point.start);
                        tooltip += `Start: ${startDate.toISOString().split('T')[0]}<br/>`;
                    }

                    if (point.end && !point.milestone) {
                        // Kurangi 1 hari untuk menampilkan tanggal due date asli
                        const endDate = new Date(point.end);
                        endDate.setDate(endDate.getDate() - 1);
                        tooltip += `End: ${endDate.toISOString().split('T')[0]}<br/>`;
                    }

                    if (point.completed && point.completed.amount !== undefined) {
                        tooltip += `Progress: ${(point.completed.amount * 100).toFixed(0)}%`;
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

    // Delay rendering untuk memastikan DOM ready
    setTimeout(() => {
        renderChart();
    }, 100);

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
    if (themeCleanup) {
        themeCleanup();
    }
});

watch(
    () => props.tasks,
    (newTasks) => {
        renderChart();
    },
    { deep: true },
);
</script>

<template>
    <div v-if="hasTasks" ref="chartRef" style="width: 100%; min-height: 600px"></div>
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
