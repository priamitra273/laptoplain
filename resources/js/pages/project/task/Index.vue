<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import { computed, onMounted, ref, watch } from 'vue';

interface TaskType {
    id: string;
    name: string;
    severity?: string;
}

interface Task {
    id: string;
    title: string;
    due_date?: string;
    project?: { id: string; title: string };
    status?: { id: string; name: string; severity?: string };
    priority?: { id: string; name: string; severity?: string };
    type?: TaskType;
    is_assigned?: boolean;
    is_created_by_me?: boolean;
}

interface Props {
    tasks: Task[];
    totalAssigned?: number;
}

const CurrentUser = usePage().props.auth.user;

const props = withDefaults(defineProps<Props>(), {
    tasks: () => [],
    totalAssigned: 0,
});

const tasksData = ref<Task[]>([]);
const filteredTasks = ref<Task[]>([]);
const totalAssigned = ref<number>(props.totalAssigned || 0);

// Pagination
const rows = ref<number>(6);
const first = ref<number>(0);

// Search & Filter
const searchQuery = ref<string>('');
const filterStatus = ref<string | null>(null);
const filterPriority = ref<string | null>(null);
const filterType = ref<string | null>(null);

// View mode
const viewMode = ref<'list' | 'board'>('list');

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

onMounted(() => {
    tasksData.value = JSON.parse(JSON.stringify(props.tasks));
    applyFilters();
});

// Watch filters
watch([searchQuery, filterStatus, filterPriority, filterType], () => {
    applyFilters();
});

const applyFilters = () => {
    let result = [...tasksData.value];

    // Search filter
    if (searchQuery.value) {
        result = result.filter((task) => task.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
    }

    // Status filter
    if (filterStatus.value) {
        result = result.filter((task) => task.status?.name === filterStatus.value);
    }

    // Priority filter
    if (filterPriority.value) {
        result = result.filter((task) => task.priority?.name === filterPriority.value);
    }

    // Type filter
    if (filterType.value) {
        result = result.filter((task) => task.type?.name === filterType.value);
    }

    filteredTasks.value = result;
    first.value = 0;
};

const statusOptions = computed(() => {
    const statuses = new Set(tasksData.value.map((t) => t.status?.name).filter(Boolean));
    return Array.from(statuses).map((s) => ({ label: s, value: s }));
});

const priorityOptions = computed(() => {
    const priorities = new Set(tasksData.value.map((t) => t.priority?.name).filter(Boolean));
    return Array.from(priorities).map((p) => ({ label: p, value: p }));
});

const typeOptions = computed(() => {
    const types = new Set(tasksData.value.map((t) => t.type?.name).filter(Boolean));
    return Array.from(types).map((t) => ({ label: t, value: t }));
});

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
};

const totalText = computed(() => `${filteredTasks.value.length} of ${totalAssigned.value} assignments`);
</script>

