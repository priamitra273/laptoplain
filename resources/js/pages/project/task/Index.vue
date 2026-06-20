<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import TaskPriorityIcon from '@/components/TaskPriorityIcon.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { severityClasses } from '@/lib/severity';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import moment from 'moment';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';
import { computed, ref, watch } from 'vue';
import TaskKanban from './partials/TaskKanban.vue';
import { Task, TaskPriority, TaskStatus, TaskType } from './type';

interface Props {
    tasks: Task[];
    projects: { id: string; title: string }[];
    statuses: TaskStatus[];
    priorities: TaskPriority[];
    types: TaskType[];
    totalAssigned?: number;
}

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
    projects: () => [],
    statuses: () => [],
    priorities: () => [],
    types: () => [],
    totalAssigned: 0,
});

const user = usePage().props.auth.user;

const tasksData = ref<Task[]>(JSON.parse(JSON.stringify(props.tasks)));
const filteredTasks = ref<Task[]>([...tasksData.value]);
const projectsData = ref<{ id: string; title: string }[]>([]);
const totalAssigned = ref<number>(props.totalAssigned || 0);

// Pagination
const rows = ref<number>(10);
const first = ref<number>(0);

// Search & filter
const searchQuery = ref<string>('');
const filterStatus = ref<TaskStatus | null>(null);
const filterPriority = ref<TaskPriority | null>(null);
const filterType = ref<TaskType | null>(null);
const filterProject = ref<{ id: string; title: string } | null>(null);

// View mode
const viewMode = ref<'list' | 'board'>('board');

const viewModeOptions = [
    { icon: 'pi pi-th-large', label: 'Board', value: 'board' },
    { icon: 'pi pi-list', label: 'List', value: 'list' },
];

const hasActiveFilters = computed(
    () => !!(searchQuery.value || filterProject.value || filterStatus.value || filterPriority.value || filterType.value),
);

const searchProjects = (event: { query: string }) => {
    const query = event.query.toLowerCase();
    projectsData.value = props.projects.filter((project) => project.title.toLowerCase().includes(query));
};

const applyFilters = () => {
    let result = [...tasksData.value];

    if (searchQuery.value) {
        result = result.filter((task) => task.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }
    if (filterProject.value) {
        result = result.filter((task) => task.project?.id == filterProject.value?.id);
    }
    if (filterStatus.value) {
        result = result.filter((task) => task.status?.id === filterStatus.value?.id);
    }
    if (filterPriority.value) {
        result = result.filter((task) => task.priority?.id === filterPriority.value?.id);
    }
    if (filterType.value) {
        result = result.filter((task) => task.type?.id === filterType.value?.id);
    }

    filteredTasks.value = result;
    first.value = 0;
};

watch([searchQuery, filterStatus, filterPriority, filterType, filterProject], applyFilters);

const clearFilters = () => {
    searchQuery.value = '';
    filterStatus.value = null;
    filterPriority.value = null;
    filterType.value = null;
    filterProject.value = null;
};

const statusSummary = computed(() =>
    props.statuses
        .map((status) => ({
            id: status.id,
            name: status.name,
            severity: status.severity,
            count: tasksData.value.filter((task) => task.status?.id === status.id).length,
        }))
        .filter((entry) => entry.count > 0),
);

const dotClass = (severity?: string) => (severityClasses[severity ?? 'default'] ?? severityClasses.default).dot;

const truncateText = (text: string, maxLength = 50) => {
    if (!text) {
        return '';
    }
    return text.length > maxLength ? `${text.substring(0, maxLength)}…` : text;
};

const formatDueDate = (date?: string) => {
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

const totalText = computed(() => `${filteredTasks.value.length} of ${totalAssigned.value} assignments`);

const onStatusUpdate = (taskId: string, newStatusId: string) => {
    const task = tasksData.value.find((item) => item.id === taskId);
    if (!task) {
        return;
    }
    const newStatus = props.statuses.find((status) => status.id === newStatusId);
    if (newStatus) {
        task.status = newStatus;
    }
};
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
                        :suggestions="projectsData"
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
                    v-if="statusSummary.length"
                    class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-surface-200 pt-3 dark:border-surface-700"
                >
                    <div v-for="entry in statusSummary" :key="entry.id" class="flex items-center gap-1.5 text-sm">
                        <span class="h-2 w-2 shrink-0 rounded-full" :class="dotClass(entry.severity)" />
                        <span class="text-surface-600 dark:text-surface-300">{{ entry.name }}</span>
                        <span class="font-semibold tabular-nums text-surface-900 dark:text-surface-100">{{ entry.count }}</span>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="card !mb-0">
                <Transition name="view-fade" mode="out-in">
                    <!-- Empty state -->
                    <div v-if="filteredTasks.length === 0" key="empty" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
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
                        <TaskKanban :tasks="filteredTasks" :statuses="statuses" @status-update="onStatusUpdate" />
                    </div>

                    <!-- List view -->
                    <div v-else key="list" class="overflow-x-auto">
                        <DataTable
                            :value="filteredTasks"
                            dataKey="id"
                            paginator
                            :rows="rows"
                            :first="first"
                            rowHover
                            class="p-datatable-sm cursor-pointer"
                            @page="
                                (event) => {
                                    first = event.first;
                                    rows = event.rows;
                                }
                            "
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
                                            :href="route('project.show', { encoded: task.project.id })"
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
