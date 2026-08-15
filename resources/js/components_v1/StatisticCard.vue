<script setup lang="ts">
import NumberFlow from '@number-flow/vue';
import Badge from 'primevue/badge';
import MeterGroup from 'primevue/metergroup';
import ProgressBar from 'primevue/progressbar';
import { computed } from 'vue';
import Icon from './Icon.vue';

interface Props {
    title?: string;
    value?: number;
    description?: string;
    max?: number;
    done?: number;
    pending?: number;
    info?: string;
    variant: 'text' | 'single' | 'multiple';
}

const props = withDefaults(defineProps<Props>(), {
    value: 0,
    done: 0,
    pending: 0,
    max: 100,
    title: '',
    description: '',
});

// Severity untuk badge
const meterValues = computed(() => [
    { label: 'Complete', value: props.done, severity: 'success' },
    { label: 'Pending', value: props.pending, severity: 'warning' },
]);

const progressValue = computed<number>(() => {
    return props.max ? Math.round((props.value / props.max) * 100) : 0;
});
</script>

<template>
    <div
        class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-4 shadow transition-shadow duration-200 hover:shadow-md"
        :class="variant === 'text' ? 'items-center text-center' : 'gap-4'"
    >
        <!-- Title + Info -->
        <div class="flex items-center gap-2 text-sm font-medium text-gray-700">
            <span>{{ props.title }}</span>
            <Icon v-if="props.info" name="Info" class="cursor-pointer text-gray-400 hover:text-gray-600" v-tooltip.bottom="props.info" />
        </div>

        <!-- Value display -->
        <div class="flex flex-col gap-1" :class="{ 'h-20 items-center justify-center': variant === 'text' }">
            <span class="text-3xl font-bold text-gray-900">
                <NumberFlow :value="props.value" will-change />
            </span>
            <span v-if="props.description" class="text-xs text-gray-400">{{ props.description }}</span>
        </div>

        <!-- Progress / MeterGroup -->
        <div v-if="variant !== 'text'" class="mt-2 flex flex-col gap-2">
            <ProgressBar v-if="variant === 'single'" :value="progressValue" :show-value="true" style="height: 8px" class="rounded-full bg-gray-100" />

            <div v-else-if="variant === 'multiple'" class="flex flex-col gap-1">
                <MeterGroup
                    :value="meterValues.map((m) => ({ label: m.label, value: m.value, color: '' }))"
                    :max="props.max"
                    :pt="{ labelList: { class: 'hidden' } }"
                    class="h-3 overflow-hidden rounded-full"
                />
                <div class="mt-1 flex gap-2">
                    <Badge v-for="(m, i) in meterValues" :key="i" :value="m.label + ' ' + m.value" :severity="m.severity" />
                </div>
            </div>
        </div>
    </div>
</template>
