<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { Hardware, HardwareBrand, HardwareComponent, HardwareModel } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { AutoCompleteCompleteEvent } from 'primevue/autocomplete';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

interface Props {
    value?: Hardware;
    visible: boolean;
    components?: HardwareComponent[];
    brands?: HardwareBrand[];
    models?: HardwareModel[];
}

interface HardwareForm {
    _method: 'POST' | 'PUT';
    serial_number: string | null;
    category: string | null;
    remarks: string | null;
    po_number: string | null;
    brand: HardwareBrand | null;
    model: string | null;
    [key: string]: any;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>();

const visible = computed<boolean>({
    get() {
        return props.visible;
    },
    set(newValue) {
        emits('update:visible', newValue);
    },
});

const formHeader = computed<string>(() => {
    return props.value?.uuid ? 'Edit Hardware' : 'Add New Hardware';
});

// Category options matching the migration enum
const categoryOptions = [
    { label: 'CPU', value: 'cpu' },
    { label: 'GPU', value: 'gpu' },
    { label: 'RAM', value: 'ram' },
    { label: 'SSD', value: 'ssd' },
    { label: 'Motherboard', value: 'mobo' },
    { label: 'Network Interface Card', value: 'nic' },
    { label: 'Power Supply Unit', value: 'psu' },
    { label: 'Liquid Cooling', value: 'lc' },
];

const brands = ref<HardwareBrand[]>([]);
const models = ref<string[]>([]);

const form: InertiaForm<HardwareForm> = useForm({
    _method: 'POST',
    serial_number: null,
    category: null,
    remarks: null,
    po_number: null,
    brand: null,
    model: null,
});

const searchBrand = (event: AutoCompleteCompleteEvent): void => {
    brands.value = props.brands?.filter((item) => item.name.toLowerCase().includes(event.query.toLowerCase())) ?? [];

    if (brands.value?.[0]?.name.toLowerCase() !== event.query.toLowerCase()) {
        const defaultItem: HardwareBrand = {
            uuid: '',
            name: event.query,
        };

        brands.value.unshift(defaultItem);
    }
};

const searchModels = (event: AutoCompleteCompleteEvent): void => {
    const filtered = (props.models?.filter(
        (item) =>
            item.component_uuid === form.category &&
            item.brand_uuid === form.brand?.uuid &&
            item.model.toLowerCase().includes(event.query.toLowerCase()),
    ) ?? []) as HardwareModel[];

    models.value = filtered.map((item) => item.model);

    if (filtered[0]?.model !== event.query) {
        models.value.unshift(event.query);
    }
};

const keyPress = (event: Event) => {
    console.log(event);
};

const save = (): void => {
    const url = props.value?.uuid ? route('hardware.update', props.value.uuid) : route('hardware.store');

    form._method = props.value?.uuid ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
            visible.value = false;
        },
    });
};

const hide = (): void => {
    form._method = 'POST';
};

const show = (): void => {
    form.po_number = props.value?.po_number ?? null;
    form.serial_number = props.value?.serial_number ?? null;
    form.category = props.value?.category_uuid ?? props.value?.category ?? null;
    form.brand = props.value?.brand ?? null;
    form.model = props.value?.model ?? null;
    form.remarks = props.value?.remarks ?? null;
};

// watching form changes
for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        { debounce: 500, maxWait: 1000 },
    );
}
</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="po_number">PO Number <span class="text-red-500">*</span></Label>
                <InputText v-model="form.po_number" id="po_number" placeholder="Enter Purchase Order Number" />
                <InputError :message="form.errors.po_number" v-if="form.errors.po_number" />
            </div>

            <div class="col-span-2 flex flex-col gap-2">
                <Label for="serial_number">Serial Number <span class="text-red-500">*</span></Label>
                <InputText v-model="form.serial_number" id="serial_number" placeholder="Enter Serial Number" />
                <InputError :message="form.errors.serial_number" v-if="form.errors.serial_number" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="category">Component <span class="text-red-500">*</span></Label>
                <Select
                    v-model="form.category"
                    :options="props.components"
                    optionLabel="name"
                    optionValue="uuid"
                    placeholder="Select Category"
                    id="category"
                    label-id="category"
                    class="w-full"
                    @change="form.model = null"
                />
                <InputError :message="form.errors.category" v-if="form.errors.category" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="brand">Brand <span class="text-red-500">*</span></Label>
                <AutoComplete v-model="form.brand" :suggestions="brands" input-id="ac-brand" option-label="name" @complete="searchBrand" fluid />
                <InputError :message="form.errors.brand" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="model">Model</Label>
                <AutoComplete v-model="form.model" input-id="model" :suggestions="models" fluid @complete="searchModels" />
                <InputError :message="form.errors.model" />
            </div>

            <div class="col-span-2 flex flex-col gap-2">
                <Label for="remarks">Remarks</Label>
                <Textarea v-model="form.remarks" rows="7" style="resize: none" id="remarks" />
                <InputError :message="form.errors.remarks" v-if="form.errors.remarks" />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </template>
    </Drawer>
</template>
