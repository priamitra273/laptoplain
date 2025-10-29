<script setup lang="ts">
import { computed, ref } from 'vue';
import Colors from 'tailwindcss/colors';
import NumberFlow from '@number-flow/vue';
import Icon from './Icon.vue';

interface Props {
    title?: string;
    value?: number;
    description?: string;
    max?: number;
    done?: number;
    pending?: number;
    info?: string;
    variant: "text" | "single" | "multiple";
}

const props = withDefaults(defineProps<Props>(), {
    value: 0,
    done: 0,
    pending: 0,
    max: 100
});

const value = computed(() => {
    return [
        { label: 'Complete', value: props.done, color: 'var(--p-primary-color)' },
        { label: 'Pending', value: props.pending, color: Colors.amber[500] },
    ]
})

const progressValue = computed<number>(() => {
    return Math.round(props.value / props.max * 100);
})

</script>

<template>
    <div
        class="aspect-video overflow-hidden rounded-xl border border-sidebar-border bg-white p-3 shadow-sm"
        :class="[variant === 'text' ? 'relative' : 'flex flex-col justify-between']"
    >
        <div class="text-sm flex gap-2 items-center">
            <span>{{ title }}</span>
            <Icon name="Info" v-tooltip.bottom="info" v-if="info" />
        </div>

        <div class="flex flex-col gap-1" :class="{'absolute top-1/2 -translate-y-3': variant === 'text'}">
            <span class="text-2xl font-semibold leading-tight">
                <NumberFlow :value="props.value" will-change />
            </span>
            <span class="text-xs text-muted-foreground" v-if="description">{{ description }}</span>
        </div>

        <div class="" v-if="variant !== 'text'">
            <ProgressBar v-if="variant === 'single'" :value="progressValue" :show-value="false" style="height: 6px"></ProgressBar>

            <MeterGroup
                v-else-if="variant === 'multiple'"
                :value="value"
                :max="max"
                :pt="{
                    labelList: {
                        class: '!hidden',
                    },
                }"
            />
        </div>
    </div>
</template>
