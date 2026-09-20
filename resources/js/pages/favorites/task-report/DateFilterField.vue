<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
import type { CalendarDate } from '@internationalized/date';
import { computed } from 'vue';
import { formatCalendarDate, toCalendarDate } from './date';

const props = defineProps<{
    modelValue: string;
    placeholder: string;
    minValue?: CalendarDate;
    maxValue?: CalendarDate;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const selected = computed<CalendarDate | undefined>({
    get: () => toCalendarDate(props.modelValue) ?? undefined,
    set: (value) => {
        emit('update:modelValue', value ? value.toString() : '');
    },
});

const label = computed(() => formatCalendarDate(toCalendarDate(props.modelValue)) ?? props.placeholder);
</script>

<template>
    <DatePicker
        v-model="selected"
        clearable
        :label="label"
        :trigger-aria-label="`Filter by ${placeholder.toLowerCase()} date`"
        :min-value="minValue"
        :max-value="maxValue"
        trigger-class="w-full justify-start"
    />
</template>
