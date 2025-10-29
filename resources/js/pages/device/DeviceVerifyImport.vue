<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import { Device } from '@/types';

type DeviceImportHeader = 'uuid' | 'mac_address' | 'ip_dhcp' | 'ip_static' | 'errors';

interface DeviceImportBody extends Device {
    errors: Record<DeviceImportHeader, string[]>;
}

interface Props {
    devices: DeviceImportBody[];
    header: DeviceImportHeader[];
}

const props = defineProps<Props>();

function toTitleCase(str: string, separator: string = ' '): string {
    return str
        .toLowerCase()
        .split(separator)
        .map((word) => {
            return word.charAt(0).toUpperCase() + word.slice(1);
        })
        .join(' ');
}

const importData = () => {
    const filteredDevices = props.devices
        .filter((item) => !Object.keys(item.errors).length)
        .map((device) => ({
            mac_address: device.mac_address,
            ip_dhcp: device.ip_dhcp,
            ip_static: device.ip_static
        }));

    const form = useForm({
        devices: filteredDevices,
    });

    if (filteredDevices.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak ada data valid',
            text: 'Semua data memiliki error, silakan perbaiki terlebih dahulu',
        });
        return;
    }

    const icon = filteredDevices.length !== props.devices.length ? 'warning' : 'question';
    const title = filteredDevices.length !== props.devices.length 
        ? 'Apakah anda yakin ingin melanjutkan proses?' 
        : 'Konfirmasi Import';

    Swal.fire({
        icon: icon,
        title: title,
        text: `Total ${filteredDevices.length} data akan diimport`,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: `Batal`,
        customClass: {
            confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            form.post(route('device.import'), {
                preserveScroll: true,
                onSuccess() {
                    Swal.fire('Sukses', 'Sukses impor data', 'success');
                },
                onError() {
                    Swal.fire('Error', 'Gagal impor data', 'error');
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Verify Import Device" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Device" description="Please verify the import data" />

            <DataTable :value="props.devices" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
                <Column v-for="key in props.header" :key="key" :field="key" :header="toTitleCase(key, '_')" bodyClass="!p-0">
                    <template #body="{ data }">
                        <div class="min-h-12 px-4 py-3" :class="{ 'bg-red-500': data.errors[key]?.length }" v-tooltip="data.errors[key]?.[0]">
                            {{ data[key] }}
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-end gap-2">
                <Button label="Batal" severity="secondary" @click="router.visit(route('device.index'))" />
                <Button label="Lanjutkan" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>