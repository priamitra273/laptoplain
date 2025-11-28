<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { severityOptions } from '@/constants';
import { TaskPriority, PrimeSeverity } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Select from 'primevue/select';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

interface Props {
    value?: TaskPriority;
    visible: boolean;
}

interface TaskPriorityForm {
    _method: 'POST' | 'PUT';
    name: string;
    severity: PrimeSeverity;
    [key: string]: any;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>();

const visible = computed<boolean>({
    get: () => props.visible,
    set: (newValue) => emits('update:visible', newValue),
});

const selectedSeverity = ref<PrimeSeverity | null>(null);

const formHeader = computed(() => (props.value?.id ? 'Edit Task Priority' : 'Create New Task Priority'));

const form: InertiaForm<TaskPriorityForm> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const save = (): void => {
    if (selectedSeverity.value) form.severity = selectedSeverity.value;

    const url = props.value?.id ? route('task-priority.update', props.value.id) : route('task-priority.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Success', 'Successfully saved data', 'success');
            visible.value = false;
        },
    });
};

const hide = (): void => {
    form.reset();
    form.clearErrors();
    form._method = 'POST';
    selectedSeverity.value = null;
};

const show = (): void => {
    form.name = props.value?.name ?? '';
    selectedSeverity.value = props.value?.severity ?? null;
};

// Clear validation error when typing
for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => delete form.errors[key],
        { debounce: 500, maxWait: 1000 },
    );
}
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid grid-cols-1 gap-6" @submit.prevent="save">
            <div class="flex flex-col gap-2">
                <Label for="name">Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Task Name" />
                <InputError :message="form.errors.name" v-if="form.errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="severity">Severity</Label>
                <Select
                    v-model="selectedSeverity"
                    :options="severityOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select severity"
                    class="w-full"
                />
                <InputError :message="form.errors.severity" v-if="form.errors.severity" />
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
