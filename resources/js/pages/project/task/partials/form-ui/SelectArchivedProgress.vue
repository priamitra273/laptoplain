<script setup lang="ts">
import Icon from '@/components/Icon.vue';

interface Props {
    progressError?: string | null;
    archivedDisabled?: boolean;
}

const props = defineProps<Props>();

const isArchived = defineModel<boolean>('isArchived', { default: false });
</script>

<template>
    <div class="flex justify-between gap-4">
        <label for="task-archived" class="flex-1 cursor-pointer">
            <span class="flex items-center gap-2">
                <Icon name="Archive" class="size-4 text-surface-400" />
                <span class="font-semibold text-surface-700">Archive Task</span>
            </span>

            <span class="text-sm text-surface-400"> Hide from boards & filters. You can restore it anytime. </span>
        </label>

        <ToggleSwitch v-model="isArchived" inputId="task-archived" />
    </div>
</template>
