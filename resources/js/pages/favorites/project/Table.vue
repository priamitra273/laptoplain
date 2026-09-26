<script setup lang="ts">
import DatePicker from '@/components/form/DatePicker.vue';
import EmojiPicker from '@/components/form/EmojiPicker.vue';
import BadgeSelect from '@/components/form/BadgeSelect.vue';
import ProgressWithLabel from '@/components/common/ProgressWithLabel.vue';
import TaskDueDateDialog from '@/components/task/TaskDueDateDialog.vue';
import ServerDataTable from '@/components/ui/ServerDataTable.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import InlineTextEdit from '@/components/form/InlineTextEdit.vue';
import { daysUntil, dueDateTone, formatDate, formatRelativeDay, type DueDateTone } from '@/lib/date';
import type { PrimeSeverity } from '@/types';
import type { TableColumn } from '@nuxt/ui';
import { parseDate, type CalendarDate } from '@internationalized/date';
import { router } from '@inertiajs/vue3';
import UButton from '@nuxt/ui/components/Button.vue';
import { useOverlay, useToast } from '@nuxt/ui/composables';
import { computed } from 'vue';

const toast = useToast();
const overlay = useOverlay();
const dueDateDialog = overlay.create(TaskDueDateDialog);
const confirm = useConfirmDialog();

const STATUSES_REQUIRING_DUE_DATE = ['Not Started', 'In Progress'];

interface Project {
    id: string;
    project_no: string | null;
    title: string | null;
    emoji: string | null;
    start_date: string | null;
    due_date: string | null;
    progress: number;
    status: { id: string; name: string; severity: PrimeSeverity | null } | null;
    priority: { id: string; name: string; severity: PrimeSeverity | null } | null;
    owner: { name: string } | null;
}

interface StatusOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

interface PriorityOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

const props = defineProps<{
    projects: Project[];
    statuses: StatusOption[];
    priorities: PriorityOption[];
    total: number;
    sort: string;
    loading?: boolean;
}>();

const emit = defineEmits<{
    sort: [column: string];
}>();

const page = defineModel<number>('page', { required: true });
const perPage = defineModel<number>('perPage', { required: true });

/**
 * `dueDateTone` di lib sengaja tidak tahu status, jadi penyaringannya di sini: project yang sudah
 * Completed tidak punya urgensi — due date-nya tinggal catatan, bukan tenggat. Flag overdue-nya
 * dihitung sendiri karena project belum punya field seperti `is_overdue` di task.
 */
const INACTIVE_STATUSES = ['Completed', 'Cancelled'];

const projectDueTone = (project: Project): DueDateTone => {
    if (!project.due_date) {
        return 'none';
    }

    /** Project yang sudah tidak berjalan tidak punya urgensi, jadi catatannya tidak perlu tampil. */
    if (INACTIVE_STATUSES.includes(project.status?.name ?? '')) {
        return 'none';
    }

    return dueDateTone(project.due_date, (daysUntil(project.due_date) ?? 0) < 0);
};

const rows = computed(() =>
    props.projects.map((project) => ({
        id: project.id,
        project_no: project.project_no ?? '—',
        title: project.title ?? 'Untitled project',
        emoji: project.emoji,
        owner_name: project.owner?.name ?? '—',
        status: { id: project.status?.id ?? '', name: project.status?.name ?? '—', severity: project.status?.severity ?? null },
        priority: { id: project.priority?.id ?? '', name: project.priority?.name ?? '—', severity: project.priority?.severity ?? null },
        start_date: formatDate(project.start_date),
        start_date_iso: project.start_date,
        due_date: formatDate(project.due_date),
        due_date_iso: project.due_date,
        due_tone: projectDueTone(project),
        due_relative: formatRelativeDay(project.due_date),
        progress: project.progress,
    })),
);

type ProjectRow = (typeof rows.value)[number];

/** Nada yang sama dengan tabel My Task, supaya due date terbaca serupa di kedua halaman. */
const dueDateClass = (row: ProjectRow) =>
    ({ overdue: 'text-error font-medium', soon: 'text-warning', normal: 'text-muted', none: 'text-dimmed' })[row.due_tone];

const updateProject = (id: string, payload: Record<string, string>) => {
    router.put(route('project.update', id), payload, {
        preserveScroll: true,
        preserveState: true,
        onError: () => toast.add({ title: 'Failed to update project', color: 'error', icon: 'i-lucide-circle-alert' }),
    });
};

const updateProjectDate = (id: string, field: 'start_date' | 'due_date', currentIso: string | null, value: CalendarDate | undefined) => {
    const iso = value?.toString();
    if (!iso || iso === currentIso) {
        return;
    }

    updateProject(id, { [field]: iso });
};

const deleteProject = async (row: ProjectRow) => {
    const confirmed = await confirm({
        title: 'Delete Project',
        description: `Are you sure you want to delete "${row.title}"? This will also delete all of its tasks.`,
    });

    if (!confirmed) {
        return;
    }

    router.delete(route('project.destroy', row.id), {
        preserveScroll: true,
        onError: () => toast.add({ title: 'Failed to delete project', color: 'error', icon: 'i-lucide-circle-alert' }),
    });
};

const changeStatus = async (row: ProjectRow, newStatusId: string) => {
    const newStatus = props.statuses.find((option) => option.id === newStatusId);
    const needsDueDate = newStatus && STATUSES_REQUIRING_DUE_DATE.includes(newStatus.name) && !row.due_date_iso;

    if (needsDueDate) {
        const dueDate = await dueDateDialog.open({
            taskTitle: row.title,
            statusName: newStatus.name,
        });

        if (!dueDate) {
            return;
        }

        updateProject(row.id, { status_id: newStatusId, due_date: dueDate });
        return;
    }

    updateProject(row.id, { status_id: newStatusId });
};

