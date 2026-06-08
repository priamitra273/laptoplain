<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, ref, type CSSProperties } from 'vue';
import type { TaskReportProps } from './type';

type QueryParamValue = string | number | string[] | undefined;
const DEFAULT_AVATAR_URL = '/images/default-avatar.png';

const props = defineProps<TaskReportProps>();

const selectedCreators = ref<string[]>([]);
const selectedStatuses = ref<string[]>([]);
const selectedProjectStatuses = ref<string[]>([]);
const selectedPriorities = ref<string[]>([]);
const selectedTypes = ref<string[]>([]);
const startDateFrom = ref<Date | null>(null);
const startDateTo = ref<Date | null>(null);
const dueDateFrom = ref<Date | null>(null);
const dueDateTo = ref<Date | null>(null);
const searchQuery = ref<string>('');
const showFilters = ref<boolean>(false);

const hasActiveFilters = computed(() => {
    return (
        selectedCreators.value?.length > 0 ||
        selectedStatuses.value?.length > 0 ||
        selectedProjectStatuses.value?.length > 0 ||
        selectedPriorities.value?.length > 0 ||
        selectedTypes.value?.length > 0 ||
        startDateFrom.value !== null ||
        startDateTo.value !== null ||
        dueDateFrom.value !== null ||
        dueDateTo.value !== null ||
        searchQuery.value !== ''
    );
});

/**
 * Navigate to task detail page
 * @param encodedTaskId - Already encoded task ID from backend
 */
