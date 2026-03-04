<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import moment from 'moment';

import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';

interface Creator {
    id: number;
    name: string;
    avatar_url?: string | null;
}

interface Status {
    id: number;
    name: string;
    severity: string;
}

interface Priority {
    id: number;
    name: string;
    severity: string;
}

interface Type {
    id: number;
    name: string;
    severity: string;
}

interface Project {
    id: string; // Encoded ID
    title: string;
}

interface Task {
    id: string; // Encoded ID
    title: string;
    summary: string;
    creator: Creator | null;
    status: Status | null;
    priority: Priority | null;
    type: Type | null;
    project: Project | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    created_at: string;
}

interface FilterOptions {
    creators: Creator[];
    statuses: Status[];
    priorities: Priority[];
    types: Type[];
}

interface PaginatedTasks {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Filters {
    names?: string[] | string;
    statuses?: string[] | string;
    priorities?: string[] | string;
    types?: string[] | string;
    start_date_from?: string;
    start_date_to?: string;
    due_date_from?: string;
    due_date_to?: string;
    search?: string;
}

interface Props {
    tasks: PaginatedTasks;
    filters: Filters;
    filterOptions: FilterOptions;
}

// ============================================================================
// PROPS
// ============================================================================

const props = defineProps<Props>();

// ============================================================================
// STATE
// ============================================================================

const selectedCreators = ref<number[]>([]);
const selectedStatuses = ref<number[]>([]);
const selectedPriorities = ref<number[]>([]);
const selectedTypes = ref<number[]>([]);
const startDateFrom = ref<Date | null>(null);
const startDateTo = ref<Date | null>(null);
const dueDateFrom = ref<Date | null>(null);
const dueDateTo = ref<Date | null>(null);
const searchQuery = ref<string>('');
const showFilters = ref<boolean>(false);

// ============================================================================
// NAVIGATION FUNCTIONS
// ============================================================================

/**
 * Navigate to task detail page
 * @param encodedTaskId - Already encoded task ID from backend
 */
const navigateToTask = (encodedTaskId: string) => {
    router.visit(route('task.show', { encoded: encodedTaskId }));
};

/**
 * Navigate to project detail page
 * @param encodedProjectId - Already encoded project ID from backend
 */
const navigateToProject = (encodedProjectId: string) => {
    router.visit(route('project.show', { encoded: encodedProjectId }));
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

const truncateText = (text: string | null, length: number = 15): string => {
    if (!text) return '-';
    return text.length > length ? text.slice(0, length) + '...' : text;
};

const formatDate = (date: string | null): string => {
    if (!date) return '-';
    return moment(date).format('DD MMM YYYY');
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getUserColor = (index: number): string => {
    return `hsl(${index * 60}, 70%, 60%)`;
};

const parseFilterValue = (value: string[] | string): number[] => {
    return Array.isArray(value) ? value.map(Number) : value.split(',').map(Number);
};

// ============================================================================
// INITIALIZATION
// ============================================================================

const initializeFilters = () => {
    const { filters } = props;

    if (filters.names) selectedCreators.value = parseFilterValue(filters.names);
    if (filters.statuses) selectedStatuses.value = parseFilterValue(filters.statuses);
    if (filters.priorities) selectedPriorities.value = parseFilterValue(filters.priorities);
    if (filters.types) selectedTypes.value = parseFilterValue(filters.types);
    if (filters.start_date_from) startDateFrom.value = new Date(filters.start_date_from);
    if (filters.start_date_to) startDateTo.value = new Date(filters.start_date_to);
    if (filters.due_date_from) dueDateFrom.value = new Date(filters.due_date_from);
    if (filters.due_date_to) dueDateTo.value = new Date(filters.due_date_to);
    if (filters.search) searchQuery.value = filters.search;
};

initializeFilters();

// ============================================================================
// COMPUTED
// ============================================================================

const hasActiveFilters = computed(() => {
    return (
        selectedCreators.value?.length > 0 ||
        selectedStatuses.value?.length > 0 ||
        selectedPriorities.value?.length > 0 ||
        selectedTypes.value?.length > 0 ||
        startDateFrom.value !== null ||
        startDateTo.value !== null ||
        dueDateFrom.value !== null ||
        dueDateTo.value !== null ||
        searchQuery.value !== ''
    );
});

const activeFilterCount = computed(() => {
    return Object.keys(props.filters).length;
});

const buildFilterParams = (): Record<string, any> => {
    const params: Record<string, any> = {};

    if (selectedCreators.value?.length > 0) params.names = selectedCreators.value;
    if (selectedStatuses.value?.length > 0) params.statuses = selectedStatuses.value;
    if (selectedPriorities.value?.length > 0) params.priorities = selectedPriorities.value;
    if (selectedTypes.value?.length > 0) params.types = selectedTypes.value;
    if (startDateFrom.value) params.start_date_from = moment(startDateFrom.value).format('YYYY-MM-DD');
    if (startDateTo.value) params.start_date_to = moment(startDateTo.value).format('YYYY-MM-DD');
    if (dueDateFrom.value) params.due_date_from = moment(dueDateFrom.value).format('YYYY-MM-DD');
    if (dueDateTo.value) params.due_date_to = moment(dueDateTo.value).format('YYYY-MM-DD');
    if (searchQuery.value) params.search = searchQuery.value;

    return params;
};

const navigateWithFilters = (params: Record<string, any> = {}) => {
    router.get(route('reports.tasks.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    navigateWithFilters(buildFilterParams());
};

const clearFilters = () => {
    selectedCreators.value = [];
    selectedStatuses.value = [];
    selectedPriorities.value = [];
    selectedTypes.value = [];
    startDateFrom.value = null;
    startDateTo.value = null;
    dueDateFrom.value = null;
    dueDateTo.value = null;
    searchQuery.value = '';

    navigateWithFilters();
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};

const exportReport = () => {
    const params = buildFilterParams();
    const queryString = new URLSearchParams(
        Object.entries(params).reduce(
            (acc, [key, value]) => {
                acc[key] = Array.isArray(value) ? value.join(',') : value;
                return acc;
            },
            {} as Record<string, string>,
        ),
    ).toString();

    window.open(`${route('reports.tasks.export')}?${queryString}`, '_blank');
};

const onPageChange = (event: any) => {
    const params = {
        ...props.filters,
        page: event.page + 1,
        per_page: event.rows,
    };
    navigateWithFilters(params);
};
</script>

<template>
    <Head title="Task Report" />

    <AppLayout>
        <div class="flex flex-col gap-6 pb-8">
            <!-- Header -->
            <Heading title="Task Report" description="Manage and track all tasks in your project." />
            <Card class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <Button
                                :label="showFilters ? 'Hide Filters' : 'Show Filters'"
                                :icon="showFilters ? 'pi pi-times' : 'pi pi-filter'"
                                :severity="hasActiveFilters ? 'primary' : 'secondary'"
                                :badge="hasActiveFilters ? String(activeFilterCount) : undefined"
                                @click="toggleFilters"
                            />
                            <!-- <Button label="Export CSV" icon="pi pi-download" severity="success" @click="exportReport" /> -->
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Filters Panel -->
            <Card v-if="showFilters" class="rounded-2xl border-0 shadow-md">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-filter text-blue-500"></i>
                            <span class="text-lg font-bold">Filters</span>
                        </div>
                        <Button
                            v-if="hasActiveFilters"
                            label="Clear All"
                            icon="pi pi-filter-slash"
                            severity="danger"
                            text
                            size="small"
                            @click="clearFilters"
                        />
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <!-- Creator Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Assignee</label>
                            <MultiSelect
                                v-model="selectedCreators"
                                :options="filterOptions.creators"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Assignee"
                                :maxSelectedLabels="2"
                                class="w-full"
                                showClear
                                filter
                            >
                                <template #option="{ option }">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            :image="
                                                option.avatar_url && option.avatar_url !== '/images/default-avatar.png'
                                                    ? option.avatar_url
                                                    : undefined
                                            "
                                            :label="
                                                !option.avatar_url || option.avatar_url === '/images/default-avatar.png'
                                                    ? getInitials(option.name)
                                                    : undefined
                                            "
                                            size="small"
                                            shape="circle"
                                        />
                                        <span>{{ option.name }}</span>
                                    </div>
                                </template>
                            </MultiSelect>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <MultiSelect
                                v-model="selectedStatuses"
                                :options="filterOptions.statuses"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select statuses"
                                :maxSelectedLabels="2"
                                class="w-full"
                            >
                                <template #option="{ option }">
                                    <Tag :value="option.name" :severity="option.severity" />
                                </template>
                            </MultiSelect>
                        </div>

                        <!-- Priority Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                            <MultiSelect
                                v-model="selectedPriorities"
                                :options="filterOptions.priorities"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select priorities"
                                :maxSelectedLabels="2"
                                class="w-full"
                            >
                                <template #option="{ option }">
                                    <Tag :value="option.name" :severity="option.severity" />
                                </template>
                            </MultiSelect>
                        </div>

                        <!-- Type Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                            <MultiSelect
                                v-model="selectedTypes"
                                :options="filterOptions.types"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select types"
                                :maxSelectedLabels="2"
                                class="w-full"
                            >
                                <template #option="{ option }">
                                    <Tag :value="option.name" :severity="option.severity" />
                                </template>
                            </MultiSelect>
                        </div>

                        <!-- Date Filters -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date From</label>
                            <DatePicker v-model="startDateFrom" dateFormat="dd M yy" placeholder="Select date" showIcon class="w-full" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date To</label>
                            <DatePicker v-model="startDateTo" dateFormat="dd M yy" placeholder="Select date" showIcon class="w-full" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Due Date From</label>
                            <DatePicker v-model="dueDateFrom" dateFormat="dd M yy" placeholder="Select date" showIcon class="w-full" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Due Date To</label>
                            <DatePicker v-model="dueDateTo" dateFormat="dd M yy" placeholder="Select date" showIcon class="w-full" />
                        </div>

                        <!-- Search -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                            <InputText v-model="searchQuery" placeholder="Search title or description..." class="w-full" />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <Button label="Clear" severity="secondary" outlined @click="clearFilters" />
                        <Button label="Apply Filters" icon="pi pi-check" @click="applyFilters" />
                    </div>
                </template>
            </Card>

            <!-- Data Table -->
            <Card class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <DataTable :value="tasks.data" stripedRows class="rounded-lg" :rows="tasks.per_page" responsiveLayout="scroll">
                        <!-- Creator Column -->
                        <Column field="creator.name" header="Name" style="min-width: 200px">
                            <template #body="{ data }">
                                <div v-if="data.creator" class="flex items-center gap-3">
                                    <Avatar
                                        :image="
                                            data.creator.avatar_url && data.creator.avatar_url !== '/images/default-avatar.png'
                                                ? data.creator.avatar_url
                                                : undefined
                                        "
                                        :label="
                                            !data.creator.avatar_url || data.creator.avatar_url === '/images/default-avatar.png'
                                                ? getInitials(data.creator.name)
                                                : undefined
                                        "
                                        shape="circle"
                                        size="normal"
                                        :style="
                                            !data.creator.avatar_url || data.creator.avatar_url === '/images/default-avatar.png'
                                                ? { backgroundColor: getUserColor(0), color: 'white', fontWeight: '600' }
                                                : {}
                                        "
                                    />
                                    <div>
                                        <p class="font-semibold">{{ data.creator.name }}</p>
                                        <!-- Clickable Project Title -->
                                        <p
                                            v-if="data.project"
                                            @click.stop="navigateToProject(data.project.id)"
                                            class="cursor-pointer text-xs text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            {{ data.project.title }}
                                        </p>
                                        <p v-else class="text-xs text-gray-500">-</p>
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Summary Column - Clickable Task -->
                        <Column field="summary" header="Summary" style="min-width: 300px">
                            <template #body="{ data }">
                                <div
                                    @click="navigateToTask(data.id)"
                                    class="-m-2 cursor-pointer rounded p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <p class="text-dark mb-1 font-semibold transition-colors dark:text-blue-400 dark:hover:text-blue-300">
                                        {{ truncateText(data.title, 15) }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ truncateText(data.summary, 15) }}</p>
                                </div>
                            </template>
                        </Column>

                        <!-- Status Column -->
                        <Column field="status.name" header="Status" style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag v-if="data.status" :value="data.status.name" :severity="data.status.severity" />
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Priority Column -->
                        <Column field="priority.name" header="Priority" style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag v-if="data.priority" :value="data.priority.name" :severity="data.priority.severity" />
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Type Column -->
                        <Column field="type.name" header="Type" style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag v-if="data.type" :value="data.type.name" :severity="data.type.severity" />
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Start Date Column -->
                        <Column field="start_date" header="Start Date" style="min-width: 130px">
                            <template #body="{ data }">
                                <span>{{ formatDate(data.start_date) }}</span>
                            </template>
                        </Column>

                        <!-- Due Date Column -->
                        <Column field="due_date" header="Due Date" style="min-width: 130px">
                            <template #body="{ data }">
                                <span>{{ formatDate(data.due_date) }}</span>
                            </template>
                        </Column>

                        <!-- Empty State -->
                        <template #empty>
                            <div class="flex flex-col items-center justify-center py-12">
                                <i class="pi pi-inbox mb-4 text-6xl text-gray-300"></i>
                                <p class="text-lg font-semibold text-gray-500">No tasks found</p>
                                <p class="text-sm text-gray-400">Try adjusting your filters</p>
                            </div>
                        </template>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="mt-4">
                        <Paginator
                            :rows="tasks.per_page"
                            :totalRecords="tasks.total"
                            :rowsPerPageOptions="[10, 25, 50, 100]"
                            :first="(tasks.current_page - 1) * tasks.per_page"
                            @page="onPageChange"
                        />
                    </div>

                    <!-- Stats -->
                    <div class="mt-4 flex items-center justify-between border-t pt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Showing {{ tasks.from }} to {{ tasks.to }} of {{ tasks.total }} tasks</p>
                        <div v-if="hasActiveFilters" class="flex items-center gap-2">
                            <i class="pi pi-filter text-blue-500"></i>
                            <span class="text-sm font-medium text-blue-600">{{ activeFilterCount }} filter(s) active</span>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
