<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/ui/Heading.vue';
import ProjectToolbar from './Toolbar.vue';
import ProjectTable from './Table.vue';
import ProjectFormDrawer from './ProjectFormDrawer.vue';
import type { PrimeSeverity } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { useOverlay } from '@nuxt/ui/composables';
import { computed, onScopeDispose, ref, watch } from 'vue';

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
        navigate();
    }
};

const search = ref(props.filters.search ?? '');
const statusFilter = ref<string | null>(props.filters.status_id ?? null);
const priorityIds = ref<string[]>(props.filters.priority_ids ?? []);
const startDate = ref<string | null>(props.filters.start_date ?? null);
const dueDate = ref<string | null>(props.filters.due_date ?? null);
const progressRange = ref<[number, number]>([props.filters.progress_min ?? 0, props.filters.progress_max ?? 100]);

const clearFilters = () => {
    search.value = '';
    statusFilter.value = null;
    priorityIds.value = [];
    startDate.value = null;
    dueDate.value = null;
    progressRange.value = [0, 100];
};

const sort = ref(props.filters.sort ?? 'id');
const direction = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');

/** Data lama tetap tampil selama request berjalan (preserveState); ini cuma penanda kecil untuk itu. */
const navigating = ref(false);

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
        {
            preserveState: true,
            preserveScroll: true,
            preserveUrl: true,
            replace: true,
            onStart: () => {
                navigating.value = true;
            },
            onFinish: () => {
                navigating.value = false;
            },
        },
    );

    // Berbarengan dengan visit di atas, bukan menunggunya selesai — lihat docblock `refreshCounts`.
    refreshCounts();
};

/**
 * Klik status harus langsung memuat, bukan ikut menunggu 400ms seperti mengetik pencarian —
 * makanya dipisah dari watcher debounced di bawah, bukan digabung dalam satu array.
 */
watch(statusFilter, () => navigate({ page: 1 }));

watchDebounced([search, priorityIds, startDate, dueDate, progressRange], () => navigate({ page: 1 }), { deep: true, debounce: 400 });

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

const inertiaPage = usePage();

/** Terisi langsung dari props kalau tidak ada filter status — tidak perlu menunggu fetch apa pun. */
const badgeCounts = ref<Record<string, number> | null>(statusFilter.value ? null : props.statusCounts);
const badgeTotal = computed(() => (badgeCounts.value ? Object.values(badgeCounts.value).reduce((sum, count) => sum + count, 0) : null));
const countsFailed = ref(false);
let countsController: AbortController | null = null;
let countsGeneration = 0;

/**
 * Dibaca dari ref reaktif yang sedang dikirim `navigate()`, bukan `props.filters` (hasil
 * visit sebelumnya) — supaya panggilan ini bisa jalan berbarengan dengan visit utama, bukan
 * menunggunya selesai dulu. Menunggu tidak perlu: nilainya sudah diketahui saat ini juga, dan
 * itu persis nilai yang baru saja dikirim `navigate()`.
 */
const refreshCounts = async () => {
    const generation = ++countsGeneration;
    countsController?.abort();
    countsFailed.value = false;
    if (!statusFilter.value) {
        badgeCounts.value = props.statusCounts;
        return;
    }
    const controller = new AbortController();
    countsController = controller;
    const query = new URLSearchParams();
    if (search.value) query.set('search', search.value);
    if (startDate.value) query.set('start_date', startDate.value);
    if (dueDate.value) query.set('due_date', dueDate.value);
    if (progressRange.value[0] !== 0) query.set('progress_min', String(progressRange.value[0]));
    if (progressRange.value[1] !== 100) query.set('progress_max', String(progressRange.value[1]));
    priorityIds.value.forEach((id) => query.append('priority_ids[]', id));
    try {
        const response = await fetch(route('project.index') + '?' + query.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Inertia': 'true',
                'X-Inertia-Version': inertiaPage.version ?? '',
                'X-Inertia-Partial-Component': inertiaPage.component,
                'X-Inertia-Partial-Data': 'statusCounts',
            },
            signal: controller.signal,
        });
        if (!response.ok) throw new Error('Could not load project counts');
        const body = await response.json();
        const counts = body.props?.statusCounts;
        if (
            body.component !== inertiaPage.component ||
            !counts ||
            typeof counts !== 'object' ||
            (Array.isArray(counts) && counts.length > 0) ||
            !Object.values(counts).every((value) => typeof value === 'number' && Number.isFinite(value) && value >= 0)
        ) {
            throw new Error('Invalid project counts');
        }
        if (generation === countsGeneration) badgeCounts.value = Array.isArray(counts) ? {} : counts;
    } catch {
        if (generation === countsGeneration && !controller.signal.aborted) {
            badgeCounts.value = null;
            countsFailed.value = true;
        }
    }
};
/** Menyinkronkan hitungan tanpa filter setiap kali visit utama membawa nilai baru dari server. */
watch(
    () => props.statusCounts,
    (counts) => {
        if (!statusFilter.value) {
            badgeCounts.value = counts;
        }
    },
);

/** Filter status aktif sejak muat awal (misalnya dari URL) belum tercakup default di atas. */
if (statusFilter.value) {
    refreshCounts();
}

onScopeDispose(() => {
    countsGeneration++;
    countsController?.abort();
});

const statusOptions = computed(() =>
    props.statuses.map((option) => ({
        ...option,
        count: badgeCounts.value ? (badgeCounts.value[option.id] ?? 0) : null,
    })),
);
</script>

<template>
    <AppLayout title="Projects">
        <Head title="Projects" />

        <div class="flex flex-col gap-5">
            <Heading title="Project" description="Manage and track all your projects" />

            <ProjectToolbar
                v-model:search="search"
                v-model:status="statusFilter"
                v-model:priority-ids="priorityIds"
                v-model:start-date="startDate"
                v-model:due-date="dueDate"
                v-model:progress-range="progressRange"
                :statuses="statusOptions"
                :priorities="priorities"
                :total="badgeTotal"
                :loading="navigating"
                @clear="clearFilters"
                @create="openCreateProject"
            />

            <div v-if="countsFailed" class="flex items-center gap-2 text-xs text-muted" role="status">
                Could not load status counts.
                <UButton label="Retry" variant="link" size="xs" @click="refreshCounts" />
            </div>

            <ProjectTable
                :projects="projects.data"
                :statuses="statuses"
                :priorities="priorities"
                v-model:page="page"
                v-model:per-page="perPage"
                :total="projects.total"
                :sort="sort"
                :loading="navigating"
                @sort="applySort"
            />
        </div>
    </AppLayout>
</template>
