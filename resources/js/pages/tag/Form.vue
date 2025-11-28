<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { severityOptions } from '@/constants';
import { Tag, PrimeSeverity } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Select from 'primevue/select';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

interface Props {
    value?: Tag;
    visible: boolean;
}

interface TagForm {
    _method: 'POST' | 'PUT';
    name: string;
    severity: PrimeSeverity;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ (event: 'update:visible', value: boolean): void }>();

const visible = computed({
    get: () => props.visible,
    set: (val) => emits('update:visible', val),
});

const selectedSeverity = ref<PrimeSeverity | null>(null);

const form: InertiaForm<TagForm> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const formHeader = computed(() => (props.value?.id ? 'Edit Tag' : 'Create Tag'));

const save = () => {
    if (selectedSeverity.value) form.severity = selectedSeverity.value;

    const url = props.value?.id ? route('tag.update', props.value.id) : route('tag.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire('Success', 'Data saved successfully!', 'success');
            visible.value = false;
        },
    });
};

const show = () => {
    form.name = props.value?.name ?? '';
    form.severity = props.value?.severity ?? '';
};

const hide = () => {
    form.reset();
    form.clearErrors();
    selectedSeverity.value = props.value?.severity ?? null;
};

// Hapus error saat user mengetik ulang
for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => delete form.errors[key],
        { debounce: 300 },
    );
}
</script>

<template>
    <Drawer v-model:visible="visible" position="right" class="!w-full md:!w-[40vw]" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid gap-6 md:grid-cols-2" @submit.prevent="save">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="name">Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Tag Type Name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="col-span-2 flex flex-col gap-2">
                <Label for="severity">Severity</Label>
                <Select
                    v-model="selectedSeverity"
                    :options="severityOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select severity"
                    class="w-full"
                />
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
