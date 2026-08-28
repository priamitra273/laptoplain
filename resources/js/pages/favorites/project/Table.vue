<script setup lang="ts">
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { lucideIconItems } from '@/lib/lucide-icons';
import { can, severityColor } from '@/lib/utils';
import { router } from '@inertiajs/vue3';
import type { TableColumn } from '@nuxt/ui';
import UButton from '@nuxt/ui/components/Button.vue';
import { getPaginationRowModel } from '@tanstack/vue-table';
import type { Column, PaginationState, SortingState, Table } from '@tanstack/vue-table';
import { computed, h, ref, useTemplateRef } from 'vue';
import ProjectDateCell from './DateCell.vue';
import type { Project, ProjectPriorityOption, ProjectStatusOption } from './types';

interface Props {
    data?: Project[];
    statuses?: ProjectStatusOption[];
    priorities?: ProjectPriorityOption[];
}

const props = withDefaults(defineProps<Props>(), {
    data: () => [],
    statuses: () => [],
    priorities: () => [],
});

const confirm = useConfirmDialog();
const toast = useToast();

const pageSizes = [10, 25, 50];

const sortIcon = (direction: false | 'asc' | 'desc') => {
    if (direction === 'asc') return 'i-lucide-arrow-up';
    if (direction === 'desc') return 'i-lucide-arrow-down';
    return 'i-lucide-arrow-up-down';
};

const withSortHeader = (column: TableColumn<Project>): TableColumn<Project> => {
    if (column.enableSorting === false || typeof column.header !== 'string') return column;

    const label = column.header;

    return {
        ...column,
        header: ({ column: col }: { column: Column<Project, unknown> }) =>
            h(UButton, {
                label,
                trailingIcon: sortIcon(col.getIsSorted()),
                variant: 'ghost',
                color: 'neutral',
                size: 'sm',
                class: '-mx-2.5 font-medium',
                onClick: () => col.toggleSorting(col.getIsSorted() === 'asc'),
            }),
    } as TableColumn<Project>;
};

const baseColumns: TableColumn<Project>[] = [
    { header: 'No', enableSorting: false, cell: ({ row }) => row.index + 1, meta: { class: { td: 'w-10' } } },
    { accessorKey: 'project_no', header: 'Project No', meta: { class: { td: 'w-28' } } },
    { accessorKey: 'title', header: 'Title', meta: { class: { td: 'min-w-64' } } },
    { accessorKey: 'status_id', header: 'Status' },
    { accessorKey: 'priority_id', header: 'Priority' },
    { accessorKey: 'start_date', header: 'Start' },
    { accessorKey: 'due_date', header: 'Due' },
    { accessorKey: 'progress', header: 'Progress', meta: { class: { td: 'min-w-40' } } },
    { id: 'actions', header: 'Action', enableSorting: false, meta: { class: { th: 'text-center' } } },
];

const columns = baseColumns.map(withSortHeader);

const globalFilter = ref('');
const sorting = ref<SortingState>([]);
const pagination = ref<PaginationState>({ pageIndex: 0, pageSize: pageSizes[0] });

const titleFilter = ref('');
const statusFilter = ref<string[]>([]);
const priorityFilter = ref<string[]>([]);
const startDateFilter = ref('');
const dueDateFilter = ref('');
const progressFilter = ref<[number, number]>([0, 100]);

const toggleFilterValue = (list: string[], value: string) => (list.includes(value) ? list.filter((item) => item !== value) : [...list, value]);

const isProgressFilterActive = computed(() => progressFilter.value[0] > 0 || progressFilter.value[1] < 100);

const iconSearch = ref('');

const filteredIconItems = computed(() => {
    if (!iconSearch.value) return lucideIconItems;
    const query = iconSearch.value.toLowerCase();
    return lucideIconItems.filter((item) => item.label.toLowerCase().includes(query));
});

const filteredData = computed(() =>
    props.data.filter((project) => {
        if (titleFilter.value && !project.title.toLowerCase().includes(titleFilter.value.toLowerCase())) return false;
        if (statusFilter.value.length && !statusFilter.value.includes(project.status_id ?? '')) return false;
        if (priorityFilter.value.length && !priorityFilter.value.includes(project.priority_id ?? '')) return false;
        if (startDateFilter.value && project.start_date !== startDateFilter.value) return false;
        if (dueDateFilter.value && project.due_date !== dueDateFilter.value) return false;
        if (project.progress < progressFilter.value[0] || project.progress > progressFilter.value[1]) return false;
        return true;
    }),
);

const table = useTemplateRef<{ tableApi: Table<Project> }>('table');
const paginationRowModel = getPaginationRowModel<Project>();

