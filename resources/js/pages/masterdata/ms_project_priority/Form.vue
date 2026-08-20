<script setup lang="ts">
import { severityOptions } from '@/constants';
import { severityColor } from '@/lib/utils';
import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed } from 'vue';
import type { MasterDataItem } from '../types';

interface Props {
    value?: MasterDataItem;
}

interface ProjectPriorityFormData {
    _method: string;
    name: string;
    severity: string;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ close: [boolean] }>();

const title = computed(() => (props.value?.id ? 'Edit Project Priority' : 'Add Project Priority'));

const form: InertiaForm<ProjectPriorityFormData> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const open = () => {
    form.name = props.value?.name ?? '';
    form.severity = props.value?.severity ?? '';
};

const save = (): void => {
    const url = props.value?.id ? route('project-priority.update', props.value.id) : route('project-priority.store');

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
                <div class="flex flex-col gap-2">
                    <Label value="Name" required />
                    <UInput v-model="form.name" placeholder="Enter priority name" />
                    <InputError v-if="form.errors.name" :message="form.errors.name" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Severity" required />
                    <USelectMenu v-model="form.severity" :items="severityOptions" value-key="value" placeholder="Select a severity" class="w-full">
                        <template #item-label="{ item }">
                            <UBadge :color="severityColor(item.value)" variant="subtle" size="sm">{{ item.label }}</UBadge>
                        </template>
                    </USelectMenu>
                    <InputError v-if="form.errors.severity" :message="form.errors.severity" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Submit" :loading="form.processing" :disabled="form.processing" @click="save" />
        </template>
    </USlideover>
</template>
