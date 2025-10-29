<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

import { Site } from '@/types';

type SiteImportHeader = 'site_category' | 'replacement_to' | 'regency_name' | 'project_name' | 'site_id' | 'site_name' | 'latitude' | 'longitude' | 'site_status_name' | 'errors';

interface SiteImportBody extends Site {
    site_category: string;
    regency_name: string;
    project_name: string;
    site_status_name: string;
    regency_id: number;
    project_id: number;
    site_status_id: number;
    errors: Record<SiteImportHeader, string[]>;
}

interface Props {
    sites: SiteImportBody[];
    header: SiteImportHeader[];
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
    const filteredSites = props.sites
        .filter((item) => !Object.keys(item.errors).length)
        .map((site) => ({
            site_category: site.site_category,
            replacement_to: site.replacement_to,
            regency_id: site.regency_id,
            project_id: site.project_id,
            site_id: site.site_id,
            site_name: site.site_name,
            latitude: site.latitude,
            longitude: site.longitude,
            site_status_id: site.site_status_id,
        }));

    const form = useForm({
        sites: filteredSites,
    });

    if (filteredSites.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak ada data valid',
            text: 'Semua data memiliki error, silakan perbaiki terlebih dahulu',
        });
        return;
    }

    const icon = filteredSites.length !== props.sites.length ? 'warning' : 'question';
    const title = filteredSites.length !== props.sites.length 
        ? 'Apakah anda yakin ingin melanjutkan proses?' 
        : 'Konfirmasi Import';

    Swal.fire({
        icon: icon,
        title: title,
        text: `Total ${filteredSites.length} data akan diimport`,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: `Batal`,
        customClass: {
            confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            form.post(route('site.import'), {
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
    <Head title="Verify Import Site" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Site" description="Please verify the import data" />

            <DataTable :value="props.sites" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
                <Column v-for="key in props.header" :key="key" :field="key" :header="toTitleCase(key, '_')" bodyClass="!p-0">
                    <template #body="{ data }">
                        <div class="min-h-12 px-4 py-3" :class="{ 'bg-red-500': data.errors[key]?.length }" v-tooltip="data.errors[key]?.[0]">
                            {{ data[key] }}
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-end gap-2">
                <Button label="Batal" severity="secondary" @click="router.visit(route('site.index'))" />
                <Button label="Lanjutkan" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>