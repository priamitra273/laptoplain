<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { ref, watch } from 'vue';
import type { Sprint } from '../type';

const props = defineProps<{ visible: boolean; sprint?: Sprint | null; loading?: boolean; disabled?: boolean }>();
const emit = defineEmits<{ 'update:visible': [v: boolean]; save: [form: object] }>();

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks'];

const form = ref({ name: '', goal: '', duration: '2 weeks', start_date: '', end_date: '' });

const handleEnter = () => {
    if (props.disabled || props.loading) return;
    emit('save', { ...form.value });
};

watch(
    () => props.sprint,
    (s) => {
        form.value = s
            ? { name: s.name, goal: s.goal ?? '', duration: s.duration ?? '2 weeks', start_date: s.start_date ?? '', end_date: s.end_date ?? '' }
            : { name: '', goal: '', duration: '2 weeks', start_date: '', end_date: '' };
    },
    { immediate: true },
);
</script>

<template>
    <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" header="Edit Sprint" modal :style="{ width: '28rem' }">
        <div class="flex flex-col gap-4 pt-2">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.name" autofocus @keydown.enter="handleEnter" />
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Goal</label>
                <Textarea v-model="form.goal" rows="2" autoResize placeholder="What is the goal of this sprint?" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Duration</label>
                    <Select v-model="form.duration" :options="DURATION_OPTIONS" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Start Date</label>
                    <InputText v-model="form.start_date" type="date" @keydown.enter="handleEnter" />
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">End Date</label>
                <InputText v-model="form.end_date" type="date" @keydown.enter="handleEnter" />
            </div>
        </div>
        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" :disabled="disabled" />
            <Button label="Update" icon="pi pi-check" @click="emit('save', { ...form })" :loading="loading" :disabled="disabled" />
        </template>
    </Dialog>
</template>
