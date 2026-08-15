<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Device } from '@/types';
import { InertiaForm } from '@inertiajs/vue3';
import { SelectChangeEvent } from 'primevue/select';
import { onMounted } from 'vue';
import { Preconfig, PreconfigCamera } from '../type';

interface Form {
    cctv: PreconfigCamera[];
    [key: string]: any;
}

interface Props {
    form: InertiaForm<Form>;
    devices: Device[];
    preconfig: Preconfig;
}

const props = defineProps<Props>();

const setCctvName = () => {
    for (const [index, cctv] of props.form.cctv.entries()) {
        cctv.cctv_name = cctv.cctv_name
            ? cctv.cctv_name
            : props.preconfig.site_id +
              '-' +
              props.preconfig.site_name
                  .replaceAll(/[^\w\s-]/gi, '')
                  .replaceAll(' ', '-')
                  .toUpperCase() +
              '_CCTV-' +
              (index + 1).toString().padStart(2, '0');
    }
};

const addCctv = () => {
    props.form.cctv.push({ cctv_name: null, mac_address: null, ip_dhcp: null, device_id: null });

    setCctvName();
};

const removeCctv = (formIndex: number) => {
    props.form.cctv = props.form.cctv.filter((item, index) => index !== formIndex);
};

const getDeviceOptions = (includeId: string) => {
    const selectedDevice = props.form.cctv.filter((item) => item.device_id !== includeId).map((item) => item.device_id);

    return props.devices.filter((item) => !selectedDevice.includes(item.uuid));
};

const onSelectMacAddress = (event: SelectChangeEvent, formIndex: number) => {
    const uuid = event.value;

    props.form.cctv[formIndex].ip_dhcp = props.devices.find((item) => item.uuid === uuid)?.ip_dhcp;

    delete props.form.errors[`cctv.${formIndex}.device_id`];
};

if (props.preconfig.cctv && props.preconfig.cctv.length) {
    props.form.cctv = props.preconfig.cctv;
}

onMounted(() => {
    setCctvName();
});
</script>

<template>
    <Card :class="{ 'border border-red-500': !!form.errors.cctv }">
        <template #content>
            <div class="grid max-w-sm md:max-w-full">
                <div class="flex justify-between">
                    <Heading title="Preconfig CCTV" description="Please fill the required fields." />
                    <span>
                        <Button label="Add CCTV" icon="pi pi-fw pi-plus" text @click="addCctv" />
                    </span>
                </div>

                <div class="w-full overflow-auto">
                    <DataTable :value="form.cctv" striped-rows tableStyle="min-width: 50rem">
                        <Column field="cctv_name" header="CCTV Name">
                            <template #body="{ index }">
                                <InputText v-model="form.cctv[index].cctv_name" :id="`cctv_name_${index}`" fluid placeholder="Enter CCTV Name" />
                                <InputError :message="form.errors[`cctv.${index}.cctv_name`]" class="mt-1" />
                            </template>
                        </Column>
        
                        <Column field="mac_address" header="Mac Address">
                            <template #body="{ index, data }">
                                <Select
                                    v-model="form.cctv[index].device_id"
                                    :options="getDeviceOptions(data.device_id)"
                                    fluid
                                    filter
                                    :virtualScrollerOptions="{ itemSize: 38 }"
                                    option-value="uuid"
                                    option-label="mac_address"
                                    placeholder="Select Mac Address"
                                    @change="onSelectMacAddress($event, index)"
                                />
                                <InputError :message="form.errors[`cctv.${index}.device_id`]" class="mt-1" />
                            </template>
                        </Column>
        
                        <Column field="ip_dhcp" header="IP DHCP"></Column>
        
                        <Column>
                            <template #body="{ index }">
                                <Button icon="pi pi-fw pi-trash" severity="danger" text v-tooltip.bottom="'Delete'" @click="removeCctv(index)" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
    
                <InputError :message="form.errors.cctv" class="mt-6" />
            </div>
        </template>
    </Card>
</template>
