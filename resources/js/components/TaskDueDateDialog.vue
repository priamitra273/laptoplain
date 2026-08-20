<script lang="ts" setup>
import { computed, ref } from 'vue';

interface Props {
    taskTitle: string;
    statusName: string;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    close: [value: string | null];
}>();

const today = new Date().toISOString().slice(0, 10);
const dueDate = ref('');
const description = computed(() => `"${props.taskTitle}" needs a due date before moving to ${props.statusName}.`);

const submit = () => {
    if (!dueDate.value) return;
    emits('close', dueDate.value);
};
</script>

<template>
    <UModal title="Set Due Date" :description="description" :dismissible="false" :ui="{ footer: 'justify-end' }">
        <template #body>
            <div class="flex flex-col gap-2">
                <Label value="Due Date" required />
                <UInput v-model="dueDate" type="date" :min="today" class="w-full" />
            </div>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', null)" />
            <UButton label="Confirm & Move" :disabled="!dueDate" @click="submit" />
        </template>
    </UModal>
</template>
