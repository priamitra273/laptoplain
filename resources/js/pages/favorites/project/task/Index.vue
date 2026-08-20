<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { severityColor } from '@/lib/utils';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import TaskBoard from './Board.vue';
import TaskTable from './Table.vue';
import type {
    AssignedTask,
    TaskBoardColumn,
    TaskFilters,
    TaskPaginator,
    TaskPriorityOption,
    TaskProjectOption,
    TaskStatusOption,
    TaskStatusSummaryEntry,
    TaskTypeOption,
} from './types';

interface Props {
    view: 'board' | 'list';
    filters: TaskFilters;
    statuses: TaskStatusOption[];
    priorities: TaskPriorityOption[];
    types: TaskTypeOption[];
    projects: TaskProjectOption[];
    summary: TaskStatusSummaryEntry[];
    board?: TaskBoardColumn[];
    tasks?: TaskPaginator<AssignedTask>;
}

const props = withDefaults(defineProps<Props>(), {
    statuses: () => [],
    priorities: () => [],
    types: () => [],
    projects: () => [],
    summary: () => [],
    board: () => [],
    tasks: undefined,
});

const user = usePage().props.auth.user;

const viewMode = ref<'board' | 'list'>(props.view);
const viewModeItems = [
    { label: 'Board', icon: 'i-lucide-layout-grid', value: 'board' },
    { label: 'List', icon: 'i-lucide-list', value: 'list' },
];

const searchQuery = ref(props.filters.search ?? '');
const filterProject = ref<string | undefined>(props.filters.project_id ?? undefined);
const filterStatus = ref<string | undefined>(props.filters.status_id ?? undefined);
const filterPriority = ref<string | undefined>(props.filters.priority_id ?? undefined);
const filterType = ref<string | undefined>(props.filters.type_id ?? undefined);
const perPage = ref(props.filters.per_page ?? 25);
const pageSizes = [10, 25, 50, 100];

const hasActiveFilters = computed(
    () => !!(searchQuery.value || filterProject.value || filterStatus.value || filterPriority.value || filterType.value),
);

const boardTotal = computed(() => (props.board ?? []).reduce((sum, column) => sum + column.total, 0));

const isEmpty = computed(() => (viewMode.value === 'list' ? (props.tasks?.data.length ?? 0) === 0 : boardTotal.value === 0));

const totalText = computed(() => {
    const total = viewMode.value === 'list' ? (props.tasks?.total ?? 0) : boardTotal.value;
    return `${total} assignment${total === 1 ? '' : 's'}`;
});

const boardFilterParams = computed<Record<string, string>>(() => {
    const params: Record<string, string> = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterProject.value) params.project_id = filterProject.value;
    if (filterPriority.value) params.priority_id = filterPriority.value;
    if (filterType.value) params.type_id = filterType.value;
    return params;
});

let suppressReload = false;
let searchTimer: ReturnType<typeof setTimeout> | null = null;
const reloading = ref(false);

const buildQuery = (): Record<string, string | number> => {
    const query: Record<string, string | number> = { view: viewMode.value };

    if (searchQuery.value) query.search = searchQuery.value;
    if (filterProject.value) query.project_id = filterProject.value;
    if (filterStatus.value) query.status_id = filterStatus.value;
    if (filterPriority.value) query.priority_id = filterPriority.value;
    if (filterType.value) query.type_id = filterType.value;
    if (viewMode.value === 'list') query.per_page = perPage.value;

    return query;
};

const reload = (extra: Record<string, string | number> = {}) => {
    router.get(
        route('task.index'),
        { ...buildQuery(), ...extra },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['tasks', 'board', 'summary', 'filters', 'view'],
            showProgress: false,
            onStart: () => {
                reloading.value = true;
            },
            onFinish: () => {
                reloading.value = false;
            },
        },
    );
};

const clearFilters = () => {
    suppressReload = true;
    searchQuery.value = '';
    filterProject.value = undefined;
    filterStatus.value = undefined;
    filterPriority.value = undefined;
    filterType.value = undefined;
    suppressReload = false;
    reload();
};

const toggleStatusChip = (statusId: string) => {
    filterStatus.value = filterStatus.value === statusId ? undefined : statusId;
};