const total = computed(() => table.value?.tableApi?.getFilteredRowModel().rows.length ?? 0);

const page = computed({
    get: () => pagination.value.pageIndex + 1,
    set: (value: number) => (pagination.value = { ...pagination.value, pageIndex: value - 1 }),
});

const pageSize = computed({
    get: () => pagination.value.pageSize,
    set: (value: number) => (pagination.value = { pageIndex: 0, pageSize: value }),
});

type EditablePatch = Partial<Pick<Project, 'title' | 'start_date' | 'due_date' | 'status_id' | 'priority_id' | 'emoji'>>;

const submitUpdate = (project: Project, patch: EditablePatch) => {
    router.put(
        route('project.update', project.id),
        {
            title: project.title,
            start_date: project.start_date,
            due_date: project.due_date,
            status_id: project.status_id,
            priority_id: project.priority_id,
            emoji: project.emoji,
            ...patch,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                const message = Object.values(errors)[0];
                toast.add({ title: 'Failed', description: message ? String(message) : 'Could not update project.', color: 'error' });
            },
        },
    );
};

const onTitleBlur = (project: Project, value: string) => {
    const trimmed = value.trim();
    if (!trimmed || trimmed === project.title) return;
    submitUpdate(project, { title: trimmed });
};

const onDateChange = (project: Project, field: 'start_date' | 'due_date', value: string) => {
    if (!value || value === project[field]) return;
    submitUpdate(project, { [field]: value });
};

const selectIcon = (project: Project, value: string) => {
    iconSearch.value = '';
    submitUpdate(project, { emoji: value });
};

const viewProject = (row: Project) => {
    router.visit(route('project.show.kanban', { encoded: row.id }));
};

const handleDelete = async (row: Project) => {
    const confirmed = await confirm({
        title: 'Delete Project',
        description: `Are you sure want to delete "${row.title}" project?`,
    });

    if (confirmed) {
        router.delete(route('project.destroy', row.id));
    }
};
</script>

