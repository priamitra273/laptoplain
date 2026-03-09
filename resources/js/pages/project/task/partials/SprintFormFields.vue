<script setup lang="ts">
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

interface SprintForm {
    name: string;
    goal: string;
    duration: string;
    start_date: Date | null;
    end_date: Date | null;
}

const props = defineProps<{
    form: SprintForm;
    errors: Record<string, string>;
    loading: boolean;
}>();

const emit = defineEmits<{
    'update:form': [form: SprintForm];
    submit: [];
    cancel: [];
}>();

const DURATION_OPTIONS = ['1 week', '2 weeks', '3 weeks', '4 weeks'];
const update = (key: keyof SprintForm, value: any) => emit('update:form', { ...props.form, [key]: value });
</script>

<template>
    <div class="flex flex-col gap-4 p-1">
        <!-- Name -->
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-surface-600 dark:text-surface-400">
                Sprint Name <span class="text-rose-400">*</span>
            </label>
            <InputText
                :value="form.name"
                @input="update('name', ($event.target as HTMLInputElement).value)"
                placeholder="e.g. Sprint 1"
                class="w-full"
                :class="errors.name ? '!border-rose-400' : ''"
                autofocus
            />
            <p v-if="errors.name" class="mt-1 text-xs text-rose-500">{{ errors.name }}</p>
        </div>

        <!-- Goal -->
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-surface-600 dark:text-surface-400">
                Sprint Goal
                <span class="font-normal text-surface-400">(optional)</span>
            </label>
            <Textarea
                :value="form.goal"
                @input="update('goal', ($event.target as HTMLTextAreaElement).value)"
                placeholder="What do you want to achieve this sprint?"
                rows="2"
                class="w-full !resize-none text-sm"
            />
        </div>

        <!-- Duration -->
        <div>
            <label class="mb-1.5 block text-xs font-semibold text-surface-600 dark:text-surface-400">Duration</label>
            <Select
                :model-value="form.duration"
                @update:model-value="update('duration', $event)"
                :options="DURATION_OPTIONS"
                placeholder="Select duration"
                class="w-full text-sm"
            />
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-surface-600 dark:text-surface-400">Start Date</label>
                <DatePicker
                    :model-value="form.start_date"
                    @update:model-value="update('start_date', $event)"
                    date-format="dd M yy"
                    show-icon
                    icon-display="input"
                    class="w-full text-sm"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold text-surface-600 dark:text-surface-400">End Date</label>
                <DatePicker
                    :model-value="form.end_date"
                    @update:model-value="update('end_date', $event)"
                    date-format="dd M yy"
                    show-icon
                    icon-display="input"
                    class="w-full text-sm"
                    :min-date="form.start_date ?? undefined"
                />
                <p v-if="errors.end_date" class="mt-1 text-xs text-rose-500">{{ errors.end_date }}</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 border-t border-surface-100 pt-3 dark:border-surface-700">
            <Button label="Cancel" severity="secondary" text @click="emit('cancel')" />
            <Button label="Save Sprint" icon="pi pi-check" :loading="loading" @click="emit('submit')" />
        </div>
    </div>
</template>
