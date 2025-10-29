<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import { AnalyticServer } from '@/types';

type ServerImportHeader = 'uuid' | 'ip_address' | 'lisence' | 'is_alive' | 'is_active' | 'cpu_sn' | 'gpu_sn' | 'ram_sn' | 'ssd_sn' | 'mobo_sn' | 'nic_sn' | 'psu_sn' | 'lc_sn' | 'errors';

interface ServerImportBody extends AnalyticServer {
    errors: Record<ServerImportHeader, string[]>;
}

interface Props {
    servers: ServerImportBody[];
    header: ServerImportHeader[];
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
    const filteredServers = props.servers
        .filter((item) => !Object.keys(item.errors).length)
        .map((server) => ({
            ip_address: server.ip_address,
            lisence: server.lisence,
            is_alive: server.is_alive,
            is_active: server.is_active,
            cpu_id: server.cpu_id,
            gpu_id: server.gpu_id,
            ram_id: server.ram_id,
            ssd_id: server.ssd_id,
            mobo_id: server.mobo_id,
            nic_id: server.nic_id,
            psu_id: server.psu_id,
            lc_id: server.lc_id,
        }));

    const form = useForm({
        servers: filteredServers,
    });

    if (filteredServers.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak ada data valid',
            text: 'Semua data memiliki error, silakan perbaiki terlebih dahulu',
        });
        return;
    }

    const icon = filteredServers.length !== props.servers.length ? 'warning' : 'question';
    const title = filteredServers.length !== props.servers.length 
        ? 'Apakah anda yakin ingin melanjutkan proses?' 
        : 'Konfirmasi Import';

    Swal.fire({
        icon: icon,
        title: title,
        text: `Total ${filteredServers.length} data akan diimport`,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: `Batal`,
        customClass: {
            confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            form.post(route('server.import'), {
                preserveScroll: true,
                onSuccess() {
                    Swal.fire('Sukses', 'Sukses impor data server', 'success');
                },
                onError() {
                    Swal.fire('Error', 'Gagal impor data server', 'error');
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Verify Import Server" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Server" description="Please verify the import data" />

            <DataTable :value="props.servers" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
                <Column v-for="key in props.header" :key="key" :field="key" :header="toTitleCase(key, '_')" bodyClass="!p-0">
                    <template #body="{ data }">
                        <div class="min-h-12 px-4 py-3" :class="{ 'bg-red-500': data.errors[key]?.length }" v-tooltip="data.errors[key]?.[0]">
                            {{ data[key] }}
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-end gap-2">
                <Button label="Batal" severity="secondary" @click="router.visit(route('analytic-server.index'))" />
                <Button label="Lanjutkan" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>