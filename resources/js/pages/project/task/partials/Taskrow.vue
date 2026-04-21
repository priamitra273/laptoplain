<script setup lang="ts">
import { useSeverityColor } from '@/composables/useSeverityColor';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Menu from 'primevue/menu';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, nextTick, ref, watch } from 'vue';

interface TaskRowTask {
    id: string | number;
    title?: string;
    parent_id?: string | null;
    task_number?: string | null;
    story_points?: number | null;
    category?: { name?: string; icon?: string; severity?: string | null } | null;
    priority?: { id?: string; name?: string; severity?: string } | null;
    status?: { id?: string; name?: string; severity?: string } | null;
    users?: { id: string; name: string; avatar_url?: string | null }[];
}

interface EpicOption {
    id: string;
    title: string;
}

interface OptionItem {
    id: string;
    name: string;
    severity?: string;
}

const props = defineProps<{
    task: TaskRowTask;
    epics?: EpicOption[];
    taskStatuses?: OptionItem[];
    taskPriorities?: OptionItem[];
    canAct?: boolean;
    draggable?: boolean;
    showChecklist?: boolean;
    selected?: boolean;
}>();

const emit = defineEmits<{
    edit: [task: TaskRowTask];
    add: [task: TaskRowTask];
    addParent: [epicId: string];
    addEpic: [task: TaskRowTask, epicId: string | null];
    updatePriority: [task: TaskRowTask, priorityId: string];
    viewEpic: [epicId: string];
    toggleSelect: [task: TaskRowTask, checked: boolean];
    menu: [event: MouseEvent, task: TaskRowTask];
}>();

const { getSeverityColorLight } = useSeverityColor();

const isEpic = (task: TaskRowTask) => task.category?.name?.toLowerCase() === 'epic';

const rowEpicId = computed(() => {
    const parentId = props.task.parent_id ?? null;
    if (parentId === null || parentId === undefined || parentId === '') return null;
    const pid = String(parentId);
    const existsInOptions = (props.epics ?? []).some((epic) => String(epic.id) === pid);
    return existsInOptions ? pid : null;
});

const selectedEpicId = ref<string | null>(rowEpicId.value);
const selectedPriorityId = ref<string | null>(props.task.priority?.id ?? null);
const epicMenu = ref();
const isEditingEpic = ref(false);

const selectedEpicTitle = computed(() => props.epics?.find((epic) => String(epic.id) === String(rowEpicId.value))?.title ?? 'Epic');
const canShowEpicMenu = computed(() => !!rowEpicId.value && !isEditingEpic.value);

const hasEpicOptions = computed(() => (props.epics?.length ?? 0) > 0);
const addEpicButtonTooltip = computed(() => (hasEpicOptions.value ? 'Pilih epic' : 'Belum ada Epic di project. Buat task bertipe Epic dulu.'));

const prioritySeverity = (name?: string): any => ({ High: 'danger', Medium: 'warn', Low: 'info', Critical: 'danger' })[name ?? ''] ?? 'secondary';
const statusSeverity = (name?: string): any =>
    ({ 'To Do': 'secondary', 'In Progress': 'info', Done: 'success', Completed: 'success' })[name ?? ''] ?? 'secondary';
const getPriorityOption = (id?: string | null) => (props.taskPriorities ?? []).find((priority) => String(priority.id) === String(id ?? ''));

const categoryIcon = (task: TaskRowTask) => {
    const byName: Record<string, string> = {
        Epic: 'pi pi-bolt',
        Issue: 'pi pi-exclamation-circle',
        Story: 'pi pi-book',
        Task: 'pi pi-check-square',
    };
    const name = task.category?.name ?? '';
    return task.category?.icon ?? byName[name] ?? 'pi pi-tag';
};

const categoryColor = (task: TaskRowTask): string => {
    const severity = task.category?.severity;
    if (severity) {
        return getSeverityColorLight(severity, 0.2);
    }

    const byName: Record<string, string> = {
        Epic: '#7c3aed',
        Issue: '#dc2626',
        Story: '#16a34a',
        Task: '#3b82f6',
        Bug: '#dc2626',
    };
    const name = task.category?.name ?? '';
    return byName[name] ?? '#64748b';
};

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
const avatarColors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];
const getAvatarColor = (name: string) => avatarColors[name.charCodeAt(0) % avatarColors.length];

