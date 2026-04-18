<script setup lang="ts">
import Button from 'primevue/button';

interface Props {
    hasSelectedTasks: boolean;
    isMember: boolean;
    hasPermission: boolean;
    isDeveloper: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'add', parentId: string | null): void;
    (e: 'removeSelected'): void;
}>();
</script>

<template>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-lg font-semibold">Tasks</h3>
        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <Button
                label="Add Task"
                icon="pi pi-plus"
                @click="emit('add', null)"
                class="w-full min-w-[120px] sm:w-auto sm:min-w-0"
                :disabled="(!isMember && !hasPermission) || isDeveloper"
            />
            <Button
                v-if="hasSelectedTasks"
                label="Delete Selected"
                icon="pi pi-trash"
                severity="danger"
                @click="emit('removeSelected')"
                class="w-full min-w-[120px] sm:w-auto sm:min-w-0"
                variant="outlined"
                :disabled="!isMember && !hasPermission"
            />
        </div>
    </div>
</template>
