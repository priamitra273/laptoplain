<script setup lang="ts">
import { InertiaForm, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, ref, watch } from 'vue';
import type { Sprint } from '../type';

interface MoveIncompleteTo {
    existing_sprint: string | number;
    other: string;
}

interface CompleteSprintForm {
    retrospective: string;
    move_incomplete_to: MoveIncompleteTo;
    [key: string]: any;
}

interface CompleteSprintProps {
    visible: boolean;
    projectId: string;
    sprint?: Sprint | null;
    sprints: Sprint[];
}

const form: InertiaForm<CompleteSprintForm> = useForm({
    retrospective: '',
    move_incomplete_to: {
        existing_sprint: '',
        other: 'backlog',
    },
});

const props = defineProps<CompleteSprintProps>();

const emit = defineEmits<{
    'update:visible': [v: boolean];
}>();

const selectedDestination = ref<string | number>('backlog');

const SPECIAL_OPTIONS = [
    { id: 'backlog', name: 'Leave in backlog' },
    { id: 'new_sprint', name: 'Create new Sprint' },
];

const SPECIAL_IDS = SPECIAL_OPTIONS.map((o) => o.id);

const otherSprints = computed(() => [...SPECIAL_OPTIONS, ...props.sprints.filter((s) => s.id !== props.sprint?.id)]);

const incompleteCount = computed(() => props.sprint?.tasks?.filter((t) => !['Done', 'Completed'].includes(t.status?.name ?? '')).length ?? 0);

const handleDestinationChange = (val: string | number) => {
    if (!val) {
        form.move_incomplete_to = { existing_sprint: '', other: '' };
    } else if (typeof val === 'string' && SPECIAL_IDS.includes(val)) {
        form.move_incomplete_to = { existing_sprint: '', other: val };
    } else {
        form.move_incomplete_to = { existing_sprint: val, other: '' };
    }
};

const submit = () => {
    if (!props.sprint?.id) return;

    form.patch(route('project.sprints.complete', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), {
        onSuccess: () => {
            emit('update:visible', false);
            form.reset();
            selectedDestination.value = 'backlog';
        },
    });
};

watch(
    () => props.visible,
    (isVisible) => {
        if (isVisible) {
            form.reset();
            selectedDestination.value = 'backlog';
            handleDestinationChange('backlog');
        }
    },
);
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        :header="`Complete Sprint: ${sprint?.name}`"
        modal
        :style="{ width: '30rem' }"
    >
        <div class="flex flex-col gap-4 pt-2">
            <!-- Incomplete task warning -->
            <div
                v-if="incompleteCount > 0"
                class="flex gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/30"
            >
                <i class="pi pi-exclamation-triangle mt-0.5 text-amber-500" />
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        {{ incompleteCount }} incomplete issue{{ incompleteCount > 1 ? 's' : '' }}
                    </p>
                    <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">Choose where to move them, or leave in backlog.</p>
                </div>
            </div>

            <!-- Move incomplete tasks -->
            <div v-if="incompleteCount > 0" class="flex flex-col gap-1">
                <label class="text-sm font-medium">Move incomplete issues to</label>
                <Select
                    v-model="selectedDestination"
                    :options="otherSprints"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select option"
                    @change="handleDestinationChange($event.value)"
                />
            </div>

            <!-- Retrospective -->
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Retrospective <span class="font-normal text-surface-400">(optional)</span></label>
                <Textarea v-model="form.retrospective" rows="3" autoResize placeholder="What went well? What could improve?" />
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" :disabled="form.processing" />
            <Button
                label="Complete Sprint"
                icon="pi pi-flag"
                severity="success"
                @click="submit"
                :loading="form.processing"
                :disabled="form.processing"
            />
        </template>
    </Dialog>
</template>
