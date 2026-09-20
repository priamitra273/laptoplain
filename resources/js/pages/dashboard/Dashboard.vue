<script setup lang="ts">
import StatCard from '@/components/StatCard.vue';
import ActivityGraphCard from './ActivityGraphCard.vue';
import NeedsAttentionCard from './NeedsAttentionCard.vue';
import TaskDonutChart from './TaskDonutChart.vue';
import ProjectProgressCard from './ProjectProgressCard.vue';
import LatestProjectsCard from './LatestProjectsCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { plural } from '@/lib/utils';
import { computed } from 'vue';
import { TASK_CHART_COLORS } from './chartColor';
import type { DashboardActivity, DashboardAttentionItem, DashboardLatestProject, DashboardProjectProgress } from './types';


const props = defineProps<{
    summary: {
        running_projects: number;
        tasks_due_this_week: number;
        attention_items: number;
    };
    stats: {
        total: number;
        done: number;
        done_recently: number;
        in_progress: number;
        in_progress_assigned_to_me: number;
        overdue: number;
        due_soon: number;
        active: number;
        progress_percent: number;
    };
    activity?: DashboardActivity;
    attention: {
        total: number;
        items: DashboardAttentionItem[];
    };
    projectsTab: string;
    projectProgress?: DashboardProjectProgress[];
    latestProjects?: DashboardLatestProject[];
}>();

const page = usePage();

const name = computed(() => page.props.auth?.user?.name ?? '');

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});


const legend = computed(() => [
    { label: 'Done', value: props.stats.done, color: TASK_CHART_COLORS.done },
    { label: 'Active', value: props.stats.active, color: TASK_CHART_COLORS.active },
    { label: 'Overdue', value: props.stats.overdue, color: TASK_CHART_COLORS.overdue },
]);


</script>

<template>
    <AppLayout title="Dashboard">

        <Head title="Dashboard" />

        <div class="flex flex-col gap-5">
            <Heading :title="`${greeting}, ${name}`">
                <template #description>
                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted">
                        <span>{{ plural(summary.running_projects, 'project') }} running</span>
                        <span aria-hidden="true" class="text-dimmed">·</span>
                        <span>{{ plural(summary.tasks_due_this_week, 'task') }} due this week</span>
                        <span aria-hidden="true" class="text-dimmed">·</span>
                        <span>
                            {{ plural(summary.attention_items, 'item') }}
                            {{ summary.attention_items === 1 ? 'needs' : 'need' }} attention today
                        </span>
                    </p>
                </template>
            </Heading>

           <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <StatCard label="Total tasks" icon="i-lucide-clipboard-list" :value="stats.total"
                    :hint="`across ${plural(summary.running_projects, 'project')}`" />

                <StatCard label="Completed" icon="i-lucide-circle-check" tone="success" :value="stats.done"
                    :hint="`+${stats.done_recently} this week`" />

                <StatCard label="In progress" icon="i-lucide-clock" :value="stats.in_progress"
                    :hint="`${stats.in_progress_assigned_to_me} assigned to you`" />

                <StatCard label="Overdue" icon="i-lucide-triangle-alert" tone="danger" :value="stats.overdue"
                    :hint="`+ ${stats.due_soon} due in 7 days`" />

               <UCard class="sm:col-span-2" :ui="{ body: 'flex items-center gap-4' }">
                    <TaskDonutChart :done="stats.done" :active="stats.active" :overdue="stats.overdue" />

                    <div class="flex min-w-0 flex-col gap-2">
                        <span class="text-sm text-toned">Task progress</span>

                        <span class="text-3xl leading-none font-semibold tabular-nums text-highlighted">
                            {{ stats.progress_percent }}%
                        </span>

                        <ul class="grid grid-cols-2 gap-x-3 gap-y-1">
                            <li v-for="item in legend" :key="item.label"
                                class="flex items-center gap-1.5 text-xs text-muted">
                                <span class="size-2 shrink-0 rounded-full" :style="{ backgroundColor: item.color }" />
                                {{ item.label }}
                            </li>
                        </ul>
                    </div>
                </UCard>
            </div>

            <div class="grid gap-3 xl:grid-cols-5">
                <ActivityGraphCard class="xl:col-span-3" :activity="activity" />

                <NeedsAttentionCard class="xl:col-span-2" :total="attention.total" :items="attention.items" />
            </div>

            <div class="grid gap-3 xl:grid-cols-5">
                <ProjectProgressCard class="xl:col-span-3" :rows="projectProgress" :tab="projectsTab" />

                <LatestProjectsCard class="xl:col-span-2" :items="latestProjects" />
            </div>
        </div>
    </AppLayout>
</template>

