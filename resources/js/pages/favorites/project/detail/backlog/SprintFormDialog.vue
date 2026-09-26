<script setup lang="ts">
import DatePicker from '@/components/form/DatePicker.vue';
import { formatDate } from '@/lib/date';
import { FetchJsonError, fetchJson } from '@/lib/utils';
import { parseDate } from '@internationalized/date';
import { computed, reactive, ref, watch } from 'vue';
import type { BacklogSprint } from './types';

const props = defineProps<{
    projectId: string;
    sprint: BacklogSprint;
    mode: 'start' | 'edit';
}>();

const emit = defineEmits<{ close: [boolean] }>();

const isStart = computed(() => props.mode === 'start');
const title = computed(() => (isStart.value ? `Start ${props.sprint.name}` : 'Edit Sprint'));

const CUSTOM_DURATION = 'Custom';
const durations = ['1 week', '2 weeks', '3 weeks', '4 weeks', CUSTOM_DURATION];

const form = reactive({
    name: props.sprint.name,
    goal: props.sprint.goal ?? '',
    duration: props.sprint.duration,
    start_date: props.sprint.start_date,
    end_date: props.sprint.end_date,
});

const errors = ref<Record<string, string[]>>({});
const failureMessage = ref('');
const processing = ref(false);

const startDate = computed({
    get: () => (form.start_date ? parseDate(form.start_date) : undefined),
    set: (value) => {
        form.start_date = value ? value.toString() : null;
    },
});

const endDate = computed({
    get: () => (form.end_date ? parseDate(form.end_date) : undefined),
    set: (value) => {
        form.end_date = value ? value.toString() : null;
    },
});

const durationModel = computed({
    get: () => form.duration ?? undefined,
    set: (value?: string) => {
        form.duration = value ?? null;
    },
});

const weeksFromDuration = computed(() => Number.parseInt(form.duration ?? '', 10));

/**
 * End date dikunci hanya ketika durasinya berupa jumlah minggu. "Custom" maupun durasi
 * yang belum dipilih sama-sama membiarkan tanggalnya diisi manual.
 */
const isEndDateLocked = computed(() => !Number.isNaN(weeksFromDuration.value));

/**
 * Backend menyimpan `duration` sebagai teks dan tidak menurunkan apa pun darinya —
 * hubungan "durasi → end date" sepenuhnya hidup di sini. Start date ikut diawasi supaya
 * mengubahnya setelah durasi dipilih tetap menggeser end date (v1 hanya mengawasi durasi,
 * jadi end date-nya bisa tertinggal basi).
 */
watch([() => form.duration, () => form.start_date], () => {
    if (!isEndDateLocked.value || !form.start_date) {
        return;
    }

    form.end_date = parseDate(form.start_date).add({ weeks: weeksFromDuration.value }).toString();
});

const submit = async () => {
    processing.value = true;
    errors.value = {};
    failureMessage.value = '';

    const payload: Record<string, unknown> = {
        goal: form.goal || null,
        duration: form.duration,
        start_date: form.start_date,
        end_date: form.end_date,
    };

    if (!isStart.value) {
        payload.name = form.name;
    }

    const url = isStart.value
        ? route('project.sprints.start', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id })
        : route('project.sprints.update', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id });

    try {
        await fetchJson(url, isStart.value ? 'PATCH' : 'PUT', payload);
        emit('close', true);
    } catch (error) {
        if (error instanceof FetchJsonError && error.status === 422) {
            errors.value = (error.data as { errors?: Record<string, string[]> })?.errors ?? {};
        }

        failureMessage.value = error instanceof Error ? error.message : 'Could not save the sprint.';
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <UModal :title="title">
        <template #body>
            <div class="grid gap-6">
                <UAlert v-if="failureMessage" color="error" variant="soft" :description="failureMessage" />

                <UFormField v-if="!isStart" name="name" label="Sprint name" required :error="errors.name?.[0]">
                    <UInput v-model="form.name" placeholder="Sprint 1" class="w-full" />
                </UFormField>

                <UFormField name="goal" label="Sprint goal" :error="errors.goal?.[0]">
                    <UTextarea v-model="form.goal" :rows="3" placeholder="What should this sprint achieve?" class="w-full" />
                </UFormField>

                <UFormField name="duration" label="Duration" :error="errors.duration?.[0]">
                    <USelectMenu v-model="durationModel" :items="durations" placeholder="Select duration" class="w-full" />
                </UFormField>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField name="start_date" label="Start date" :error="errors.start_date?.[0]">
                        <DatePicker
                            v-model="startDate"
                            :label="startDate ? formatDate(startDate.toString()) : 'Select date'"
                            trigger-aria-label="Select sprint start date"
                            trigger-class="w-full"
                            clearable
                        />
                    </UFormField>

                    <UFormField name="end_date" :label="isEndDateLocked ? 'End date (auto)' : 'End date'" :error="errors.end_date?.[0]">
                        <DatePicker
                            v-model="endDate"
                            :label="endDate ? formatDate(endDate.toString()) : 'Select date'"
                            trigger-aria-label="Select sprint end date"
                            :min-value="startDate"
                            :disabled="isEndDateLocked"
                            trigger-class="w-full"
                            clearable
                        />
                    </UFormField>
                </div>
            </div>
        </template>

        <template #footer>
            <UButton color="neutral" variant="ghost" label="Cancel" :disabled="processing" @click="emit('close', false)" />
            <UButton :label="isStart ? 'Start Sprint' : 'Save'" :loading="processing" :disabled="processing" @click="submit" />
        </template>
    </UModal>
</template>
