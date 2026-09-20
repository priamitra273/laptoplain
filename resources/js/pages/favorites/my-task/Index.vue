<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Heading from '@/components/ui/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import MyTaskBoard from './Board.vue';
import MyTaskTable from './Table.vue';
import MyTaskToolbar from './Toolbar.vue';
import type {
    AssignedTask,
    MyTaskBadge,
    MyTaskBoardColumn,
    MyTaskFilters,
    MyTaskProject,
    MyTaskQueryParams,
    MyTaskStatus,
    MyTaskSummaryEntry,
    Paginator,
} from './types';

const props = withDefaults(
    defineProps<{
        view: 'board' | 'list';
        filters: MyTaskFilters;
        statuses: MyTaskStatus[];
        priorities: MyTaskBadge[];
        types: MyTaskBadge[];
        projects: MyTaskProject[];
        summary: MyTaskSummaryEntry[];
        board?: MyTaskBoardColumn[];
        tasks?: Paginator<AssignedTask>;
    }>(),
    { board: () => [], tasks: undefined },
);

const userName = computed(() => usePage().props.auth.user.name);

/** Semua kontrol dihidrasi sekali dari filter yang dikembalikan server, lalu hanya diubah user. */
const view = ref<'board' | 'list'>(props.view);
const search = ref(props.filters.search ?? '');
const projectId = ref<string | undefined>(props.filters.project_id ?? undefined);
const statusId = ref<string | null>(props.filters.status_id ?? null);
const priorityId = ref<string | undefined>(props.filters.priority_id ?? undefined);
const typeId = ref<string | undefined>(props.filters.type_id ?? undefined);
const page = ref(props.tasks?.current_page ?? 1);
const perPage = ref(props.filters.per_page);
const loading = ref(false);

const boardTotal = computed(() => (props.board ?? []).reduce((sum, column) => sum + column.total, 0));
const total = computed(() => (view.value === 'list' ? (props.tasks?.total ?? 0) : boardTotal.value));
const hasActiveFilters = computed(() => !!(search.value.trim() || projectId.value || statusId.value || priorityId.value || typeId.value));

/**
 * Skeleton hanya untuk tampilan yang memang belum punya isi — misalnya saat pindah ke List
 * pertama kali. Saat menyaring, isi lama dibiarkan terlihat dengan penanda "Updating", meniru
 * tab List project, supaya tabel tidak berkedip tiap ketikan.
 */
const showSkeleton = computed(() => loading.value && !total.value);
const showEmpty = computed(() => !loading.value && !total.value);

/** Filter aktif tanpa `view`/`page` — dipakai board untuk "Load more" per kolom. */
const filterParams = computed<MyTaskQueryParams>(() => {
    const params: MyTaskQueryParams = {};

    if (search.value.trim()) params.search = search.value.trim();
    if (projectId.value) params.project_id = projectId.value;
    if (priorityId.value) params.priority_id = priorityId.value;
    if (typeId.value) params.type_id = typeId.value;

    return params;
});

/**
 * Ringkasan dari server ikut tersaring `status_id`, jadi memilih satu status membuat angka
 * status lain jatuh ke nol. Angkanya diambil ulang lewat permintaan parsial tanpa `status_id` —
 * pola yang sama dengan `refreshCounts` di halaman Project, dan tidak menyentuh sisi server.
 */
const summaryCounts = ref<Record<string, number>>({});

const applySummary = (entries: MyTaskSummaryEntry[]) => {
    summaryCounts.value = Object.fromEntries(entries.map((entry) => [entry.id, entry.count]));
};

const refreshSummaryCounts = () => {
    router.get(
        route('task.index'),
        { ...filterParams.value, view: view.value },
        {
            only: ['summary'],
            preserveState: true,
            preserveScroll: true,
            preserveUrl: true,
            replace: true,
            onSuccess: () => applySummary(props.summary),
        },
    );
};

/** Status tanpa task tetap tampil sebagai `0`, sama seperti pil "Not Started 0" di halaman Project. */
const statusOptions = computed(() => props.statuses.map((status) => ({ ...status, count: summaryCounts.value[status.id] ?? 0 })));
const summaryTotal = computed(() => Object.values(summaryCounts.value).reduce((sum, count) => sum + count, 0));

