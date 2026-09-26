<script setup lang="ts">
import { CalendarDate } from '@internationalized/date';
import { ref } from 'vue';

interface Props {
    label: string;
    triggerAriaLabel: string;
    appearance?: 'button' | 'inline';
    minValue?: CalendarDate;
    maxValue?: CalendarDate;
    disabled?: boolean;
    triggerClass?: string;
    clearable?: boolean;
}

const props = withDefaults(defineProps<Props>(), { appearance: 'button' });

const modelValue = defineModel<CalendarDate | undefined>();

const open = ref(false);

const onSelect = (value: unknown) => {
    if (props.disabled) {
        return;
    }

    if (value instanceof CalendarDate) {
        modelValue.value = value;
        open.value = false;
        return;
    }

    if (props.clearable) {
        modelValue.value = undefined;
        open.value = false;
    }
};
</script>

<template>
    <UPopover v-model:open="open">
        <UButton
            v-if="appearance === 'button'"
            :label="label"
            :aria-label="triggerAriaLabel"
            :disabled="disabled"
            :class="triggerClass"
            icon="i-lucide-calendar"
            color="neutral"
            variant="outline"
        />

        <UButton v-else type="button" color="neutral" variant="link" :aria-label="triggerAriaLabel" :disabled="disabled" :class="triggerClass">
            {{ label }}
        </UButton>

        <template #content>
            <UCalendar
                :model-value="modelValue"
                :min-value="minValue"
                :max-value="maxValue"
                :disabled="disabled"
                :prevent-deselect="!clearable"
                class="p-2"
                @update:model-value="onSelect"
            />
        </template>
    </UPopover>
</template>
