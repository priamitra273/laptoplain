<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

type HardwareImportHeader = 'uuid' | 'po_number' | 'serial_number' | 'category' | 'brand' | 'model' | 'errors';

interface HardwareForm {
    po_number: string;
    serial_number: string;
    category: string;
    brand: string;
    model: string;
    remarks?: string | null;
}

interface HardwareImportBody extends HardwareForm {
    errors: Record<HardwareImportHeader, string[]>;
}

interface Props {
    hardwares: HardwareImportBody[];
    header: HardwareImportHeader[];
}

interface ImportForm {
    hardwares: HardwareForm[];
    [key: string]: any;
}

const props = defineProps<Props>();

const form = useForm<ImportForm>({
    hardwares: [],
});

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
    const filteredHardware = props.hardwares
        .filter((item) => !Object.keys(item.errors).length)
        .map((hardware) => ({
            po_number: hardware.po_number,
            serial_number: hardware.serial_number,
            category: hardware.category,
            brand: hardware.brand,
            model: hardware.model,
            remarks: hardware.remarks,
        }));

    form.hardwares = filteredHardware;

    if (filteredHardware.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak ada data valid',
            text: 'Semua data memiliki error, silakan perbaiki terlebih dahulu',
        });
        return;
    }

    const icon = filteredHardware.length !== props.hardwares.length ? 'warning' : 'question';
    const title = filteredHardware.length !== props.hardwares.length ? 'Apakah anda yakin ingin melanjutkan proses?' : 'Konfirmasi Import';

    Swal.fire({
        icon: icon,
        title: title,
        text: `Total ${filteredHardware.length} data akan diimport`,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: `Batal`,
        customClass: {
            confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            form.post(route('hardware.import'), {
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
    <Head title="Verify Import Hardware" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Hardware" description="Please verify the import data" />

            <DataTable :value="props.hardwares" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
                <Column v-for="key in props.header" :key="key" :field="key" :header="toTitleCase(key, '_')" bodyClass="!p-0">
                    <template #body="{ data }">
                        <div class="min-h-12 px-4 py-3" :class="{ 'bg-red-500': data.errors[key]?.length }" v-tooltip="data.errors[key]?.[0]">
                            {{ data[key] }}
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-end gap-2">
                <Button label="Batal" severity="secondary" @click="router.visit(route('hardware.index'))" />
                <Button label="Lanjutkan" :disabled="form.processing" :loading="form.processing" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>
