<script setup lang="ts">
import type { VisibleTask } from './useGantt';

defineProps<{
    tasks: VisibleTask[];
    expandedIds: Set<string>;
}>();

defineEmits<{
    'toggle-expand': [id: string];
}>();
</script>

<template>
    <div class="sticky left-0 z-9 w-48 shrink-0 border-r border-default bg-default">
        <slot name="header" />

        <div
            v-for="task in tasks"
            :key="task.id"
            class="flex h-12 items-center gap-1 border-b border-default pr-3 text-sm"
            :style="{ paddingLeft: `${12 + task.depth * 20}px` }"
        >
            <UButton
                v-if="task.hasChildren"
                :icon="expandedIds.has(task.id) ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                color="neutral"
                variant="ghost"
                size="xs"
                class="shrink-0"
                @click="$emit('toggle-expand', task.id)"
            />
            <span v-else class="w-6 shrink-0"></span>
            <span class="truncate" :title="task.title">{{ task.title }}</span>
        </div>
    </div>
</template>
