<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { ProjectPolicyKey } from '@/types/type';
import Button from 'primevue/button';
import { inject } from 'vue';

interface Props {
    selectedCount: number;
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'add', parentId: string | null): void;
    (e: 'removeSelected'): void;
}>();

const policy = inject(ProjectPolicyKey, null);
const { canAction } = useProjectPermissions(policy);
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
                :disabled="!canAction('task', 'create')"
            />
            <Button
                v-if="selectedCount > 0"
                :label="`Delete Selected (${selectedCount})`"
                icon="pi pi-trash"
                severity="danger"
                @click="emit('removeSelected')"
                class="w-full min-w-[120px] sm:w-auto sm:min-w-0"
                variant="outlined"
                :disabled="!canAction('task', 'delete')"
            />
        </div>
    </div>
</template>
