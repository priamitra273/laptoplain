<script setup lang="ts">
import type { TaskCategory } from '@/pages/project';

interface Props {
    options: TaskCategory[];
    error?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<string | null>('modelValue');

const getSelectValue = (id: string, options: TaskCategory[]): TaskCategory | null => {
    return options.find((option) => option.id === id) || null;
};
</script>

<template>
    <div class="flex flex-col">
        <label class="font-semibold">Category</label>
        <Select
            class="w-full"
            v-model="modelValue"
            :options="props.options"
            optionValue="id"
            placeholder="Select Category"
            showClear
            :disabled="props.disabled"
            :class="{ 'p-invalid': props.error }"
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
</template>
