<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import moment from 'moment';
import { watch } from 'vue';
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

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks'];

const form = useForm({
    name: '',
    goal: '',
    duration: '2 weeks',
    start_date: null as Date | null,
    end_date: null as Date | null,
});

const submit = () => {
    if (!props.sprint) return;

    form.transform((data) => ({
        ...data,
        start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
        end_date: data.end_date ? moment(data.end_date).format('YYYY-MM-DD') : null,
    })).put(
        route('project.sprints.update', {
            projectEncoded: props.projectId,
            sprintEncoded: props.sprint.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:visible', false);
            },
        },
    );
};

watch(
    () => props.sprint,
    (s) => {
        form.name = s?.name ?? '';
        form.goal = s?.goal ?? '';
        form.duration = s?.duration ?? '2 weeks';
        form.start_date = s?.start_date ? new Date(s.start_date) : null;
        form.end_date = s?.end_date ? new Date(s.end_date) : null;
        form.clearErrors();
    },
    { immediate: true },
);
</script>

<template>
    <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" header="Edit Sprint" modal :style="{ width: '28rem' }">
        <form class="flex flex-col gap-4 pt-2" @submit.prevent="submit">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.name" autofocus :disabled="form.processing" />
                <div v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Goal</label>
                <Textarea v-model="form.goal" rows="2" autoResize placeholder="What is the goal of this sprint?" :disabled="form.processing" />
                <div v-if="form.errors.goal" class="text-xs text-red-500">{{ form.errors.goal }}</div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Duration</label>
                    <Select v-model="form.duration" :options="DURATION_OPTIONS" fluid :disabled="form.processing" />
                    <div v-if="form.errors.duration" class="text-xs text-red-500">{{ form.errors.duration }}</div>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Start Date</label>
                    <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="form.processing" />
                    <div v-if="form.errors.start_date" class="text-xs text-red-500">{{ form.errors.start_date }}</div>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">End Date</label>
                <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" showIcon fluid :disabled="form.processing" />
                <div v-if="form.errors.end_date" class="text-xs text-red-500">{{ form.errors.end_date }}</div>
            </div>
        </form>
        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" :disabled="form.processing" />
            <Button label="Update" icon="pi pi-check" @click="submit" :loading="form.processing" :disabled="form.processing" />
        </template>
    </Dialog>
</template>
