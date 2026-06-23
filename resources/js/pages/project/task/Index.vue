<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import TaskPriorityIcon from '@/components/TaskPriorityIcon.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { severityClasses } from '@/lib/severity';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, ref, watch } from 'vue';
import TaskKanban from './partials/TaskKanban.vue';
import TaskKanbanSkeleton from './partials/TaskKanbanSkeleton.vue';
import TaskListSkeleton from './partials/TaskListSkeleton.vue';

type AssignedTask = App.Data.Task.AssignedTaskData;
type ProjectOption = App.Data.Project.ProjectOptionData;
type TaskStatusOption = App.Data.Task.TaskStatusData;
type TaskPriorityOption = App.Data.Task.TaskPriorityData;
type TaskTypeOption = App.Data.Task.TaskTypeData;

interface Paginator<T> {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
}

interface SummaryEntry {
    id: string;
    name: string;
    severity: string | null;
    count: number;
}

interface Filters {
    search: string | null;
    project_id: string | null;
    status_id: string | null;
    priority_id: string | null;
    type_id: string | null;
    per_page: number;
}

interface BoardColumn {
    status: TaskStatusOption;
    tasks: AssignedTask[];
    total: number;
    has_more: boolean;
}

interface Props {
    view: 'board' | 'list';
    filters: Filters;
    statuses: TaskStatusOption[];
    priorities: TaskPriorityOption[];
    types: TaskTypeOption[];
    projects: ProjectOption[];
    summary: SummaryEntry[];
    board?: BoardColumn[];
    tasks?: Paginator<AssignedTask>;
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

// View mode (server-driven)
const viewMode = ref<'board' | 'list'>(props.view);

// Filter controls — hydrated from the echoed server filters.
const searchQuery = ref<string>(props.filters.search ?? '');
const filterProject = ref<ProjectOption | null>(props.projects.find((p) => p.id === props.filters.project_id) ?? null);
const filterStatus = ref<TaskStatusOption | null>(props.statuses.find((s) => s.id === props.filters.status_id) ?? null);
const filterPriority = ref<TaskPriorityOption | null>(props.priorities.find((p) => p.id === props.filters.priority_id) ?? null);
const filterType = ref<TaskTypeOption | null>(props.types.find((t) => t.id === props.filters.type_id) ?? null);
const perPage = ref<number>(props.filters.per_page ?? 25);

const projectSuggestions = ref<ProjectOption[]>([]);

const viewModeOptions = [
    { icon: 'pi pi-th-large', label: 'Board', value: 'board' },
    { icon: 'pi pi-list', label: 'List', value: 'list' },
];

const hasActiveFilters = computed(
    () => !!(searchQuery.value || filterProject.value || filterStatus.value || filterPriority.value || filterType.value),
);

const boardTotal = computed(() => (props.board ?? []).reduce((sum, column) => sum + column.total, 0));

const isEmpty = computed(() => (viewMode.value === 'list' ? (props.tasks?.data.length ?? 0) === 0 : boardTotal.value === 0));

const totalText = computed(() => {
    const total = viewMode.value === 'list' ? (props.tasks?.total ?? 0) : boardTotal.value;
    return `${total} assignment${total === 1 ? '' : 's'}`;
});

// Active filters (encoded) forwarded to the board's per-column "Load more" requests.
const boardFilterParams = computed<Record<string, string>>(() => {
    const params: Record<string, string> = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterProject.value) params.project_id = filterProject.value.id;
    if (filterPriority.value) params.priority_id = filterPriority.value.id;
    if (filterType.value) params.type_id = filterType.value.id;

    return params;
});

const searchProjects = (event: { query: string }) => {
    const query = event.query.toLowerCase();
    projectSuggestions.value = props.projects.filter((project) => project.title.toLowerCase().includes(query));
};

// ── Server-driven reload ─────────────────────────────────────────────────
let suppressReload = false;
let searchTimer: ReturnType<typeof setTimeout> | null = null;
const reloading = ref(false);

const buildQuery = (): Record<string, string | number> => {
    const query: Record<string, string | number> = { view: viewMode.value };

    if (searchQuery.value) query.search = searchQuery.value;
    if (filterProject.value) query.project_id = filterProject.value.id;
    if (filterStatus.value) query.status_id = filterStatus.value.id;
    if (filterPriority.value) query.priority_id = filterPriority.value.id;
    if (filterType.value) query.type_id = filterType.value.id;
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
    filterProject.value = null;
    filterStatus.value = null;
    filterPriority.value = null;
    filterType.value = null;
    suppressReload = false;
    reload();
};