watch(
    () => rowEpicId.value,
    (value) => {
        selectedEpicId.value = value;
        if (value) isEditingEpic.value = false;
    },
);
watch(
    () => props.task.priority?.id,
    (value) => {
        selectedPriorityId.value = value ?? null;
    },
);
watch(selectedEpicId, (value, oldValue) => {
    if (!value || value === oldValue || String(value) === String(rowEpicId.value)) return;
    emit('addEpic', props.task, value);
    isEditingEpic.value = false;
});
watch(selectedPriorityId, (value, oldValue) => {
    if (!value || value === oldValue || String(value) === String(props.task.priority?.id ?? '')) return;
    emit('updatePriority', props.task, value);
});

const epicMenuItems = computed(() => {
    if (!rowEpicId.value) return [];
    return [
        {
            label: 'Add parent',
            icon: 'pi pi-plus',
            command: () => emit('addParent', rowEpicId.value as string),
        },
        {
            label: 'View',
            icon: 'pi pi-eye',
            command: () => emit('viewEpic', rowEpicId.value as string),
        },
        {
            label: 'Change epic',
            icon: 'pi pi-pencil',
            command: () => {
                isEditingEpic.value = true;
            },
        },
        {
            label: 'Delete',
            icon: 'pi pi-trash',
            command: () => emit('addEpic', props.task, null),
        },
    ];
});

const onEpicClick = (event: MouseEvent) => {
    epicMenu.value?.toggle(event);
};

const epicDropdown = ref<{ show?: (focus?: boolean) => void } | null>(null);

const openEpicPicker = () => {
    if (!hasEpicOptions.value) return;
    isEditingEpic.value = true;
    nextTick(() => epicDropdown.value?.show?.(true));
};

const onEpicDropdownHide = () => {
    isEditingEpic.value = false;
};
</script>