const onStatusUpdate = () => {
    router.reload({ only: ['summary'] });
};

watch([filterProject, filterStatus, filterPriority, filterType], () => {
    if (suppressReload) return;
    reload();
});

watch(searchQuery, () => {
    if (suppressReload) return;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => reload(), 300);
});

watch(viewMode, () => {
    if (suppressReload) return;
    reload();
});

const page = computed({
    get: () => props.tasks?.current_page ?? 1,
    set: (value: number) => reload({ page: value }),
});

const pageSize = computed({
    get: () => props.tasks?.per_page ?? perPage.value,
    set: (value: number) => {
        perPage.value = value;
        reload({ page: 1, per_page: value });
    },
});
</script>

<template>
    <Head title="My Task" />

    <AppLayout title="My Task">
        <div class="flex flex-col gap-5">
            <div class="flex items-center justify-between gap-3">
                <Heading title="My Task" :description="`Manage and track your work items — ${user?.name ?? 'User'}`" />
                <UTabs v-model="viewMode" :items="viewModeItems" :content="false" class="w-fit shrink-0" />
            </div>

            <div class="flex flex-col gap-3">
                <div class="flex items-center gap-3">
                    <UInput v-model="searchQuery" icon="i-lucide-search" placeholder="Search assignments..." class="max-w-sm min-w-0 flex-1" />
                    <span class="ml-auto shrink-0 text-sm whitespace-nowrap text-muted">{{ totalText }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <USelectMenu v-model="filterProject" :items="projects" label-key="title" value-key="id" placeholder="Project" class="w-56" />

                    <USelectMenu v-model="filterStatus" :items="statuses" label-key="name" value-key="id" placeholder="Status" class="w-44">
                        <template #item-label="{ item }">
                            <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                        </template>
                    </USelectMenu>

                    <USelectMenu v-model="filterPriority" :items="priorities" label-key="name" value-key="id" placeholder="Priority" class="w-44">
                        <template #item-label="{ item }">
                            <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                        </template>
                    </USelectMenu>

                    <USelectMenu v-model="filterType" :items="types" label-key="name" value-key="id" placeholder="Type" class="w-44">
                        <template #item-label="{ item }">
                            <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                        </template>
                    </USelectMenu>

                    <UButton
                        v-if="hasActiveFilters"
                        label="Clear"
                        icon="i-lucide-filter-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        class="ml-auto"
                        @click="clearFilters"
                    />
                </div>

                <div v-if="summary.length" class="flex flex-wrap items-center gap-2 border-t border-default pt-3">
                    <UBadge
                        v-for="entry in summary"
                        :key="entry.id"
                        :color="severityColor(entry.severity)"
                        :variant="filterStatus === entry.id ? 'solid' : 'subtle'"
                        class="cursor-pointer"
                        @click="toggleStatusChip(entry.id)"
                    >
                        {{ entry.name }} · {{ entry.count }}
                    </UBadge>
                </div>
            </div>

            <div>
                <div v-if="reloading" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <USkeleton v-for="n in 8" :key="n" class="h-28 w-full" />
                </div>

                <div v-else-if="isEmpty" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                    <UIcon name="i-lucide-inbox" class="size-10 text-muted" />
                    <div class="space-y-1">
                        <p class="text-base font-medium">{{ hasActiveFilters ? 'No matching tasks' : 'No tasks yet' }}</p>
                        <p class="text-sm text-muted">
                            {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Tasks assigned to you will appear here.' }}
                        </p>
                    </div>
                    <UButton
                        v-if="hasActiveFilters"
                        label="Clear filters"
                        icon="i-lucide-filter-x"
                        color="neutral"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                    />
                </div>

                <TaskBoard
                    v-else-if="viewMode === 'board'"
                    :columns="board ?? []"
                    :filter-params="boardFilterParams"
                    @status-update="onStatusUpdate"
                />

                <div v-else class="flex flex-col gap-4">
                    <TaskTable :data="tasks?.data ?? []" />

                    <div class="flex items-center justify-center gap-3">
                        <USelect v-model="pageSize" :items="pageSizes" class="w-20" />
                        <UPagination v-model:page="page" :items-per-page="pageSize" :total="tasks?.total ?? 0" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
