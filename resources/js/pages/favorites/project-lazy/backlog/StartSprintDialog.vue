<script setup lang="ts">
import { FetchJsonError, fetchJson } from '@/lib/utils';
import moment from 'moment';
import { computed, reactive, ref, watch } from 'vue';
import type { BacklogSprint } from './types';

interface Props {
    sprint: BacklogSprint;
    projectId: string;
}

const props = defineProps<Props>();

const emits = defineEmits<{ close: [boolean] }>();

const toast = useToast();

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks', 'Custom'];

const form = reactive({
    goal: '',
    duration: '2 weeks',
    start_date: '',
    end_date: '',
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const isCustomDuration = computed(() => form.duration === 'Custom');

const calcEndDate = () => {
    if (isCustomDuration.value || !form.start_date) return;
    const weeks = parseInt(form.duration, 10);
    if (!Number.isNaN(weeks)) {
        form.end_date = moment(form.start_date).add(weeks, 'weeks').format('YYYY-MM-DD');
    }
};

watch(() => form.duration, calcEndDate);
watch(() => form.start_date, calcEndDate);

const initialize = () => {
    errors.value = {};
    form.goal = props.sprint.goal ?? '';
    form.duration = props.sprint.duration ?? '2 weeks';
    form.start_date = props.sprint.start_date ?? moment().format('YYYY-MM-DD');
    form.end_date = props.sprint.end_date ?? '';
    calcEndDate();
};

const submit = async () => {
    processing.value = true;
    errors.value = {};

    try {
        await fetchJson(route('project.sprints.start', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), 'PATCH', { ...form });
        toast.add({ title: 'Success', description: `Sprint "${props.sprint.name}" started`, color: 'success' });
        emits('close', true);
    } catch (error) {
        if (error instanceof FetchJsonError && error.status === 422) {
            const fieldErrors = (error.data as { errors?: Record<string, string[]> })?.errors ?? {};
            errors.value = Object.fromEntries(Object.entries(fieldErrors).map(([key, value]) => [key, value[0]]));
        }
        toast.add({ title: 'Failed', description: 'Could not start sprint.', color: 'error' });
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <UModal
        :title="`Start Sprint: ${sprint.name}`"
        :close="{ onClick: () => emits('close', false) }"
        :ui="{ footer: 'justify-end' }"
        @enter="initialize"
    >
        <template #body>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <Label value="Sprint Goal" />
                    <UTextarea v-model="form.goal" :rows="2" placeholder="What is the goal of this sprint?" class="w-full" autoresize />
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
                    <UInput v-model="form.end_date" type="date" :disabled="!isCustomDuration" :min="form.start_date" class="w-full" />
                    <InputError v-if="errors.end_date" :message="errors.end_date" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" :disabled="processing" @click="emits('close', false)" />
            <UButton label="Start Sprint" icon="i-lucide-play" color="success" :loading="processing" :disabled="processing" @click="submit" />
        </template>
    </UModal>
</template>
