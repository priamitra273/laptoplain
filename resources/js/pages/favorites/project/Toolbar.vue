<script setup lang="ts">
import DatePicker from '@/components/form/DatePicker.vue';
import FilterResetButton from '@/components/form/FilterResetButton.vue';
import StatusFilterPills, { type StatusPillOption } from '@/components/form/StatusFilterPills.vue';
import { priorityIcon, severityColor } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { formatDate } from '@/lib/date';
import { parseDate } from '@internationalized/date';
import { computed, ref } from 'vue';

interface PriorityOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

withDefaults(
    defineProps<{
        statuses: StatusPillOption[];
        priorities: PriorityOption[];
        total: number | null;
        loading?: boolean;
    }>(),
    { loading: false },
);

const search = defineModel<string>('search', { required: true });
const status = defineModel<string | null>('status', { required: true });
const priorityIds = defineModel<string[]>('priorityIds', { required: true });
const startDate = defineModel<string | null>('startDate', { required: true });
const dueDate = defineModel<string | null>('dueDate', { required: true });

const startCalendarDate = computed({
    get: () => (startDate.value ? parseDate(startDate.value) : undefined),
    set: (value) => (startDate.value = value ? value.toString() : null),
});

const dueCalendarDate = computed({
    get: () => (dueDate.value ? parseDate(dueDate.value) : undefined),
    set: (value) => (dueDate.value = value ? value.toString() : null),
});

const progressRange = defineModel<[number, number]>('progressRange', { required: true });
const expanded = ref(false);

const emit = defineEmits<{ create: []; clear: [] }>();

const hasAdvancedFilters = computed(
    () => priorityIds.value.length > 0 || !!startDate.value || !!dueDate.value || progressRange.value[0] !== 0 || progressRange.value[1] !== 100,
);
const hasAnyFilter = computed(() => !!search.value.trim() || !!status.value || hasAdvancedFilters.value);

</script>

<template>
    <div class="flex flex-col gap-2 py-2.5">
        <div class="flex flex-wrap items-center gap-2">
            <UInput v-model="search" icon="i-lucide-search" placeholder="Search project, or title" :loading="loading"
                class="w-full sm:w-72" />

            <div class="ms-auto flex flex-wrap items-center gap-2">
                <UButton icon="i-lucide-filter" :label="expanded ? 'Hide' : 'Filters'"
                    :variant="expanded || hasAdvancedFilters ? 'solid' : 'outline'" color="neutral" class="rounded-full"
                    :aria-expanded="expanded" @click="expanded = !expanded" />

                <FilterResetButton v-if="hasAnyFilter" :disabled="loading" @click="emit('clear')" />

                <UButton label="Add Project" icon="i-lucide-plus" @click="emit('create')" />
            </div>
        </div>

        <StatusFilterPills v-model="status" :options="statuses" :total="total" />

        <div v-if="expanded" class="flex flex-wrap items-center gap-4 border-t border-default pt-3">
            <USelectMenu v-model="priorityIds" :items="priorities" label-key="name" value-key="id" multiple
                placeholder="Priority" color="neutral" variant="outline" class="w-44 rounded-full">
                <template #default="{ modelValue }">
                    <span v-if="!modelValue || (modelValue as string[]).length === 0">Priority</span>
                    <span v-else class="flex items-center gap-1">
                        {{ (modelValue as string[]).length }} selected

                        <UIcon name="i-lucide-x" class="size-3.5 text-muted hover:text-highlighted"
                            @click.stop.prevent="priorityIds = []" />
                    </span>
                </template>

                <template #item-leading="{ item }">
                    <UIcon :name="priorityIcon(item.name)" class="size-4"
                        :class="`text-${severityColor(item.severity)}`" />
                </template>

                <template #item-label="{ item }">
                    <span :class="`text-${severityColor(item.severity)}`">{{ item.name }}</span>
                </template>
            </USelectMenu>

            <DatePicker
                v-model="startCalendarDate"
                clearable
                :label="startCalendarDate ? formatDate(startCalendarDate.toString()) : 'Start Date'"
                trigger-aria-label="Filter by start date"
                trigger-class="w-40 rounded-full"
            />

            <DatePicker
                v-model="dueCalendarDate"
                clearable
                :label="dueCalendarDate ? formatDate(dueCalendarDate.toString()) : 'Due Date'"
                trigger-aria-label="Filter by due date"
                trigger-class="w-40 rounded-full"
            />

            <UPopover>
                <UButton
                    :label="progressRange[0] === 0 && progressRange[1] === 100 ? 'Progress' : `Progress: ${progressRange[0]}% - ${progressRange[1]}%`"
                    icon="i-lucide-percent" color="neutral" variant="outline" class="rounded-full whitespace-nowrap" />

                <template #content>
                    <div class="w-44 space-y-2 p-3">
                        <USlider v-model="progressRange" :min="0" :max="100" :step="5" />

                        <div class="flex justify-between text-xs text-muted">
                            <span>0%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </template>
            </UPopover>
        </div>
    </div>
</template>
