<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Label from '@/components/Label.vue';

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
    <div class="grid grid-cols-4 gap-4">
        <Label value="Dates" icon="Calendar" />

        <div class="col-span-3 flex items-center gap-4">
            <div>
                <DatePicker
                    :disabled="props.disabled"
                    v-model="startDate"
                    dateFormat="dd M yy"
                    showIcon
                    iconDisplay="input"
                    placeholder="Start Date"
                    :class="{ 'p-invalid': props.startDateError }"
                />
                <small v-if="props.startDateError" class="p-error text-red-500">{{ props.startDateError }}</small>
            </div>

            <Icon name="ArrowRight" />

            <div>
                <DatePicker
                    v-model="dueDate"
                    dateFormat="dd M yy"
                    showIcon
                    iconDisplay="input"
                    :minDate="props.minDueDate"
                    placeholder="Due Date"
                    :class="{ 'p-invalid': props.dueDateError }"
                />
                <small v-if="props.dueDateError" class="p-error text-red-500">{{ props.dueDateError }}</small>
            </div>
        </div>
    </div>
</template>
