<script setup lang="ts">
import FilterResetButton from '@/components/form/FilterResetButton.vue';
import PriorityIcon from '@/components/common/PriorityIcon.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';
import TaskTypeBadge from '@/components/task/TaskTypeBadge.vue';
import { getInitials } from '@/lib/utils';
import { computed, ref } from 'vue';
import DateFilterField from './DateFilterField.vue';
import { toCalendarDate } from './date';
import { normalizeTaskReportFilterOptions } from './filterOptions';
import type { TaskReportFilterOptions } from './types';

const props = defineProps<{
    filterOptions?: Partial<TaskReportFilterOptions>;
}>();

const filterOptions = computed(() => normalizeTaskReportFilterOptions(props.filterOptions));

const emit = defineEmits<{
    reset: [];
    export: [];
}>();

const search = defineModel<string>('search', { required: true });
const names = defineModel<string[]>('names', { required: true });
const statuses = defineModel<string[]>('statuses', { required: true });
const projectStatuses = defineModel<string[]>('projectStatuses', { required: true });
const priorities = defineModel<string[]>('priorities', { required: true });
const types = defineModel<string[]>('types', { required: true });
const startDateFrom = defineModel<string>('startDateFrom', { required: true });
const startDateTo = defineModel<string>('startDateTo', { required: true });
const dueDateFrom = defineModel<string>('dueDateFrom', { required: true });
const dueDateTo = defineModel<string>('dueDateTo', { required: true });

/**
 * Semua select dilipat di balik tombol "Filters", mengikuti anatomi toolbar halaman
 * Project/List/My Task. Jumlah filter aktif tetap terlihat pada tombol utama.
 */
const advancedActiveCount = computed(
    () =>
        names.value.length +
        projectStatuses.value.length +
        statuses.value.length +
        priorities.value.length +
        types.value.length +
        (startDateFrom.value ? 1 : 0) +
        (startDateTo.value ? 1 : 0) +
        (dueDateFrom.value ? 1 : 0) +
        (dueDateTo.value ? 1 : 0),
);

const hasAnyFilter = computed(() => !!search.value.trim() || advancedActiveCount.value > 0);

const expanded = ref(advancedActiveCount.value > 0);
</script>

<template>
    <div class="flex flex-col gap-2 py-2.5">
        <div class="flex flex-wrap items-center gap-2">
            <UInput v-model="search" icon="i-lucide-search" placeholder="Search title or description" class="w-full sm:w-72" />

            <div class="ms-auto flex flex-wrap items-center gap-2">
                <UButton
                    icon="i-lucide-filter"
                    :label="expanded ? 'Hide' : 'Filters'"
                    :variant="expanded || advancedActiveCount ? 'solid' : 'outline'"
                    color="neutral"
                    class="rounded-full"
                    :aria-expanded="expanded"
                    @click="expanded = !expanded"
                >
                    <template v-if="advancedActiveCount" #trailing>
                        <span class="text-xs opacity-70">{{ advancedActiveCount }}</span>
                    </template>
                </UButton>

                <FilterResetButton v-if="hasAnyFilter" @click="emit('reset')" />

                <UButton label="Export CSV" icon="i-lucide-download" @click="emit('export')" />
            </div>
        </div>

        <div v-if="expanded" class="border-default flex flex-wrap items-end gap-4 border-t pt-3">
            <USelectMenu
                v-model="names"
                :items="filterOptions.creators"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Creator"
                color="neutral"
                variant="outline"
                class="w-44 rounded-full"
            >
                <template #item-leading="{ item }">
                    <UAvatar :src="item.avatar_url ?? undefined" :alt="item.name" :text="getInitials(item.name)" size="2xs" />
                </template>
            </USelectMenu>

            <USelectMenu
                v-model="projectStatuses"
                :items="filterOptions.project_statuses"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Project Status"
                color="neutral"
                variant="outline"
                class="w-44 rounded-full"
            >
                <template #item-label="{ item }">
                    <StatusBadge :label="item.name" :severity="item.severity" />
                </template>
            </USelectMenu>

            <USelectMenu
                v-model="statuses"
                :items="filterOptions.statuses"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Task Status"
                color="neutral"
                variant="outline"
                class="w-44 rounded-full"
            >
                <template #item-label="{ item }">
                    <StatusBadge :label="item.name" :severity="item.severity" />
                </template>
            </USelectMenu>

            <USelectMenu
                v-model="priorities"
                :items="filterOptions.priorities"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Priority"
                color="neutral"
                variant="outline"
                class="w-44 rounded-full"
            >
                <template #item-label="{ item }">
                    <PriorityIcon pill :label="item.name" :severity="item.severity" />
                </template>
            </USelectMenu>

            <USelectMenu
                v-model="types"
                :items="filterOptions.types"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Type"
                color="neutral"
                variant="outline"
                class="w-44 rounded-full"
            >
                <template #item-label="{ item }">
                    <TaskTypeBadge :label="item.name" :severity="item.severity" />
                </template>
            </USelectMenu>

            <div class="flex flex-col gap-1">
                <span class="text-muted text-xs">Start Date</span>
                <div class="flex items-center gap-1.5">
                    <DateFilterField
                        v-model="startDateFrom"
                        placeholder="From"
                        :max-value="toCalendarDate(startDateTo) ?? undefined"
                        class="w-32"
                    />
                    <span class="text-muted text-xs">–</span>
                    <DateFilterField
                        v-model="startDateTo"
                        placeholder="To"
                        :min-value="toCalendarDate(startDateFrom) ?? undefined"
                        class="w-32"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-muted text-xs">Due Date</span>
                <div class="flex items-center gap-1.5">
                    <DateFilterField
                        v-model="dueDateFrom"
                        placeholder="From"
                        :max-value="toCalendarDate(dueDateTo) ?? undefined"
                        class="w-32"
                    />
                    <span class="text-muted text-xs">–</span>
                    <DateFilterField
                        v-model="dueDateTo"
                        placeholder="To"
                        :min-value="toCalendarDate(dueDateFrom) ?? undefined"
                        class="w-32"
                    />
                </div>
            </div>
        </div>

    </div>
</template>
