<script setup lang="ts">
import axios from 'axios';
import moment from 'moment';
import { reactive, ref, watch } from 'vue';
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

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks'];

const form = reactive({
    name: '',
    goal: '',
    duration: '2 weeks',
    start_date: null as Date | null,
    end_date: null as Date | null,
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    if (!props.sprint) return;

    processing.value = true;
    errors.value = {};

    try {
        await axios.put(route('project.sprints.update', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), {
            name: form.name,
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
    (s) => {
        form.name = s?.name ?? '';
        form.goal = s?.goal ?? '';
        form.duration = s?.duration ?? '2 weeks';
        form.start_date = s?.start_date ? new Date(s.start_date) : null;
        form.end_date = s?.end_date ? new Date(s.end_date) : null;
        errors.value = {};
    },
    { immediate: true },
);
</script>

<template>
    <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" header="Edit Sprint" modal :style="{ width: '28rem' }">
        <form class="flex flex-col gap-4 pt-2" @submit.prevent="submit">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.name" autofocus :disabled="processing" />
                <div v-if="errors.name" class="text-xs text-red-500">{{ errors.name }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Goal</label>
                <Textarea v-model="form.goal" rows="2" autoResize placeholder="What is the goal of this sprint?" :disabled="processing" />
                <div v-if="errors.goal" class="text-xs text-red-500">{{ errors.goal }}</div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Duration</label>
                    <Select v-model="form.duration" :options="DURATION_OPTIONS" fluid :disabled="processing" />
                    <div v-if="errors.duration" class="text-xs text-red-500">{{ errors.duration }}</div>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Start Date</label>
                    <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="processing" />
                    <div v-if="errors.start_date" class="text-xs text-red-500">{{ errors.start_date }}</div>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">End Date</label>
                <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="processing" />
                <div v-if="errors.end_date" class="text-xs text-red-500">{{ errors.end_date }}</div>
            </div>
        </form>
        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" :disabled="processing" />
            <Button label="Update" icon="pi pi-check" @click="submit" :loading="processing" :disabled="processing" />
        </template>
    </Dialog>
</template>
