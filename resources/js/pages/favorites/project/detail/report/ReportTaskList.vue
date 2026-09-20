<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TaskCategoryBadge from '@/components/task/TaskCategoryBadge.vue';
import UserAvatarGroup from '@/components/UserAvatarGroup.vue';
import type { TableColumn } from '@nuxt/ui';
import type { ReportTask } from './types';

interface Props {
    title: string;
    tasks: ReportTask[];
    loading?: boolean;
    emptyMessage: string;
}

defineProps<Props>();

const columns: TableColumn<ReportTask>[] = [
    { accessorKey: 'key', header: 'Key' },
    { accessorKey: 'title', header: 'Title' },
    { accessorKey: 'category', header: 'Work Type' },
    { accessorKey: 'rootAncestor', header: 'Epic' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'users', header: 'Assignee' },
    { accessorKey: 'story_points', header: 'Story Points' },
];
</script>

<template>
    <div class="flex flex-col gap-2">
        <HeadingSmall :title="title" />

        <div class="overflow-hidden rounded-md ring ring-default">
            <UTable :data="tasks" :columns="columns" :loading="loading" size="sm">
                <template #key-cell="{ row }">
                    <a
                        :href="route('task.show', row.original.id)"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-1 text-primary hover:underline"
                    >
                        {{ row.original.key }}
                        <UIcon name="i-lucide-square-arrow-out-up-right" class="size-3.5" />
                    </a>
                </template>

                <template #category-cell="{ row }">
                    <TaskCategoryBadge v-if="row.original.category" :category="row.original.category" />
                    <span v-else class="text-muted">—</span>
                </template>

                <template #rootAncestor-cell="{ row }">
                    <UBadge v-if="row.original.rootAncestor" color="primary" variant="subtle">{{ row.original.rootAncestor.title }}</UBadge>
                    <span v-else class="text-muted">—</span>
                </template>

                <template #status-cell="{ row }">
                    <StatusBadge :label="row.original.status?.name" :severity="row.original.status?.severity" />
                </template>

                <template #users-cell="{ row }">
                    <UserAvatarGroup :users="row.original.users ?? []" :max="4" size="xs" empty-label="Unassigned" />
                </template>

                <template #story_points-cell="{ row }">
                    {{ row.original.story_points ?? '—' }}
                </template>

                <template #empty>
                    <EmptyState :title="emptyMessage" size="compact" />
                </template>
            </UTable>
        </div>
    </div>
</template>
