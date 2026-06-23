<script setup lang="ts">
import Label from '@/components/Label.vue';

interface TagOption {
    id: string;
    name: string;
    severity: string;
}

interface Props {
    typeOptions: TagOption[];
    statusOptions: TagOption[];
    priorityOptions: TagOption[];
    typeError?: string | null;
    statusError?: string | null;
    priorityError?: string | null;
    typeDisabled?: boolean;
    priorityDisabled?: boolean;
}

const props = defineProps<Props>();

const typeId = defineModel<string | null>('typeId', { default: null });
const statusId = defineModel<string | null>('statusId', { default: null });
const priorityId = defineModel<string | null>('priorityId', { default: null });

const getSelectValue = (id: string, options: TagOption[]): TagOption | null => {
    return options.find((option) => option.id === id) || null;
};
</script>

<template>
    <div class="grid gap-3">
        <div class="grid grid-cols-4 gap-4">
            <Label for="typeId" icon="Tag" value="Type" />
            <div class="col-span-3">
                <Select
                    v-model="typeId"
                    :options="props.typeOptions"
                    optionValue="id"
                    placeholder="Empty"
                    labelId="typeId"
                    class="min-w-48 !border-0 !shadow-none hover:bg-surface-100 dark:hover:bg-surface-900"
                    :class="{ 'p-invalid': props.typeError }"
                    :disabled="props.typeDisabled"
                    pt:dropdown:class="!w-0"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.typeOptions)?.name"
                                :severity="getSelectValue(slotProps.value, props.typeOptions)?.severity"
                            />
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="props.typeError" class="p-error text-red-500">{{ props.typeError }}</small>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <Label for="statusId" icon="Loader" value="Status" />
            <div class="col-span-3">
                <Select
                    v-model="statusId"
                    :options="props.statusOptions"
                    optionValue="id"
                    placeholder="Empty"
                    class="min-w-48 !border-0 !shadow-none hover:bg-surface-100 dark:hover:bg-surface-900"
                    :class="{ 'p-invalid': props.statusError }"
                    pt:dropdown:class="!w-0"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.statusOptions)?.name"
                                :severity="getSelectValue(slotProps.value, props.statusOptions)?.severity"
                            />
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="props.statusError" class="p-error text-red-500">{{ props.statusError }}</small>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <Label for="statusId" icon="Flag" value="Priority" />
            <div class="col-span-3">
                <Select
                    :disabled="props.priorityDisabled"
                    v-model="priorityId"
                    :options="props.priorityOptions"
                    optionValue="id"
                    placeholder="Empty"
                    class="min-w-48 !border-0 !shadow-none hover:bg-surface-100 dark:hover:bg-surface-900"
                    :class="{ 'p-invalid': props.priorityError }"
                    pt:dropdown:class="!w-0"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.priorityOptions)?.name"
                                :severity="getSelectValue(slotProps.value, props.priorityOptions)?.severity"
                            />
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="props.priorityError" class="p-error text-red-500">{{ props.priorityError }}</small>
            </div>
        </div>
    </div>
</template>
