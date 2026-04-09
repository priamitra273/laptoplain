<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, onMounted, ref, watch } from 'vue';
import TaskKanban from './partials/TaskKanban.vue';
import { Task, TaskPriorityOption, TaskStatusOption, TaskTypeOption } from './type';

interface Props {
    tasks: Task[];
    projects: { id: string; title: string }[];
    statuses: TaskStatusOption[];
    priorities: TaskPriorityOption[];
    types: TaskTypeOption[];
    totalAssigned?: number;
}

const user = usePage().props.auth.user;

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
    totalAssigned: 0,
});

const tasksData = ref<Task[]>([]);
const projectsData = ref<{ id: string; title: string }[]>([]);
const filteredTasks = ref<Task[]>([]);
const totalAssigned = ref<number>(props.totalAssigned || 0);

// Pagination
const rows = ref<number>(6);
const first = ref<number>(0);

// Search & Filter
const searchQuery = ref<string>('');
const filterStatus = ref<TaskStatusOption | null>(null);
const filterPriority = ref<TaskPriorityOption | null>(null);
const filterType = ref<TaskTypeOption | null>(null);
const filterProject = ref<{ id: string; title: string } | null>(null);

// View mode
const viewMode = ref<'list' | 'board'>('board');

const viewModeOptions = [
    { icon: 'pi pi-th-large', label: 'Board', value: 'board' },
    { icon: 'pi pi-list', label: 'List', value: 'list' },
];

// Truncate text helper
const truncateText = (text: string, maxLength: number = 50) => {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

// Format tanggal
const formatDueDate = (date?: string) => {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const diffTime = d.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    const formatted = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });

    if (diffDays < 0) return `${formatted} (Overdue)`;
    if (diffDays === 0) return `${formatted} (Today)`;
    if (diffDays === 1) return `${formatted} (Tomorrow)`;
    return formatted;
};

const isOverdue = (date?: string) => {
    if (!date) return false;
    const d = new Date(date);
    const today = new Date();
    return d < today;
};

const searchProjects = (event: { query: string }) => {
    const query = event.query.toLowerCase();
    projectsData.value = props.projects.filter((p) => p.title.toLowerCase().includes(query));
};

onMounted(() => {
    tasksData.value = JSON.parse(JSON.stringify(props.tasks));
    applyFilters();
});

// Watch filters
watch([searchQuery, filterStatus, filterPriority, filterType, filterProject], () => {
    applyFilters();
});

