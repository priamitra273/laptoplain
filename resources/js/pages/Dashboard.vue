<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import type { BreadcrumbItem, Project, Task } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// PrimeVue
import Heading from '@/components/Heading.vue';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

interface Props {
    projects: Project[];
    tasks: Task[];
    stats: {
        tasks: { total: number; completed: number; in_progress: number };
        projects: { total: number; completed: number; in_progress: number };
        members: { total: number; list: { id: number; name: string }[] };
    };
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

// Latest project
const latestProjects = ref<Project[]>(props.projects.slice(0, 5));

// Task statistic
const taskStatistic = computed(() => {
    const completed = props.stats.tasks.completed;
    const inProgress = props.stats.tasks.in_progress;
    const notStarted = props.stats.tasks.total - completed - inProgress;
    return { completed, inProgress, notStarted };
});

// Project statistic
const projectStatistic = computed(() => {
    const notStarted = props.stats.projects.total - props.stats.projects.completed - props.stats.projects.in_progress;
    return {
        completed: props.stats.projects.completed,
        inProgress: props.stats.projects.in_progress,
        notStarted,
        total: props.stats.projects.total,
    };
});

// Latest tasks
const latestTasks = ref<Task[]>(props.tasks.slice(0, 5));

// Helpers
const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getRandomColor = (index: number) => {
    const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#6366f1', '#f43f5e'];
    return colors[index % colors.length];
};

const getStatusSeverity = (severity: number) => ({ 1: 'success', 2: 'info', 3: 'warning', 4: 'danger' })[severity] || 'info';
const getPrioritySeverity = (severity: number) => ({ 1: 'success', 2: 'info', 3: 'warning', 4: 'danger' })[severity] || 'info';

const viewAllProjects = () => router.get(route('project.index'));
const viewAllTasks = () => router.get(route('task.index'));
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="dashboard space-y-6 p-4">
            <Heading title="Dashboard" description="Overview of your projects, tasks, and team members" />

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Tasks Card -->
                <Card class="shadow-md transition-shadow hover:shadow-lg">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <i class="pi pi-check-square text-xl text-blue-500"></i>
                                        <span class="text-sm font-semibold uppercase tracking-wide text-gray-500">Tasks</span>
                                    </div>
                                    <div class="text-4xl font-bold text-gray-900 dark:text-white">
                                        {{ props.stats.tasks.total }}
                                    </div>
                                </div>
                                <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                                    <i class="pi pi-check-square text-3xl text-blue-500"></i>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Progress</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ Math.round((taskStatistic.completed / props.stats.tasks.total) * 100) }}%
                                    </span>
                                </div>
                                <ProgressBar :value="(taskStatistic.completed / props.stats.tasks.total) * 100" :showValue="false" class="h-2" />
                            </div>

                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="success" class="h-2 w-2 min-w-0 p-0" />
                                    <span class="text-gray-600 dark:text-gray-400">Completed</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ taskStatistic.completed }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="info" class="h-2 w-2 min-w-0 p-0" />
                                    <span class="text-gray-600 dark:text-gray-400">In Progress</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ taskStatistic.inProgress }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Projects Card -->
                <Card class="shadow-md transition-shadow hover:shadow-lg">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <i class="pi pi-briefcase text-xl text-purple-500"></i>
                                        <span class="text-sm font-semibold uppercase tracking-wide text-gray-500">Projects</span>
                                    </div>
                                    <div class="text-4xl font-bold text-gray-900 dark:text-white">
                                        {{ projectStatistic.total }}
                                    </div>
                                </div>
                                <div class="rounded-lg bg-purple-50 p-3 dark:bg-purple-900/20">
                                    <i class="pi pi-briefcase text-3xl text-purple-500"></i>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Progress</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ Math.round((projectStatistic.completed / projectStatistic.total) * 100) }}%
                                    </span>
                                </div>
                                <ProgressBar :value="(projectStatistic.completed / projectStatistic.total) * 100" :showValue="false" class="h-2" />
                            </div>

                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="success" class="h-2 w-2 min-w-0 p-0" />
                                    <span class="text-gray-600 dark:text-gray-400">Completed</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ projectStatistic.completed }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="info" class="h-2 w-2 min-w-0 p-0" />
                                    <span class="text-gray-600 dark:text-gray-400">In Progress</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ projectStatistic.inProgress }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Members Card -->
                <Card class="shadow-md transition-shadow hover:shadow-lg">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <i class="pi pi-users text-xl text-green-500"></i>
                                        <span class="text-sm font-semibold uppercase tracking-wide text-gray-500">Team Members</span>
                                    </div>
                                    <div class="text-4xl font-bold text-gray-900 dark:text-white">
                                        {{ props.stats.members.total }}
                                    </div>
                                </div>
                                <div class="rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                    <i class="pi pi-users text-3xl text-green-500"></i>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Active team members</div>
                                <AvatarGroup>
                                    <Avatar
                                        v-for="(member, index) in props.stats.members.list.slice(0, 5)"
                                        :key="member.id"
                                        :label="getInitials(member.name)"
                                        shape="circle"
                                        size="large"
                                        :style="{ backgroundColor: getRandomColor(index), color: 'white', fontWeight: '600' }"
                                        :title="member.name"
                                        class="border-2 border-white dark:border-gray-800"
                                    />
                                    <Avatar
                                        v-if="props.stats.members.total > 5"
                                        :label="`+${props.stats.members.total - 5}`"
                                        shape="circle"
                                        size="large"
                                        style="background-color: #64748b; color: white; font-weight: 600"
                                        class="border-2 border-white dark:border-gray-800"
                                    />
                                </AvatarGroup>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Latest Projects -->
            <Card class="shadow-md">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-briefcase text-2xl text-purple-500"></i>
                            <span class="text-xl font-bold">Latest Projects</span>
                        </div>
                        <Button
                            label="View All"
                            icon="pi pi-arrow-right"
                            iconPos="right"
                            text
                            size="small"
                            @click="viewAllProjects"
                            class="font-semibold"
                        />
                    </div>
                </template>
                <template #content>
                    <DataTable
                        :value="latestProjects"
                        stripedRows
                        responsiveLayout="scroll"
                        class="text-sm"
                        :pt="{
                            header: { class: 'bg-gray-50 dark:bg-gray-900' },
                            bodyRow: { class: 'hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors' },
                        }"
                    >
                        <Column field="title" header="Project" style="min-width: 250px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <span class="text-3xl">{{ data.emoji }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ data.title }}</span>
                                </div>
                            </template>
                        </Column>
                        <Column field="status" header="Status" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.status.name" :severity="getStatusSeverity(data.status.severity)" rounded class="font-semibold" />
                            </template>
                        </Column>
                        <Column field="priority" header="Priority" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag
                                    :value="data.priority.name"
                                    :severity="getPrioritySeverity(data.priority.severity)"
                                    rounded
                                    class="font-semibold"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Latest Tasks -->
            <Card class="shadow-md">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-check-square text-2xl text-blue-500"></i>
                            <span class="text-xl font-bold">Latest Tasks</span>
                        </div>
                        <Button
                            label="View All"
                            icon="pi pi-arrow-right"
                            iconPos="right"
                            text
                            size="small"
                            @click="viewAllTasks"
                            class="font-semibold"
                        />
                    </div>
                </template>
                <template #content>
                    <DataTable
                        :value="latestTasks"
                        stripedRows
                        responsiveLayout="scroll"
                        class="text-sm"
                        :pt="{
                            header: { class: 'bg-gray-50 dark:bg-gray-900' },
                            bodyRow: { class: 'hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors' },
                        }"
                    >
                        <Column field="title" header="Task" style="min-width: 250px">
                            <template #body="{ data }">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ data.title }}</div>
                            </template>
                        </Column>
                        <Column field="status" header="Status" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.status.name" :severity="getStatusSeverity(data.status.severity)" rounded class="font-semibold" />
                            </template>
                        </Column>
                        <Column field="priority" header="Priority" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag
                                    :value="data.priority.name"
                                    :severity="getPrioritySeverity(data.priority.severity)"
                                    rounded
                                    class="font-semibold"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.dashboard {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