const onPage = (event: { page: number; rows: number }) => {
    perPage.value = event.rows;
    reload({ page: event.page + 1, per_page: event.rows });
};

const dotClass = (severity?: string | null) => (severityClasses[severity ?? 'default'] ?? severityClasses.default).dot;

const truncateText = (text: string, maxLength = 50) => {
    if (!text) {
        return '';
    }
    return text.length > maxLength ? `${text.substring(0, maxLength)}…` : text;
};

const formatDueDate = (date?: string | null) => {
    if (!date) {
        return '—';
    }
    const due = moment(date);
    const formatted = due.format('DD MMM');
    const diffDays = due.startOf('day').diff(moment().startOf('day'), 'days');

    if (diffDays < 0) {
        return `${formatted} · Overdue`;
    }
    if (diffDays === 0) {
        return `${formatted} · Today`;
    }
    if (diffDays === 1) {
        return `${formatted} · Tomorrow`;
    }
    return formatted;
};

const onStatusUpdate = () => {
    // Refresh the per-status summary chips after a drag-drop move.
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
</script>

<template>
    <Head title="Tasks" />
    <AppLayout>
        <div class="flex flex-col gap-5">
            <!-- Page header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Heading title="My Task" :description="`Manage and track your work items — ${user?.name ?? 'User'}`" class="!mb-0" />

                <SelectButton
                    v-model="viewMode"
                    :options="viewModeOptions"
                    optionLabel="label"
                    optionValue="value"
                    dataKey="value"
                    :allowEmpty="false"
                    aria-label="Switch view"
                    class="shrink-0 self-start sm:self-auto"
                >
                    <template #option="{ option }">
                        <i :class="option.icon" />
                        <span class="ml-2 hidden text-sm font-medium sm:inline">{{ option.label }}</span>
                    </template>
                </SelectButton>
            </div>

            <!-- Toolbar: search, filters, status summary -->
            <div class="flex flex-col gap-3">
                <!-- Search + count -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <IconField class="w-full sm:max-w-sm">
                        <InputText v-model="searchQuery" placeholder="Search assignments..." class="w-full" />
                        <InputIcon class="pi pi-search" />
                    </IconField>
                    <span class="text-sm text-surface-500 sm:ml-auto dark:text-surface-400">{{ totalText }}</span>
                </div>

                <!-- Filters -->
                <div class="flex flex-col flex-wrap gap-2 sm:flex-row sm:items-center">
                    <AutoComplete
                        v-model="filterProject"
                        :suggestions="projectSuggestions"
                        optionLabel="title"
                        placeholder="Project"
                        dropdown
                        class="w-full sm:w-auto sm:min-w-[16rem] lg:min-w-[20rem]"
                        @complete="searchProjects"
                    />
                    <Select
                        v-model="filterStatus"
                        :options="statuses"
                        optionLabel="name"
                        placeholder="Status"
                        :showClear="true"
                        class="w-full sm:w-44"
                    >
                        <template #value="{ value, placeholder }">
                            <Tag v-if="value" :value="value.name" :severity="value.severity" />
                            <span v-else>{{ placeholder }}</span>
                        </template>
                        <template #option="{ option }">
                            <Tag :value="option.name" :severity="option.severity" />
                        </template>
                    </Select>
                    <Select
                        v-model="filterPriority"
                        :options="priorities"
                        optionLabel="name"
                        placeholder="Priority"
                        :showClear="true"
                        class="w-full sm:w-44"
                    >
                        <template #value="{ value, placeholder }">
                            <Tag v-if="value" :value="value.name" :severity="value.severity" />
                            <span v-else>{{ placeholder }}</span>
                        </template>
                        <template #option="{ option }">
                            <Tag :value="option.name" :severity="option.severity" />
                        </template>
                    </Select>
                    <Select v-model="filterType" :options="types" optionLabel="name" placeholder="Type" :showClear="true" class="w-full sm:w-44">
                        <template #value="{ value, placeholder }">
                            <Tag v-if="value" :value="value.name" :severity="value.severity" />
                            <span v-else>{{ placeholder }}</span>
                        </template>
                        <template #option="{ option }">
                            <Tag :value="option.name" :severity="option.severity" />
                        </template>
                    </Select>

                    <Button
                        v-if="hasActiveFilters"
                        label="Clear"
                        icon="pi pi-filter-slash"
                        text
                        severity="secondary"
                        size="small"
                        class="w-full sm:ml-auto sm:w-auto"
                        @click="clearFilters"
                    />
                </div>

                <!-- Status summary -->
                <div
                    v-if="summary.length"
                    class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-surface-200 pt-3 dark:border-surface-700"
                >
                    <div v-for="entry in summary" :key="entry.id" class="flex items-center gap-1.5 text-sm">
                        <span class="h-2 w-2 shrink-0 rounded-full" :class="dotClass(entry.severity)" />
                        <span class="text-surface-600 dark:text-surface-300">{{ entry.name }}</span>
                        <span class="font-semibold tabular-nums text-surface-900 dark:text-surface-100">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="card !mb-0">
                <Transition name="view-fade" mode="out-in">
                    <!-- Loading skeleton (selama reload server-driven) -->
                    <div v-if="reloading" key="loading">
                        <TaskKanbanSkeleton v-if="viewMode === 'board'" :columns="statuses.length || 4" />
                        <TaskListSkeleton v-else />
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="isEmpty" key="empty" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-surface-100 dark:bg-surface-800">
                            <i class="pi pi-inbox text-2xl text-surface-400 dark:text-surface-500" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-base font-medium text-surface-700 dark:text-surface-200">
                                {{ hasActiveFilters ? 'No matching tasks' : 'No tasks yet' }}
                            </p>
                            <p class="text-sm text-surface-500 dark:text-surface-400">
                                {{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'Tasks assigned to you will appear here.' }}
                            </p>
                        </div>
                        <Button v-if="hasActiveFilters" label="Clear filters" icon="pi pi-filter-slash" text size="small" @click="clearFilters" />
                    </div>

                    <!-- Board view -->
                    <div v-else-if="viewMode === 'board'" key="board">
                        <TaskKanban :columns="board ?? []" :filter-params="boardFilterParams" @status-update="onStatusUpdate" />
                    </div>

                    <!-- List view -->
                    <div v-else key="list" class="overflow-x-auto">
                        <DataTable
                            :value="tasks?.data ?? []"
                            dataKey="id"
                            lazy
                            paginator
                            :rows="tasks?.per_page ?? perPage"
                            :totalRecords="tasks?.total ?? 0"
                            :first="((tasks?.current_page ?? 1) - 1) * (tasks?.per_page ?? perPage)"
                            rowHover
                            class="p-datatable-sm cursor-pointer"
                            @page="onPage"
                            @row-click="(event) => router.get(route('task.show', event.data.id))"
                        >
                            <Column header="Task" style="width: 40%">
                                <template #body="{ data: task }">
                                    <div class="flex flex-col gap-0.5">
                                        <Link
                                            :href="route('task.show', task.id)"
                                            class="font-medium text-surface-900 hover:text-primary-600 hover:underline dark:text-surface-50 dark:hover:text-primary-400"
                                            :title="task.title"
                                            @click.stop
                                        >
                                            {{ truncateText(task.title, 60) }}
                                        </Link>
                                        <Link
                                            v-if="task.project"
                                            :href="route('project.show.kanban', { encoded: task.project.id })"
                                            class="inline-flex w-fit items-center gap-1 text-xs text-surface-500 hover:text-primary-600 dark:text-surface-400 dark:hover:text-primary-400"
                                            :title="task.project.title"
                                            @click.stop
                                        >
                                            <i class="pi pi-folder text-[10px]" />
                                            <span>{{ truncateText(task.project.title, 28) }}</span>
                                        </Link>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Status" style="width: 14%">
                                <template #body="{ data: task }">
                                    <Tag v-if="task.status" :value="task.status.name" :severity="task.status.severity" />
                                </template>
                            </Column>

                            <Column header="Priority" style="width: 16%">
                                <template #body="{ data: task }">
                                    <div v-if="task.priority" class="flex items-center gap-2">
                                        <TaskPriorityIcon :priority="task.priority" />
                                        <span class="text-sm text-surface-700 dark:text-surface-300">{{ task.priority.name }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Type" style="width: 14%">
                                <template #body="{ data: task }">
                                    <Tag v-if="task.type" :value="task.type.name" :severity="task.type.severity" />
                                </template>
                            </Column>

                            <Column header="Due date" style="width: 16%">
                                <template #body="{ data: task }">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-sm"
                                        :class="
                                            task.is_overdue
                                                ? 'font-medium text-rose-600 dark:text-rose-400'
                                                : 'text-surface-600 dark:text-surface-300'
                                        "
                                    >
                                        <i v-if="task.due_date" class="pi pi-calendar text-xs" />
                                        {{ formatDueDate(task.due_date) }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </Transition>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.view-fade-enter-active,
.view-fade-leave-active {
    transition: opacity 0.15s ease;
}

.view-fade-enter-from,
.view-fade-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .view-fade-enter-active,
    .view-fade-leave-active {
        transition: none;
    }
}
</style>
