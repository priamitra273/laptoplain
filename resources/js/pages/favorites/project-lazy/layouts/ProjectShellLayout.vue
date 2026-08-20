<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials, severityColor } from '@/lib/utils';
import { ProjectPolicyKey } from '@/types/type';
import { router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, provide } from 'vue';
import type { ShellProps } from '../types';

const page = usePage();

const shell = computed(() => page.props as unknown as ShellProps);
const project = computed(() => shell.value.project);

provide(ProjectPolicyKey, shell.value.policy);

const formatDate = (date: string | null) => (date ? moment(date).format('MMM D, YYYY') : '—');

const tabs = [
    { value: 'kanban', label: 'Kanban', icon: 'i-lucide-layout-grid' },
    { value: 'list', label: 'List', icon: 'i-lucide-list' },
    { value: 'backlog', label: 'Backlog', icon: 'i-lucide-inbox' },
    { value: 'detail', label: 'Details', icon: 'i-lucide-info' },
    { value: 'team', label: 'Team', icon: 'i-lucide-users' },
    { value: 'timeline', label: 'Timeline', icon: 'i-lucide-chart-gantt' },
    { value: 'report', label: 'Report', icon: 'i-lucide-chart-line' },
];

const activeKey = computed(() => {
    const path = page.url.split('?')[0].replace(/\/$/, '');
    const last = path.split('/').pop() ?? 'kanban';
    return tabs.some((tab) => tab.value === last) ? last : 'kanban';
});

const navigate = (key: string | number) => {
    if (key === activeKey.value) return;
    router.visit(route(`project.show.${key}`, { encoded: project.value.id }), { preserveScroll: true });
};
</script>

<template>
    <AppLayout :title="project.title">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <UButton
                        icon="i-lucide-arrow-left"
                        color="neutral"
                        variant="ghost"
                        aria-label="Back to projects"
                        @click="router.visit(route('project.index'))"
                    />
                    <div>
                        <h1 class="text-xl font-bold">{{ project.title }}</h1>
                        <div class="mt-0.5 flex flex-wrap items-center gap-2 text-sm text-muted">
                            <span v-if="project.project_no" class="font-mono">{{ project.project_no }}</span>
                            <span v-if="shell.isMember" class="flex items-center gap-1 text-success">
                                <UIcon name="i-lucide-circle-check" class="size-4" />
                                Member
                            </span>
                        </div>
                    </div>
                </div>

                <UAvatarGroup v-if="shell.members.length" :max="4" size="md">
                    <UAvatar
                        v-for="member in shell.members"
                        :key="member.id"
                        :src="member.user.avatar_url ?? undefined"
                        :alt="member.user.name"
                        :text="getInitials(member.user.name)"
                    />
                </UAvatarGroup>
            </div>

            <USeparator />

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <UCard :ui="{ body: 'p-4' }">
                    <p class="text-xs font-medium tracking-wide text-muted uppercase">Status</p>
                    <UBadge v-if="project.status" :color="severityColor(project.status.severity)" variant="subtle" class="mt-2">
                        {{ project.status.name }}
                    </UBadge>
                    <span v-else class="mt-2 block text-sm text-muted">—</span>
                </UCard>

                <UCard :ui="{ body: 'p-4' }">
                    <p class="text-xs font-medium tracking-wide text-muted uppercase">Priority</p>
                    <UBadge v-if="project.priority" :color="severityColor(project.priority.severity)" variant="subtle" class="mt-2">
                        {{ project.priority.name }}
                    </UBadge>
                    <span v-else class="mt-2 block text-sm text-muted">—</span>
                </UCard>

                <UCard :ui="{ body: 'p-4' }">
                    <p class="text-xs font-medium tracking-wide text-muted uppercase">Timeline</p>
                    <div class="mt-2 space-y-1 text-sm">
                        <p>Start: {{ formatDate(project.start_date) }}</p>
                        <p>Due: {{ formatDate(project.due_date) }}</p>
                    </div>
                </UCard>

                <UCard :ui="{ body: 'p-4' }">
                    <p class="text-xs font-medium tracking-wide text-muted uppercase">Progress</p>
                    <div class="mt-3 flex items-center gap-2">
                        <UProgress :model-value="project.progress" size="sm" />
                        <span class="shrink-0 text-sm tabular-nums">{{ project.progress }}%</span>
                    </div>
                </UCard>
            </div>

            <UCard :ui="{ body: 'p-0 sm:p-0' }">
                <UTabs :items="tabs" :model-value="activeKey" :content="false" class="border-b border-default px-2" @update:model-value="navigate" />

                <div class="p-4">
                    <slot />
                </div>
            </UCard>
        </div>
    </AppLayout>
</template>
