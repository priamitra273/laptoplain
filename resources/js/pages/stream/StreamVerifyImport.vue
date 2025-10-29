<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

type StreamImportHeader =
    | 'cctv_name'
    | 'ip_static'
    | 'ip_flussonic'
    | 'link_embed'
    | 'link_embed_nonrelay'
    | 'link_rtsp'
    | 'errors';

interface StreamImportBody {
    cctv_name: string;
    ip_static?: string;
    ip_flussonic?: string;
    link_embed?: string;
    link_embed_nonrelay?: string;
    link_rtsp: string;

    cctv_id?: number;
    errors: Record<string, string[]>;
}

interface Props {
    streams: StreamImportBody[];
    header: StreamImportHeader[];
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
    const filteredStreams = props.streams
        .filter((item) => !Object.keys(item.errors).length)
        .map((stream) => ({
            cctv_id: stream.cctv_id,
            cctv_name: stream.cctv_name,
            ip_static: stream.ip_static,
            ip_flussonic: stream.ip_flussonic,
            link_embed: stream.link_embed,
            link_embed_nonrelay: stream.link_embed_nonrelay,
            link_rtsp: stream.link_rtsp,
        }));

    const form = useForm({
        streams: filteredStreams,
    });

    if (filteredStreams.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak ada data valid',
            text: 'Semua data memiliki error, silakan perbaiki terlebih dahulu',
        });
        return;
    }

    const icon = filteredStreams.length !== props.streams.length ? 'warning' : 'question';
    const title = filteredStreams.length !== props.streams.length
        ? 'Apakah anda yakin ingin melanjutkan proses?'
        : 'Konfirmasi Import';

    Swal.fire({
        icon: icon,
        title: title,
        text: `Total ${filteredStreams.length} data stream akan diimport`,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: 'Batal',
        customClass: {
            confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            form.post(route('stream.import'), {
                preserveScroll: true,
                onSuccess() {
                    Swal.fire('Sukses', 'Sukses impor data stream', 'success');
                },
                onError() {
                    Swal.fire('Error', 'Gagal impor data stream', 'error');
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Verify Import Stream" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Stream" description="Please verify the stream import data" />

            <DataTable :value="props.streams" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
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
                <Button label="Batal" severity="secondary" @click="router.visit(route('stream.index'))" />
                <Button label="Lanjutkan" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>