const navigateToTask = (encodedTaskId: string) => {
    router.visit(route('task.show', encodedTaskId));
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

const hasCustomAvatar = (avatarUrl?: string | null): boolean => {
    return Boolean(avatarUrl && avatarUrl !== DEFAULT_AVATAR_URL);
};

const getAvatarImage = (avatarUrl?: string | null): string | undefined => {
    return hasCustomAvatar(avatarUrl) ? (avatarUrl ?? undefined) : undefined;
};

const getAvatarLabel = (name: string, avatarUrl?: string | null): string | undefined => {
    return hasCustomAvatar(avatarUrl) ? undefined : getInitials(name);
};

const getAvatarStyle = (avatarUrl?: string | null): CSSProperties => {
    return hasCustomAvatar(avatarUrl) ? {} : { backgroundColor: getUserColor(0), color: 'white', fontWeight: '600' };
};

const parseFilterValue = (value: string[] | string): string[] => {
    return Array.isArray(value) ? value : value.split(',');
};

const buildFilterParams = (): Record<string, QueryParamValue> => {
    const params: Record<string, QueryParamValue> = {};

    if (selectedCreators.value?.length > 0) params.names = selectedCreators.value;
    if (selectedStatuses.value?.length > 0) params.statuses = selectedStatuses.value;
    if (selectedProjectStatuses.value?.length > 0) params.project_statuses = selectedProjectStatuses.value;
    if (selectedPriorities.value?.length > 0) params.priorities = selectedPriorities.value;
    if (selectedTypes.value?.length > 0) params.types = selectedTypes.value;
    if (startDateFrom.value) params.start_date_from = moment(startDateFrom.value).format('YYYY-MM-DD');
    if (startDateTo.value) params.start_date_to = moment(startDateTo.value).format('YYYY-MM-DD');
    if (dueDateFrom.value) params.due_date_from = moment(dueDateFrom.value).format('YYYY-MM-DD');
    if (dueDateTo.value) params.due_date_to = moment(dueDateTo.value).format('YYYY-MM-DD');
    if (searchQuery.value) params.search = searchQuery.value;

    return params;
};

const navigateWithFilters = (params: Record<string, QueryParamValue> = {}) => {
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
    selectedProjectStatuses.value = [];
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

const navigateToProject = (encodedProjectId: string) => {
    router.visit(route('project.show', { encoded: encodedProjectId }));
};

const exportReport = () => {
    const params = buildFilterParams();
    const queryString = new URLSearchParams(
        Object.entries(params).reduce(
            (acc, [key, value]) => {
                acc[key] = Array.isArray(value) ? value.join(',') : String(value ?? '');
                return acc;
            },
            {} as Record<string, string>,
        ),
    ).toString();

    window.open(`${route('reports.tasks.export')}?${queryString}`, '_blank');
};

const initializeFilters = () => {
    const { filters } = props;

    if (filters.names) selectedCreators.value = parseFilterValue(filters.names);
    if (filters.statuses) selectedStatuses.value = parseFilterValue(filters.statuses);
    if (filters.project_statuses) selectedProjectStatuses.value = parseFilterValue(filters.project_statuses);
    if (filters.priorities) selectedPriorities.value = parseFilterValue(filters.priorities);
    if (filters.types) selectedTypes.value = parseFilterValue(filters.types);
    if (filters.start_date_from) startDateFrom.value = new Date(filters.start_date_from);
    if (filters.start_date_to) startDateTo.value = new Date(filters.start_date_to);
    if (filters.due_date_from) dueDateFrom.value = new Date(filters.due_date_from);
    if (filters.due_date_to) dueDateTo.value = new Date(filters.due_date_to);
    if (filters.search) searchQuery.value = filters.search;
};

const onPageChange = (event: { page: number; rows: number }) => {
    const params: Record<string, QueryParamValue> = {
        ...(props.filters as Record<string, QueryParamValue>),
        page: event.page + 1,
        per_page: event.rows,
    };

    navigateWithFilters(params);
};

initializeFilters();
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
                                            :image="getAvatarImage(option.avatar_url)"
                                            :label="getAvatarLabel(option.name, option.avatar_url)"
                                            size="small"
                                            shape="circle"
                                        />
                                        <span>{{ option.name }}</span>
                                    </div>
                                </template>
                            </MultiSelect>
                        </div>

                        <!-- Project Status Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Project Status</label>
                            <MultiSelect
                                v-model="selectedProjectStatuses"
                                :options="filterOptions.project_statuses"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select project statuses"
                                :maxSelectedLabels="2"
                                class="w-full"
                            >
                                <template #option="{ option }">
                                    <Tag :value="option.name" :severity="option.severity" />
                                </template>
                            </MultiSelect>
                        </div>

                        <!-- Status Filter -->
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Task Status</label>
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
                    <DataTable :value="tasks.data" stripedRows class="rounded-lg" :rows="tasks.meta.per_page" responsiveLayout="scroll">
                        <!-- Creator Column -->
                        <Column field="creator.name" header="Name" style="min-width: 200px">
                            <template #body="{ data }">
                                <div v-if="data.creator" class="flex items-center gap-3">
                                    <Avatar
                                        :image="getAvatarImage(data.creator.avatar_url)"
                                        :label="getAvatarLabel(data.creator.name, data.creator.avatar_url)"
                                        shape="circle"
                                        size="normal"
                                        :style="getAvatarStyle(data.creator.avatar_url)"
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

                        <Column field="project.status_name" header="Project Status" style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag
                                    v-if="data.project"
                                    :value="data.project.status_name"
                                    :severity="data.project.severity"
                                    class="!bg-transparent"
                                />
                                <span v-else class="text-gray-400">-</span>
                            </template>
                        </Column>

                        <!-- Summary Column - Clickable Task -->
                        <Column field="summary" header="Summary" style="width: 300px; min-width: 300px; max-width: 300px">
                            <template #body="{ data }">
                                <Link
                                    :href="route('task.show', data.id)"
                                    class="-m-2 block cursor-pointer rounded p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <p
                                        class="text-dark mb-1 truncate text-sm font-semibold transition-colors dark:text-blue-400 dark:hover:text-blue-300"
                                        :title="data.title"
                                    >
                                        {{ data.title }}
                                    </p>

                                    <p class="truncate text-sm text-gray-600 dark:text-gray-400" v-html="data.summary"></p>
                                </Link>
                            </template>
                        </Column>

                        <!-- Status Column -->
                        <Column field="status.name" header="Task Status" style="min-width: 120px">
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
                            :rows="tasks.meta.per_page"
                            :totalRecords="tasks.meta.total"
                            :rowsPerPageOptions="[10, 25, 50, 100]"
                            :first="(tasks.meta.current_page - 1) * tasks.meta.per_page"
                            @page="onPageChange"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
