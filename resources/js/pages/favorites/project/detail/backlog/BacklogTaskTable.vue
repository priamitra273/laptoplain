<script setup lang="ts">
import EmptyState from '@/components/common/EmptyState.vue';
import PriorityIcon from '@/components/common/PriorityIcon.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import UserAvatarGroup from '@/components/common/UserAvatarGroup.vue';
import type { DropdownMenuItem, TableColumn } from '@nuxt/ui';
import { computed, ref, watch } from 'vue';
import { useDraggable, type DraggableEvent } from 'vue-draggable-plus';
import TaskEpicChip from './TaskEpicChip.vue';
import { setDraggedTask, takeDraggedTask } from './dragState';
import type { BacklogEpic, BacklogSprint, BacklogTask } from './types';

const props = withDefaults(
    defineProps<{
        tasks: BacklogTask[];
        epics: BacklogEpic[];
        sprints: Pick<BacklogSprint, 'id' | 'name'>[];
        currentSprintId: string | null;
        emptyMessage: string;
        canAct?: boolean;
        emptyDescription?: string;
        label?: string;
    }>(),
    { canAct: false, emptyDescription: '', label: 'Tasks' },
);

const emit = defineEmits<{
    edit: [task: BacklogTask];
    move: [task: BacklogTask, fromSprintId: string | null, toSprintId: string | null];
    assignEpic: [task: BacklogTask, epicId: string | null];
    bulkMove: [tasks: BacklogTask[], fromSprintId: string | null, toSprintId: string | null];
}>();

const columns: TableColumn<BacklogTask>[] = [
    { id: 'drag', meta: { class: { th: 'w-8 pr-0', td: 'w-8 pr-0' } } },
    { id: 'select', meta: { class: { th: 'w-10', td: 'w-10' } } },
    { accessorKey: 'title', header: 'Task' },
    { accessorKey: 'priority', header: 'Priority', meta: { class: { th: 'w-[14%]' } } },
    { accessorKey: 'status', header: 'Status', meta: { class: { th: 'w-[16%]' } } },
    { accessorKey: 'users', header: 'Assignee', meta: { class: { th: 'w-[14%]' } } },
    { id: 'actions', meta: { class: { th: 'w-14', td: 'text-right' } } },
];

const selectedIds = ref<string[]>([]);

watch(
    () => props.tasks,
    () => {
        selectedIds.value = [];
    },
);

const allSelected = computed(() => props.tasks.length > 0 && selectedIds.value.length === props.tasks.length);

/** Sebagian terpilih ditampilkan sebagai strip, bukan centang penuh maupun kosong. */
const headerCheckboxState = computed<boolean | 'indeterminate'>(() => {
    if (!selectedIds.value.length) {
        return false;
    }

    return allSelected.value ? true : 'indeterminate';
});

const toggleAll = (checked: boolean | 'indeterminate') => {
    selectedIds.value = checked === true ? props.tasks.map((task) => task.id) : [];
};

const toggleOne = (taskId: string, checked: boolean | 'indeterminate') => {
    selectedIds.value = checked === true ? [...selectedIds.value, taskId] : selectedIds.value.filter((id) => id !== taskId);
};

const BACKLOG_TARGET = '__backlog__';

const bulkTargets = computed(() => {
    const items = props.sprints.filter((sprint) => sprint.id !== props.currentSprintId).map((sprint) => ({ id: sprint.id, name: sprint.name }));

    if (props.currentSprintId) {
        items.unshift({ id: BACKLOG_TARGET, name: 'Backlog' });
    }

    return items;
});

const bulkTarget = ref<string | undefined>(undefined);

const applyBulkMove = () => {
    if (!bulkTarget.value) {
        return;
    }

    const tasks = props.tasks.filter((task) => selectedIds.value.includes(task.id));
    const toSprintId = bulkTarget.value === BACKLOG_TARGET ? null : bulkTarget.value;

    emit('bulkMove', tasks, props.currentSprintId, toSprintId);

    selectedIds.value = [];
    bulkTarget.value = undefined;
};

/**
 * `useDraggable` me-resolve selector-nya lewat `document.querySelector`, jadi kelas
 * drop zone harus unik antar tabel di halaman ini.
 */
const dropZoneClass = `backlog-dnd-${props.currentSprintId ?? 'backlog'}`;

const onStart = (event: DraggableEvent<BacklogTask>) => {
    const task = typeof event.oldDraggableIndex === 'number' ? (props.tasks[event.oldDraggableIndex] ?? null) : null;

    setDraggedTask(task, props.currentSprintId);
};

const onAdd = (event: DraggableEvent<BacklogTask>) => {
    /**
     * Sortable sudah memindahkan node-nya secara fisik. Node itu dibuang di sini supaya
     * Vue tetap jadi satu-satunya pemilik DOM tabel — kalau dibiarkan, node tersebut
     * tertinggal saat list asal berpindah ke cabang empty-state milik `UTable`.
     */
    event.item.remove();

    const { task, fromSprintId } = takeDraggedTask();

    if (task) {
        emit('move', task, fromSprintId, props.currentSprintId);
    }
};

/**
 * Sengaja dipanggil tanpa argumen list: dengan begitu vue-draggable-plus tidak memasang
 * handler internalnya yang memutasi DOM dan array. Sumber kebenaran satu-satunya adalah
 * data dari server, yang selalu ditarik ulang parent lewat `router.reload` setiap
 * perpindahan berhasil.
 *
 * `sort: false` karena urutan task di dalam satu list tidak punya kolom penyimpan
 * maupun endpoint — drag hanya untuk memindahkan antar sprint/backlog.
 */
