<script setup lang="ts">
import type { TimelineTask } from './types';
import { computed, ref } from 'vue';
import GanttTaskBar from './GanttTaskBar.vue';
import GanttTaskList from './GanttTaskList.vue';
import { computeBarPosition, formatDayLabel, hasBar } from './ganttDate';
import { useGantt, type VisibleTask } from './useGantt';

interface Props {
    tasks: TimelineTask[];
}

const props = defineProps<Props>();

const {
    expandedIds,
    toggleExpand,
    visibleTasks,
    zoomLevels,
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
} = useGantt(() => props.tasks);

const zoomDropdownItems = computed(() =>
    zoomLevels.map((level, index) => ({
        label: level.label,
        icon: index === zoomIndex.value ? 'i-lucide-check' : undefined,
        onSelect: () => {
            setZoom(index);
        },
    })),
);

const scrollContainer = ref<HTMLDivElement | null>(null);
const scrollLeft = ref(0);

const onScroll = () => {
    scrollLeft.value = scrollContainer.value?.scrollLeft ?? 0;
};

const shiftWindow = (direction: 1 | -1) => {
    scrollContainer.value?.scrollBy({ left: direction * 14 * dayWidth.value, behavior: 'smooth' });
};

const barStyle = (task: VisibleTask) => {
    const windowStart = dayColumns.value[0]?.date ?? new Date();

    return computeBarPosition(task, windowStart, dayWidth.value);
};

const isViewingToday = computed(() => {
    if (!scrollContainer.value || todayIndex.value === -1) return false;

    const todayStart = todayIndex.value * dayWidth.value;
    const todayEnd = todayStart + dayWidth.value;
    const viewStart = scrollLeft.value;
    const viewEnd = scrollLeft.value + scrollContainer.value.clientWidth;

    return todayStart >= viewStart && todayEnd <= viewEnd;
});

const goToToday = () => {
    if (scrollContainer.value && todayIndex.value !== -1) {
        scrollContainer.value.scrollLeft = todayIndex.value * dayWidth.value - scrollContainer.value.clientWidth / 2;
    }
};
</script>

