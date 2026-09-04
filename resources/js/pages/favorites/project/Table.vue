<script setup lang="ts">
import EmojiPicker from '@/components/EmojiPicker.vue';
import TaskDueDateDialog from '@/components/TaskDueDateDialog.vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { severityColor, severityDotClass } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import type { TableColumn } from '@nuxt/ui';
import { parseDate } from '@internationalized/date';
import { router } from '@inertiajs/vue3';
import UButton from '@nuxt/ui/components/Button.vue';
import { useOverlay, useToast } from '@nuxt/ui/composables';
import { computed, h, ref } from 'vue';


const toast = useToast();
const overlay = useOverlay();
const dueDateDialog = overlay.create(TaskDueDateDialog);
const confirm = useConfirmDialog();


const STATUSES_REQUIRING_DUE_DATE = ['Not Started', 'In Progress'];
const PRIORITY_ICONS: Record<string, string> = {
    Low: 'i-lucide-signal-low',
    Medium: 'i-lucide-signal-medium',
    High: 'i-lucide-signal-high',
    Critical: 'i-lucide-signal',
};

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
    direction: 'asc' | 'desc';
}>();

const emit = defineEmits<{
    sort: [column: string];
}>();

const page = defineModel<number>('page', { required: true });
const perPage = defineModel<number>('perPage', { required: true });

const dateFormatter = new Intl.DateTimeFormat('en-US', { day: 'numeric', month: 'short', year: 'numeric' });

const formatDate = (iso: string | null): string => (iso ? dateFormatter.format(new Date(iso)) : '—');

const dueNote = (project: Project): { text: string; class: string } | null => {
    if (!project.due_date) {
        return null;
    }

    if (project.status?.name === 'Completed') {
        return { text: 'delivered', class: 'text-muted' };
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffDays = Math.round((new Date(project.due_date).getTime() - today.getTime()) / 86400000);

    if (diffDays < 0) {
        return { text: `${Math.abs(diffDays)}d overdue`, class: 'text-error' };
    }

    if (diffDays <= 7) {
        return { text: diffDays === 0 ? 'due today' : `due in ${diffDays}d`, class: 'text-warning' };
    }

    return null;
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
        due_note: dueNote(project),
        progress: project.progress,
    })),
);

type ProjectRow = (typeof rows.value)[number];


const priorityIcon = (name: string): string => PRIORITY_ICONS[name] ?? 'i-lucide-signal-low';

