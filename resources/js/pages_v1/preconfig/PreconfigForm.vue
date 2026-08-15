<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import { FormProps, PreconfigCamera } from './type';
import Heading from '@/components/Heading.vue';
import { computed } from 'vue';
import SiteSection from './partials/SiteSection.vue';
import CameraSection from './partials/CameraSection.vue';
import Swal from 'sweetalert2';

interface Form {
    cctv: PreconfigCamera[];
    [key: string]: any;
}

const props = withDefaults(defineProps<FormProps>(), {
    devices: () => [],
});

const description = computed<string>(() => 'Site Name: ' + props.preconfig?.site_name);

const form: InertiaForm<Form> = useForm({
    cctv: [
        { cctv_name: null, device_id: null, mac_address: null, ip_dhcp: null },
        { cctv_name: null, device_id: null, mac_address: null, ip_dhcp: null },
    ]
})

function save() {
    form.put(route('preconfig.update', props.preconfig.uuid), {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
        }
    })
}
</script>

<template>
    <Head title="Site Preconfig" />

    <AppLayout>
        <form class="grid gap-6" @submit.prevent="save">
            <Heading title="Setup Preconfig" :description="description" />

            <SiteSection :preconfig="preconfig" />

            <CameraSection :form="form" :preconfig="preconfig" :devices="devices" />

            <div class="flex justify-end gap-3">
                <Button label="Back" severity="secondary" @click="router.visit(route('preconfig.index'))" />
                <Button label="Submit" type="submit" :loading="form.processing" :disabled="form.processing"
                    @click="save" />
            </div>
        </form>
    </AppLayout>
</template>
