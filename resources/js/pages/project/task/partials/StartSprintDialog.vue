<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, ref, watch } from 'vue';
import type { Sprint } from '../type';

const props = defineProps<{ visible: boolean; sprint: Sprint | null }>();
const emit = defineEmits<{ 'update:visible': [v: boolean]; save: [form: object] }>();

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks', 'Custom'];

const form = ref({ goal: '', duration: '2 weeks', start_date: '', end_date: '' });

const isCustom = computed(() => form.value.duration === 'Custom');

const calcEndDate = () => {
    if (isCustom.value || !form.value.start_date) return;
    const weeks = parseInt(form.value.duration);
    const start = new Date(form.value.start_date);
    start.setDate(start.getDate() + weeks * 7);
    form.value.end_date = start.toISOString().slice(0, 10);
};

watch(
    () => props.sprint,
    (s) => {
        form.value = {
            goal: s?.goal ?? '',
            duration: s?.duration ?? '2 weeks',
            start_date: s?.start_date ?? new Date().toISOString().slice(0, 10),
            end_date: s?.end_date ?? '',
        };
        calcEndDate();
    },
    { immediate: true },
);

watch(
    () => form.value.duration,
    (val) => {
        if (val !== 'Custom') calcEndDate();
        else form.value.end_date = ''; // reset agar user isi sendiri
    },
);

watch(() => form.value.start_date, calcEndDate);
</script>

<template>
    <Dialog
        :visible="visible"
        @update:visible="emit('update:visible', $event)"
        :header="`Start Sprint: ${sprint?.name}`"
        modal
        :style="{ width: '28rem' }"
    >
        <div class="flex flex-col gap-4 pt-2">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Goal</label>
                <Textarea v-model="form.goal" rows="2" autoResize placeholder="What is the goal of this sprint?" autofocus />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Duration</label>
                    <Select v-model="form.duration" :options="DURATION_OPTIONS" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Start Date</label>
                    <InputText v-model="form.start_date" type="date" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">
                    End Date
                    <span v-if="!isCustom" class="text-xs font-normal text-surface-400">(auto)</span>
                </label>
                <InputText
                    v-model="form.end_date"
                    type="date"
                    :readonly="!isCustom"
                    :class="!isCustom ? 'cursor-not-allowed bg-surface-50 dark:bg-surface-800' : ''"
                />
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" />
            <Button label="Start Sprint" icon="pi pi-play" severity="success" @click="emit('save', { ...form })" />
        </template>
    </Dialog>
</template>
