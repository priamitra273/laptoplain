<script setup lang="ts">
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
    id: number;
    title: string;
}

interface Task {
    id: string;
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

interface Props {
    tasks: PaginatedTasks;
    filters: {
        names?: string[] | string;
        statuses?: string[] | string;
        priorities?: string[] | string;
        types?: string[] | string;
        start_date_from?: string;
        start_date_to?: string;
        due_date_from?: string;
        due_date_to?: string;
        search?: string;
    };
    filterOptions: FilterOptions;
}

const props = defineProps<Props>();

// Filter states
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

// Initialize filters from props
const initializeFilters = () => {
    if (props.filters.names) {
        selectedCreators.value = Array.isArray(props.filters.names) ? props.filters.names.map(Number) : props.filters.names.split(',').map(Number);
    }
    if (props.filters.statuses) {
        selectedStatuses.value = Array.isArray(props.filters.statuses)
            ? props.filters.statuses.map(Number)
            : props.filters.statuses.split(',').map(Number);
    }
    if (props.filters.priorities) {
        selectedPriorities.value = Array.isArray(props.filters.priorities)
            ? props.filters.priorities.map(Number)
            : props.filters.priorities.split(',').map(Number);
    }
    if (props.filters.types) {
        selectedTypes.value = Array.isArray(props.filters.types) ? props.filters.types.map(Number) : props.filters.types.split(',').map(Number);
    }
    if (props.filters.start_date_from) {
        startDateFrom.value = new Date(props.filters.start_date_from);
    }
    if (props.filters.start_date_to) {
        startDateTo.value = new Date(props.filters.start_date_to);
    }
    if (props.filters.due_date_from) {
        dueDateFrom.value = new Date(props.filters.due_date_from);
    }
    if (props.filters.due_date_to) {
        dueDateTo.value = new Date(props.filters.due_date_to);
    }
    if (props.filters.search) {
        searchQuery.value = props.filters.search;
    }
};

initializeFilters();

// Format date helper
const formatDate = (date: string | null): string => {
    if (!date) return '-';
    return moment(date).format('DD MMM YYYY');
};

// Get initials for avatar
const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Get color for avatar
const getUserColor = (index: number): string => {
    return `hsl(${index * 60}, 70%, 60%)`;
};

// Apply filters
const applyFilters = () => {
    const filterParams: any = {};

    if (selectedCreators.value.length > 0) {
        filterParams.names = selectedCreators.value;
    }
    if (selectedStatuses.value.length > 0) {
        filterParams.statuses = selectedStatuses.value;
    }
    if (selectedPriorities.value.length > 0) {
        filterParams.priorities = selectedPriorities.value;
    }
    if (selectedTypes.value.length > 0) {
        filterParams.types = selectedTypes.value;
    }
    if (startDateFrom.value) {
        filterParams.start_date_from = moment(startDateFrom.value).format('YYYY-MM-DD');
    }
    if (startDateTo.value) {
        filterParams.start_date_to = moment(startDateTo.value).format('YYYY-MM-DD');
    }
    if (dueDateFrom.value) {
        filterParams.due_date_from = moment(dueDateFrom.value).format('YYYY-MM-DD');
    }
    if (dueDateTo.value) {
        filterParams.due_date_to = moment(dueDateTo.value).format('YYYY-MM-DD');
    }
    if (searchQuery.value) {
        filterParams.search = searchQuery.value;
    }

    router.get(route('reports.tasks.index'), filterParams, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Clear all filters
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

    router.get(
        route('reports.tasks.index'),
        {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Export to CSV
const exportReport = () => {
    const filterParams: any = {};

    if (selectedCreators.value.length > 0) {
        filterParams.names = selectedCreators.value.join(',');
    }
    if (selectedStatuses.value.length > 0) {
        filterParams.statuses = selectedStatuses.value.join(',');
    }
    if (selectedPriorities.value.length > 0) {
        filterParams.priorities = selectedPriorities.value.join(',');
    }
    if (selectedTypes.value.length > 0) {
        filterParams.types = selectedTypes.value.join(',');
    }
    if (startDateFrom.value) {
        filterParams.start_date_from = moment(startDateFrom.value).format('YYYY-MM-DD');
    }
    if (startDateTo.value) {
        filterParams.start_date_to = moment(startDateTo.value).format('YYYY-MM-DD');
    }
    if (dueDateFrom.value) {
        filterParams.due_date_from = moment(dueDateFrom.value).format('YYYY-MM-DD');
    }
    if (dueDateTo.value) {
        filterParams.due_date_to = moment(dueDateTo.value).format('YYYY-MM-DD');
    }
    if (searchQuery.value) {
        filterParams.search = searchQuery.value;
    }

    const queryString = new URLSearchParams(filterParams).toString();
    window.open(route('reports.tasks.export') + '?' + queryString, '_blank');
};

// Pagination
const onPageChange = (event: any) => {
    const filterParams = { ...props.filters, page: event.page + 1, per_page: event.rows };
    router.get(route('reports.tasks.index'), filterParams, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return (
        selectedCreators.value.length > 0 ||
        selectedStatuses.value.length > 0 ||
        selectedPriorities.value.length > 0 ||
        selectedTypes.value.length > 0 ||
        startDateFrom.value !== null ||
        startDateTo.value !== null ||
        dueDateFrom.value !== null ||
        dueDateTo.value !== null ||
        searchQuery.value !== ''
    );
});

// Toggle filters panel
const toggleFilters = () => {
    showFilters.value = !showFilters.value;
};
</script>

<template>
    <Head title="Task Report" />

    <AppLayout>
        <div class="flex flex-col gap-6 pb-8">
            <!-- Header -->
            <Card class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Task Report</h1>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Comprehensive task overview with advanced filtering</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                :label="showFilters ? 'Hide Filters' : 'Show Filters'"
                                :icon="showFilters ? 'pi pi-times' : 'pi pi-filter'"
                                @click="toggleFilters"
                                :severity="hasActiveFilters ? 'primary' : 'secondary'"
                                :badge="hasActiveFilters ? String(Object.keys(props.filters).length) : undefined"
                            />
                            <Button label="Export CSV" icon="pi pi-download" @click="exportReport" severity="success" />
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
                            @click="clearFilters"
                            severity="danger"
                            text
                            size="small"
                        />
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <!-- Creator Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Creator Name</label>
                            <MultiSelect
                                v-model="selectedCreators"
                                :options="props.filterOptions.creators"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select creators"
                                :maxSelectedLabels="2"
                                class="w-full"
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
                                :options="props.filterOptions.statuses"
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
                                :options="props.filterOptions.priorities"
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
                                :options="props.filterOptions.types"
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

                        <!-- Start Date Range -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date From</label>
                            <DatePicker v-model="startDateFrom" dateFormat="dd M yy" placeholder="Select date" showIcon class="w-full" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Start Date To</label>
                            <DatePicker v-model="startDateTo" dateFormat="dd M yy" placeholder="Select date" showIcon class="w-full" />
                        </div>

                        <!-- Due Date Range -->
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
                        <Button label="Clear" @click="clearFilters" severity="secondary" outlined />
                        <Button label="Apply Filters" @click="applyFilters" icon="pi pi-check" />
                    </div>
                </template>
            </Card>

            <!-- Data Table -->
            <Card class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <DataTable :value="props.tasks.data" stripedRows class="rounded-lg" :rows="props.tasks.per_page" responsiveLayout="scroll">
                        <!-- Creator Name Column -->
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
                                        <p class="text-xs text-gray-500">{{ data.project?.title || '-' }}</p>
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Summary Column -->
                        <Column field="summary" header="Summary" style="min-width: 300px">
                            <template #body="{ data }">
                                <div>
                                    <p class="mb-1 font-semibold text-gray-800 dark:text-white">{{ data.title }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ data.summary }}</p>
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
                            :rows="props.tasks.per_page"
                            :totalRecords="props.tasks.total"
                            :rowsPerPageOptions="[10, 25, 50, 100]"
                            @page="onPageChange"
                            :first="(props.tasks.current_page - 1) * props.tasks.per_page"
                        />
                    </div>

                    <!-- Stats -->
                    <div class="mt-4 flex items-center justify-between border-t pt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ props.tasks.from }} to {{ props.tasks.to }} of {{ props.tasks.total }} tasks
                        </p>
                        <div v-if="hasActiveFilters" class="flex items-center gap-2">
                            <i class="pi pi-filter text-blue-500"></i>
                            <span class="text-sm font-medium text-blue-600"> {{ Object.keys(props.filters).length }} filter(s) active </span>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
