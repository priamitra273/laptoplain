<script lang="ts" setup>
import { DateFormatter, getLocalTimeZone, today } from '@internationalized/date';
import type { DateValue } from '@internationalized/date';
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
const dueDate = ref<DateValue>();
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
            <div class="flex flex-col gap-2">
                <Label value="Due Date" required />

                <UPopover>
                    <UButton
                        color="neutral"
                        variant="subtle"
                        icon="i-lucide-calendar"
                        class="w-full justify-start font-normal"
                        :class="{ 'text-muted': !dueDate }"
                        :label="dueDateLabel"
                    />

                    <template #content>
                        <UCalendar v-model="dueDate" :min-value="minDate" class="p-2" />
                    </template>
                </UPopover>
            </div>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', null)" />
            <UButton label="Confirm & Move" :disabled="!dueDate" @click="submit" />
        </template>
    </UModal>
</template>
