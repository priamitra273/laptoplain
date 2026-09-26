<script setup lang="ts">
import DonutChart from '@/components/charts/DonutChart.vue';
import StatCard from '@/components/common/StatCard.vue';
import { computed } from 'vue';
import type { WorkloadStatusOption, WorkloadSummary } from './types';
import { buildSegments, workloadDonutSegments, workloadUtilization } from './workload';

const props = defineProps<{
    summary: WorkloadSummary;
    statusOptions: WorkloadStatusOption[];
    activeStatusIds: number[];
}>();

const iconByStatusId: Record<number, string> = {
    1: 'i-lucide-circle-check',
    2: 'i-lucide-bar-chart-2',
    3: 'i-lucide-clock',
};

const cards = computed<{ key: string; label: string; count: number; icon: string; tone: 'neutral' | 'success' | 'danger'; active: boolean }[]>(() => [
    { key: 'total', label: 'Total users', count: props.summary.total_users, icon: 'i-lucide-users', tone: 'neutral', active: false },
    ...buildSegments(props.summary, props.statusOptions, [])
        .filter((segment) => segment.id !== 4)
        .map((segment) => ({
            key: String(segment.id),
            label: segment.label,
            count: segment.count,
            icon: iconByStatusId[segment.id] ?? 'i-lucide-gauge',
            tone: segment.id === 1 ? ('success' as const) : ('neutral' as const),
            active: props.activeStatusIds.includes(segment.id),
        })),
]);

const utilization = computed(() => workloadUtilization(props.summary));
const utilizationSegments = computed(() => workloadDonutSegments(props.summary));
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <StatCard
            v-for="card in cards"
            :key="card.key"
            :label="card.label"
            :value="card.count"
            :icon="card.icon"
    :tone="card.tone"
    :class="card.active ? 'ring-2 ring-primary' : undefined"
/>

        <UCard class="sm:col-span-2" :ui="{ body: 'flex items-center gap-4' }">
            <DonutChart :segments="utilizationSegments" series-name="Members" />

            <div class="flex min-w-0 flex-col gap-2">
                <span class="text-sm text-toned">Team utilisation</span>
                <span class="text-3xl leading-none font-semibold tabular-nums text-highlighted">{{ utilization }}%</span>
                <span class="text-xs text-error">
                    {{ summary.busy }} {{ summary.busy === 1 ? 'member' : 'members' }} overloaded
                </span>
            </div>
        </UCard>
    </div>
</template>