<template>
    <div class="space-y-3">
        <UInput v-model="globalFilter" icon="i-lucide-search" placeholder="Search Project" class="md:w-md" />

        <UCard :ui="{ root: 'p-1', body: 'p-0 sm:p-1' }">
            <div>
                <UTable
                    ref="table"
                    v-model:global-filter="globalFilter"
                    v-model:sorting="sorting"
                    v-model:pagination="pagination"
                    :data="filteredData"
                    :columns="columns"
                    :pagination-options="{ getPaginationRowModel: paginationRowModel }"
                    class="flex-1"
                >
                    <template #status_id-header="{ column }">
                        <div class="flex items-center gap-0.5">
                            <UButton
                                label="Status"
                                :trailing-icon="sortIcon(column.getIsSorted())"
                                variant="ghost"
                                color="neutral"
                                size="sm"
                                class="-mx-2.5 font-medium"
                                @click="column.toggleSorting(column.getIsSorted() === 'asc')"
                            />
                            <UPopover>
                                <UButton
                                    icon="i-lucide-list-filter"
                                    :color="statusFilter.length ? 'primary' : 'neutral'"
                                    variant="ghost"
                                    size="xs"
                                    square
                                />
                                <template #content>
                                    <div class="flex w-56 flex-col gap-2 p-3">
                                        <UCheckbox
                                            v-for="option in statuses"
                                            :key="option.id"
                                            :model-value="statusFilter.includes(option.id)"
                                            :label="option.name"
                                            @update:model-value="statusFilter = toggleFilterValue(statusFilter, option.id)"
                                        />
                                        <UButton
                                            v-if="statusFilter.length"
                                            label="Clear"
                                            size="xs"
                                            variant="ghost"
                                            color="neutral"
                                            class="self-start"
                                            @click="statusFilter = []"
                                        />
                                    </div>
                                </template>
                            </UPopover>
                        </div>
                    </template>

                    <template #priority_id-header="{ column }">
                        <div class="flex items-center gap-0.5">
                            <UButton
                                label="Priority"
                                :trailing-icon="sortIcon(column.getIsSorted())"
                                variant="ghost"
                                color="neutral"
                                size="sm"
                                class="-mx-2.5 font-medium"
                                @click="column.toggleSorting(column.getIsSorted() === 'asc')"
                            />
                            <UPopover>
                                <UButton
                                    icon="i-lucide-list-filter"
                                    :color="priorityFilter.length ? 'primary' : 'neutral'"
                                    variant="ghost"
                                    size="xs"
                                    square
                                />
                                <template #content>
                                    <div class="flex w-56 flex-col gap-2 p-3">
                                        <UCheckbox
                                            v-for="option in priorities"
                                            :key="option.id"
                                            :model-value="priorityFilter.includes(option.id)"
                                            :label="option.name"
                                            @update:model-value="priorityFilter = toggleFilterValue(priorityFilter, option.id)"
                                        />
                                        <UButton
                                            v-if="priorityFilter.length"
                                            label="Clear"
                                            size="xs"
                                            variant="ghost"
                                            color="neutral"
                                            class="self-start"
                                            @click="priorityFilter = []"
                                        />
                                    </div>
                                </template>
                            </UPopover>
                        </div>
                    </template>

                    <template #start_date-header="{ column }">
                        <div class="flex items-center gap-0.5">
                            <UButton
                                label="Start"
                                :trailing-icon="sortIcon(column.getIsSorted())"
                                variant="ghost"
                                color="neutral"
                                size="sm"
                                class="-mx-2.5 font-medium"
                                @click="column.toggleSorting(column.getIsSorted() === 'asc')"
                            />
                            <UPopover>
                                <UButton
                                    icon="i-lucide-list-filter"
                                    :color="startDateFilter ? 'primary' : 'neutral'"
                                    variant="ghost"
                                    size="xs"
                                    square
                                />
                                <template #content>
                                    <div class="flex flex-col gap-2 p-3">
                                        <UInput v-model="startDateFilter" type="date" size="sm" />
                                        <UButton
                                            v-if="startDateFilter"
                                            label="Clear"
                                            size="xs"
                                            variant="ghost"
                                            color="neutral"
                                            class="self-start"
                                            @click="startDateFilter = ''"
                                        />
                                    </div>
                                </template>
                            </UPopover>
                        </div>
                    </template>

                    <template #due_date-header="{ column }">
                        <div class="flex items-center gap-0.5">
                            <UButton
                                label="Due"
                                :trailing-icon="sortIcon(column.getIsSorted())"
                                variant="ghost"
                                color="neutral"
                                size="sm"
                                class="-mx-2.5 font-medium"
                                @click="column.toggleSorting(column.getIsSorted() === 'asc')"
                            />
                            <UPopover>
                                <UButton
                                    icon="i-lucide-list-filter"
                                    :color="dueDateFilter ? 'primary' : 'neutral'"
                                    variant="ghost"
                                    size="xs"
                                    square
                                />
                                <template #content>
                                    <div class="flex flex-col gap-2 p-3">
                                        <UInput v-model="dueDateFilter" type="date" size="sm" />
                                        <UButton
                                            v-if="dueDateFilter"
                                            label="Clear"
                                            size="xs"
                                            variant="ghost"
                                            color="neutral"
                                            class="self-start"
                                            @click="dueDateFilter = ''"
                                        />
                                    </div>
                                </template>
                            </UPopover>
                        </div>
                    </template>

                    <template #title-header="{ column }">
                        <div class="flex items-center gap-0.5">
                            <UButton
                                label="Title"
                                :trailing-icon="sortIcon(column.getIsSorted())"
                                variant="ghost"
                                color="neutral"
                                size="sm"
                                class="-mx-2.5 font-medium"
                                @click="column.toggleSorting(column.getIsSorted() === 'asc')"
                            />
                            <UPopover>
                                <UButton icon="i-lucide-list-filter" :color="titleFilter ? 'primary' : 'neutral'" variant="ghost" size="xs" square />
                                <template #content>
                                    <div class="flex flex-col gap-2 p-3">
                                        <UInput v-model="titleFilter" icon="i-lucide-search" placeholder="Filter title" size="sm" />
                                        <UButton
                                            v-if="titleFilter"
                                            label="Clear"
                                            size="xs"
                                            variant="ghost"
                                            color="neutral"
                                            class="self-start"
                                            @click="titleFilter = ''"
                                        />
                                    </div>
                                </template>
                            </UPopover>
                        </div>
                    </template>

                    <template #progress-header="{ column }">
                        <div class="flex items-center gap-0.5">
                            <UButton
                                label="Progress"
                                :trailing-icon="sortIcon(column.getIsSorted())"
                                variant="ghost"
                                color="neutral"
                                size="sm"
                                class="-mx-2.5 font-medium"
                                @click="column.toggleSorting(column.getIsSorted() === 'asc')"
                            />
                            <UPopover>
                                <UButton
                                    icon="i-lucide-list-filter"
                                    :color="isProgressFilterActive ? 'primary' : 'neutral'"
                                    variant="ghost"
                                    size="xs"
                                    square
                                />
                                <template #content>
                                    <div class="flex w-56 flex-col gap-3 p-3">
                                        <USlider v-model="progressFilter" :min="0" :max="100" />
                                        <div class="flex items-center justify-between text-sm text-muted">
                                            <span>{{ progressFilter[0] }}%</span>
                                            <span>{{ progressFilter[1] }}%</span>
                                        </div>
                                        <UButton
                                            v-if="isProgressFilterActive"
                                            label="Clear"
                                            size="xs"
                                            variant="ghost"
                                            color="neutral"
                                            class="self-start"
                                            @click="progressFilter = [0, 100]"
                                        />
                                    </div>
                                </template>
                            </UPopover>
                        </div>
                    </template>

                    <template #title-cell="{ row }">
                        <UInput
                            :model-value="row.original.title"
                            variant="none"
                            size="sm"
                            class="w-full"
                            :disabled="!can('project.update')"
                            @blur="(e: FocusEvent) => onTitleBlur(row.original, (e.target as HTMLInputElement).value)"
                            @keyup.enter="(e: KeyboardEvent) => (e.target as HTMLInputElement).blur()"
                        >
                            <template #leading>
                                <UPopover>
                                    <button
                                        type="button"
                                        :disabled="!can('project.update')"
                                        class="flex size-5 items-center justify-center rounded text-muted hover:text-highlighted disabled:cursor-not-allowed"
                                    >
                                        <Icon v-if="row.original.emoji" :name="row.original.emoji" class="size-4" />
                                        <UIcon v-else name="i-lucide-smile-plus" class="size-4" />
                                    </button>

                                    <template #content>
                                        <div class="flex w-64 flex-col gap-2 p-2">
                                            <UInput v-model="iconSearch" icon="i-lucide-search" placeholder="Search icon..." size="sm" autofocus />
                                            <div class="grid max-h-56 grid-cols-6 gap-1 overflow-y-auto">
                                                <button
                                                    v-for="item in filteredIconItems"
                                                    :key="item.value"
                                                    type="button"
                                                    :title="item.label"
                                                    class="flex size-8 items-center justify-center rounded hover:bg-elevated"
                                                    :class="row.original.emoji === item.value ? 'bg-elevated ring-1 ring-primary' : ''"
                                                    @click="selectIcon(row.original, item.value)"
                                                >
                                                    <Icon :name="item.value" class="size-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </UPopover>
                            </template>
                        </UInput>
                    </template>

                    <template #status_id-cell="{ row }">
                        <USelectMenu
                            :model-value="row.original.status_id ?? undefined"
                            :items="statuses"
                            label-key="name"
                            value-key="id"
                            size="sm"
                            class="w-32"
                            :disabled="!can('project.update')"
                            @update:model-value="(value: string) => submitUpdate(row.original, { status_id: value })"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </template>

                    <template #priority_id-cell="{ row }">
                        <USelectMenu
                            :model-value="row.original.priority_id ?? undefined"
                            :items="priorities"
                            label-key="name"
                            value-key="id"
                            size="sm"
                            class="w-32"
                            :disabled="!can('project.update')"
                            @update:model-value="(value: string) => submitUpdate(row.original, { priority_id: value })"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                    </template>

                    <template #start_date-cell="{ row }">
                        <ProjectDateCell
                            :model-value="row.original.start_date"
                            :disabled="!can('project.update')"
                            @update="(value: string) => onDateChange(row.original, 'start_date', value)"
                        />
                    </template>

                    <template #due_date-cell="{ row }">
                        <ProjectDateCell
                            :model-value="row.original.due_date"
                            :min="row.original.start_date"
                            :disabled="!can('project.update')"
                            @update="(value: string) => onDateChange(row.original, 'due_date', value)"
                        />
                    </template>

                    <template #progress-cell="{ row }">
                        <div class="flex items-center gap-3">
                            <UProgress :model-value="row.original.progress" size="sm" class="w-24 shrink-0" />
                            <span class="w-14 shrink-0 text-muted tabular-nums">{{ row.original.progress }}%</span>
                        </div>
                    </template>

                    <template #actions-cell="{ row }">
                        <div class="flex justify-center gap-2">
                            <UButton
                                v-if="can('project.read')"
                                icon="i-lucide-eye"
                                color="neutral"
                                variant="subtle"
                                aria-label="View Details"
                                @click="viewProject(row.original)"
                            />
                            <UButton
                                v-if="can('project.delete')"
                                icon="i-lucide-trash"
                                color="error"
                                variant="subtle"
                                aria-label="Delete"
                                @click="handleDelete(row.original)"
                            />
                        </div>
                    </template>

                    <template #empty>
                        <p class="text-center text-sm text-muted">No projects found.</p>
                    </template>
                </UTable>
            </div>
        </UCard>

        <div class="flex items-center justify-center gap-3">
            <USelect v-model="pageSize" :items="pageSizes" class="w-20" />
            <UPagination v-model:page="page" :items-per-page="pageSize" :total="total" />
        </div>
    </div>
</template>
