<script setup lang="ts">
import StatCard from '@/components/StatCard.vue';
import ActivityHeatmap from './dashboard/ActivityHeatmap.vue';
import NeedsAttentionCard from './dashboard/NeedsAttentionCard.vue';
import TaskDonutChart from './dashboard/TaskDonutChart.vue';
import ProjectProgressCard from './dashboard/ProjectProgressCard.vue';
import LatestProjectsCard from './dashboard/LatestProjectsCard.vue';
import type { PrimeSeverity } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';


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
    activity: {
        values: { date: string; count: number }[];
        total_events: number;
        weeks: number;
        busiest_date: string | null;
        busiest_count: number;
        current_streak: number;
        weekly_average: number;
    };
    attention: {
        total: number;
        items: {
            id: string;
            title: string;
            due_date: string;
            days_remaining: number;
            open_subtasks: number;
            owner_name: string | null;
        }[];
    };
    projectsTab: string;
    projectProgress: {
        id: string;
        title: string;
        emoji: string | null;
        total_tasks: number;
        done_tasks: number;
        progress_percent: number;
        due_date: string | null;
        status_name: string | null;
        status_severity: string | null;
    }[];
    latestProjects: {
        id: string;
        title: string;
        description: string | null;
        created_at: string;
        members_count: number;
        status_name: string | null;
        status_severity: PrimeSeverity | null;
    }[];
}>();

const page = usePage();

const name = computed(() => page.props.auth?.user?.name ?? '');

// Salam mengikuti jam di perangkat pengguna, bukan jam server: keduanya bisa berbeda
// zona waktu, dan yang dirasakan pengguna adalah jam di layarnya sendiri.
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

const plural = (count: number, word: string): string => `${count} ${word}${count === 1 ? '' : 's'}`;

const legend = computed(() => [
    { label: 'Done', value: props.stats.done, class: 'bg-[#22c55e]' },
    { label: 'Active', value: props.stats.active, class: 'bg-[#6366f1]' },
    { label: 'Overdue', value: props.stats.overdue, class: 'bg-[#ef4444]' },
]);

const busiestDay = computed(() => {
    if (!props.activity.busiest_date) {
        return '—';
    }

    const date = new Date(props.activity.busiest_date);
    const formatted = date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });

    return `${formatted} · ${plural(props.activity.busiest_count, 'event')}`;
});

</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <div class="flex flex-col gap-5">
            <header class="flex flex-col gap-1">
                <h1 class="text-xl font-semibold text-highlighted">{{ greeting }}, {{ name }}</h1>

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
            </header>

            <!-- Enam kolom, bukan lima: kartu donut mengambil dua supaya angka dan
                 legendanya punya ruang di samping grafik. -->
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <StatCard
                    label="Total tasks"
                    icon="i-lucide-clipboard-list"
                    :value="stats.total"
                    :hint="`across ${plural(summary.running_projects, 'project')}`"
                />

                <StatCard
                    label="Completed"
                    icon="i-lucide-circle-check"
                    tone="success"
                    :value="stats.done"
                    :hint="`+${stats.done_recently} this week`"
                />

                <StatCard
                    label="In progress"
                    icon="i-lucide-clock"
                    :value="stats.in_progress"
                    :hint="`${stats.in_progress_assigned_to_me} assigned to you`"
                />

                <StatCard
                    label="Overdue"
                    icon="i-lucide-triangle-alert"
                    tone="danger"
                    :value="stats.overdue"
                    :hint="`+ ${stats.due_soon} due in 7 days`"
                />

                <UCard class="sm:col-span-2" :ui="{ root: 'gap-0 py-0', body: 'flex items-center gap-4 p-4 sm:p-4' }">
                    <TaskDonutChart :done="stats.done" :active="stats.active" :overdue="stats.overdue" />

                    <div class="flex min-w-0 flex-col gap-2">
                        <span class="text-sm text-toned">Task progress</span>

                        <span class="text-3xl leading-none font-semibold tabular-nums text-highlighted">
                            {{ stats.progress_percent }}%
                        </span>

                        <ul class="grid grid-cols-2 gap-x-3 gap-y-1">
                            <li v-for="item in legend" :key="item.label" class="flex items-center gap-1.5 text-xs text-muted">
                                <span class="size-2 shrink-0 rounded-full" :class="item.class" />
                                {{ item.label }}
                            </li>
                        </ul>
                    </div>
                </UCard>
            </div>

             <div class="grid gap-3 xl:grid-cols-5">
                <UCard class="xl:col-span-3" :ui="{ root: 'gap-0 py-0', body: 'flex flex-col gap-4 p-4 sm:p-4' }">
                    <div class="flex flex-wrap items-baseline gap-2">
                        <h2 class="text-base font-semibold text-highlighted">Activity graph</h2>
                        <p class="text-sm text-muted">
                            {{ plural(activity.total_events, 'task event') }} · last {{ activity.weeks }} weeks
                        </p>
                    </div>

                    <ActivityHeatmap :values="activity.values" />

                    <USeparator />

                    <dl class="flex flex-wrap gap-x-10 gap-y-3">
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted">Busiest day</dt>
                            <dd class="text-lg font-semibold text-highlighted">{{ busiestDay }}</dd>
                        </div>

                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted">Current streak</dt>
                            <dd class="text-lg font-semibold text-highlighted">{{ plural(activity.current_streak, 'day') }}</dd>
                        </div>

                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted">Weekly average</dt>
                            <dd class="text-lg font-semibold text-highlighted">{{ plural(activity.weekly_average, 'event') }}</dd>
                        </div>
                    </dl>
                </UCard>
                <NeedsAttentionCard class="xl:col-span-2" :total="attention.total" :items="attention.items" />
            </div>

            <div class="grid gap-3 xl:grid-cols-5">
                <ProjectProgressCard class="xl:col-span-3" :rows="projectProgress" :tab="projectsTab" />

                <LatestProjectsCard class="xl:col-span-2" :items="latestProjects" />
            </div>
        </div>
    </AppLayout>
</template>

