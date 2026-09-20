import { computed, ref } from 'vue';
import type { PrimeSeverity } from '@/types';
import type { TimelineTask } from './types';
import { computeDayColumns, computeMonthGroups, computeQuarterGroups, computeTaskDateRange, computeWeekGroups } from './ganttDate';

export interface VisibleTask {
    id: string;
    title: string;
    depth: number;
    hasChildren: boolean;
    start_date: string | null;
    due_date: string | null;
    severity: PrimeSeverity | null;
    progress: number;
}

type ZoomLevel = 'day' | 'week' | 'month';

interface ZoomLevelOption {
    value: ZoomLevel;
    label: string;
}

const ZOOM_LEVELS: ZoomLevelOption[] = [
    { value: 'day', label: 'Days' },
    { value: 'week', label: 'Weeks' },
    { value: 'month', label: 'Months' },
];

// Lebar piksel per hari beda-beda tergantung level zoom — logika tanggal
// (dayColumns, bar, garis hari-ini) tetap sama persis, cuma skalanya beda.
const ZOOM_PX_PER_DAY: Record<ZoomLevel, number> = {
    day: 96,
    week: 16,
    month: 4,
};

export function useGantt(tasks: () => TimelineTask[]) {
    const expandedIds = ref(new Set<string>());

    const toggleExpand = (id: string) => {
        if (expandedIds.value.has(id)) {
            expandedIds.value.delete(id);
        } else {
            expandedIds.value.add(id);
        }
        // Ganti reference biar computed yang pakai expandedIds ke-trigger re-evaluate.
        expandedIds.value = new Set(expandedIds.value);
    };

    const visibleTasks = computed<VisibleTask[]>(() => {
        const result: VisibleTask[] = [];

        const walk = (list: TimelineTask[], depth: number) => {
            for (const task of list) {
                const hasChildren = task.sub_task_recursive.length > 0;
                result.push({
                    id: task.id,
                    title: task.title,
                    depth,
                    hasChildren,
                    start_date: task.start_date,
                    due_date: task.due_date,
                    severity: task.status?.severity ?? null,
                    progress: Math.max(0, Math.min(100, task.progress ?? 0)),
                });

                if (hasChildren && expandedIds.value.has(task.id)) {
                    walk(task.sub_task_recursive, depth + 1);
                }
            }
        };

        walk(tasks(), 0);

        return result;
    });

    const zoomIndex = ref(ZOOM_LEVELS.findIndex((level) => level.value === 'day'));
    const currentZoom = computed(() => ZOOM_LEVELS[zoomIndex.value]);

    const setZoom = (index: number) => {
        zoomIndex.value = index;
    };

    const zoomIn = () => {
        if (zoomIndex.value > 0) zoomIndex.value--;
    };

    const zoomOut = () => {
        if (zoomIndex.value < ZOOM_LEVELS.length - 1) zoomIndex.value++;
    };

    const dayWidth = computed(() => ZOOM_PX_PER_DAY[currentZoom.value.value]);

    // Aturan rentang tanggal (padding 7 hari, ambang "tarik hari ini" 90 hari)
    // ada di computeTaskDateRange.
    const taskDateRange = computed(() => computeTaskDateRange(tasks(), new Date()));

    const dayColumns = computed(() => computeDayColumns(taskDateRange.value.min, taskDateRange.value.max, new Date()));

    const monthGroups = computed(() => computeMonthGroups(dayColumns.value, new Date()));

    const quarterGroups = computed(() => computeQuarterGroups(dayColumns.value, new Date()));

    const weekGroups = computed(() => computeWeekGroups(dayColumns.value, new Date()));

    const todayIndex = computed(() => dayColumns.value.findIndex((day) => day.isToday));

    return {
        expandedIds,
        toggleExpand,
        visibleTasks,
        zoomLevels: ZOOM_LEVELS,
        zoomIndex,
        currentZoom,
        setZoom,
        zoomIn,
        zoomOut,
        dayWidth,
        dayColumns,
        monthGroups,
        quarterGroups,
        weekGroups,
        todayIndex,
    };
}
