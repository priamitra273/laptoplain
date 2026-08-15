<script setup lang="ts">
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { inject, ref, watch } from 'vue';
import type { BacklogTask } from '@/pages/project-lazy';
import { BacklogKey } from './types';

const props = defineProps<{
    task: BacklogTask;
}>();

const context = inject(BacklogKey);

const selectedPriorityId = ref<string | null>(props.task.priority?.id ?? null);

const prioritySeverity = (name?: string): any => ({ High: 'danger', Medium: 'warn', Low: 'info', Critical: 'danger' })[name ?? ''] ?? 'secondary';
const getPriorityOption = (id?: string | null) => (context?.taskPriorities ?? []).find((priority) => String(priority.id) === String(id ?? ''));

watch(
    () => props.task.priority?.id,
    (value) => {
        selectedPriorityId.value = value ?? null;
    },
);

watch(selectedPriorityId, (value, oldValue) => {
    if (!value || value === oldValue || String(value) === String(props.task.priority?.id ?? '')) return;
    context?.updatePriority(props.task, value);
});
</script>

<template>
    <Select
        v-if="context?.canAct && (context?.taskPriorities?.length ?? 0) > 0"
        v-model="selectedPriorityId"
        :options="context?.taskPriorities"
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
            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity ?? prioritySeverity(slotProps.option.name)" class="text-xs" />
        </template>
    </Select>
</template>
