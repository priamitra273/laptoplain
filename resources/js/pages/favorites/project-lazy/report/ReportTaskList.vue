<script setup lang="ts">
import { severityColor } from '@/lib/utils';
import type { TableColumn } from '@nuxt/ui';
import type { ReportTask } from './types';

interface Props {
    title: string;
    description?: string;
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
    <UCard :title="title" :description="description">
        <UTable :data="tasks" :columns="columns" :loading="loading">
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
                <UBadge v-if="row.original.category" color="neutral" variant="subtle">{{ row.original.category.name }}</UBadge>
                <span v-else class="text-muted">—</span>
            </template>

            <template #rootAncestor-cell="{ row }">
                <UBadge v-if="row.original.rootAncestor" color="primary" variant="subtle">{{ row.original.rootAncestor.title }}</UBadge>
                <span v-else class="text-muted">—</span>
            </template>

            <template #status-cell="{ row }">
                <UBadge v-if="row.original.status" :color="severityColor(row.original.status.severity)" variant="subtle">
                    {{ row.original.status.name }}
                </UBadge>
            </template>

            <template #users-cell="{ row }">
                <UAvatarGroup v-if="row.original.users?.length" :max="4" size="xs">
                    <UAvatar
                        v-for="user in row.original.users"
                        :key="user.id"
                        :src="user.avatar_url ?? undefined"
                        :alt="user.name"
                        :title="user.name"
                    />
                </UAvatarGroup>
                <span v-else class="text-muted">Unassigned</span>
            </template>

            <template #story_points-cell="{ row }">
                {{ row.original.story_points ?? '—' }}
            </template>

            <template #empty>
                <p class="text-center text-sm text-muted">{{ emptyMessage }}</p>
            </template>
        </UTable>
    </UCard>
</template>