/** `id` dipakai sebagai kolom sort ke server; kolom tanpa `enableSorting: false` otomatis dapat header tombol. */
const columns: TableColumn<ProjectRow>[] = [
    { id: 'no', header: 'No', enableSorting: false },
    { accessorKey: 'project_no', header: 'Project No' },
    { accessorKey: 'title', header: 'Title' },
    { accessorKey: 'status', id: 'status_id', header: 'Status' },
    { accessorKey: 'priority', id: 'priority_id', header: 'Priority' },
    { accessorKey: 'start_date', header: 'Start' },
    { accessorKey: 'due_date', header: 'Due' },
    { accessorKey: 'progress', header: 'Progress' },
    {
        id: 'actions',
        header: 'Action',
        enableSorting: false,
        meta: {
            class: {
                th: 'sticky right-0 bg-default border-s border-default',
                td: 'sticky right-0 bg-default border-s border-default',
            },
        },
    },
];
</script>

<template>
    <ServerDataTable
        v-model:page="page"
        v-model:per-page="perPage"
        :data="rows"
        :columns="columns"
        :sort="sort"
        :total="total"
        :loading="loading"
        empty="No projects found."
        result-label="projects"
        table-base-class="min-w-full"
        @sort="emit('sort', $event)"
    >
        <template #no-cell="{ row }">
            <span class="text-sm text-muted">{{ (page - 1) * perPage + row.index + 1 }}</span>
        </template>

        <template #title-cell="{ row }">
            <div class="flex min-w-0 items-center gap-2.5">
                <UPopover :content="{ side: 'right', align: 'start' }">
                    <button
                        type="button"
                        class="flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-xl hover:bg-elevated"
                    >
                        {{ row.original.emoji }}
                    </button>

                    <template #content>
                        <EmojiPicker
                            :model-value="row.original.emoji"
                            @update:model-value="(value) => updateProject(row.original.id, { emoji: value })"
                        />
                    </template>
                </UPopover>

                <div class="flex min-w-0 flex-col">
                    <InlineTextEdit :value="row.original.title" @save="(value) => updateProject(row.original.id, { title: value })">
                        <template #default="{ startEditing }">
                            <button
                                type="button"
                                class="-mx-1.5 -my-0.5 cursor-text truncate rounded-md px-1.5 py-0.5 text-start text-sm font-semibold text-highlighted hover:bg-elevated"
                                @click="startEditing"
                            >
                                {{ row.original.title }}
                            </button>
                        </template>
                    </InlineTextEdit>
                </div>
            </div>
        </template>

        <template #status_id-cell="{ row }">
            <BadgeSelect
                :model-value="row.original.status.id"
                :items="statuses"
                class="w-auto"
                :ui="{ base: 'border-0 bg-transparent shadow-none ring-0 p-0', trailingIcon: 'hidden', content: 'w-48' }"
                @update:model-value="(value) => changeStatus(row.original, value)"
            />
        </template>

        <template #priority_id-cell="{ row }">
            <BadgeSelect
                display="priority"
                :model-value="row.original.priority.id"
                :items="priorities"
                class="w-auto"
                :ui="{ base: 'border-0 bg-transparent shadow-none ring-0 p-0', trailingIcon: 'hidden', content: 'w-48' }"
                @update:model-value="(value) => updateProject(row.original.id, { priority_id: value })"
            />
        </template>

        <template #start_date-cell="{ row }">
            <DatePicker
                :model-value="row.original.start_date_iso ? parseDate(row.original.start_date_iso) : undefined"
                :label="row.original.start_date"
                trigger-aria-label="Change start date"
                appearance="inline"
                :max-value="row.original.due_date_iso ? parseDate(row.original.due_date_iso) : undefined"
                trigger-class="rounded-md px-1.5 py-0.5 text-sm"
                @update:model-value="(value) => updateProjectDate(row.original.id, 'start_date', row.original.start_date_iso, value)"
            />
        </template>

        <template #due_date-cell="{ row }">
            <div class="flex flex-col items-start">
                <DatePicker
                    :model-value="row.original.due_date_iso ? parseDate(row.original.due_date_iso) : undefined"
                    :label="row.original.due_date"
                    trigger-aria-label="Change due date"
                    appearance="inline"
                    :min-value="row.original.start_date_iso ? parseDate(row.original.start_date_iso) : undefined"
                    trigger-class="rounded-md px-1.5 py-0.5 text-start text-sm"
                    @update:model-value="(value) => updateProjectDate(row.original.id, 'due_date', row.original.due_date_iso, value)"
                />

                <span v-if="row.original.due_tone !== 'none'" class="px-1.5 text-xs" :class="dueDateClass(row.original)">
                    {{ row.original.due_relative }}
                </span>
            </div>
        </template>
        <template #progress-cell="{ row }">
            <ProgressWithLabel :value="row.original.progress" bar-aria-label="Project progress" class="min-w-36" />
        </template>

        <template #actions-cell="{ row }">
            <div class="flex items-center gap-2">
                <UButton
                    icon="i-lucide-eye"
                    color="neutral"
                    variant="outline"
                    size="sm"
                    :to="route('project.show.kanban', row.original.id)"
                    aria-label="View detail"
                />

                <UButton icon="i-lucide-trash-2" color="error" variant="solid" size="sm" aria-label="Delete" @click="deleteProject(row.original)" />
            </div>
        </template>
    </ServerDataTable>
</template>
