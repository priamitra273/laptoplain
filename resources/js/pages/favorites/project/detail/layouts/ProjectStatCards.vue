<script setup lang="ts">
import FieldLabel from '@/components/ui/FieldLabel.vue';
import BadgeSelect from '@/components/form/BadgeSelect.vue';
import ProgressWithLabel from '@/components/common/ProgressWithLabel.vue';
import DatePicker from '@/components/form/DatePicker.vue';
import { formatDate, formatDateShort } from '@/lib/date';
import { getLocalTimeZone, parseDate } from '@internationalized/date';
import { computed } from 'vue';
import type { ShellPriorityOption, ShellProject, ShellStatusOption } from '../types';

interface Props {
    project: ShellProject;
    statuses: ShellStatusOption[];
    priorities: ShellPriorityOption[];
    canEdit: boolean;
}

interface Emits {
    (e: 'update', value: string, field: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const inlineSelectUi = { base: 'border-0 bg-transparent shadow-none ring-0 p-0', trailingIcon: 'hidden', content: 'w-48' };

const startDateValue = computed(() => (props.project.start_date ? parseDate(props.project.start_date) : undefined));
const dueDateValue = computed(() => (props.project.due_date ? parseDate(props.project.due_date) : undefined));

// Start dibuang tahunnya kalau tahunnya sama dengan due, biar barisnya tidak mengulang angka yang sama.
const startLabel = computed(() => {
    const sameYear = startDateValue.value && dueDateValue.value ? startDateValue.value.year === dueDateValue.value.year : false;

    return sameYear ? formatDateShort(props.project.start_date) : formatDate(props.project.start_date);
});

const dueLabel = computed(() => formatDate(props.project.due_date));

const overdueDays = computed(() => {
    if (!dueDateValue.value) return 0;
    if (props.project.status?.name === 'Completed' || props.project.status?.name === 'Cancelled') return 0;

    const diffDays = Math.floor((Date.now() - dueDateValue.value.toDate(getLocalTimeZone()).getTime()) / (1000 * 60 * 60 * 24));

    return diffDays > 0 ? diffDays : 0;
});

const onStatusChange = (id: string) => {
    emit('update', id, 'status_id');
};

const onPriorityChange = (id: string) => {
    emit('update', id, 'priority_id');
};

const statCardClass = 'flex flex-col items-start gap-2 rounded-xl bg-default p-3.5 shadow-sm ring ring-default';
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div :class="statCardClass">
            <FieldLabel title="Status" />
            <BadgeSelect
                :model-value="project.status?.id"
                :items="statuses"
                :disabled="!canEdit"
                :ui="inlineSelectUi"
                @update:model-value="onStatusChange"
            />
        </div>

        <div :class="statCardClass">
            <FieldLabel title="Priority" />
            <BadgeSelect
                display="priority"
                :model-value="project.priority?.id"
                :items="priorities"
                :disabled="!canEdit"
                :ui="inlineSelectUi"
                pill
                @update:model-value="onPriorityChange"
            />
        </div>

        <div :class="statCardClass">
            <FieldLabel title="Timeline" />
            <div class="flex w-full min-w-0 flex-wrap items-center gap-1.5 text-[13px]">
                <UIcon name="i-lucide-calendar" class="size-4 shrink-0 text-muted" />

                <DatePicker
                    :model-value="startDateValue"
                    :label="startLabel"
                    trigger-aria-label="Change start date"
                    appearance="inline"
                    :max-value="dueDateValue"
                    :disabled="!canEdit"
                    trigger-class="-mx-1"
                    @update:model-value="
                        (value) => value && value.toString() !== project.start_date && emit('update', value.toString(), 'start_date')
                    "
                />

                <span class="text-muted">→</span>

                <DatePicker
                    :model-value="dueDateValue"
                    :label="dueLabel"
                    trigger-aria-label="Change due date"
                    appearance="inline"
                    :min-value="startDateValue"
                    :disabled="!canEdit"
                    trigger-class="-mx-1"
                    @update:model-value="(value) => value && value.toString() !== project.due_date && emit('update', value.toString(), 'due_date')"
                />

                <span v-if="overdueDays" class="font-medium text-error">{{ overdueDays }}d overdue</span>
            </div>
        </div>

        <div :class="statCardClass">
            <FieldLabel title="Project progress" />
            <ProgressWithLabel :value="project.progress" bar-aria-label="Project progress" class="w-full" />
        </div>
    </div>
</template>
