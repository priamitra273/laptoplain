<script setup lang="ts">
import moment from 'moment';
import { ref } from 'vue';
import type { TaskActivity } from '../kanban/types';

interface Props {
    taskId: string;
    taskTitle: string;
}

const props = defineProps<Props>();

const emits = defineEmits<{ close: [boolean] }>();

const loading = ref(false);
const activities = ref<TaskActivity[]>([]);

const load = async () => {
    loading.value = true;
    try {
        const response = await fetch(route('task.activities', props.taskId), { headers: { Accept: 'application/json' } });
        const body = await response.json();
        activities.value = body.activities ?? [];
    } finally {
        loading.value = false;
    }
};

const formatFieldValue = (value: string | null) => (value === null ? '—' : value);
</script>

<template>
    <UModal title="History Log" :description="taskTitle" @enter="load">
        <template #body>
            <div v-if="loading" class="flex justify-center py-8">
                <UIcon name="i-lucide-loader-2" class="size-5 animate-spin text-muted" />
            </div>

            <p v-else-if="!activities.length" class="py-8 text-center text-sm text-muted">No history yet.</p>

            <div v-else class="flex max-h-96 flex-col gap-4 overflow-y-auto">
                <div v-for="activity in activities" :key="activity.id" class="flex flex-col gap-1.5 border-l-2 border-default pl-3">
                    <p class="text-sm">
                        <span class="font-medium">{{ activity.causer?.name ?? 'System' }}</span>
                        updated this task
                        <span class="text-muted">· {{ moment(activity.created_at).fromNow() }}</span>
                    </p>
                    <ul class="flex flex-col gap-1">
                        <li v-for="(field, index) in activity.changed_fields" :key="index" class="text-xs">
                            <span class="font-medium">{{ field.field }}</span
                            >:
                            <span class="text-muted line-through">
                                <span v-if="field.field === 'Description'" v-html="formatFieldValue(field.old_value)" />
                                <span v-else>{{ formatFieldValue(field.old_value) }}</span>
                            </span>
                            →
                            <span v-if="field.field === 'Description'" v-html="field.new_value ?? 'Removed'" />
                            <span v-else>{{ field.new_value ?? 'Removed' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Close" color="neutral" variant="outline" @click="emits('close', true)" />
        </template>
    </UModal>
</template>
