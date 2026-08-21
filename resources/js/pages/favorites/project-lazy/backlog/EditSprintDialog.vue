<script setup lang="ts">
import { FetchJsonError, fetchJson } from '@/lib/utils';
import { reactive, ref } from 'vue';
import type { BacklogSprint } from './types';

interface Props {
    sprint: BacklogSprint;
    projectId: string;
}

const props = defineProps<Props>();

const emits = defineEmits<{ close: [boolean] }>();

const toast = useToast();

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks'];

const form = reactive({
    name: '',
    goal: '',
    duration: '2 weeks',
    start_date: '',
    end_date: '',
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const initialize = () => {
    errors.value = {};
    form.name = props.sprint.name;
    form.goal = props.sprint.goal ?? '';
    form.duration = props.sprint.duration ?? '2 weeks';
    form.start_date = props.sprint.start_date ?? '';
    form.end_date = props.sprint.end_date ?? '';
};

const submit = async () => {
    processing.value = true;
    errors.value = {};

    try {
        await fetchJson(route('project.sprints.update', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), 'PUT', { ...form });
        toast.add({ title: 'Success', description: 'Sprint updated successfully', color: 'success' });
        emits('close', true);
    } catch (error) {
        if (error instanceof FetchJsonError && error.status === 422) {
            const fieldErrors = (error.data as { errors?: Record<string, string[]> })?.errors ?? {};
            errors.value = Object.fromEntries(Object.entries(fieldErrors).map(([key, value]) => [key, value[0]]));
        }
        toast.add({ title: 'Failed', description: 'Could not update sprint.', color: 'error' });
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <UModal title="Edit Sprint" :close="{ onClick: () => emits('close', false) }" :ui="{ footer: 'justify-end' }" @enter="initialize">
        <template #body>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <Label value="Sprint Name" required />
                    <UInput v-model="form.name" class="w-full" />
                    <InputError v-if="errors.name" :message="errors.name" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Sprint Goal" />
                    <UTextarea v-model="form.goal" :rows="2" autoresize placeholder="What is the goal of this sprint?" class="w-full" />
                    <InputError v-if="errors.goal" :message="errors.goal" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-2">
                        <Label value="Duration" />
                        <USelectMenu v-model="form.duration" :items="DURATION_OPTIONS" class="w-full" />
                        <InputError v-if="errors.duration" :message="errors.duration" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Start Date" />
                        <UInput v-model="form.start_date" type="date" class="w-full" />
                        <InputError v-if="errors.start_date" :message="errors.start_date" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="End Date" />
                    <UInput v-model="form.end_date" type="date" :min="form.start_date" class="w-full" />
                    <InputError v-if="errors.end_date" :message="errors.end_date" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" :disabled="processing" @click="emits('close', false)" />
            <UButton label="Update" icon="i-lucide-check" :loading="processing" :disabled="processing" @click="submit" />
        </template>
    </UModal>
</template>
