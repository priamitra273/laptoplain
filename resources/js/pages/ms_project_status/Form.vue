<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { severityOptions } from '@/constants';
import { MsProjectStatus, PrimeSeverity, SeverityOption } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

interface Props {
    value?: MsProjectStatus;
    visible: boolean;
}

interface ProjectStatusForm {
    _method: 'POST' | 'PUT';
    name: string;
    severity: PrimeSeverity | string;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ (event: 'update:visible', value: boolean): void }>();

const visible = computed({
    get() {
        return props.visible;
    },
    set(newValue) {
        emits('update:visible', newValue);
    },
});

const selectedSeverity = ref<SeverityOption | null>(null);

const formHeader = computed(() => (props.value?.id ? 'Edit Project Status' : 'Create New Project Status'));

const form: InertiaForm<ProjectStatusForm> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const toast = useToast();

const save = () => {
    if (selectedSeverity.value) form.severity = selectedSeverity.value.value;

    const url = props.value?.id ? route('project-status.update', props.value.id) : route('project-status.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save project status', life: 3000 });
        },
    });
};

const hide = () => {
    form.reset();
    form.clearErrors();
    selectedSeverity.value = null;
};

const show = () => {
    form.name = props.value?.name ?? '';
    selectedSeverity.value = getSeverityByValue(props.value?.severity ?? '');
};

const getSeverityByValue = (value: PrimeSeverity | string): SeverityOption | null => {
    return severityOptions.find((option) => option.value === value) || null;
};

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => delete form.errors[key],
        { debounce: 400, maxWait: 1000 },
    );
}
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid gap-6" @submit.prevent="save">
            <div class="flex flex-col gap-2">
                <Label for="name">Status Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Status Name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="severity">Severity</Label>
                <Select v-model="selectedSeverity" :options="severityOptions" placeholder="Select severity" class="w-full">
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag :value="slotProps.value.label" :severity="slotProps.value.value" />
                        </div>
                        <span v-else>
                            {{ slotProps.placeholder }}
                        </span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex w-full">
                            <Tag :value="slotProps.option.label" :severity="slotProps.option.value" />
                        </div>
                    </template>
                </Select>
                <InputError :message="form.errors.severity" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </template>
    </Drawer>
</template>
