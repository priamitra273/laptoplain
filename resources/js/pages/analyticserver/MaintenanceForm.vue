<script setup lang="ts">
import HardwareStatusTag from '@/components/HardwareStatusTag.vue';
import InputError from '@/components/InputError.vue';
import { AnalyticServer, Hardware, HardwareComponent, HardwareStatus } from '@/types';
import { router, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { DataTableRowSelectEvent, DataTableRowUnselectEvent } from 'primevue/datatable';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';
import { HardwareComponentCodeEnum, MaintenanceForm, UnsuedHardware } from './type';

export interface Props {
    value?: AnalyticServer;
    visible: boolean;
    hardware?: Hardware[];
    components: HardwareComponent[];
    hardwareStatus: HardwareStatus[];
}

const props = withDefaults(defineProps<Props>(), {
    hardware: () => [],
});

const emits = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>();

const hardwareKeyMap: Record<string, HardwareComponentCodeEnum | 'motherboard'> = {
    cpu: 'cpu',
    gpu: 'gpu',
    ram: 'ram',
    ssd: 'ssd',
    mobo: 'motherboard',
    nic: 'nic',
    psu: 'psu',
    lc: 'lc',
};

const visible = computed<boolean>({
    get() {
        return props.visible;
    },
    set(newValue) {
        emits('update:visible', newValue);
    },
});

const form = useForm<MaintenanceForm>({
    _method: 'PUT',
    replacements: {
        cpu: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        gpu: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        ram: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        ssd: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        mobo: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        nic: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        psu: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        lc: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
    },
});

const replacements = ref<HardwareComponent[]>([]);
const selectedReplacements = computed<HardwareComponentCodeEnum[]>(() => {
    return replacements.value.map((item) => item.code.toLowerCase()) as HardwareComponentCodeEnum[];
});

const unusedHardwares = ref<UnsuedHardware>({});

const getSerialNumber = (value: HardwareComponent): string | null | undefined => {
    return props.value?.[hardwareKeyMap[value.code.toLowerCase()]]?.serial_number;
};

const save = () => {
    form.post(route('server.maintenance', props.value?.uuid), {
        preserveScroll: true,
        onSuccess(e) {
            Swal.fire('Success', 'Successfully update data', 'success');
            visible.value = false;

            router.reload();
        },
        onError(e) {
            console.log(e);
        },
    });
};

const resetForm = () => {
    form.errors = {};
    form.replacements = {
        cpu: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        gpu: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        ram: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        ssd: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        mobo: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        nic: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        psu: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
        lc: {
            current_uuid: null,
            status_uuid: null,
            replacement_uuid: null,
        },
    };

    replacements.value = [];
};

const onRowSelect = (event: DataTableRowSelectEvent) => {
    let key = event.data.code.toLowerCase() as HardwareComponentCodeEnum;

    form.replacements[key].current_uuid = key == 'mobo' ? (props.value?.motherboard?.uuid ?? null) : (props.value?.[key]?.uuid ?? null);
};

const onRowUnselect = (event: DataTableRowUnselectEvent) => {
    const key = event.data.code.toLowerCase() as HardwareComponentCodeEnum;

    form.replacements[key] = {
        current_uuid: null,
        status_uuid: null,
        replacement_uuid: null,
    };

    for (const index in form.errors) {
        if (index.includes(key)) delete form.errors[index];
    }
};

const onChangeReason = (component: HardwareComponent) => {
    delete form.errors[`replacements.${component.code.toLowerCase()}.status_uuid`];
};

const onChangeReplacement = (component: HardwareComponent) => {
    delete form.errors[`replacements.${component.code.toLowerCase()}.replacement_uuid`];
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

onMounted(() => {
    for (const component of props.components) {
        unusedHardwares.value[component.code.toLowerCase() as HardwareComponentCodeEnum] =
            props.hardware.filter((item) => item.category === component.code) ?? [];
    }
});
</script>

<template>
    <Drawer v-model:visible="visible" header="Maintenance Form" class="!w-full md:!w-[60vw]" position="right" @after-hide="resetForm">
        <form class="grid gap-8" @submit.prevent="save">
            <div class="flex flex-col gap-2">
                <div class="grid grid-cols-3 gap-6">
                    <span class="font-bold">IP Address</span>
                    <span class="">{{ props.value?.ip_address }}</span>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <span class="font-bold">Lisence</span>
                    <span class="">{{ props.value?.lisence }}</span>
                </div>
            </div>

            <DataTable
                v-model:selection="replacements"
                :value="props.components"
                data-key="uuid"
                size="small"
                row-hover
                @row-select="onRowSelect"
                @row-unselect="onRowUnselect"
                @row-unselect-all="resetForm"
            >
                <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                <Column field="code" header="Code"></Column>
                <Column header="Current Hardware">
                    <template #body="{ data }">
                        {{ getSerialNumber(data) }}
                    </template>
                </Column>

                <Column header="Reason" class="w-20">
                    <template #body="{ data }">
                        <Select
                            v-model="form.replacements[data.code.toLowerCase() as HardwareComponentCodeEnum].status_uuid"
                            :options="props.hardwareStatus"
                            option-label="name"
                            option-value="uuid"
                            fluid
                            :disabled="!selectedReplacements.includes(data.code.toLowerCase())"
                            placeholder="Select a reason"
                            @change="onChangeReason(data)"
                        ></Select>
                        <InputError :message="form.errors[`replacements.${data.code.toLowerCase()}.status_uuid`]" />
                    </template>
                </Column>

                <Column header="Replacement" class="w-36">
                    <template #body="{ data }">
                        <Select
                            v-model="form.replacements[data.code.toLowerCase() as HardwareComponentCodeEnum].replacement_uuid"
                            :options="unusedHardwares[data.code.toLowerCase() as HardwareComponentCodeEnum]"
                            option-value="uuid"
                            option-label="serial_number"
                            fluid
                            :disabled="!selectedReplacements.includes(data.code.toLowerCase())"
                            placeholder="Select a new replacement"
                            @change="onChangeReplacement(data)"
                        >
                            <template #option="{ option }">
                                <div class="flex items-center gap-2">
                                    <HardwareStatusTag :value="option.status" />
                                    <span>
                                        {{ option.brand ? `${option.brand.name} - ${option.model} - ${option.serial_number}` : option.serial_number }}
                                    </span>
                                </div>
                            </template>
                        </Select>

                        <InputError :message="form.errors[`replacements.${data.code.toLowerCase()}.replacement_uuid`]" />
                    </template>
                </Column>
            </DataTable>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <Button label="Cancel" outlined severity="secondary" @click="visible = false" />
                <Button label="Submit" @click="save" :loading="form.processing" :disabled="form.processing || !selectedReplacements.length" />
            </div>
        </template>
    </Drawer>
</template>
