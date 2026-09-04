<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/ui/Heading.vue';
import ProjectToolbar from './Toolbar.vue';
import ProjectTable from './Table.vue';
import ProjectFormDrawer from './ProjectFormDrawer.vue';
import type { PrimeSeverity } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { useOverlay } from '@nuxt/ui/composables';
import { computed, ref } from 'vue';

interface ProjectStatus {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

interface ProjectPriority {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

interface Project {
    id: string;
    project_no: string | null;
    title: string | null;
    emoji: string | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    status_id: string | null;
    priority_id: string | null;
    status: ProjectStatus | null;
    priority: ProjectPriority | null;
    owner: { id: string; name: string; email: string; avatar_url: string | null } | null;
}

interface ProjectPaginator {
    data: Project[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface ProjectFilters {
    search: string | null;
    status_id: string | null;
    priority_ids: string[] | null;
    start_date: string | null;
    due_date: string | null;
    progress_min: number;
    progress_max: number;
    sort: string;
    direction: 'asc' | 'desc';
}

const props = defineProps<{
    projects: ProjectPaginator;
    statuses: ProjectStatus[];
    priorities: ProjectPriority[];
    statusCounts: Record<string, number>;
    filters: ProjectFilters;
}>();

const overlay = useOverlay();
const projectForm = overlay.create(ProjectFormDrawer);

const openCreateProject = async () => {
    const saved = await projectForm.open({ statuses: props.statuses, priorities: props.priorities });

    if (saved) {
        router.reload({ only: ['projects', 'statusCounts'] });
    }
};

const search = ref(props.filters.search ?? '');
const statusFilter = ref<string | null>(props.filters.status_id ?? null);
const priorityIds = ref<string[]>(props.filters.priority_ids ?? []);
const startDate = ref<string | null>(props.filters.start_date ?? null);
const dueDate = ref<string | null>(props.filters.due_date ?? null);
const progressRange = ref<[number, number]>([props.filters.progress_min ?? 0, props.filters.progress_max ?? 100]);

const sort = ref(props.filters.sort ?? 'id');
const direction = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');

const navigate = (overrides: { page?: number; per_page?: number } = {}) => {
    router.get(
        route('project.index'),
        {
            search: search.value || undefined,
            status_id: statusFilter.value || undefined,
            priority_ids: priorityIds.value.length ? priorityIds.value : undefined,
            start_date: startDate.value || undefined,
            due_date: dueDate.value || undefined,
            progress_min: progressRange.value[0] !== 0 ? progressRange.value[0] : undefined,
            progress_max: progressRange.value[1] !== 100 ? progressRange.value[1] : undefined,
            sort: sort.value,
            direction: direction.value,
            page: overrides.page ?? props.projects.current_page,
            per_page: overrides.per_page ?? props.projects.per_page,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watchDebounced([search, statusFilter, priorityIds, startDate, dueDate, progressRange], () => navigate({ page: 1 }), { deep: true, debounce: 400 });

const applySort = (column: string) => {
    if (sort.value === column) {
        direction.value = direction.value === 'desc' ? 'asc' : 'desc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }

    navigate({ page: 1 });
};

const page = computed({
    get: () => props.projects.current_page,
    set: (value: number) => navigate({ page: value }),
});

const perPage = computed({
    get: () => props.projects.per_page,
    set: (value: number) => navigate({ page: 1, per_page: value }),
});

const VISIBLE_STATUS_NAMES = ['In Progress', 'Not Started', 'Completed'];

const statusOptions = computed(() =>
    VISIBLE_STATUS_NAMES.map((name) => props.statuses.find((option) => option.name === name))
        .filter((option): option is ProjectStatus => option !== undefined)
        .map((option) => ({
            ...option,
            count: props.statusCounts[option.id] ?? 0,
        })),
);
</script>

<template>
    <AppLayout title="Projects">
        <Head title="Projects" />

        <div class="flex flex-col gap-5">
            <Heading title="Project" description="Manage and track all your projects">
                <UButton label="Add Project" icon="i-lucide-plus" @click="openCreateProject" />
            </Heading>

            <ProjectToolbar
                v-model:search="search"
                v-model:status="statusFilter"
                v-model:priority-ids="priorityIds"
                v-model:start-date="startDate"
                v-model:due-date="dueDate"
                v-model:progress-range="progressRange"
                :statuses="statusOptions"
                :priorities="priorities"
                :total="projects.total"
            />

            <ProjectTable
                :projects="projects.data"
                :statuses="statuses"
                :priorities="priorities"
                v-model:page="page"
                v-model:per-page="perPage"
                :total="projects.total"
                :sort="sort"
                :direction="direction"
                @sort="applySort"
            />
        </div>
    </AppLayout>
</template>
