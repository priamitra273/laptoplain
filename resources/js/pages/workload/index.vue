<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';

// ============================================================================
// INTERFACES
// ============================================================================

interface User {
    id: number;
    name: string;
    avatar_url?: string | null;
}

interface WorkloadStatus {
    id: number;
    name: string;
    severity: string;
}

interface PaginatedUsers {
    data: UserWithWorkload[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface UserWithWorkload extends User {
    remaining_work_percent: number;
    workload_status: string;
}

interface FilterOptions {
    users: User[];
    workload_statuses: WorkloadStatus[];
}

interface Filters {
    names?: string[] | string;
    workload_statuses?: string[] | string;
    search?: string;
}

interface Props {
    users: PaginatedUsers;
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

const selectedUsers = ref<number[]>([]);
const selectedWorkloadStatuses = ref<number[]>([]);
const searchQuery = ref<string>('');
const showFilters = ref<boolean>(false);

// ============================================================================
// NAVIGATION FUNCTIONS
// ============================================================================

/**
 * Navigate to user detail page
 */
const navigateToUser = (userId: number) => {
    router.visit(route('users.show', { user: userId }));
};

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

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

const getSeverityByStatus = (status: string): string => {
    switch (status) {
        case 'FREE (100%)':
            return 'success';
        case '80%':
            return 'warning';
        case '50%':
            return 'help';
        default:
            return 'danger';
    }
};

// ============================================================================
// INITIALIZATION
// ============================================================================

const initializeFilters = () => {
    const { filters } = props;

    if (filters.names) selectedUsers.value = parseFilterValue(filters.names);
    if (filters.workload_statuses) selectedWorkloadStatuses.value = parseFilterValue(filters.workload_statuses);
    if (filters.search) searchQuery.value = filters.search;
};

initializeFilters();

// ============================================================================
// COMPUTED
// ============================================================================

const hasActiveFilters = computed(() => {
    return selectedUsers.value.length > 0 || selectedWorkloadStatuses.value.length > 0 || searchQuery.value !== '';
});

const activeFilterCount = computed(() => {
    return Object.keys(props.filters).length;
});

const buildFilterParams = (): Record<string, any> => {
    const params: Record<string, any> = {};

    if (selectedUsers.value.length > 0) params.names = selectedUsers.value;
    if (selectedWorkloadStatuses.value.length > 0) params.workload_statuses = selectedWorkloadStatuses.value;
    if (searchQuery.value) params.search = searchQuery.value;

    return params;
};

const navigateWithFilters = (params: Record<string, any> = {}) => {
    router.get(route('workload-users.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    navigateWithFilters(buildFilterParams());
};

const clearFilters = () => {
    selectedUsers.value = [];
    selectedWorkloadStatuses.value = [];
    searchQuery.value = '';

    navigateWithFilters();
};

const toggleFilters = () => {
    showFilters.value = !showFilters.value;
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
    <Head title="Workload User" />

    <AppLayout>
        <div class="flex flex-col gap-6 pb-8">
            <!-- Header -->
            <Heading title="Workload User" description="Monitor and manage user workload distribution." />

            <!-- Action Bar -->
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
                        <!-- User Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">User Name</label>
                            <MultiSelect
                                v-model="selectedUsers"
                                :options="filterOptions.users"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select users"
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

                        <!-- Workload Status Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Workload Status</label>
                            <MultiSelect
                                v-model="selectedWorkloadStatuses"
                                :options="filterOptions.workload_statuses"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select workload statuses"
                                :maxSelectedLabels="2"
                                class="w-full"
                            >
                                <template #option="{ option }">
                                    <Tag :value="option.name" :severity="option.severity" />
                                </template>
                            </MultiSelect>
                        </div>

                        <!-- Search -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                            <InputText v-model="searchQuery" placeholder="Search user name..." class="w-full" />
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
                    <DataTable
                        :value="users.data"
                        stripedRows
                        class="rounded-lg"
                        :rows="users.per_page"
                        responsiveLayout="scroll"
                        @row-click="(event) => navigateToUser(event.data.id)"
                    >
                        <!-- User Column -->
                        <Column field="name" header="Name" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        :image="data.avatar_url && data.avatar_url !== '/images/default-avatar.png' ? data.avatar_url : undefined"
                                        :label="
                                            !data.avatar_url || data.avatar_url === '/images/default-avatar.png' ? getInitials(data.name) : undefined
                                        "
                                        shape="circle"
                                        size="normal"
                                        :style="
                                            !data.avatar_url || data.avatar_url === '/images/default-avatar.png'
                                                ? { backgroundColor: getUserColor(0), color: 'white', fontWeight: '600' }
                                                : {}
                                        "
                                    />
                                    <div>
                                        <p class="font-semibold">{{ data.name }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <!-- Remaining Work Column -->
                        <Column field="remaining_work_percent" header="Remaining Work (%)" style="min-width: 150px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-full rounded-full bg-gray-200">
                                        <div
                                            class="h-2 rounded-full"
                                            :class="{
                                                'bg-green-500': data.workload_status === 'FREE (100%)',
                                                'bg-yellow-500': data.workload_status === '80%',
                                                'bg-orange-500': data.workload_status === '50%',
                                                'bg-red-500': data.workload_status === 'BUSY',
                                            }"
                                            :style="{ width: `${data.remaining_work_percent}%` }"
                                        ></div>
                                    </div>
                                    <span>{{ data.remaining_work_percent }}%</span>
                                </div>
                            </template>
                        </Column>

                        <!-- Workload Status Column -->
                        <Column field="workload_status" header="Workload Status" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.workload_status" :severity="getSeverityByStatus(data.workload_status)" />
                            </template>
                        </Column>

                        <!-- Empty State -->
                        <template #empty>
                            <div class="flex flex-col items-center justify-center py-12">
                                <i class="pi pi-users mb-4 text-6xl text-gray-300"></i>
                                <p class="text-lg font-semibold text-gray-500">No users found</p>
                                <p class="text-sm text-gray-400">Try adjusting your filters</p>
                            </div>
                        </template>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="mt-4">
                        <Paginator
                            :rows="users.per_page"
                            :totalRecords="users.total"
                            :rowsPerPageOptions="[10, 25, 50, 100]"
                            :first="(users.current_page - 1) * users.per_page"
                            @page="onPageChange"
                        />
                    </div>

                    <!-- Stats -->
                    <div class="mt-4 flex items-center justify-between border-t pt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users</p>
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