const updateProject = (id: string, payload: Record<string, string>) => {
    router.put(route('project.update', id), payload, {
        preserveScroll: true,
        preserveState: true,
        onError: () => toast.add({ title: 'Failed to update project', color: 'error', icon: 'i-lucide-circle-alert' }),
    });
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

const editingTitleId = ref<string | null>(null);
const titleDraft = ref('');

const startEditingTitle = (row: ProjectRow) => {
    editingTitleId.value = row.id;
    titleDraft.value = row.title;
};

const saveTitle = (row: ProjectRow) => {
    editingTitleId.value = null;

    if (titleDraft.value.trim() && titleDraft.value !== row.title) {
        updateProject(row.id, { title: titleDraft.value.trim() });
    }
};

const pageSizes = [
    { label: '10 / page', value: 10 },
    { label: '25 / page', value: 25 },
    { label: '50 / page', value: 50 },
];

const rangeStart = computed(() => (props.total === 0 ? 0 : (page.value - 1) * perPage.value + 1));
const rangeEnd = computed(() => Math.min(page.value * perPage.value, props.total));

// Header berupa teks doang (tanpa ikon panah) — kolom yang lagi aktif dibedakan
// lewat warna teks (primary + tebal), sama pola kayak workload/Table.vue.
const withSortHeader = (column: string, label: string): TableColumn<ProjectRow>['header'] => () => {
    const isSorted = props.sort === column;

    return h(UButton, {
        label,
        variant: 'ghost',
        color: isSorted ? 'primary' : 'neutral',
        size: 'sm',
        class: ['-mx-2.5', isSorted ? 'font-semibold' : 'font-medium'],
        onClick: () => emit('sort', column),
    });
};

const columns: TableColumn<ProjectRow>[] = [
    { id: 'no', header: 'No' },
    { accessorKey: 'project_no', header: withSortHeader('project_no', 'Project No') },
    { accessorKey: 'title', header: withSortHeader('title', 'Title') },
    { accessorKey: 'status', header: withSortHeader('status_id', 'Status') },
    { accessorKey: 'priority', header: withSortHeader('priority_id', 'Priority') },
    { accessorKey: 'start_date', header: withSortHeader('start_date', 'Start') },
    { accessorKey: 'due_date', header: withSortHeader('due_date', 'Due') },
    { accessorKey: 'progress', header: withSortHeader('progress', 'Progress') },
    {
        id: 'actions',
        header: 'Action',
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
    <div class="overflow-hidden rounded-md ring ring-default">
        <UTable :data="rows" :columns="columns">
            <template #no-cell="{ row }">
                <span class="text-sm text-muted">{{ (page - 1) * perPage + row.index + 1 }}</span>
            </template>

            <template #title-cell="{ row }">
                <div class="flex min-w-0 items-center gap-2.5">
                    <UPopover :content="{ side: 'right', align: 'start' }">
                        <button type="button" class="flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-xl hover:bg-elevated">
                            {{ row.original.emoji }}
                        </button>

                        <template #content>
                            <EmojiPicker :model-value="row.original.emoji" @update:model-value="(value) => updateProject(row.original.id, { emoji: value })" />
                        </template>
                    </UPopover>

                    <div class="flex min-w-0 flex-col">
                        <UInput
                            v-if="editingTitleId === row.original.id"
                            v-model="titleDraft"
                            size="sm"
                            autofocus
                            @blur="saveTitle(row.original)"
                            @keyup.enter="saveTitle(row.original)"
                            @keyup.esc="editingTitleId = null"
                        />
                        <button
                            v-else
                            type="button"
                            class="-mx-1.5 -my-0.5 truncate rounded-md px-1.5 py-0.5 text-start text-sm font-semibold text-highlighted cursor-text hover:bg-elevated"
                            @click="startEditingTitle(row.original)"
                        >
                            {{ row.original.title }}
                        </button>
                    </div>
                </div>
            </template>


            <template #status-cell="{ row }">
                <USelectMenu
                    :model-value="row.original.status.id"
                    :items="statuses"
                    label-key="name"
                    value-key="id"
                    class="w-auto"
                    :ui="{ base: 'border-0 bg-transparent shadow-none ring-0 p-0', trailingIcon: 'hidden', content: 'w-48' }"
                    @update:model-value="(value) => changeStatus(row.original, value as string)"
                >
                    <template #default>
                        <UBadge color="neutral" variant="subtle" size="sm">
                            <template #leading>
                                <span class="size-1.5 shrink-0 rounded-full" :class="severityDotClass(row.original.status.severity)" />
                            </template>

                            {{ row.original.status.name }}
                        </UBadge>
                    </template>

                    <template #item-leading="{ item }">
                        <span class="size-1.5 shrink-0 rounded-full" :class="severityDotClass(item.severity)" />
                    </template>
                </USelectMenu>
            </template>

            <template #priority-cell="{ row }">
                <USelectMenu
                    :model-value="row.original.priority.id"
                    :items="priorities"
                    label-key="name"
                    value-key="id"
                    class="w-auto"
                    :ui="{ base: 'border-0 bg-transparent shadow-none ring-0 p-0', trailingIcon: 'hidden', content: 'w-48' }"
                    @update:model-value="(value) => updateProject(row.original.id, { priority_id: value as string })"
                >
                    <template #default>
                        <div class="flex items-center gap-1.5" :class="`text-${severityColor(row.original.priority.severity)}`">
                            <UIcon :name="priorityIcon(row.original.priority.name)" class="size-4" />
                            <span class="text-sm font-medium">{{ row.original.priority.name }}</span>
                        </div>
                    </template>

                    <template #item-leading="{ item }">
                        <UIcon :name="priorityIcon(item.name)" class="size-4" :class="`text-${severityColor(item.severity)}`" />
                    </template>

                    <template #item-label="{ item }">
                        <span :class="`text-${severityColor(item.severity)}`">{{ item.name }}</span>
                    </template>
                </USelectMenu>
            </template>

            <template #start_date-cell="{ row }">
                <UPopover>
                    <button type="button" class="cursor-pointer rounded-md px-1.5 py-0.5 text-sm hover:bg-elevated">
                        {{ row.original.start_date }}
                    </button>

                    <template #content>
                        <UCalendar
                            :model-value="row.original.start_date_iso ? parseDate(row.original.start_date_iso) : undefined"
                            class="p-2"
                            @update:model-value="(value) => value && updateProject(row.original.id, { start_date: value.toString() })"
                        />
                    </template>
                </UPopover>
            </template>

            <template #due_date-cell="{ row }">
                <UPopover>
                    <button type="button" class="cursor-pointer rounded-md px-1.5 py-0.5 text-start text-sm hover:bg-elevated">
                        {{ row.original.due_date }}
                    </button>

                    <template #content>
                        <UCalendar
                            :model-value="row.original.due_date_iso ? parseDate(row.original.due_date_iso) : undefined"
                            class="p-2"
                            @update:model-value="(value) => value && updateProject(row.original.id, { due_date: value.toString() })"
                        />
                    </template>
                </UPopover>
            </template>

            <template #progress-cell="{ row }">
                <div class="flex min-w-32 items-center gap-2.5">
                    <div class="h-1.5 min-w-8 flex-1 overflow-hidden rounded-full bg-accented">
                        <div class="h-1.5 rounded-full bg-primary" :style="{ width: `${row.original.progress}%` }" />
                    </div>
                    <span class="w-12 shrink-0 text-end text-xs font-medium tabular-nums">{{ row.original.progress }}%</span>
                </div>
            </template>

            <template #actions-cell="{ row }">
                <div class="flex items-center gap-2">
                    <UButton
                        icon="i-lucide-eye"
                        color="neutral"
                        variant="outline"
                        size="sm"
                        :to="route('project.show', row.original.id)"
                        aria-label="View detail"
                    />

                    <UButton icon="i-lucide-trash-2" color="error" variant="solid" size="sm" aria-label="Delete" @click="deleteProject(row.original)" />
                </div>
            </template>
        </UTable>

        <div class="flex items-center justify-between gap-3 border-t border-default px-4 py-2.5">
            <p class="text-sm text-muted">Showing {{ rangeStart }}-{{ rangeEnd }} of {{ total }} projects</p>

            <div class="flex items-center gap-3">
                <USelect v-model="perPage" :items="pageSizes" label-key="label" value-key="value" color="neutral" variant="outline" class="w-28" />
                <UPagination v-model:page="page" :items-per-page="perPage" :total="total" size="sm" />
            </div>
        </div>
    </div>
</template>
