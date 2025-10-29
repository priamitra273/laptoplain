<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import { Site } from '@/types';

type PreconfigImportHeader =
    | 'project_name'
    | 'regency_name'
    | 'site_id'
    | 'site_name'
    | 'latitude'
    | 'longitude'
    | 'cctv_name'
    | 'mac_address'
    | 'errors';

interface PreconfigImportBody extends Partial<Site> {
    project_name: string;
    regency_name: string;
    site_id: number;
    site_name: string;
    latitude: number;
    longitude: number;
    cctv_name: string;
    mac_address: string;

    project_id?: number;
    regency_id?: number;
    cctv_id?: number;
    site_uuid?: string;
    device_id?: number;
    device_uuid?: string;
    errors: Record<string, string[]>;
}

interface Props {
    preconfigs: PreconfigImportBody[];
    header: PreconfigImportHeader[];
    type: 'INSERT' | 'UPDATE';
}

const props = defineProps<Props>();

function toTitleCase(str: string, separator: string = ' '): string {
    return str
        .toLowerCase()
        .split(separator)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

const importData = () => {
    const filteredPreconfigs = props.preconfigs
        .filter((item) => !Object.keys(item.errors).length)
        .map((preconfig) => ({
            project_id: preconfig.project_id,
            regency_id: preconfig.regency_id,
            site_id: preconfig.site_id,
            site_name: preconfig.site_name,
            latitude: preconfig.latitude,
            longitude: preconfig.longitude,
            cctv_id: preconfig.cctv_id,
            cctv_name: preconfig.cctv_name,
            device_id: preconfig.device_id,
            site_uuid: preconfig.site_uuid,
            device_uuid: preconfig.device_uuid,
        }));

    const form = useForm({
        preconfigs: filteredPreconfigs,
    });

    if (filteredPreconfigs.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak ada data valid',
            text: 'Semua data memiliki error, silakan perbaiki terlebih dahulu',
        });
        return;
    }

    const icon = filteredPreconfigs.length !== props.preconfigs.length ? 'warning' : 'question';
    const title = filteredPreconfigs.length !== props.preconfigs.length
        ? 'Apakah anda yakin ingin melanjutkan proses?'
        : 'Konfirmasi Import';

    Swal.fire({
        icon: icon,
        title: title,
        text: `Total ${filteredPreconfigs.length} data preconfig akan diimport`,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: 'Batal',
        customClass: {
            confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            form.post(route('preconfig.import'), {
                preserveScroll: true,
                onSuccess() {
                    Swal.fire('Sukses', 'Sukses impor data preconfig', 'success');
                },
                onError() {
                    Swal.fire('Error', 'Gagal impor data preconfig', 'error');
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Verify Import Preconfig" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Preconfig" description="Please verify the preconfig import data" />

            <DataTable :value="props.preconfigs" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
                <Column
                    v-for="key in props.header"
                    :key="key"
                    :field="key"
                    :header="toTitleCase(key, '_')"
                    bodyClass="!p-0"
                >
                    <template #body="{ data }">
                        <div
                            class="min-h-12 px-4 py-3"
                            :class="{ 'bg-red-500': data.errors[key]?.length }"
                            v-tooltip="data.errors[key]?.[0]"
                        >
                            {{ data[key] }}
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-end gap-2">
                <Button label="Batal" severity="secondary" @click="router.visit(route('preconfig.index'))" />
                <Button label="Lanjutkan" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>