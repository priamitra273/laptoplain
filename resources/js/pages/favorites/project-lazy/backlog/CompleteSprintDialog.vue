<script setup lang="ts">
import { fetchJson } from '@/lib/utils';
import { computed, reactive, ref } from 'vue';
import type { BacklogSprint } from './types';

interface Props {
    sprint: BacklogSprint;
    sprints: BacklogSprint[];
    projectId: string;
}

const props = defineProps<Props>();

const emits = defineEmits<{ close: [boolean] }>();

const toast = useToast();

const form = reactive({
    retrospective: '',
    move_incomplete_to: { existing_sprint: '', other: 'backlog' as string },
});
const processing = ref(false);

const SPECIAL_OPTIONS = [
    { id: 'backlog', name: 'Leave in backlog' },
    { id: 'new_sprint', name: 'Create new Sprint' },
];
const SPECIAL_IDS = SPECIAL_OPTIONS.map((option) => option.id);

const destinationOptions = computed(() => [...SPECIAL_OPTIONS, ...props.sprints.filter((sprint) => sprint.id !== props.sprint.id)]);
const selectedDestination = computed({
    get: () => form.move_incomplete_to.other || form.move_incomplete_to.existing_sprint,
    set: (value: string) => {
        if (SPECIAL_IDS.includes(value)) {
            form.move_incomplete_to = { existing_sprint: '', other: value };
        } else {
            form.move_incomplete_to = { existing_sprint: value, other: '' };
        }
    },
});

const incompleteCount = computed(() => (props.sprint.tasks ?? []).filter((task) => !['Done', 'Completed'].includes(task.status?.name ?? '')).length);

const initialize = () => {
    form.retrospective = '';
    form.move_incomplete_to = { existing_sprint: '', other: 'backlog' };
};

const submit = async () => {
    processing.value = true;

    try {
        await fetchJson(route('project.sprints.complete', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), 'PATCH', {
            ...form,
        });
        toast.add({ title: 'Success', description: `Sprint "${props.sprint.name}" completed`, color: 'success' });
        emits('close', true);
    } catch {
        toast.add({ title: 'Failed', description: 'Could not complete sprint.', color: 'error' });
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <UModal
        :title="`Complete Sprint: ${sprint.name}`"
        :close="{ onClick: () => emits('close', false) }"
        :ui="{ footer: 'justify-end' }"
        @enter="initialize"
    >
        <template #body>
            <div class="flex flex-col gap-4">
                <div v-if="incompleteCount > 0" class="flex gap-2 rounded-lg border border-warning/30 bg-warning/10 p-3">
                    <UIcon name="i-lucide-triangle-alert" class="mt-0.5 size-4 shrink-0 text-warning" />
                    <div>
                        <p class="text-sm font-medium text-warning">{{ incompleteCount }} incomplete issue{{ incompleteCount > 1 ? 's' : '' }}</p>
                        <p class="mt-0.5 text-xs text-muted">Choose where to move them, or leave in backlog.</p>
                    </div>
                </div>

                <div v-if="incompleteCount > 0" class="flex flex-col gap-2">
                    <Label value="Move incomplete issues to" />
                    <USelectMenu v-model="selectedDestination" :items="destinationOptions" label-key="name" value-key="id" class="w-full" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Retrospective" />
                    <UTextarea v-model="form.retrospective" :rows="3" autoresize placeholder="What went well? What could improve?" class="w-full" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" :disabled="processing" @click="emits('close', false)" />
            <UButton label="Complete Sprint" icon="i-lucide-flag" color="success" :loading="processing" :disabled="processing" @click="submit" />
        </template>
    </UModal>
</template>
