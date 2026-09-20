<script setup lang="ts">
import FilterResetButton from '@/components/FilterResetButton.vue';
import StatusFilterPills, { type StatusPillOption } from '@/components/StatusFilterPills.vue';
import type { TaskFilters } from './types';

defineProps<{
    statuses: StatusPillOption[];
    total: number;
    canCreate: boolean;
    busy: boolean;
    allExpanded: boolean;
    hasBranches: boolean;
    filtering: boolean;
}>();
const filters = defineModel<TaskFilters>({ required: true });
const emit = defineEmits<{ create: []; toggleAll: []; clear: [] }>();
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <UInput
                v-model="filters.search"
                icon="i-lucide-search"
                placeholder="Search all tasks"
                aria-label="Search all tasks"
                class="w-full sm:w-72"
            />
            <div class="ms-auto flex flex-wrap items-center gap-2">
                <FilterResetButton v-if="filtering" @click="emit('clear')" />
                <UButton
                    :label="allExpanded ? 'Collapse all' : 'Expand all'"
                    :icon="allExpanded ? 'i-lucide-chevrons-down-up' : 'i-lucide-chevrons-up-down'"
                    color="neutral"
                    variant="outline"
                    :disabled="!hasBranches"
                    @click="emit('toggleAll')"
                />
                <UButton v-if="canCreate" label="Add task" icon="i-lucide-plus" :disabled="busy" @click="emit('create')" />
            </div>
        </div>
        <StatusFilterPills v-model="filters.status" :options="statuses" :total="total" />
    </div>
</template>