const query = () => {
    const params: Record<string, string | number> = { ...filterParams.value, view: view.value };

    if (statusId.value) params.status_id = statusId.value;

    if (view.value === 'list') {
        params.per_page = perPage.value;
        params.page = page.value;
    }

    return params;
};

const navigate = () => {
    router.get(route('task.index'), query(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['tasks', 'board', 'summary', 'filters', 'view'],
        onStart: () => {
            loading.value = true;
        },
        onFinish: () => {
            loading.value = false;

            if (statusId.value) {
                refreshSummaryCounts();
            } else {
                applySummary(props.summary);
            }
        },
    });
};

/** Ganti filter selalu kembali ke halaman pertama, kalau tidak hasilnya bisa jatuh di halaman kosong. */
const navigateFromFirstPage = () => {
    page.value = 1;
    navigate();
};

watchDebounced(search, navigateFromFirstPage, { debounce: 400 });
watch([projectId, statusId, priorityId, typeId], navigateFromFirstPage);
watch(view, navigateFromFirstPage);

/**
 * Tabel menulis lewat v-model, jadi setter-nya sekalian yang memicu permintaan ke server.
 * Mengubah ukuran halaman selalu kembali ke halaman satu, sama seperti mengubah filter.
 */
const tablePage = computed({
    get: () => page.value,
    set: (value: number) => {
        page.value = value;
        navigate();
    },
});

const tablePerPage = computed({
    get: () => perPage.value,
    set: (value: number) => {
        perPage.value = value;
        navigateFromFirstPage();
    },
});

const clearFilters = () => {
    search.value = '';
    projectId.value = undefined;
    statusId.value = null;
    priorityId.value = undefined;
    typeId.value = undefined;
};

/** Filter status aktif sejak muat awal (misalnya dari URL) membuat `summary` bawaan sudah tersaring. */
if (statusId.value) {
    refreshSummaryCounts();
} else {
    applySummary(props.summary);
}
</script>

<template>
    <AppLayout title="My Task">
        <Head title="My Task" />

        <div class="flex flex-col gap-5">
            <Heading title="My Task" :description="`Manage and track your work items — ${userName}`" />

            <MyTaskToolbar
                v-model:search="search"
                v-model:project-id="projectId"
                v-model:status-id="statusId"
                v-model:priority-id="priorityId"
                v-model:type-id="typeId"
                v-model:view="view"
                :projects="projects"
                :statuses="statusOptions"
                :priorities="priorities"
                :types="types"
                :total="summaryTotal"
                :disabled="loading"
                @clear="clearFilters"
            />

            <div v-if="loading && total" role="status" class="flex items-center gap-2 text-xs text-muted">
                <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" />
                Updating assignments…
            </div>

            <div v-if="showSkeleton" role="status" :aria-label="`Loading ${view}`">
                <div v-if="view === 'board'" class="flex items-start gap-4 overflow-x-auto pb-2">
                    <div
                        v-for="column in Math.max(statuses.length, 4)"
                        :key="column"
                        class="flex w-72 shrink-0 flex-col gap-3 rounded-xl p-3 ring ring-default"
                    >
                        <USkeleton class="h-4 w-28" />
                        <USkeleton v-for="card in 3" :key="card" class="h-24 w-full rounded-lg" />
                    </div>
                </div>

                <div v-else class="overflow-hidden rounded-xl border border-default">
                    <USkeleton v-for="row in 6" :key="row" class="m-4 h-9" />
                </div>
            </div>

            <div v-else-if="showEmpty" class="flex flex-col items-center gap-3">
                <EmptyState
                    icon="i-lucide-inbox"
                    :title="hasActiveFilters ? 'No matching tasks' : 'No tasks yet'"
                    :description="hasActiveFilters ? 'Try adjusting your search or filters.' : 'Tasks assigned to you will appear here.'"
                />
            </div>

            <MyTaskTable v-else-if="view === 'list'" v-model:page="tablePage" v-model:per-page="tablePerPage" :tasks="tasks" :loading="loading" />

            <MyTaskBoard v-else :board="board" :filter-params="filterParams" @moved="refreshSummaryCounts" />
        </div>
    </AppLayout>
</template>
