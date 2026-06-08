<script lang="ts" setup>
import { cn } from '@/lib/utils';
import Icon from './Icon.vue';

interface Props {
    value: string;
    required?: boolean;
    as?: string;
    class?: string;
    icon?: string;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'span',
    required: false,
});
</script>

<template>
    <component :is="props.as" :class="cn('flex items-center gap-2 text-surface-600 dark:text-surface-200', props.class)">
        <Icon v-if="props.icon" :name="props.icon" />
        <span>{{ props.value }}</span>
        <span v-if="props.required" class="text-red-500">*</span>
    </component>
</template>
