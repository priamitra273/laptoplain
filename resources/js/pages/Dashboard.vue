<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials } from '@/lib/utils';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AttentionPanel from './dashboard/AttentionPanel.vue';
import EmptyWorkspace from './dashboard/EmptyWorkspace.vue';
import ProjectGrid from './dashboard/ProjectGrid.vue';
import StatsOverview from './dashboard/StatsOverview.vue';
import TaskList from './dashboard/TaskList.vue';
import type { DashboardStats, Member, ProjectRow, TaskRow } from './dashboard/types';

const props = defineProps<{
    attention: TaskRow[];
    tasks: TaskRow[];
    projects: ProjectRow[];
    stats: DashboardStats;
    members?: Member[];
}>();

const page = usePage();

const user = computed(() => page.props.auth?.user);

const avatar = computed(() => ({
    src: user.value?.avatar_url,
    alt: user.value?.name,
    text: user.value?.name ? getInitials(user.value.name) : undefined,
}));

const today = new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' }).format(new Date());

// Master data marks blocking statuses with the `danger` severity. "Blocked" is the only one
// seeded today, but counting by severity keeps the tally right if more are ever added.
const blocked = computed(() =>
    props.stats.tasks.byStatus.filter((status) => status.severity === 'danger').reduce((total, status) => total + status.count, 0),
);

const isEmpty = computed(() => props.stats.projects.total === 0 && props.stats.tasks.total === 0);
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <div class="flex flex-col gap-6">
            <header class="flex flex-wrap items-center gap-3">
                <UAvatar v-bind="avatar" size="lg" :ui="{ root: 'rounded-lg' }" />

                <h1 class="min-w-0 truncate text-base font-semibold text-highlighted">{{ user?.name }}'s workspace</h1>

                <span class="ms-auto rounded-md px-2.5 py-1.5 font-mono text-xs text-muted ring ring-default">{{ today }}</span>
            </header>

            <EmptyWorkspace v-if="isEmpty" />

            <template v-else>
                <AttentionPanel :tasks="attention" :overdue="stats.tasks.overdue" :due-soon="stats.tasks.dueSoon" :blocked="blocked" />

                <StatsOverview :stats="stats" :members="members" />

                <ProjectGrid :projects="projects" :total="stats.projects.total" />

                <TaskList :tasks="tasks" :total="stats.tasks.total" />
            </template>
        </div>
    </AppLayout>
</template>
