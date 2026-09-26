<script lang="ts" setup>
import DatePicker from '@/components/form/DatePicker.vue';
import { DateFormatter, getLocalTimeZone, today, type CalendarDate } from '@internationalized/date';
import { computed, ref } from 'vue';

interface Props {
    taskTitle: string;
    statusName: string;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    close: [value: string | null];
}>();

const dateFormatter = new DateFormatter('en-GB', { dateStyle: 'medium' });
const minDate = today(getLocalTimeZone());
const dueDate = ref<CalendarDate>();
const dueDateLabel = computed(() => (dueDate.value ? dateFormatter.format(dueDate.value.toDate(getLocalTimeZone())) : 'Select a date'));
const description = computed(() => `"${props.taskTitle}" needs a due date before moving to ${props.statusName}.`);

const submit = () => {
    if (!dueDate.value) return;
    emits('close', dueDate.value.toString());
};
</script>

<template>
    <UModal title="Set Due Date" :description="description" :dismissible="false" :ui="{ footer: 'justify-end' }">
        <template #body>
            <UFormField label="Due Date" required>
                <DatePicker
                    v-model="dueDate"
                    :label="dueDateLabel"
                    trigger-aria-label="Select due date"
                    :min-value="minDate"
                    :trigger-class="`w-full justify-start font-normal ${dueDate ? '' : 'text-muted'}`"
                />
            </UFormField>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', null)" />
            <UButton label="Confirm & Move" :disabled="!dueDate" @click="submit" />
        </template>
    </UModal>
</template>
