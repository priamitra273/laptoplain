<script setup lang="ts">
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
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="font-semibold">Type <span class="text-red-500">*</span></label>
            <Select
                :disabled="props.typeDisabled"
                class="w-full"
                v-model="typeId"
                :options="props.typeOptions"
                optionValue="id"
                placeholder="Select Type"
                showClear
                :class="{ 'p-invalid': props.typeError }"
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

        <div>
            <label class="font-semibold">Status <span class="text-red-500">*</span></label>
            <Select
                class="w-full"
                v-model="statusId"
                :options="props.statusOptions"
                optionValue="id"
                placeholder="Select Status"
                showClear
                :class="{ 'p-invalid': props.statusError }"
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

        <div>
            <label class="font-semibold">Priority <span class="text-red-500">*</span></label>
            <Select
                :disabled="props.priorityDisabled"
                class="w-full"
                v-model="priorityId"
                :options="props.priorityOptions"
                optionValue="id"
                placeholder="Select Priority"
                showClear
                :class="{ 'p-invalid': props.priorityError }"
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
</template>
