<script setup lang="ts">
import { useSeverityColor } from '@/composables/useSeverityColor';
import { computed } from 'vue';

interface TaskCategory {
    name?: string;
    icon?: string;
    severity?: string | null;
}

const props = defineProps<{
    category?: TaskCategory | null;
}>();

const { getSeverityColorLight } = useSeverityColor();

const categoryIcon = computed(() => {
    const byName: Record<string, string> = {
        Epic: 'pi pi-bolt',
        Issue: 'pi pi-exclamation-circle',
        Story: 'pi pi-book',
        Task: 'pi pi-check-square',
    };
    const name = props.category?.name ?? '';
    return props.category?.icon ?? byName[name] ?? 'pi pi-tag';
});

const categoryColor = computed((): string => {
    const severity = props.category?.severity;
    if (severity) {
        return getSeverityColorLight(severity, 0.2);
    }

    const byName: Record<string, string> = {
        Epic: '#7c3aed',
        Issue: '#dc2626',
        Story: '#16a34a',
        Task: '#3b82f6',
        Bug: '#dc2626',
    };
    const name = props.category?.name ?? '';
    return byName[name] ?? '#64748b';
});
</script>

<template>
    <i
        v-tooltip.top="category?.name || 'Category'"
        :class="['flex-shrink-0 text-sm', categoryIcon]"
        :style="{ color: categoryColor }"
    ></i>
</template>