<template>
    <div
        class="group flex items-center gap-2 border-t border-surface-100 px-3 py-2 hover:bg-surface-50 dark:border-surface-700 dark:hover:bg-surface-800/50"
    >
        <i
            v-if="draggable"
            class="drag-handle pi pi-bars flex-shrink-0 cursor-grab text-xs text-surface-300 opacity-0 active:cursor-grabbing group-hover:opacity-100 dark:text-surface-600"
            @click.stop
        ></i>

        <Checkbox
            v-if="showChecklist"
            :modelValue="!!selected"
            binary
            class="flex-shrink-0"
            @update:modelValue="emit('toggleSelect', task, !!$event)"
            @click.stop
        />

        <i
            v-tooltip.top="task.category?.name || 'Category'"
            :class="categoryIcon(task)"
            :style="{ color: categoryColor(task) }"
            class="flex-shrink-0 text-sm"
        ></i>

        <span class="min-w-0 flex-1 truncate text-sm text-surface-800 dark:text-surface-100" :title="task.title">{{ task.title }}</span>

        <span
            v-if="task.story_points != null"
            class="hidden h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-surface-100 text-xs font-semibold text-surface-600 sm:inline-flex dark:bg-surface-700 dark:text-surface-300"
            title="Story points"
        >
            {{ task.story_points }}
        </span>

        <!-- Epic | Priority | Status | Members (satu blok, Epic di kiri Priority & Member) -->
        <div class="ml-auto flex min-w-0 shrink-0 items-center gap-1 sm:gap-2">
            <!-- Epic -->
            <div class="flex min-w-[6.5rem] max-w-[9rem] flex-1 shrink-0 items-center justify-start gap-0.5 sm:min-w-[7rem]">
                <Button
                    v-if="canAct && !isEpic(task) && canShowEpicMenu"
                    :label="selectedEpicTitle"
                    text
                    size="small"
                    severity="secondary"
                    class="inline-flex min-w-0 flex-1 !justify-start !px-0 !py-0 text-xs [&_.p-button-label]:block [&_.p-button-label]:truncate"
                    :title="selectedEpicTitle"
                    @click.stop="onEpicClick"
                >
                    <template #icon>
                        <i v-tooltip.top="selectedEpicTitle" class="pi pi-bolt text-xs text-violet-500"></i>
                    </template>
                </Button>

                <Button
                    v-if="canAct && !isEpic(task) && !rowEpicId && !isEditingEpic"
                    v-tooltip.top="addEpicButtonTooltip"
                    :disabled="!hasEpicOptions"
                    icon="pi pi-plus"
                    label="Epic"
                    size="small"
                    outlined
                    severity="secondary"
                    class="shrink-0 !px-2 !py-1 text-xs"
                    @click.stop="openEpicPicker"
                />

                <span v-if="canAct && !isEpic(task) && hasEpicOptions && isEditingEpic" v-tooltip.top="'Pilih epic'" class="block min-w-0 flex-1">
                    <Select
                        ref="epicDropdown"
                        v-model="selectedEpicId"
                        :options="props.epics"
                        optionLabel="title"
                        optionValue="id"
                        placeholder="Epic"
                        @hide="onEpicDropdownHide"
                        :class="[
                            'w-full min-w-0 !border-0 !bg-transparent !shadow-none [&_.p-dropdown-clear-icon]:hidden [&_.p-dropdown-label]:px-0 [&_.p-dropdown-label]:pr-0 [&_.p-dropdown-trigger-icon]:hidden [&_.p-dropdown-trigger]:hidden [&_.p-select-dropdown-icon]:hidden [&_.p-select-dropdown]:hidden [&_.p-select-label]:px-0 [&_.p-select-label]:pr-0',
                            rowEpicId ? 'text-xs opacity-90' : 'text-xs opacity-80 focus-within:opacity-100 group-hover:opacity-100',
                        ]"
                    />
                </span>
            </div>

            <!-- Priority -->
            <Select
                v-if="canAct && (props.taskPriorities?.length ?? 0) > 0"
                v-model="selectedPriorityId"
                :options="props.taskPriorities"
                optionLabel="name"
                optionValue="id"
                placeholder="Priority"
                class="w-24 min-w-[5.5rem] shrink-0 !border-0 !bg-transparent !shadow-none [&_.p-dropdown-clear-icon]:hidden [&_.p-dropdown-label]:px-0 [&_.p-dropdown-label]:pr-0 [&_.p-dropdown-trigger-icon]:hidden [&_.p-dropdown-trigger]:hidden [&_.p-select-dropdown-icon]:hidden [&_.p-select-dropdown]:hidden [&_.p-select-label]:px-0 [&_.p-select-label]:pr-0"
            >
                <template #value="slotProps">
                    <Tag
                        v-if="slotProps.value"
                        :value="getPriorityOption(slotProps.value)?.name"
                        :severity="getPriorityOption(slotProps.value)?.severity ?? prioritySeverity(getPriorityOption(slotProps.value)?.name)"
                        class="w-full justify-center text-xs"
                    />
                    <span v-else class="text-xs text-surface-500">{{ slotProps.placeholder }}</span>
                </template>
                <template #option="slotProps">
                    <Tag
                        :value="slotProps.option.name"
                        :severity="slotProps.option.severity ?? prioritySeverity(slotProps.option.name)"
                        class="text-xs"
                    />
                </template>
            </Select>

            <!-- Status -->
            <Tag
                v-if="task.status"
                :value="task.status.name"
                :severity="statusSeverity(task.status.name)"
                class="w-24 min-w-[5.5rem] shrink-0 justify-center truncate text-xs"
            />
            <span v-else class="w-24 min-w-[5.5rem] shrink-0"></span>

            <!-- Members -->
            <div class="flex w-24 min-w-[5.5rem] shrink-0 items-center justify-end">
                <div class="flex justify-end -space-x-1.5">
                    <div
                        v-for="user in (task.users ?? []).slice(0, 3)"
                        :key="user.id"
                        class="flex h-6 w-6 items-center justify-center overflow-hidden rounded-full text-xs text-white ring-2 ring-white dark:ring-surface-900"
                        :style="{ backgroundColor: getAvatarColor(user.name) }"
                        :title="user.name"
                    >
                        <img v-if="user.avatar_url" :src="user.avatar_url" class="h-full w-full object-cover" />
                        <span v-else>{{ getInitials(user.name) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <Button
            v-if="isEpic(task)"
            icon="pi pi-plus"
            size="small"
            text
            rounded
            severity="secondary"
            class="flex-shrink-0 opacity-0 group-hover:opacity-100"
            @click.stop="emit('add', task)"
        />

        <Button
            icon="pi pi-ellipsis-v"
            size="small"
            text
            rounded
            severity="secondary"
            class="flex-shrink-0 opacity-0 group-hover:opacity-100"
            @click.stop="emit('menu', $event, task)"
        />

        <Menu ref="epicMenu" :model="epicMenuItems" popup />
    </div>
</template>
