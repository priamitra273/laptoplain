<script setup lang="ts">
import { severityOptions } from '@/constants';
import { severityColor } from '@/lib/utils';
import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed } from 'vue';
import type { TaskStatusItem } from './Index.vue';

interface Props {
    value?: TaskStatusItem;
}

interface TaskStatusFormData {
    _method: string;
    name: string;
    severity: string;
    score: number;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ close: [boolean] }>();

const title = computed(() => (props.value?.id ? 'Edit Task Status' : 'Add Task Status'));

const form: InertiaForm<TaskStatusFormData> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
    score: 0,
});

const open = () => {
    form.name = props.value?.name ?? '';
    form.severity = props.value?.severity ?? '';
    form.score = props.value?.score ?? 0;
};

const save = (): void => {
    const url = props.value?.id ? route('task-status.update', props.value.id) : route('task-status.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            emits('close', true);
        },
    });
};

// watching form changes
for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        {
            debounce: 500,
            maxWait: 1000,
        },
    );
}
</script>

<template>
    <USlideover :title="title" :close="{ onClick: () => emits('close', false) }" @enter="open">
        <template #body>
            <div class="grid gap-6">
                <UFormField label="Name" name="name" required :error="form.errors.name">
                    <UInput v-model="form.name" placeholder="Enter status name" class="w-full" />
                </UFormField>

                <UFormField label="Severity" name="severity" required :error="form.errors.severity">
                    <USelectMenu v-model="form.severity" :items="severityOptions" value-key="value" placeholder="Select a severity" class="w-full">
                        <template #item-label="{ item }">
                            <UBadge :color="severityColor(item.value)" variant="subtle" size="sm">{{ item.label }}</UBadge>
                        </template>
                    </USelectMenu>
                </UFormField>

                <UFormField label="Score" name="score" required :error="form.errors.score">
                    <UInputNumber v-model="form.score" :min="0" :max="255" placeholder="Enter score" class="w-full" />
                </UFormField>
            </div>
        </template>

        <template #footer>
            <UButton label="Submit" :loading="form.processing" :disabled="form.processing" @click="save" />
        </template>
    </USlideover>
</template>
