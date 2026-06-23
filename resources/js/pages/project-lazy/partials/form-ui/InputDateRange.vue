<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Label from '@/components/Label.vue';

interface Props {
    minDueDate?: Date;
    isInProgressStatus?: boolean;
    required?: boolean;
    startDateError?: string | null;
    dueDateError?: string | null;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
});

const startDate = defineModel<Date | null>('startDate', { default: null });
const dueDate = defineModel<Date | null>('dueDate', { default: null });
</script>

<template>
    <div class="grid grid-cols-4 gap-4">
        <Label value="Dates" icon="Calendar" :required="props.required" />

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
            </div>

            <Icon name="ArrowRight" />

            <div>
                <DatePicker
                    v-model="dueDate"
                    :disabled="props.disabled"
                    dateFormat="dd M yy"
                    showIcon
                    iconDisplay="input"
                    :minDate="props.minDueDate"
                    placeholder="Due Date"
                    :class="{ 'p-invalid': props.dueDateError }"
                />
            </div>
        </div>

        <div class="col-span-3 col-start-2">
            <small v-if="props.startDateError" class="p-error text-red-500">{{ props.startDateError }}</small>
            <small v-if="props.dueDateError" class="p-error text-red-500">{{ props.dueDateError }}</small>
        </div>
    </div>
</template>
