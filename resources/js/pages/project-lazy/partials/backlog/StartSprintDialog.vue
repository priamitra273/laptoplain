<script setup lang="ts">
import axios from 'axios';
import moment from 'moment';
import { computed, reactive, ref, watch } from 'vue';
import type { BacklogSprint } from '../../index';

const props = defineProps<{
    visible: boolean;
    sprint: BacklogSprint | null;
    projectId: string;
}>();

const emit = defineEmits<{
    'update:visible': [v: boolean];
    saved: [];
}>();

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks', 'Custom'];

const form = reactive({
    goal: '',
    duration: '2 weeks',
    start_date: null as Date | null,
    end_date: null as Date | null,
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const isCustom = computed(() => form.duration === 'Custom');

const calcEndDate = () => {
    if (isCustom.value || !form.start_date) return;
    const weeks = parseInt(form.duration);
    form.end_date = moment(form.start_date).add(weeks, 'weeks').toDate();
};

const submit = async () => {
    if (!props.sprint) return;

    processing.value = true;
    errors.value = {};

    try {
        await axios.patch(route('project.sprints.start', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), {
            goal: form.goal,
            duration: form.duration,
            start_date: form.start_date ? moment(form.start_date).format('YYYY-MM-DD') : null,
            end_date: form.end_date ? moment(form.end_date).format('YYYY-MM-DD') : null,
        });
        emit('saved');
        emit('update:visible', false);
    } catch (error: any) {
        const responseErrors = error?.response?.data?.errors ?? {};
        errors.value = Object.fromEntries(Object.entries(responseErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]));
    } finally {
        processing.value = false;
    }
};

watch(
    () => props.sprint,
    (value) => {
        form.goal = value?.goal ?? '';
        form.duration = value?.duration ?? '2 weeks';
        form.start_date = value?.start_date ? moment(value.start_date).toDate() : moment().toDate();
        form.end_date = value?.end_date ? moment(value.end_date).toDate() : null;
        errors.value = {};
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
                <div v-if="errors.goal" class="mt-1 text-xs text-red-500">{{ errors.goal }}</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Duration</label>
                    <Select v-model="form.duration" :options="DURATION_OPTIONS" fluid />
                    <div v-if="errors.duration" class="mt-1 text-xs text-red-500">{{ errors.duration }}</div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Start Date</label>
                    <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid />
                    <div v-if="errors.start_date" class="mt-1 text-xs text-red-500">{{ errors.start_date }}</div>
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
                <div v-if="errors.end_date" class="mt-1 text-xs text-red-500">{{ errors.end_date }}</div>
            </div>
        </form>

        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" :disabled="processing" />
            <Button label="Start Sprint" icon="pi pi-play" severity="success" @click="submit" :loading="processing" :disabled="processing" />
        </template>
    </Dialog>
</template>