<template>
    <div class="flex items-center justify-end gap-2 px-4 pb-4">
        <UButton v-if="todayIndex !== -1 && !isViewingToday" label="Today" color="neutral" variant="outline" size="sm" @click="goToToday" />

        <div class="inline-flex divide-x divide-default rounded-md border border-default overflow-hidden">
            <UButton icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="sm" class="rounded-none" @click="shiftWindow(-1)" />
            <UButton icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="sm" class="rounded-none" @click="shiftWindow(1)" />
        </div>

        <div class="inline-flex divide-x divide-default rounded-md border border-default overflow-hidden">
            <UDropdownMenu :items="zoomDropdownItems" :content="{ align: 'start', side: 'bottom' }">
                <UButton :label="currentZoom.label" color="neutral" variant="ghost" size="sm" class="rounded-none" />
            </UDropdownMenu>

            <UButton icon="i-lucide-minus" color="neutral" variant="ghost" size="sm" class="rounded-none" :disabled="zoomIndex >= zoomLevels.length - 1" @click="zoomOut" />
            <UButton icon="i-lucide-plus" color="neutral" variant="ghost" size="sm" class="rounded-none" :disabled="zoomIndex <= 0" @click="zoomIn" />
        </div>
    </div>

    <div v-if="currentZoom.value === 'day'" ref="scrollContainer" class="flex overflow-x-auto border-t border-default" @scroll="onScroll">
        <!-- Kolom task, sticky kiri -->
        <GanttTaskList :tasks="visibleTasks" :expanded-ids="expandedIds" @toggle-expand="toggleExpand">
            <template #header>
                <div class="h-14.25"></div>
                <div class="h-6 border-b border-default"></div>
            </template>
        </GanttTaskList>

        <!-- Grid tanggal -->
        <div class="relative">
            <div
                v-if="todayIndex !== -1"
                class="pointer-events-none absolute top-14.25 bottom-0 z-0 border-l-2 border-inverted"
                :style="{ left: `${todayIndex * dayWidth + dayWidth / 2}px` }"
            >
                <span class="absolute -top-1 -left-1.25 size-2 rounded-full bg-inverted" />
            </div>

            <div class="relative border-b border-default" style="height: 29px">
                <div class="absolute inset-0 flex">
                    <div
                        v-for="(group, index) in weekGroups"
                        :key="index"
                        class="flex items-center border-r border-default px-2 text-xs font-medium text-muted"
                        :style="{ width: `${group.span * dayWidth}px` }"
                    >
                        {{ group.label }}
                    </div>
                </div>

                <div class="absolute inset-0 flex">
                    <div v-for="(group, index) in monthGroups" :key="index" class="relative" :style="{ width: `${group.span * dayWidth}px` }">
                        <div
                            class="sticky left-48 flex h-full w-max items-center bg-default px-2 text-xs font-medium"
                            :class="group.isCurrent ? 'text-primary' : 'text-muted'"
                        >
                            {{ group.label }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex border-b border-default">
                <div
                    v-for="(day, index) in dayColumns"
                    :key="index"
                    class="flex items-center justify-center gap-1.5 px-2 text-xs"
                    :class="[
                        day.isToday ? 'bg-primary/10 text-primary font-medium' : day.isWeekend ? 'bg-elevated text-muted' : 'text-default',
                        day.isWeekEnd ? 'border-r border-default' : '',
                    ]"
                    :style="{ width: `${dayWidth}px`, height: '28px' }"
                >
                    {{ formatDayLabel(day.date) }}
                    <span
                        v-if="day.isToday"
                        class="flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-semibold text-inverted"
                    >
                        {{ day.date.getDate() }}
                    </span>
                    <span v-else>{{ day.date.getDate() }}</span>
                </div>
            </div>

            <div class="relative flex h-6 border-b border-default">
                <div
                    v-for="(day, index) in dayColumns"
                    :key="index"
                    class="shrink-0"
                    :class="[day.isWeekend ? 'bg-elevated' : '', day.isWeekEnd ? 'border-r border-default' : '']"
                    :style="{ width: `${dayWidth}px` }"
                ></div>
            </div>

            <div v-for="task in visibleTasks" :key="task.id" class="relative flex h-12 border-b border-default">
                <div
                    v-for="(day, index) in dayColumns"
                    :key="index"
                    class="shrink-0 border-r border-default"
                    :class="day.isWeekend ? 'bg-elevated' : ''"
                    :style="{ width: `${dayWidth}px` }"
                ></div>

                <GanttTaskBar v-if="hasBar(task)" :task="task" :style="barStyle(task)" />
            </div>
        </div>
    </div>

    <div v-else-if="currentZoom.value === 'week'" ref="scrollContainer" class="flex overflow-x-auto border-t border-default" @scroll="onScroll">
        <!-- Kolom task, sticky kiri -->
        <GanttTaskList :tasks="visibleTasks" :expanded-ids="expandedIds" @toggle-expand="toggleExpand">
            <template #header>
                <div class="h-13.25"></div>
            </template>
        </GanttTaskList>

        <!-- Grid minggu -->
        <div class="relative">
            <div
                v-if="todayIndex !== -1"
                class="pointer-events-none absolute top-13.25 bottom-0 z-0 border-l-2 border-inverted"
                :style="{ left: `${todayIndex * dayWidth + dayWidth / 2}px` }"
            >
                <span class="absolute -top-1 -left-1.25 size-2 rounded-full bg-inverted" />
            </div>

            <div class="relative border-b border-default" style="height: 53px">
                <div class="absolute inset-x-0 bottom-0 flex" style="height: 29px">
                    <div
                        v-for="(group, index) in weekGroups"
                        :key="index"
                        class="flex items-center justify-center border-r border-default text-xs"
                        :class="group.isCurrent ? 'text-primary font-medium' : 'text-muted'"
                        :style="{ width: `${group.span * dayWidth}px` }"
                    >
                        <span v-if="group.isCurrent" class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold text-inverted">{{ group.label }}</span>
                        <span v-else>{{ group.label }}</span>
                    </div>
                </div>

                <div class="flex" style="height: 24px">
                    <div v-for="(group, index) in monthGroups" :key="index" class="relative" :style="{ width: `${group.span * dayWidth}px` }">
                        <div
                            class="sticky left-48 flex h-full w-max items-center bg-default px-2 text-xs font-medium"
                            :class="group.isCurrent ? 'text-primary' : 'text-muted'"
                        >
                            {{ group.label }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-for="task in visibleTasks" :key="task.id" class="relative flex h-12 border-b border-default">
                <div
                    v-for="(group, index) in weekGroups"
                    :key="index"
                    class="shrink-0 border-r border-default"
                    :class="group.isCurrent ? 'bg-primary/5' : ''"
                    :style="{ width: `${group.span * dayWidth}px` }"
                ></div>

                <GanttTaskBar v-if="hasBar(task)" :task="task" :style="barStyle(task)" />
            </div>
        </div>
    </div>

    <div v-else-if="currentZoom.value === 'month'" ref="scrollContainer" class="flex overflow-x-auto border-t border-default" @scroll="onScroll">
        <!-- Kolom task, sticky kiri -->
        <GanttTaskList :tasks="visibleTasks" :expanded-ids="expandedIds" @toggle-expand="toggleExpand">
            <template #header>
                <div class="h-13.25"></div>
            </template>
        </GanttTaskList>

        <!-- Grid bulan -->
        <div class="relative">
            <div
                v-if="todayIndex !== -1"
                class="pointer-events-none absolute top-13.25 bottom-0 z-0 border-l-2 border-inverted"
                :style="{ left: `${todayIndex * dayWidth + dayWidth / 2}px` }"
            >
                <span class="absolute -top-1 -left-1.25 size-2 rounded-full bg-inverted" />
            </div>

            <div class="relative border-b border-default" style="height: 53px">
                <div class="absolute inset-x-0 bottom-0 flex" style="height: 29px">
                    <div
                        v-for="(group, index) in monthGroups"
                        :key="index"
                        class="flex items-center justify-center border-r border-default text-xs"
                        :class="group.isCurrent ? 'text-primary font-medium' : 'text-muted'"
                        :style="{ width: `${group.span * dayWidth}px` }"
                    >
                        <span v-if="group.isCurrent" class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-semibold text-inverted">{{ group.label }}</span>
                        <span v-else>{{ group.label }}</span>
                    </div>
                </div>

                <div class="flex" style="height: 24px">
                    <div v-for="(group, index) in quarterGroups" :key="index" class="relative" :style="{ width: `${group.span * dayWidth}px` }">
                        <div
                            class="sticky left-48 flex h-full w-max items-center bg-default px-2 text-xs font-medium"
                            :class="group.isCurrent ? 'text-primary' : 'text-muted'"
                        >
                            {{ group.label }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-for="task in visibleTasks" :key="task.id" class="relative flex h-12 border-b border-default">
                <div
                    v-for="(group, index) in monthGroups"
                    :key="index"
                    class="shrink-0 border-r border-default"
                    :class="group.isCurrent ? 'bg-primary/5' : ''"
                    :style="{ width: `${group.span * dayWidth}px` }"
                ></div>

                <GanttTaskBar v-if="hasBar(task)" :task="task" :style="barStyle(task)" />
            </div>
        </div>
    </div>

</template>
