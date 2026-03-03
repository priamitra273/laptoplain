<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Paginator from 'primevue/paginator';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

import { computed, ref } from 'vue';

/* ============================================================================
 * INTERFACES
 * ========================================================================== */

interface User {
    id: string; // Sqids encoded
    name: string;
    avatar_url?: string | null;
}

interface WorkloadStatus {
    id: number;
    name: string;
    severity: string;
}

interface UserWithWorkload extends User {
    remaining_work_percent: number;
    workload_status: string;
    total_tasks: number;
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

interface Filters {
    names?: string[] | string | null;
    workload_statuses?: number[] | string[] | string | null;
    search?: string | null;
    per_page?: number | null;
}

interface FilterOptions {
    users: User[];
    workload_statuses: WorkloadStatus[];
}

interface Summary {
    total_users: number;
    free: number;
    light: number;
    moderate: number;
    busy: number;
}

interface Props {
    users: PaginatedUsers;
    filters: Filters;
    filterOptions: FilterOptions;
    summary: Summary;
}

const props = defineProps<Props>();

const safeUsers = computed(() => props.users?.data ?? []);
const safeFilterUsers = computed(() => props.filterOptions?.users ?? []);
const safeFilterStatuses = computed(() => props.filterOptions?.workload_statuses ?? []);
const safeSummary = computed(
    () =>
        props.summary ?? {
            total_users: 0,
            free: 0,
            light: 0,
            moderate: 0,
            busy: 0,
        },
);

const selectedUsers = ref<string[]>(parseStringArray(props.filters?.names));
const selectedWorkloadStatuses = ref<number[]>(parseNumberArray(props.filters?.workload_statuses));
const searchQuery = ref<string>(props.filters?.search ?? '');
const showFilters = ref(false);

/* ============================================================================
 * HELPERS
 * ========================================================================== */

function parseStringArray(value: string[] | string | null | undefined): string[] {
    if (!value) return [];
    if (Array.isArray(value)) return value.map(String).filter(Boolean);
    if (typeof value === 'string' && value.length > 0) return value.split(',').filter(Boolean);
    return [];
}

function parseNumberArray(value: number[] | string[] | string | null | undefined): number[] {
    if (!value) return [];
    if (Array.isArray(value)) return value.map(Number).filter(Boolean);
    if (typeof value === 'string' && value.length > 0) return value.split(',').map(Number).filter(Boolean);
    return [];
}

const getInitials = (name: string): string =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getUserColor = (index: number): string => `hsl(${index * 60}, 70%, 60%)`;

const getSeverityByStatus = (status: string): string => {
    switch (status) {
        case 'Free':
            return 'success';
        case 'Almost Done':
            return 'info';
        case 'Ongoing':
            return 'warn';
        default:
            return 'danger';
    }
};

/* ============================================================================
 * COMPUTED
 * ========================================================================== */

const hasActiveFilters = computed(() => selectedUsers.value.length > 0 || selectedWorkloadStatuses.value.length > 0 || searchQuery.value !== '');

const activeFilterCount = computed(
    () => (selectedUsers.value.length > 0 ? 1 : 0) + (selectedWorkloadStatuses.value.length > 0 ? 1 : 0) + (searchQuery.value !== '' ? 1 : 0),
);

/* ============================================================================
 * FILTER ACTIONS
 * ========================================================================== */

const buildFilterParams = (): Record<string, any> => {
    const params: Record<string, any> = {};
    if (selectedUsers.value.length) params.names = selectedUsers.value;
    if (selectedWorkloadStatuses.value.length) params.workload_statuses = selectedWorkloadStatuses.value;
    if (searchQuery.value) params.search = searchQuery.value;
    return params;
};

const navigateWithFilters = (params: Record<string, any> = {}) => {
    router.get(route('workload-users.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => navigateWithFilters(buildFilterParams());

const clearFilters = () => {
    selectedUsers.value = [];
    selectedWorkloadStatuses.value = [];
    searchQuery.value = '';
    navigateWithFilters();
};

const onPageChange = (event: any) => {
    navigateWithFilters({
        ...buildFilterParams(),
        page: event.page + 1,
        per_page: event.rows,
    });
};

/* ============================================================================
 * SUMMARY CARDS
 * ========================================================================== */

const summaryCards = [
    { key: 'total_users' as const, label: 'Total Users', icon: 'pi pi-users', color: '#6366f1', bg: '#e0e7ff' },
    { key: 'free' as const, label: 'Free', icon: 'pi pi-check-circle', color: '#16a34a', bg: '#dcfce7' },
    { key: 'light' as const, label: 'Almost Done', icon: 'pi pi-chart-bar', color: '#2563eb', bg: '#dbeafe' },
    { key: 'moderate' as const, label: 'Ongoing', icon: 'pi pi-clock', color: '#ca8a04', bg: '#fef9c3' },
    { key: 'busy' as const, label: 'Overloaded', icon: 'pi pi-exclamation-triangle', color: '#dc2626', bg: '#fee2e2' },
];
</script>

<template>
    <Head title="Workload Users" />

    <AppLayout>
        <div class="flex flex-col gap-6 pb-8">
            <Heading title="Workload Users" description="Monitor and manage user workload distribution." />

            <!-- SUMMARY CARDS -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <Card v-for="card in summaryCards" :key="card.key" class="rounded-2xl border-0 shadow-md">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl text-lg"
                                :style="{ backgroundColor: card.bg, color: card.color }"
                            >
                                <i :class="card.icon" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-2xl font-bold leading-none">
                                    {{ safeSummary[card.key] }}
                                </span>
                                <span class="mt-0.5 text-xs text-gray-400">{{ card.label }}</span>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- FILTER TOGGLE -->
            <Card class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <Button
                        :label="showFilters ? 'Hide Filters' : 'Show Filters'"
                        :icon="showFilters ? 'pi pi-times' : 'pi pi-filter'"
                        :severity="hasActiveFilters ? 'primary' : 'secondary'"
                        :badge="hasActiveFilters ? String(activeFilterCount) : undefined"
                        @click="showFilters = !showFilters"
                    />
                </template>
            </Card>

            <!-- FILTER PANEL -->
            <Card v-if="showFilters" class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <!-- User filter — optionValue is Sqids-encoded string -->
                        <MultiSelect
                            v-model="selectedUsers"
                            :options="safeFilterUsers"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select users"
                            filter
                        >
                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <Avatar
                                        :image="option.avatar_url || undefined"
                                        :label="!option.avatar_url ? getInitials(option.name) : undefined"
                                        shape="circle"
                                        size="small"
                                        :style="
                                            !option.avatar_url
                                                ? { backgroundColor: getUserColor(safeFilterUsers.indexOf(option)), color: 'white' }
                                                : {}
                                        "
                                    />
                                    <span>{{ option.name }}</span>
                                </div>
                            </template>
                        </MultiSelect>

                        <!-- Status filter — optionValue is numeric ID -->
                        <MultiSelect
                            v-model="selectedWorkloadStatuses"
                            :options="safeFilterStatuses"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select workload status"
                        >
                            <template #option="{ option }">
                                <Tag :value="option.name" :severity="option.severity" rounded />
                            </template>
                        </MultiSelect>

                        <InputText v-model="searchQuery" placeholder="Search by name..." />
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <Button label="Clear" outlined severity="secondary" @click="clearFilters" />
                        <Button label="Apply Filters" icon="pi pi-check" @click="applyFilters" />
                    </div>
                </template>
            </Card>

            <!-- TABLE -->
            <Card class="rounded-2xl border-0 shadow-md">
                <template #content>
                    <DataTable
                        :value="safeUsers"
                        stripedRows
                        responsiveLayout="scroll"
                        @row-click="(e) => router.visit(route('users.show', { user: e.data.id }))"
                    >
                        <!-- NAME -->
                        <Column header="Name" style="min-width: 200px">
                            <template #body="{ data, index }">
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        :image="data.avatar_url || undefined"
                                        :label="!data.avatar_url ? getInitials(data.name) : undefined"
                                        shape="circle"
                                        :style="!data.avatar_url ? { backgroundColor: getUserColor(index), color: 'white' } : {}"
                                    />
                                    <span class="font-semibold">{{ data.name }}</span>
                                </div>
                            </template>
                        </Column>

                        <!-- TOTAL TASKS -->
                        <Column header="Total Tasks" style="min-width: 120px; text-align: center">
                            <template #body="{ data }">
                                <Badge :value="String(data.total_tasks ?? 0)" severity="secondary" />
                            </template>
                        </Column>

                        <!-- REMAINING WORK -->
                        <Column header="Remaining Work (%)" style="min-width: 240px">
                            <template #body="{ data }">
                                <div class="relative w-full">
                                    <ProgressBar
                                        :value="Number(data.remaining_work_percent ?? 0)"
                                        :showValue="false"
                                        class="h-6 overflow-hidden rounded-lg transition-all duration-500"
                                        :class="{
                                            'p-progressbar-success': data.workload_status === 'Free',
                                            'p-progressbar-warning': data.workload_status === 'Almost Done',
                                            'p-progressbar-help': data.workload_status === 'Ongoing',
                                            'p-progressbar-danger': data.workload_status === 'Overloaded',
                                        }"
                                    />
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-white">
                                        {{ data.remaining_work_percent ?? 0 }}%
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <!-- STATUS -->
                        <Column header="Status" style="min-width: 150px">
                            <template #body="{ data }">
                                <Tag :value="data.workload_status" :severity="getSeverityByStatus(data.workload_status)" />
                            </template>
                        </Column>

                        <template #empty>
                            <div class="py-10 text-center text-gray-400">No users found.</div>
                        </template>
                    </DataTable>

                    <Paginator
                        class="mt-4"
                        :rows="props.users?.per_page ?? 50"
                        :totalRecords="props.users?.total ?? 0"
                        :first="((props.users?.current_page ?? 1) - 1) * (props.users?.per_page ?? 50)"
                        :rowsPerPageOptions="[10, 25, 50, 100]"
                        @page="onPageChange"
                    />
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
