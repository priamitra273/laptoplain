<script setup lang="ts">
import Label from '@/components/Label.vue';
import type { TaskCategoryOption } from '@/pages/project-lazy';

interface Props {
    options: TaskCategoryOption[];
    error?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<string | null>('modelValue');

const getSelectValue = (id: string, options: TaskCategoryOption[]): TaskCategoryOption | null => {
    return options.find((option) => option.id === id) || null;
};
</script>

<template>
    <div class="grid grid-cols-4 gap-4">
        <Label value="Category" icon="Circle" />

        <div class="col-span-3">
            <Select
                v-model="modelValue"
                :options="props.options"
                optionValue="id"
                placeholder="Empty"
                class="min-w-48 !border-0 !shadow-none hover:bg-surface-100 dark:hover:bg-surface-900"
                overlayClass="!min-w-48"
                :disabled="props.disabled"
                :class="{ 'p-invalid': props.error }"
                pt:dropdown:class="!w-0"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex items-center gap-2">
                        <Tag
                            :icon="getSelectValue(slotProps.value, props.options)?.icon"
                            :value="getSelectValue(slotProps.value, props.options)?.name"
                            :severity="getSelectValue(slotProps.value, props.options)?.severity"
                        />
                    </div>
                    <span v-else>{{ slotProps.placeholder }}</span>
                </template>
                <template #option="{ option }">
                    <div class="flex items-center gap-2">
                        <Tag :icon="option.icon" :value="option.name" :severity="option.severity" class="flex-1" />
                    </div>
                </template>
            </Select>

            <small v-if="props.error" class="p-error text-red-500">{{ props.error }}</small>
        </div>
    </div>
</template>