const applyFilters = () => {
    let result = [...tasksData.value];

    // Search filter
    if (searchQuery.value) {
        result = result.filter((task) => task.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }

    // Project filter
    if (filterProject.value) {
        result = result.filter((task) => task.project?.id == filterProject.value?.id);
    }

    // Status filter
    if (filterStatus.value) {
        result = result.filter((task) => task.status?.id === filterStatus.value?.id);
    }

    // Priority filter
    if (filterPriority.value) {
        result = result.filter((task) => task.priority?.id === filterPriority.value?.id);
    }

    // Type filter
    if (filterType.value) {
        result = result.filter((task) => task.type?.id === filterType.value?.id);
    }

    filteredTasks.value = result;
    first.value = 0;
};

const statusSummary = computed(() => {
    const summary: Record<string, { count: number; severity?: string }> = {};
    tasksData.value.forEach((task) => {
        if (task.status?.name) {
            if (!summary[task.status.name]) {
                summary[task.status.name] = { count: 1, severity: task.status.severity };
            } else {
                summary[task.status.name].count += 1;
            }
        }
    });
    return summary;
});

const getPriorityIcon = (priority?: { name: string }) => {
    if (!priority) return '';
    const name = priority.name.toLowerCase();
    if (name.includes('high') || name.includes('urgent')) return 'pi-arrow-up';
    if (name.includes('low')) return 'pi-arrow-down';
    return 'pi-minus';
};

const clearFilters = () => {
    searchQuery.value = '';
    filterStatus.value = null;
    filterPriority.value = null;
    filterType.value = null;
    filterProject.value = null;
};

const totalText = computed(() => `${filteredTasks.value.length} of ${totalAssigned.value} assignments`);

const onStatusUpdate = (taskId: string, newStatusId: string) => {
    const task = tasksData.value.find((t) => t.id === taskId);

    if (!task) return;

    const newStatus = props.statuses.find((s) => s.id === newStatusId);

    if (newStatus) {
        task.status = newStatus;
    }
};
</script>

<template>
    <Head title="Tasks" />
    <AppLayout>
        <div class="space-y-6 p-4">
            <Heading title="My Task" :description="`Manage and track your work items - ${user?.name ?? 'User'}`" />
            <div class="flex flex-col gap-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <!-- Toolbar -->
                <div class="flex flex-col gap-3">
                    <!-- Row 1: Search + View Mode -->
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <InputText v-model="searchQuery" placeholder="Search assignments..." class="w-full pl-9" />
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <span class="hidden whitespace-nowrap text-sm text-gray-500 sm:block dark:text-gray-400">
                                {{ totalText }}
                            </span>
                            <SelectButton
                                v-model="viewMode"
                                :options="viewModeOptions"
                                option-label="value"
                                option-value="value"
                                data-key="value"
                                aria-labelledby="custom"
                                :allow-empty="false"
                            >
                                <template #option="slotProps">
                                    <i :class="slotProps.option.icon"></i>
                                </template>
                            </SelectButton>
                        </div>
                    </div>

                    <!-- Row 2: Filters -->
                    <div class="flex flex-wrap items-center gap-2">
                        <AutoComplete
                            v-model="filterProject"
                            :suggestions="projectsData"
                            @complete="searchProjects"
                            @change="applyFilters"
                            optionLabel="title"
                            placeholder="Project"
                            dropdown
                            class="lg:w-96"
                        />
                        <Select v-model="filterStatus" :options="statuses" optionLabel="name" placeholder="Status" :showClear="true" class="w-48">
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <Tag :value="slotProps.value.name" :severity="slotProps.value.severity" />
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                            </template>
                        </Select>
                        <Select
                            v-model="filterPriority"
                            :options="priorities"
                            optionLabel="name"
                            placeholder="Priority"
                            :showClear="true"
                            class="w-48"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <Tag :value="slotProps.value.name" :severity="slotProps.value.severity" />
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                            </template>
                        </Select>
                        <Select v-model="filterType" :options="types" optionLabel="name" placeholder="Type" :showClear="true" class="w-48">
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <Tag :value="slotProps.value.name" :severity="slotProps.value.severity" />
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                            </template>
                        </Select>

                        <!-- Total (mobile only) -->
                        <span class="ml-auto text-sm text-gray-500 sm:hidden dark:text-gray-400">
                            {{ totalText }}
                        </span>

                        <!-- Clear Button -->
                        <Button
                            v-if="searchQuery || filterProject || filterStatus || filterPriority || filterType"
                            label="Clear"
                            icon="pi pi-filter-slash"
                            text
                            severity="secondary"
                            size="small"
                            class="ml-auto"
                            @click="clearFilters"
                        />
                    </div>
                </div>

                <!-- Status Summary -->
                <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-4 dark:border-gray-700">
                    <Tag
                        v-for="(data, status) in statusSummary"
                        :key="status"
                        :value="`${status} (${data.count})`"
                        :severity="data.severity"
                        class="px-3 py-1"
                    />
                </div>

                <!-- LIST VIEW -->
                <div v-if="viewMode === 'list'" class="overflow-x-auto">
                    <DataTable
                        v-if="filteredTasks.length > 0"
                        :value="filteredTasks"
                        data-key="id"
                        paginator
                        :rows="rows"
                        :first="first"
                        @page="
                            (e) => {
                                first = e.first;
                                rows = e.rows;
                            }
                        "
                        row-hover
                        class="p-datatable-sm cursor-pointer"
                        @row-click="(e) => router.get(route('task.show', { encoded: e.data.id }))"
                    >
                        <!-- SUMMARY -->
                        <Column header="Summary" style="width: 35%">
                            <template #body="{ data: task }">
                                <div class="flex flex-col gap-1">
                                    <Link :href="route('task.show', { encoded: task.id })" @click.stop>
                                        <span class="font-medium text-gray-900 hover:underline dark:text-white" :title="task.title">
                                            {{ truncateText(task.title, 50) }}
                                        </span>
                                    </Link>

                                    <div v-if="task.project" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                        <i class="pi pi-folder flex-shrink-0 text-xs"></i>
                                        <Link
                                            :href="route('project.show', { encoded: task.project.id })"
                                            class="hover:text-blue-600"
                                            @click.stop
                                            :title="task.project.title"
                                        >
                                            {{ truncateText(task.project.title, 20) }}
                                        </Link>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- STATUS -->
                        <Column header="Status" style="width: 15%">
                            <template #body="{ data: task }">
                                <Tag v-if="task.status" :value="task.status.name" :severity="task.status.severity" class="text-xs" />
                            </template>
                        </Column>

                        <!-- PRIORITY -->
                        <Column header="Priority" style="width: 15%">
                            <template #body="{ data: task }">
                                <div v-if="task.priority" class="flex items-center gap-2">
                                    <i
                                        :class="[
                                            'pi',
                                            getPriorityIcon(task.priority),
                                            task.priority.severity === 'danger'
                                                ? 'text-red-500'
                                                : task.priority.severity === 'warning'
                                                  ? 'text-yellow-500'
                                                  : 'text-gray-500',
                                        ]"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ task.priority.name }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <!-- TYPE -->
                        <Column header="Type" style="width: 15%">
                            <template #body="{ data: task }">
                                <Tag v-if="task.type" :value="task.type.name" :severity="task.type.severity" class="text-xs" />
                            </template>
                        </Column>

                        <!-- DUE DATE -->
                        <Column header="Due Date" style="width: 20%">
                            <template #body="{ data: task }">
                                <span
                                    :class="[
                                        'text-sm',
                                        isOverdue(task.due_date) ? 'font-medium text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400',
                                    ]"
                                >
                                    {{ formatDueDate(task.due_date) }}
                                </span>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Empty State -->
                    <div v-else class="py-12 text-center">
                        <i class="pi pi-inbox mb-4 text-5xl text-gray-300 dark:text-gray-600"></i>
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No tasks found</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">Try adjusting your filters or search query</p>
                    </div>
                </div>

                <!-- BOARD VIEW -->
                <div v-else>
                    <template v-if="filteredTasks.length > 0">
                        <TaskKanban :tasks="filteredTasks" :statuses="props.statuses" @status-update="onStatusUpdate" />
                    </template>

                    <!-- Empty State -->
                    <div v-else class="col-span-full py-12 text-center">
                        <i class="pi pi-inbox mb-4 text-5xl text-gray-300 dark:text-gray-600"></i>
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No tasks found</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">Try adjusting your filters or search query</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
