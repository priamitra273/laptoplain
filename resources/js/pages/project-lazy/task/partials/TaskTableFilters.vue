<script setup lang="ts">
import type { LazyTaskTableFilter, TaskStatusOption, TaskTypeOption } from '@/pages/project-lazy';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Tag from 'primevue/tag';
import { computed } from 'vue';

interface Props {
    statusOptions: TaskStatusOption[];
    typeOptions: TaskTypeOption[];
    filters: LazyTaskTableFilter;
}

interface Emits {
    (e: 'update:filters', filters: LazyTaskTableFilter): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const localFilters = computed({
    get: () => props.filters,
    set: (val) => emit('update:filters', val),
});

const hasActiveFilters = computed(() => {
    return (
        localFilters.value.global !== '' ||
        (localFilters.value['status.name'] && localFilters.value['status.name'].length > 0) ||
        (localFilters.value['type.name'] && localFilters.value['type.name'].length > 0)
    );
});

const clearFilters = () => {
    emit('update:filters', {
        global: '',
        'status.name': [],
        'type.name': [],
    });
};

const handleClearStatuses = () => {
    emit('update:filters', {
        ...localFilters.value,
        'status.name': [],
    });
};

const handleClearTypes = () => {
    emit('update:filters', {
        ...localFilters.value,
        'type.name': [],
    });
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Search</label>
                <InputText v-model="localFilters.global" placeholder="Search by title..." class="w-full" />
            </div>
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Status</label>
                <MultiSelect
                    v-model="localFilters['status.name']"
                    :options="statusOptions"
                    optionLabel="name"
                    optionValue="name"
                    placeholder="Select Status"
                    class="w-full"
                    :maxSelectedLabels="2"
                    showClear
                    @clear="handleClearStatuses"
                >
                    <template #option="slotProps">
                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                    </template>
                </MultiSelect>
            </div>
            <div class="w-full">
                <label class="mb-2 block text-sm font-medium">Type</label>
                <MultiSelect
                    v-model="localFilters['type.name']"
                    :options="typeOptions"
                    optionLabel="name"
                    optionValue="name"
                    placeholder="Select Type"
                    class="w-full"
                    :maxSelectedLabels="2"
                    showClear
                    @clear="handleClearTypes"
                >
                    <template #option="slotProps">
                        <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" />
                    </template>
                </MultiSelect>
            </div>
        </div>

        <div v-if="hasActiveFilters" class="flex justify-end">
            <Button label="Clear Filters" icon="pi pi-filter-slash" @click="clearFilters" severity="secondary" size="small" text />
        </div>
    </div>
</template>