useDraggable(`.${dropZoneClass}`, {
    group: 'backlog-tasks',
    sort: false,
    disabled: !props.canAct,
    handle: '.drag-handle',
    draggable: 'tr[data-slot="tr"]',
    animation: 150,
    ghostClass: 'opacity-40',
    onStart,
    onAdd,
});

/**
 * Edit dan Move sama-sama bergantung pada izin `task.update` — drawer edit menyimpan lewat
 * endpoint yang dijaga gate yang sama, jadi keduanya disembunyikan bersamaan.
 */
const rowActions = (task: BacklogTask): DropdownMenuItem[][] => {
    if (!props.canAct) {
        return [];
    }

    const items: DropdownMenuItem[][] = [[{ label: 'Edit', icon: 'i-lucide-pencil', onSelect: () => emit('edit', task) }]];

    const moveItems: DropdownMenuItem[] = [];

    if (props.currentSprintId) {
        moveItems.push({
            label: 'Move to Backlog',
            icon: 'i-lucide-inbox',
            onSelect: () => emit('move', task, props.currentSprintId, null),
        });
    }

    for (const sprint of props.sprints) {
        if (sprint.id === props.currentSprintId) {
            continue;
        }

        moveItems.push({
            label: `Move to ${sprint.name}`,
            icon: 'i-lucide-arrow-right',
            onSelect: () => emit('move', task, props.currentSprintId, sprint.id),
        });
    }

    if (moveItems.length) {
        items.push(moveItems);
    }

    return items;
};
</script>


<template>
    <div class="min-w-0" :data-sprint-id="currentSprintId ?? ''">
        <div v-if="canAct && selectedIds.length"
            class="bg-primary/5 flex flex-wrap items-center gap-3 border-b border-default px-4 py-2.5">
            <span class="text-highlighted text-xs font-medium">{{ selectedIds.length }} selected</span>

            <USelectMenu v-model="bulkTarget" :items="bulkTargets" label-key="name" value-key="id"
                placeholder="Move to..." size="xs" class="w-40" />

            <UButton label="Move" size="xs" :disabled="!bulkTarget" @click="applyBulkMove" />
            <UButton label="Cancel" color="neutral" variant="ghost" size="xs" @click="selectedIds = []" />
        </div>

        <UTable :data="tasks" :columns="columns" :get-row-id="(task: BacklogTask) => task.id" :aria-label="label" :ui="{
            root: 'overflow-x-auto',
            base: 'w-full min-w-[760px] table-fixed',
            thead: 'bg-muted/50',
            tbody: dropZoneClass,
            th: 'px-4 py-2.5 text-[11px] font-medium uppercase tracking-wide text-dimmed',
            td: 'px-4 py-3 text-xs whitespace-normal',
            tr: 'group transition-colors hover:bg-muted/40',
        }">
            <template #drag-header>
                <UCheckbox v-if="canAct" :model-value="headerCheckboxState" aria-label="Select all tasks"
                    @update:model-value="toggleAll" />
            </template>
            <template #drag-cell>
                <UIcon v-if="canAct" name="i-lucide-grip-vertical"
                    class="drag-handle text-dimmed size-4 shrink-0 cursor-grab opacity-0 transition-opacity group-hover:opacity-100 active:cursor-grabbing"
                    aria-hidden="true" />
            </template>
            <template #select-cell="{ row }">
                <UCheckbox v-if="canAct" :model-value="selectedIds.includes(row.original.id)"
                    :aria-label="`Select ${row.original.title}`"
                    @update:model-value="(checked) => toggleOne(row.original.id, checked)" @click.stop />
            </template>

            <template #title-cell="{ row }">
                <div class="flex min-w-0 items-center gap-2">
                    <TaskCategoryBadge v-if="row.original.category" :category="row.original.category"
                        :show-label="false" />

                    <span class="text-highlighted min-w-0 flex-1 text-[13px] leading-5 font-medium wrap-anywhere">
                        {{ row.original.title }}
                    </span>
                    <span v-if="row.original.story_points !== null"
                        class="bg-muted text-muted inline-flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                        title="Story points">{{ row.original.story_points }}</span>

                    <TaskEpicChip :task="row.original" :epics="epics" :can-act="canAct"
                        @assign="(epicId) => emit('assignEpic', row.original, epicId)" />
                </div>
            </template>
            <template #priority-cell="{ row }">
                <PriorityIcon v-if="row.original.priority" :label="row.original.priority.name"
                    :severity="row.original.priority.severity" />
                <span v-else class="text-dimmed">—</span>
            </template>
            <template #status-cell="{ row }">
                <StatusBadge v-if="row.original.status" :label="row.original.status.name"
                    :severity="row.original.status.severity" />
                <span v-else class="text-dimmed">—</span>
            </template>
            <template #users-cell="{ row }">
                <UserAvatarGroup :users="row.original.users" :max="3" size="xs" empty-label="Unassigned" />
            </template>
            <template #actions-cell="{ row }">
                <UDropdownMenu v-if="canAct" :items="rowActions(row.original)"
                    :content="{ align: 'end', side: 'bottom' }">
                    <UButton icon="i-lucide-ellipsis-vertical" color="neutral" variant="ghost" size="xs"
                        :aria-label="`Actions for ${row.original.title}`" />
                </UDropdownMenu>
            </template>
            <template #empty>
                <EmptyState icon="i-lucide-inbox" :title="emptyMessage" :description="emptyDescription" />
            </template>
        </UTable>
    </div>
</template>
