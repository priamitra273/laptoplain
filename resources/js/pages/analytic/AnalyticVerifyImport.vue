<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

type AnalyticImportHeader =
    | 'cctv_name'
    | 'link_embed_bb'
    | 'department_name'
    | 'categories_name'
    | 'server'
    | 'polygon'
    | 'threshold'
    | 'errors';

interface AnalyticImportBody {
    cctv_name: string;
    link_embed_bb?: string;
    department_name: string;
    categories_name: string;
    server: string;
    polygon?: number[][];
    threshold?: number[];

    cctv_id?: number;
    errors: Record<string, string[]>;
}

interface Props {
    analytics: AnalyticImportBody[];
    header: AnalyticImportHeader[];
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
    const filteredAnalytics = props.analytics
        .filter((item) => !Object.keys(item.errors).length)
        .map((analytic) => ({
            cctv_id: analytic.cctv_id,
            cctv_name: analytic.cctv_name,
            link_embed_bb: analytic.link_embed_bb,
            department_name: analytic.department_name,
            categories_name: analytic.categories_name,
            server: analytic.server,
            polygon: analytic.polygon,
            threshold: analytic.threshold,
        }));

    const form = useForm({
        analytics: filteredAnalytics,
    });

    if (filteredAnalytics.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak ada data valid',
            text: 'Semua data memiliki error, silakan perbaiki terlebih dahulu',
        });
        return;
    }

    const icon = filteredAnalytics.length !== props.analytics.length ? 'warning' : 'question';
    const title = filteredAnalytics.length !== props.analytics.length
        ? 'Apakah anda yakin ingin melanjutkan proses?'
        : 'Konfirmasi Import';

    Swal.fire({
        icon: icon,
        title: title,
        text: `Total ${filteredAnalytics.length} data analytic akan diimport`,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: 'Batal',
        customClass: {
            confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            form.post(route('analytic.import'), {
                preserveScroll: true,
                onSuccess() {
                    Swal.fire('Sukses', 'Sukses impor data analytic', 'success');
                },
                onError() {
                    Swal.fire('Error', 'Gagal impor data analytic', 'error');
                },
            });
        }
    });
};

const formatArrayValue = (value: any) => {
    if (Array.isArray(value)) {
        return JSON.stringify(value);
    }
    return value;
};
</script>

<template>
    <Head title="Verify Import Analytic" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Analytic" description="Please verify the analytic import data" />

            <DataTable :value="props.analytics" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
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
                            {{ formatArrayValue(data[key]) }}
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-end gap-2">
                <Button label="Batal" severity="secondary" @click="router.visit(route('analytic.index'))" />
                <Button label="Lanjutkan" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>