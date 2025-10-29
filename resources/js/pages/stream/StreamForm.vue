<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { watch } from 'vue';
import DeviceSection from './partials/DeviceSection.vue';
import EmbedSection from './partials/EmbedSection.vue';
import { Form, Stream } from './type';
import SiteMap from '@/components/SiteMap.vue';

interface Props {
    stream: Stream;
}

const props = defineProps<Props>();

const form: InertiaForm<Form> = useForm({
    cctv_name: props.stream.name ?? null,
    ip_flussonic: props.stream.ip_flussonic ?? null,
    ip_static: props.stream.ip_static ?? null,
    link_embed: props.stream.link_embed ?? null,
    link_embed_nonrelay: props.stream.link_embed_nonrelay ?? null,
    link_rtsp: props.stream.link_rtsp ?? null,
    status_stream_uuid: null,
});

const save = () => {
    form.put(route('stream.update', props.stream.uuid), {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
        },
    });
};

for (const key in form.data()) {
    watch(
        () => form[key],
        () => {
            delete form.errors[key];
        },
    );
}
</script>

<template>

    <Head title="Setup Streaming CCTV" />

    <AppLayout>
        <form class="grid grid-cols-2 gap-6" @submit.prevent="save">
            <DeviceSection :stream="stream" :form="form" />

            <SiteMap :site_id="stream.site_id" :site_name="stream.site_name" :project_name="stream.project_name"
                :latitude="stream.latitude" :longitude="stream.longitude" :regency_name="stream.regency_name"
                variant="popup" />

            <EmbedSection class="col-span-2" :form="form" />

            <div class="flex justify-end gap-3 md:col-span-2">
                <Button label="Back" severity="secondary" @click="router.visit(route('stream.index'))" />
                <Button label="Submit" type="submit" :loading="form.processing" :disabled="form.processing"
                    @click="save" />
            </div>
        </form>
    </AppLayout>
</template>
