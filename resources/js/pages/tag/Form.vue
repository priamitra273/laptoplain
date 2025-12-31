<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { severityOptions } from '@/constants';
import { PrimeSeverity, SeverityOption, Tag as TagData } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

interface Props {
    value?: TagData;
    visible: boolean;
}

interface TagForm {
    _method: 'POST' | 'PUT';
    name: string;
    severity: PrimeSeverity | string;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ (event: 'update:visible', value: boolean): void }>();

const visible = computed({
    get: () => props.visible,
    set: (val) => emits('update:visible', val),
});

const selectedSeverity = ref<SeverityOption | null>(null);

const form: InertiaForm<TagForm> = useForm({
    _method: 'POST',
    name: '',
    severity: '',
});

const formHeader = computed(() => (props.value?.id ? 'Edit Tag' : 'Create Tag'));

const toast = useToast();

const save = () => {
    if (selectedSeverity.value) form.severity = selectedSeverity.value.value;

    const url = props.value?.id ? route('tag.update', props.value.id) : route('tag.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    const isUpdate = props.value?.id ? true : false;

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: isUpdate ? 'Updated!' : 'Created!',
                detail: isUpdate ? 'Tag has been updated successfully' : 'Project priority has been created successfully',
                life: 3000,
            });
            visible.value = false;
        },
    });
};

const show = () => {
    form.name = props.value?.name ?? '';
    form.severity = props.value?.severity ?? '';
    selectedSeverity.value = getSeverityByValue(props.value?.severity ?? '');
};

const hide = () => {
    form.reset();
    form.clearErrors();
    selectedSeverity.value = null;
};

const getSeverityByValue = (value: PrimeSeverity | string): SeverityOption | null => {
    return severityOptions.find((option) => option.value === value) || null;
};

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
                            <Tag :value="slotProps.option.label" :severity="slotProps.option.value" class="mx-auto" />
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