<template>
    <Head title="Tasks" />
    <AppLayout>
        <div class="p-4">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tasks</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage and track your work items - {{ CurrentUser?.name || 'User' }}</p>
            </div>

            <div class="flex flex-col gap-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <!-- Toolbar -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <!-- Left: Search & Filters -->
                    <div class="flex w-full flex-wrap items-center gap-3">
                        <!-- Search -->
                        <InputText v-model="searchQuery" placeholder="Search assignments..." class="min-w-[200px] flex-1 sm:w-80">
                            <template #prefix>
                                <i class="pi pi-search text-gray-400"></i>
                            </template>
                        </InputText>

                        <!-- Filters -->
                        <Dropdown
                            v-model="filterStatus"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Status"
                            :showClear="true"
                            class="w-36"
                        />
                        <Dropdown
                            v-model="filterPriority"
                            :options="priorityOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Priority"
                            :showClear="true"
                            class="w-36"
                        />
                        <Dropdown
                            v-model="filterType"
                            :options="typeOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Type"
                            :showClear="true"
                            class="w-36"
                        />

                        <!-- Clear Button -->
                        <Button
                            v-if="searchQuery || filterStatus || filterPriority || filterType"
                            label="Clear"
                            icon="pi pi-filter-slash"
                            text
                            class="ml-auto"
                            @click="clearFilters"
                        />
                    </div>

                    <!-- Right: View Mode & Total -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ totalText }}</span>

                        <div class="flex rounded-lg border border-gray-200 dark:border-gray-600">
                            <Button
                                icon="pi pi-list"
                                :class="viewMode === 'list' ? 'bg-gray-100 dark:bg-gray-700' : ''"
                                text
                                @click="viewMode = 'list'"
                            />
                            <Button
                                icon="pi pi-th-large"
                                :class="viewMode === 'board' ? 'bg-gray-100 dark:bg-gray-700' : ''"
                                text
                                @click="viewMode = 'board'"
                            />
                        </div>
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
                    <table v-if="filteredTasks.length > 0" class="w-full">
                        <thead class="border-b border-gray-200 dark:border-gray-700">
                            <tr class="text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">
                                <th class="w-full pb-3 pr-4">Summary</th>
                                <th class="pb-3 pr-4">Status</th>
                                <th class="pb-3 pr-4">Priority</th>
                                <th class="pb-3 pr-4">Type</th>
                                <th class="pb-3 pr-4">Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="task in filteredTasks.slice(first, first + rows)"
                                :key="task.id"
                                class="cursor-pointer border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                                @click="router.get(route('task.show', { encoded: task.id }))"
                            >
                                <!-- Title & Project -->
                                <td class="py-3 pr-4">
                                    <div class="flex max-w-md flex-col gap-1">
                                        <span class="truncate font-medium text-gray-900 dark:text-white">
                                            {{ task.title }}
                                        </span>
                                        <div v-if="task.project" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                            <i class="pi pi-folder flex-shrink-0 text-xs"></i>
                                            <Link
                                                :href="route('project.show', { encoded: task.project.id })"
                                                class="truncate hover:text-blue-600 hover:underline"
                                                @click.stop
                                            >
                                                {{ task.project.title }}
                                            </Link>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="py-3 pr-4">
                                    <Tag v-if="task.status" :value="task.status.name" :severity="task.status.severity" class="text-xs" />
                                </td>

                                <!-- Priority -->
                                <td class="py-3 pr-4">
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
                                        ></i>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ task.priority.name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="py-3 pr-4">
                                    <Tag v-if="task.type" :value="task.type.name" :severity="task.type.severity" class="text-xs" />
                                </td>

                                <!-- Due Date -->
                                <td class="py-3 pr-4">
                                    <span
                                        :class="[
                                            'text-sm',
                                            isOverdue(task.due_date)
                                                ? 'font-medium text-red-600 dark:text-red-400'
                                                : 'text-gray-600 dark:text-gray-400',
                                        ]"
                                    >
                                        {{ formatDueDate(task.due_date) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-else class="py-12 text-center">
                        <i class="pi pi-inbox mb-4 text-5xl text-gray-300 dark:text-gray-600"></i>
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No tasks found</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">Try adjusting your filters or search query</p>
                    </div>
                </div>

                <!-- BOARD VIEW -->
                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <template v-if="filteredTasks.length > 0">
                        <div
                            v-for="task in filteredTasks.slice(first, first + rows)"
                            :key="task.id"
                            class="group cursor-pointer rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                            @click="router.get(route('task.show', { encoded: task.id }))"
                        >
                            <!-- Header -->
                            <div class="mb-3 flex items-start justify-between">
                                <Tag v-if="task.status" :value="task.status.name" :severity="task.status.severity" class="text-xs" />
                            </div>

                            <!-- Title -->
                            <h3
                                class="mb-2 block w-full overflow-hidden truncate text-ellipsis text-base font-semibold text-gray-900 dark:text-white"
                            >
                                {{ task.title }}
                            </h3>

                            <!-- Project -->
                            <div v-if="task.project" class="mb-3 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                <i class="pi pi-folder text-xs"></i>
                                <Link
                                    :href="route('project.show', { encoded: task.project.id })"
                                    class="hover:text-blue-600 hover:underline"
                                    @click.stop
                                >
                                    {{ task.project.title }}
                                </Link>
                            </div>

                            <!-- Meta Info -->
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <div v-if="task.priority" class="flex items-center gap-1">
                                    <i
                                        :class="[
                                            'pi',
                                            getPriorityIcon(task.priority),
                                            'text-xs',
                                            task.priority.severity === 'danger'
                                                ? 'text-red-500'
                                                : task.priority.severity === 'warning'
                                                  ? 'text-yellow-500'
                                                  : 'text-gray-500',
                                        ]"
                                    ></i>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ task.priority.name }}
                                    </span>
                                </div>
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span
                                    :class="[
                                        'text-xs',
                                        isOverdue(task.due_date) ? 'font-medium text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400',
                                    ]"
                                >
                                    {{ formatDueDate(task.due_date) }}
                                </span>
                            </div>

                            <!-- Type & Badges -->
                            <div class="flex flex-wrap gap-1">
                                <Tag v-if="task.type" :value="task.type.name" :severity="task.type.severity" class="text-xs" />
                                <Tag v-if="task.is_assigned" value="Assigned" severity="info" class="text-xs" />
                                <Tag v-if="task.is_created_by_me" value="Created by me" severity="success" class="text-xs" />
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="col-span-full py-12 text-center">
                        <i class="pi pi-inbox mb-4 text-5xl text-gray-300 dark:text-gray-600"></i>
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No tasks found</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">Try adjusting your filters or search query</p>
                    </div>
                </div>

                <!-- Paginator -->
                <div v-if="filteredTasks.length > rows" class="flex justify-center border-t border-gray-200 pt-4 dark:border-gray-700">
                    <Paginator :first="first" :rows="rows" :totalRecords="filteredTasks.length" @page="(e) => (first = e.first)" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
