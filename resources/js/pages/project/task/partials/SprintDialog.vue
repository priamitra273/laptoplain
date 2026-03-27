<script setup lang="ts">
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { ref, watch } from 'vue';
import type { Sprint } from '../type';

export interface SprintForm {
    name: string;
    goal: string;
    duration: string;
    start_date: string;
    end_date: string;
}

// mode: 'start' = Start Sprint dialog, 'edit' = Edit Sprint dialog
const props = defineProps<{ visible: boolean; sprint?: Sprint | null; mode?: 'start' | 'edit' }>();
const emit = defineEmits<{ 'update:visible': [v: boolean]; save: [form: SprintForm] }>();

const DURATION_OPTIONS = ['1', '2', '3', '4'];

const form = ref<SprintForm>({ name: '', goal: '', duration: '2', start_date: '', end_date: '' });

watch(
    () => props.sprint,
    (s) => {
        form.value = s
            ? { name: s.name, goal: s.goal ?? '', duration: String(s.duration ?? 2), start_date: s.start_date ?? '', end_date: s.end_date ?? '' }
            : { name: '', goal: '', duration: '2', start_date: '', end_date: '' };
    },
    { immediate: true },
);

const dialogHeader = () => (props.mode === 'start' ? `Start Sprint: ${props.sprint?.name}` : 'Edit Sprint');
const submitLabel = () => (props.mode === 'start' ? 'Start' : 'Update');
const submitIcon = () => (props.mode === 'start' ? 'pi pi-play' : 'pi pi-check');

const submit = () => emit('save', { ...form.value });
</script>

<template>
    <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" :header="dialogHeader()" modal :style="{ width: '28rem' }">
        <div class="flex flex-col gap-4 pt-2">
            <!-- Show name field only on edit mode -->
            <div v-if="mode === 'edit'" class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.name" placeholder="e.g. Sprint 1" autofocus />
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Sprint Goal</label>
                <Textarea v-model="form.goal" placeholder="What is the goal of this sprint?" rows="2" autoResize :autofocus="mode === 'start'" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Duration</label>
                    <Select v-model="form.duration" :options="DURATION_OPTIONS">
                        <template #value="{ value }">{{ value }} week{{ value !== '1' ? 's' : '' }}</template>
                        <template #option="{ option }">{{ option }} week{{ option !== '1' ? 's' : '' }}</template>
                    </Select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Start Date</label>
                    <InputText v-model="form.start_date" type="date" />
                </div>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">End Date</label>
                <InputText v-model="form.end_date" type="date" />
            </div>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="emit('update:visible', false)" />
            <Button :label="submitLabel()" :icon="submitIcon()" :severity="mode === 'start' ? 'success' : 'primary'" @click="submit" />
        </template>
    </Dialog>
</template>
