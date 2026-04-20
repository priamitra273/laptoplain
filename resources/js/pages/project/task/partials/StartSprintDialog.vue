<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, watch } from 'vue';
import type { Sprint } from '../type';

interface Props {
    visible: boolean;
    sprint: Sprint | null;
    projectId: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [v: boolean];
}>();

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks', 'Custom'];

const form = useForm({
    goal: '',
    duration: '2 weeks',
    start_date: null as Date | null,
    end_date: null as Date | null,
});

const isCustom = computed(() => form.duration === 'Custom');

const submit = () => {
    if (!props.sprint) return;

    form.transform((data) => ({
        ...data,
        start_date: moment(form.start_date).format('YYYY-MM-DD'),
        end_date: moment(form.end_date).format('YYYY-MM-DD'),
    })).patch(route('project.sprints.start', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:visible', false);
        },
    });
};

const calcEndDate = () => {
    if (isCustom.value || !form.start_date) return;
    const weeks = parseInt(form.duration);
    const start = form.start_date instanceof Date ? new Date(form.start_date) : new Date(form.start_date);
    start.setDate(start.getDate() + weeks * 7);
    form.end_date = start;
};

watch(
    () => props.sprint,
    (value) => {
        form.goal = value?.goal ?? '';
        form.duration = value?.duration ?? '2 weeks';
        form.start_date = value?.start_date ? new Date(value.start_date) : new Date();
        form.end_date = value?.end_date ? new Date(value.end_date) : null;
        calcEndDate();
    },
    { immediate: true },
);

watch(
    () => form.duration,
    (val) => {
        if (val !== 'Custom') calcEndDate();
        else form.end_date = null;
    },
);

watch(() => form.start_date, calcEndDate);
</script>

<template>
    <Dialog
        :visible="visible"
        :header="`Start Sprint: ${sprint?.name}`"
        modal
        :style="{ width: '28rem' }"
        @update:visible="emit('update:visible', $event)"
    >
        <form class="flex flex-col gap-4 pt-2" @submit.prevent="submit">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Goal</label>
                <Textarea v-model="form.goal" rows="2" autoResize placeholder="What is the goal of this sprint?" autofocus />
                <div v-if="form.errors.goal" class="mt-1 text-xs text-red-500">{{ form.errors.goal }}</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Duration</label>
                    <Select v-model="form.duration" :options="DURATION_OPTIONS" fluid />
                    <div v-if="form.errors.duration" class="mt-1 text-xs text-red-500">{{ form.errors.duration }}</div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Start Date</label>
                    <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid />
                    <div v-if="form.errors.start_date" class="mt-1 text-xs text-red-500">{{ form.errors.start_date }}</div>
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">
                    End Date
                    <span v-if="!isCustom" class="text-xs font-normal text-surface-400">(auto)</span>
                </label>
                <DatePicker
                    v-model="form.end_date"
                    :disabled="!isCustom"
                    dateFormat="yy-mm-dd"
                    showIcon
                    fluid
                    :class="{
                        'cursor-not-allowed bg-surface-50 dark:bg-surface-800': !isCustom,
                    }"
                />
                <div v-if="form.errors.end_date" class="mt-1 text-xs text-red-500">{{ form.errors.end_date }}</div>
            </div>
        </form>

        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" :disabled="form.processing" />
            <Button
                label="Start Sprint"
                icon="pi pi-play"
                severity="success"
                @click="submit"
                :loading="form.processing"
                :disabled="form.processing"
            />
        </template>
    </Dialog>
</template>
