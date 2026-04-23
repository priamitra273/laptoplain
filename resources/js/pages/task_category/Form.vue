<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { severityOptions } from '@/constants';
import { PrimeSeverity, SeverityOption, TaskCategory } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { PrimeIcons } from '@primevue/core/api';
import { watchDebounced } from '@vueuse/core';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';

interface Props {
    value?: TaskCategory;
    visible: boolean;
}

interface TaskCategoryForm {
    _method: 'POST' | 'PUT';
    name: string;
    icon: string;
    severity: PrimeSeverity | string;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ (event: 'update:visible', value: boolean): void }>();

const toast = useToast();

const visible = computed({
    get() {
        return props.visible;
    },
    set(newValue) {
        emits('update:visible', newValue);
    },
});

const selectedSeverity = ref<SeverityOption | null>(null);

const formHeader = computed(() => (props.value?.id ? 'Edit Task Category' : 'Create New Task Category'));

const form: InertiaForm<TaskCategoryForm> = useForm({
    _method: 'POST',
    name: '',
    icon: '',
    severity: '',
});

const iconList = ref<string[]>(Object.values(PrimeIcons));

const save = () => {
    if (selectedSeverity.value) form.severity = selectedSeverity.value.value;

    const url = props.value?.id ? route('task-category.update', props.value.id) : route('task-category.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save task category', life: 3000 });
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
    form.icon = props.value?.icon ?? '';
    form.severity = props.value?.severity ?? '';
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
        <form class="grid gap-8 md:grid-cols-2" @submit.prevent="save">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="name">Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Priority Name" />
                <InputError :message="form.errors.name" />
            </div>

            <div class="col-span-2 flex flex-col gap-2">
                <Label for="icon">Icon</Label>
                <Select
                    v-model="form.icon"
                    :options="iconList"
                    placeholder="Select an Icon"
                    class="w-full"
                    filter
                    show-clear
                    :virtualScrollerOptions="{ itemSize: 38 }"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-2">
                            <i :class="[slotProps.value, 'text-lg']" />
                            <span>{{ slotProps.value }}</span>
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex items-center gap-2">
                            <i :class="[slotProps.option, 'text-lg']" />
                            <span>{{ slotProps.option }}</span>
                        </div>
                    </template>
                </Select>
                <InputError :message="form.errors.icon" />
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
