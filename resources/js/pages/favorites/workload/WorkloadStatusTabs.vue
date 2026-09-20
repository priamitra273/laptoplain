<script setup lang="ts">
import { computed } from 'vue';
import { statusIdsForWorkloadTab, workloadTabForStatusIds, type WorkloadStatusTab } from './workload';

const statuses = defineModel<number[]>({ required: true });

const items: { label: string; value: WorkloadStatusTab }[] = [
    { label: 'All', value: 'all' },
    { label: 'Free', value: 'free' },
    { label: 'Ongoing', value: 'ongoing' },
    { label: 'Overloaded', value: 'overloaded' },
];

const selectedTab = computed<WorkloadStatusTab | undefined>({
    get: () => workloadTabForStatusIds(statuses.value),
    set: (value) => {
        if (value) {
            statuses.value = statusIdsForWorkloadTab(value);
        }
    },
});
</script>

<template>
    <div class="max-w-full overflow-x-auto">
        <UTabs
            v-model="selectedTab"
            :items="items"
            :content="false"
            variant="pill"
            color="primary"
            size="sm"
            class="min-w-max"
            :ui="{
                list: 'gap-2 bg-transparent p-1',
                indicator: 'rounded-full bg-primary ring-4 ring-primary/20 shadow-none',
                trigger:
                    'rounded-full px-4 py-2 text-sm data-[state=inactive]:bg-elevated data-[state=inactive]:ring data-[state=inactive]:ring-default data-[state=inactive]:hover:bg-accented data-[state=active]:font-semibold',
            }"
        />
    </div>
</template>
