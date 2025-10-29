<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { AnalyticServer, Hardware } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Swal from 'sweetalert2';
import { computed } from 'vue';

interface Props {
    value?: AnalyticServer;
    visible: boolean;
    hardware?: Hardware[];
}

interface ServerForm {
    _method: 'POST' | 'PUT';
    ip_address: string | null;
    lisence: string | null;
    is_active: boolean;
    is_alive: boolean;
    cpu_id: string | null;
    gpu_id: string | null;
    ram_id: string | null;
    ssd_id: string | null;
    mobo_id: string | null;
    nic_id: string | null;
    psu_id: string | null;
    lc_id: string | null;
    [key: string]: any;
}

const props = withDefaults(defineProps<Props>(), {
    hardware: () => [],
});

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

const formHeader = computed(() => {
    return props.value?.uuid ? 'Edit Server' : 'Add New Server';
});

const form: InertiaForm<ServerForm> = useForm({
    _method: 'POST',
    ip_address: null,
    lisence: null,
    is_active: false,
    is_alive: false,
    cpu_id: null,
    gpu_id: null,
    ram_id: null,
    ssd_id: null,
    mobo_id: null,
    nic_id: null,
    psu_id: null,
    lc_id: null,
});

const save = (): void => {
    const url = props.value?.uuid ? route('analytic-server.update', props.value.uuid) : route('analytic-server.store');

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
    form.reset();
};

const hardwareByCategory = computed(() => {
    const categories = {
        cpu: props.hardware.filter((h) => h.category === 'cpu').map((h) => ({ label: h.serial_number, value: h.uuid })),
        gpu: props.hardware.filter((h) => h.category === 'gpu').map((h) => ({ label: h.serial_number, value: h.uuid })),
        ram: props.hardware.filter((h) => h.category === 'ram').map((h) => ({ label: h.serial_number, value: h.uuid })),
        ssd: props.hardware.filter((h) => h.category === 'ssd').map((h) => ({ label: h.serial_number, value: h.uuid })),
        mobo: props.hardware.filter((h) => h.category === 'mobo').map((h) => ({ label: h.serial_number, value: h.uuid })),
        nic: props.hardware.filter((h) => h.category === 'nic').map((h) => ({ label: h.serial_number, value: h.uuid })),
        psu: props.hardware.filter((h) => h.category === 'psu').map((h) => ({ label: h.serial_number, value: h.uuid })),
        lc: props.hardware.filter((h) => h.category === 'lc').map((h) => ({ label: h.serial_number, value: h.uuid })),
    };

    if (props.value && props.value.uuid && props.visible) {
        const addCurrentlyAssigned = (category: keyof typeof categories, hardware: Hardware | null | undefined) => {
            if (hardware && !categories[category].some((h) => h.value === hardware.uuid)) {
                categories[category].push({
                    label: hardware.serial_number,
                    value: hardware.uuid,
                });
            }
        };

        addCurrentlyAssigned('cpu', props.value.cpu);
        addCurrentlyAssigned('gpu', props.value.gpu);
        addCurrentlyAssigned('ram', props.value.ram);
        addCurrentlyAssigned('ssd', props.value.ssd);
        addCurrentlyAssigned('mobo', props.value.motherboard);
        addCurrentlyAssigned('nic', props.value.nic);
        addCurrentlyAssigned('psu', props.value.psu);
        addCurrentlyAssigned('lc', props.value.lc);
    }

    return categories;
});

const show = (): void => {
    if (props.value?.uuid) {
        form.ip_address = props.value.ip_address ?? null;
        form.lisence = props.value.lisence ?? null;
        form.is_active = props.value.is_active ?? false;
        form.is_alive = props.value.is_alive ?? false;

        form.cpu_id = props.value.cpu?.uuid ?? null;
        form.gpu_id = props.value.gpu?.uuid ?? null;
        form.ram_id = props.value.ram?.uuid ?? null;
        form.ssd_id = props.value.ssd?.uuid ?? null;
        form.mobo_id = props.value.motherboard?.uuid ?? null;
        form.nic_id = props.value.nic?.uuid ?? null;
        form.psu_id = props.value.psu?.uuid ?? null;
        form.lc_id = props.value.lc?.uuid ?? null;
    } else {
        form.reset();
    }
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
    <Drawer v-model:visible="visible" class="!w-full md:!w-[60vw]" position="right" :header="formHeader" @show="show" @after-hide="hide">
        <form class="grid gap-6 md:grid-cols-2" @submit.prevent="save">
            <div class="flex flex-col gap-2">
                <Label for="ip_address">IP Address</Label>
                <InputText v-model="form.ip_address" id="ip_address" placeholder="Enter IP Address" />
                <InputError :message="form.errors.ip_address" v-if="form.errors.ip_address" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="lisence">Lisence</Label>
                <InputText v-model="form.lisence" id="lisence" placeholder="Enter Lisence" />
                <InputError :message="form.errors.lisence" v-if="form.errors.lisence" />
            </div>

            <!-- Hardware Selection -->
            <div class="flex flex-col gap-2">
                <Label for="cpu_id">CPU</Label>
                <Select
                    v-model="form.cpu_id"
                    :options="hardwareByCategory.cpu"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select CPU"
                    id="cpu_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.cpu_id" v-if="form.errors.cpu_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="gpu_id">GPU</Label>
                <Select
                    v-model="form.gpu_id"
                    :options="hardwareByCategory.gpu"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select GPU"
                    id="gpu_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.gpu_id" v-if="form.errors.gpu_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="ram_id">RAM</Label>
                <Select
                    v-model="form.ram_id"
                    :options="hardwareByCategory.ram"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select RAM"
                    id="ram_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.ram_id" v-if="form.errors.ram_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="ssd_id">SSD</Label>
                <Select
                    v-model="form.ssd_id"
                    :options="hardwareByCategory.ssd"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select SSD"
                    id="ssd_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.ssd_id" v-if="form.errors.ssd_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="mobo_id">Motherboard</Label>
                <Select
                    v-model="form.mobo_id"
                    :options="hardwareByCategory.mobo"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Motherboard"
                    id="mobo_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.mobo_id" v-if="form.errors.mobo_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="nic_id">Network Card</Label>
                <Select
                    v-model="form.nic_id"
                    :options="hardwareByCategory.nic"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Network Card"
                    id="nic_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.nic_id" v-if="form.errors.nic_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="psu_id">Power Supply</Label>
                <Select
                    v-model="form.psu_id"
                    :options="hardwareByCategory.psu"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Power Supply"
                    id="psu_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.psu_id" v-if="form.errors.psu_id" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="lc_id">Liquid Cooling</Label>
                <Select
                    v-model="form.lc_id"
                    :options="hardwareByCategory.lc"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Liquid Cooling"
                    id="lc_id"
                    class="w-full"
                    showClear
                />
                <InputError :message="form.errors.lc_id" v-if="form.errors.lc_id" />
            </div>

            <div class="col-span-2 divide-y">
                <Label class="col-span-2 flex cursor-pointer items-center justify-between rounded-lg p-3 hover:bg-surface-100" for="is_active">
                    <span>Active</span>
                    <ToggleSwitch input-id="is_active" v-model="form.is_active" />
                </Label>

                <Label class="col-span-2 flex cursor-pointer items-center justify-between rounded-lg p-3 hover:bg-surface-100" for="is_alive">
                    <span>Alive</span>
                    <ToggleSwitch input-id="is_alive" v-model="form.is_alive" />
                </Label>
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