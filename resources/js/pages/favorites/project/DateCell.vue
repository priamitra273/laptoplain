<script setup lang="ts">
import type { DateValue } from '@internationalized/date';
import { computed, ref } from 'vue';
import { formatCalendarDate, toCalendarDate } from './date';

const props = defineProps<{
    modelValue?: string | null;
    min?: string | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{ update: [value: string] }>();

const open = ref(false);

const selected = computed<DateValue | null>({
    get: () => toCalendarDate(props.modelValue),
    set: (value) => {
        if (!value) {
            return;
        }

        open.value = false;
        emit('update', value.toString());
    },
});

const minValue = computed(() => toCalendarDate(props.min) ?? undefined);

const label = computed(() => {
    const date = toCalendarDate(props.modelValue);

    return formatCalendarDate(date) ?? 'Set date';
});
</script>

<template>
    <UPopover v-model:open="open" :disabled="disabled">
        <UButton
            icon="i-lucide-calendar"
            :label="label"
            color="neutral"
            variant="ghost"
            size="sm"
            :disabled="disabled"
            class="-mx-2 whitespace-nowrap"
            :class="modelValue ? '' : 'text-muted'"
        />

        <template #content>
            <UCalendar v-model="selected" :min-value="minValue" size="sm" class="p-2" />
        </template>
    </UPopover>
</template>
