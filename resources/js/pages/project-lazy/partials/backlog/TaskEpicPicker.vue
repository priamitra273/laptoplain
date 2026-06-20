<script setup lang="ts">
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Select from 'primevue/select';
import { computed, inject, nextTick, ref, watch } from 'vue';
import type { BacklogTask } from '../../index';
import { BacklogKey } from './types';

const props = defineProps<{
    task: BacklogTask;
}>();

const context = inject(BacklogKey);

const rowEpicId = computed(() => {
    const parentId = props.task.parent_id ?? null;
    if (parentId === null || parentId === undefined || parentId === '') return null;
    const pid = String(parentId);
    const existsInOptions = (context?.epics ?? []).some((epic) => String(epic.id) === pid);
    return existsInOptions ? pid : null;
});

const selectedEpicId = ref<string | null>(rowEpicId.value);
const epicMenu = ref();
const isEditingEpic = ref(false);
const epicDropdown = ref();

const selectedEpicTitle = computed(() => context?.epics?.find((epic) => String(epic.id) === String(rowEpicId.value))?.title ?? 'Epic');
const canShowEpicMenu = computed(() => !!rowEpicId.value && !isEditingEpic.value);
const hasEpicOptions = computed(() => (context?.epics?.length ?? 0) > 0);
const addEpicButtonTooltip = computed(() => (hasEpicOptions.value ? 'Pilih epic' : 'Belum ada Epic di project. Buat task bertipe Epic dulu.'));

const epicMenuItems = computed(() => {
    if (!rowEpicId.value) return [];
    return [
        {
            label: 'Add parent',
            icon: 'pi pi-plus',
            command: () => context?.addParent(rowEpicId.value as string),
        },
        {
            label: 'View',
            icon: 'pi pi-eye',
            command: () => context?.viewEpic(rowEpicId.value as string),
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
            command: () => context?.addEpic(props.task, null),
        },
    ];
});

const onEpicClick = (event: MouseEvent) => {
    epicMenu.value?.toggle(event);
};

const openEpicPicker = () => {
    if (!hasEpicOptions.value) return;
    isEditingEpic.value = true;
    nextTick(() => epicDropdown.value?.show?.(true));
};

const onEpicDropdownHide = () => {
    isEditingEpic.value = false;
};

watch(
    () => rowEpicId.value,
    (value) => {
        selectedEpicId.value = value;
        if (value) isEditingEpic.value = false;
    },
);

watch(selectedEpicId, (value, oldValue) => {
    if (!value || value === oldValue || String(value) === String(rowEpicId.value)) return;
    context?.addEpic(props.task, value);
    isEditingEpic.value = false;
});
</script>

<template>
    <div class="flex min-w-[6.5rem] max-w-[9rem] flex-1 shrink-0 items-center justify-start gap-0.5 sm:min-w-[7rem]">
        <Button
            v-if="context?.canAct && canShowEpicMenu"
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
            v-if="context?.canAct && !rowEpicId && !isEditingEpic"
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

        <span v-if="context?.canAct && hasEpicOptions && isEditingEpic" v-tooltip.top="'Pilih epic'" class="block min-w-0 flex-1">
            <Select
                ref="epicDropdown"
                v-model="selectedEpicId"
                :options="context?.epics"
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

        <Menu ref="epicMenu" :model="epicMenuItems" popup />
    </div>
</template>
