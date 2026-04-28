<script setup lang="ts">
interface Props {
    minDueDate?: Date;
    isInProgressStatus?: boolean;
    startDateError?: string | null;
    dueDateError?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();

const startDate = defineModel<Date | null>('startDate', { default: null });
const dueDate = defineModel<Date | null>('dueDate', { default: null });
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="font-semibold">Start Date</label>
            <DatePicker
                :disabled="props.disabled"
                class="w-full"
                v-model="startDate"
                dateFormat="yy-mm-dd"
                showIcon
                :class="{ 'p-invalid': props.startDateError }"
            />
            <small v-if="props.startDateError" class="p-error text-red-500">{{ props.startDateError }}</small>
        </div>

        <div>
            <label class="font-semibold">
                Due Date
                <span v-if="props.isInProgressStatus" class="text-red-500">*</span>
            </label>
            <DatePicker
                class="w-full"
                v-model="dueDate"
                dateFormat="yy-mm-dd"
                showIcon
                :minDate="props.minDueDate"
                :class="{ 'p-invalid': props.dueDateError }"
            />
            <small v-if="props.dueDateError" class="p-error text-red-500">{{ props.dueDateError }}</small>
        </div>
    </div>
</template>
