<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import type { BreadcrumbItem, Project } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { Task } from './project';

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

import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { Emoji, EmojiIndex } from 'emoji-mart-vue-fast/src';

const emojiIndex = new EmojiIndex(emojiData);

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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const latestProjects = ref<Project[]>(props.projects.slice(0, 5));
const latestTasks = ref<Task[]>(props.tasks.slice(0, 5));

const taskStatistic = computed(() => ({
    completed: props.stats.tasks.completed,
    inProgress: props.stats.tasks.in_progress,
    notStarted: props.stats.tasks.total - props.stats.tasks.completed - props.stats.tasks.in_progress,
}));

const projectStatistic = computed(() => ({
    completed: props.stats.projects.completed,
    inProgress: props.stats.projects.in_progress,
    notStarted: props.stats.projects.total - props.stats.projects.completed - props.stats.projects.in_progress,
    total: props.stats.projects.total,
}));

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getRandomColor = (index: number) => {
    const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#6366f1', '#f43f5e'];
    return colors[index % colors.length];
};

const viewAllProjects = () => router.get(route('project.index'));
const viewAllTasks = () => router.get(route('task.index'));
const calcProgress = (completed: number, total: number) => {
    if (!total || total <= 0) return 0;
    return Math.round((completed / total) * 100);
};

const onTaskRowClick = (event: any) => {
    router.visit(route('task.show', event.data.id));
};
const onProjectRowClick = (event: any) => {
    router.visit(route('project.show', event.data.id));
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="dashboard space-y-6 p-4">
            <Heading title="Dashboard" description="Overview of your projects, tasks, and team members" />

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <Card class="shadow-md transition-shadow hover:shadow-lg">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <i class="pi pi-check-square text-xl text-blue-500"></i>
                                        <span class="text-sm font-semibold uppercase tracking-wide text-gray-500">Tasks</span>
                                    </div>
                                    <div class="text-4xl font-bold">{{ props.stats.tasks.total }}</div>
                                </div>
                                <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                                    <i class="pi pi-check-square text-3xl text-blue-500"></i>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span>Progress</span>
                                    <span class="font-semibold"> {{ calcProgress(taskStatistic.completed, props.stats.tasks.total) }}% </span>
                                </div>
                                <ProgressBar :value="calcProgress(taskStatistic.completed, props.stats.tasks.total)" :showValue="false" class="h-2" />
                            </div>

                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="success" class="h-2 w-2 min-w-0 p-0" />
                                    <span>Completed</span>
                                    <span class="font-semibold">{{ taskStatistic.completed }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="warn" class="h-2 w-2 min-w-0 p-0" />
                                    <span>In Progress</span>
                                    <span class="font-semibold">{{ taskStatistic.inProgress }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-md transition-shadow hover:shadow-lg">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <i class="pi pi-briefcase text-xl text-purple-500"></i>
                                        <span class="text-sm font-semibold uppercase tracking-wide text-gray-500">Projects</span>
                                    </div>
                                    <div class="text-4xl font-bold">{{ projectStatistic.total }}</div>
                                </div>
                                <div class="rounded-lg bg-purple-50 p-3 dark:bg-purple-900/20">
                                    <i class="pi pi-briefcase text-3xl text-purple-500"></i>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span>Progress</span>
                                    <span class="font-semibold"> {{ calcProgress(projectStatistic.completed, projectStatistic.total) }}% </span>
                                </div>
                                <ProgressBar
                                    :value="calcProgress(projectStatistic.completed, projectStatistic.total)"
                                    :showValue="false"
                                    class="h-2"
                                />
                            </div>

                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="success" class="h-2 w-2 min-w-0 p-0" />
                                    <span>Completed</span>
                                    <span class="font-semibold">{{ projectStatistic.completed }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge value="" severity="warn" class="h-2 w-2 min-w-0 p-0" />
                                    <span>In Progress</span>
                                    <span class="font-semibold">{{ projectStatistic.inProgress }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="shadow-md transition-shadow hover:shadow-lg">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center gap-2">
                                        <i class="pi pi-users text-xl text-green-500"></i>
                                        <span class="text-sm font-semibold uppercase tracking-wide text-gray-500">Team Members</span>
                                    </div>
                                    <div class="text-4xl font-bold">{{ props.stats.members.total }}</div>
                                </div>
                                <div class="rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                    <i class="pi pi-users text-3xl text-green-500"></i>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="text-sm">Active team members</div>
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

            <Card class="shadow-md">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-briefcase text-2xl text-purple-500"></i>
                            <span class="text-xl font-bold">Latest Projects</span>
                        </div>
                        <Button label="View All" icon="pi pi-arrow-right" iconPos="right" text size="small" @click="viewAllProjects" />
                    </div>
                </template>
                <template #content>
                    <DataTable
                        :value="latestProjects"
                        stripedRows
                        responsiveLayout="scroll"
                        class="cursor-pointer text-sm"
                        row-hover
                        @row-click="onProjectRowClick"
                    >
                        <Column field="title" header="Project" style="min-width: 250px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Emoji v-if="data.emoji?.startsWith(':')" :data="emojiIndex" :emoji="data.emoji" set="google" :size="24" />
                                    <span v-else class="text-2xl leading-none">
                                        {{ data.emoji }}
                                    </span>
                                    <span class="truncate font-semibold">
                                        {{ data.title }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="Status" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.status.name" :severity="data.status.severity" rounded class="font-semibold" />
                            </template>
                        </Column>
                        <Column field="priority" header="Priority" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.priority.name" :severity="data.priority.severity" rounded class="font-semibold" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card class="shadow-md">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="pi pi-check-square text-2xl text-blue-500"></i>
                            <span class="text-xl font-bold">Latest Tasks</span>
                        </div>
                        <Button label="View All" icon="pi pi-arrow-right" iconPos="right" text size="small" @click="viewAllTasks" />
                    </div>
                </template>
                <template #content>
                    <DataTable
                        :value="latestTasks"
                        stripedRows
                        responsiveLayout="scroll"
                        class="cursor-pointer text-sm"
                        row-hover
                        @row-click="onTaskRowClick"
                    >
                        <Column field="title" header="Task" style="min-width: 250px">
                            <template #body="{ data }">
                                <div class="font-semibold">{{ data.title }}</div>
                            </template>
                        </Column>
                        <Column field="status" header="Status" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.status.name" :severity="data.status.severity" rounded class="font-semibold" />
                            </template>
                        </Column>
                        <Column field="priority" header="Priority" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.priority.name" :severity="data.priority.severity" rounded class="font-semibold" />
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
