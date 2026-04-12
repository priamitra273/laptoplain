<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        priority: { name: string; severity?: string };
        size?: string;
    }>(),
    {
        size: 'text-xs',
    },
);

const iconClass = computed(() => {
    const map: Record<string, string> = {
        highest: 'pi-angle-double-up',
        high: 'pi-angle-up',
        medium: 'pi-minus',
        low: 'pi-angle-down',
        lowest: 'pi-angle-double-down',
    };
    return map[props.priority.name?.toLowerCase() || ''] || 'pi-minus';
});

const colorStyle = computed(() => {
    const sev = props.priority.severity;
    if (sev === 'danger') return '#ef4444';
    if (sev === 'warn' || sev === 'warning') return '#f59e0b';
    if (sev === 'success') return '#10b981';
    return '#94a3b8';
});
</script>

<template>
    <i :class="['pi', iconClass, size]" :style="`color:${colorStyle}`" :title="priority.name" />
</template>
