<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { getInitials, severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { Deferred, Head } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import { computed } from 'vue';

interface Member {
    id: string;
    name: string;
    email?: string;
    avatar_url?: string | null;
}

interface Labelled {
    name: string;
    severity: PrimeSeverity;
}

/** Hanya kolom yang dipakai tabel tugas; payload masih membawa proyek dan tenggat. */
interface TaskRow {
    id: string;
    title: string;
    status?: Labelled | null;
    priority?: Labelled | null;
}

/** Hanya kolom yang dipakai halaman ini; payload sengaja tidak lagi membawa anggota proyek. */
interface ProjectRow {
    id: string;
    title: string;
    emoji?: string | null;
    due_date?: string | null;
    status?: Labelled | null;
    priority?: Labelled | null;
}

interface StatusCount {
    name: string;
    severity: PrimeSeverity | null;
    count: number;
}

const props = defineProps<{
    attention: TaskRow[];
    projects: ProjectRow[];
    stats: {
        tasks: { total: number; progress: number; overdue: number; byStatus: StatusCount[] };
        projects: { total: number; progress: number; byStatus: StatusCount[] };
    };
    members?: Member[];
}>();

const relative = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
const absolute = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

const dayInMs = 86_400_000;


const startOfLocalDay = (value: string) => {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return new Date(year, month - 1, day).getTime();
};

const startOfToday = () => {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
};

const dueInfo = (value?: string | null): { label: string; color: 'error' | 'warning' | 'neutral' } => {
    if (!value) {
        return { label: 'No due date', color: 'neutral' };
    }

    const days = Math.round((startOfLocalDay(value) - startOfToday()) / dayInMs);

    if (days < 0) {
        return { label: absolute.format(new Date(startOfLocalDay(value))), color: 'error' };
    }

    return { label: relative.format(days, 'day'), color: days === 0 ? 'warning' : 'neutral' };
};

const memberAvatars = computed(() =>
    (props.members ?? []).map((member) => ({
        id: member.id,
        src: member.avatar_url && !member.avatar_url.includes('default-avatar') ? member.avatar_url : undefined,
        text: getInitials(member.name),
        alt: member.name,
    })),
);
const statCardUi = { root: 'flex flex-col', body: 'flex flex-1 flex-col' };

const projectColumns: TableColumn<ProjectRow>[] = [
    { accessorKey: 'title', header: 'Project' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'priority', header: 'Priority' },
    { accessorKey: 'due_date', header: 'Due date' },
];

const taskColumns: TableColumn<TaskRow>[] = [
    { accessorKey: 'title', header: 'Task' },
    { accessorKey: 'status', header: 'Status' },
    { accessorKey: 'priority', header: 'Priority' },
];
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <div class="flex flex-col gap-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <UCard :ui="statCardUi">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm text-muted">Tasks</p>
                        <UIcon name="i-lucide-square-check-big" class="size-5 shrink-0 text-muted" />
                    </div>

                    <div class="mt-2 flex items-baseline gap-2">
                        <p class="text-3xl font-semibold tabular-nums">{{ stats.tasks.total }}</p>
                        <UBadge
                            v-if="stats.tasks.overdue"
                            color="error"
                            variant="subtle"
                            size="sm"
                            :label="`${stats.tasks.overdue} overdue`"
                            class="tabular-nums"
                        />
                    </div>

                    <div class="mt-4 space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Progress</span>
                            <span class="font-medium tabular-nums">{{ stats.tasks.progress }}%</span>
                        </div>
                        <UProgress :model-value="stats.tasks.progress" size="sm" />
                    </div>

                    <div class="mt-auto flex flex-wrap gap-1.5 border-t border-default pt-4">
                        <UBadge
                            v-for="status in stats.tasks.byStatus"
                            :key="status.name"
                            :color="severityColor(status.severity)"
                            variant="subtle"
                            size="sm"
                            :label="`${status.name} (${status.count})`"
                        />
                        <span v-if="!stats.tasks.byStatus.length" class="text-xs text-muted">No tasks yet</span>
                    </div>
                </UCard>

                <UCard :ui="statCardUi">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm text-muted">Projects</p>
                        <UIcon name="i-lucide-briefcase" class="size-5 shrink-0 text-muted" />
                    </div>

                    <p class="mt-2 text-3xl font-semibold tabular-nums">{{ stats.projects.total }}</p>

                    <div class="mt-4 space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Progress</span>
                            <span class="font-medium tabular-nums">{{ stats.projects.progress }}%</span>
                        </div>
                        <UProgress :model-value="stats.projects.progress" size="sm" />
                    </div>

                    <div class="mt-auto flex flex-wrap gap-1.5 border-t border-default pt-4">
                        <UBadge
                            v-for="status in stats.projects.byStatus"
                            :key="status.name"
                            :color="severityColor(status.severity)"
                            variant="subtle"
                            size="sm"
                            :label="`${status.name} (${status.count})`"
                        />
                        <span v-if="!stats.projects.byStatus.length" class="text-xs text-muted">No projects yet</span>
                    </div>
                </UCard>

                <UCard :ui="statCardUi">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm text-muted">Team members</p>
                        <UIcon name="i-lucide-users" class="size-5 shrink-0 text-muted" />
                    </div>

                    <Deferred data="members">
                        <template #fallback>
                            <USkeleton class="mt-2 h-9 w-14" />
                            <USkeleton class="mt-auto h-8 w-36" />
                        </template>

                        <p class="mt-2 text-3xl font-semibold tabular-nums">{{ memberAvatars.length }}</p>

                        <div class="mt-auto pt-4">
                            <UAvatarGroup v-if="memberAvatars.length" :max="5" size="md">
                                <UAvatar
                                    v-for="avatar in memberAvatars"
                                    :key="avatar.id"
                                    :src="avatar.src"
                                    :text="avatar.text"
                                    :alt="avatar.alt"
                                    :title="avatar.alt"
                                />
                            </UAvatarGroup>
                            <p v-else class="text-xs text-muted">No team members yet</p>
                        </div>
                    </Deferred>
                </UCard>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <UCard title="Latest projects" description="The five most recently created projects.">
                    <template #footer>
                        <UButton
                            :to="route('project.index')"
                            label="All projects"
                            trailing-icon="i-lucide-arrow-right"
                            variant="link"
                            size="sm"
                            class="-mx-2"
                        />
                    </template>

                    <UTable :data="projects" :columns="projectColumns">
                        <template #title-cell="{ row }">
                            <div class="flex items-center gap-2">
                                <Icon v-if="row.original.emoji" :name="row.original.emoji" class="size-4 shrink-0 text-muted" />
                                <UIcon v-else name="i-lucide-folder" class="size-4 shrink-0 text-muted" />
                                <ULink :to="route('project.show', String(row.original.id))" class="truncate font-medium">
                                    {{ row.original.title }}
                                </ULink>
                            </div>
                        </template>

                        <template #status-cell="{ row }">
                            <UBadge v-if="row.original.status" :color="severityColor(row.original.status.severity)" variant="subtle">
                                {{ row.original.status.name }}
                            </UBadge>
                            <span v-else class="text-muted">—</span>
                        </template>

                        <template #priority-cell="{ row }">
                            <UBadge v-if="row.original.priority" :color="severityColor(row.original.priority.severity)" variant="subtle">
                                {{ row.original.priority.name }}
                            </UBadge>
                            <span v-else class="text-muted">—</span>
                        </template>

                        <template #due_date-cell="{ row }">
                            <span class="tabular-nums" :class="{ 'text-error': dueInfo(row.original.due_date).color === 'error' }">
                                {{ dueInfo(row.original.due_date).label }}
                            </span>
                        </template>

                        <template #empty>
                            <p class="text-center text-sm text-muted">No projects yet</p>
                        </template>
                    </UTable>
                </UCard>

                <UCard title="Needs attention" description="Unfinished tasks with the nearest due dates.">
                    <template #footer>
                        <UButton
                            :to="route('task.index')"
                            label="All tasks"
                            trailing-icon="i-lucide-arrow-right"
                            variant="link"
                            size="sm"
                            class="-mx-2"
                        />
                    </template>

                    <UTable :data="attention" :columns="taskColumns">
                        <template #title-cell="{ row }">
                            <ULink :to="route('task.show', String(row.original.id))" class="truncate font-medium">
                                {{ row.original.title }}
                            </ULink>
                        </template>

                        <template #status-cell="{ row }">
                            <UBadge v-if="row.original.status" :color="severityColor(row.original.status.severity)" variant="subtle">
                                {{ row.original.status.name }}
                            </UBadge>
                            <span v-else class="text-muted">—</span>
                        </template>

                        <template #priority-cell="{ row }">
                            <UBadge v-if="row.original.priority" :color="severityColor(row.original.priority.severity)" variant="subtle">
                                {{ row.original.priority.name }}
                            </UBadge>
                            <span v-else class="text-muted">—</span>
                        </template>

                        <template #empty>
                            <p class="text-center text-sm text-muted">Nothing urgent right now</p>
                        </template>
                    </UTable>
                </UCard>
            </div>
        </div>
    </AppLayout>
</template>
