<script setup lang="ts">
import { HardwareStatus } from '@/types';

interface Props {
    value: HardwareStatus;
}

const props = defineProps<Props>();

const severities = {
    1: 'success',
    2: 'info',
    3: 'warn',
    4: 'secondary',
    5: 'danger',
    default: 'secondary',
};
</script>

<template>
    <Tag :severity="severities[props.value.level]" :value="props.value.name" />
</template>
