<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import { LatLng, PointTuple } from 'leaflet';
import Swal from 'sweetalert2';
import { ref, watch } from 'vue';
import InputProject from './partials/InputProject.vue';
import InputSiteId from './partials/InputSiteId.vue';
import InputSiteReplacement from './partials/InputSiteReplacement.vue';
import SiteMap from './partials/SiteMap.vue';
import { SiteFormNew, SiteFormProps } from './type';

const props = withDefaults(defineProps<SiteFormProps>(), {
    pageTitle: 'Add Site',
    regencies: () => [],
    devices: () => [],
});

const form: InertiaForm<SiteFormNew> = useForm({
    _method: 'POST',
    project_id: props.site?.project_uuid ?? null,
    regency_id: props.site?.regency_uuid ?? null,
    site_category: props.site?.replacement_to ? 'Replacement' : 'New',
    site_id: props.site?.site_id ?? null,
    site_name: props.site?.site_name ?? null,
    latitude: props.site?.latitude ?? null,
    longitude: props.site?.longitude ?? null,
    replacement_to: props.site?.replacement_to ?? null,
});

const markerPosition = ref<LatLng>();
const center = ref<PointTuple>([props.site?.latitude ?? -6.196272919783133, props.site?.longitude ?? 106.82881328696864]);

const save = () => {
    const url = props.site?.uuid ? route('site.update', props.site.uuid) : route('site.store');

    if (props.site?.uuid) form._method = 'PUT';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
        },
    });
};

const onChangeLatLng = () => {
    if (form.latitude && form.longitude) {
        markerPosition.value = { lat: form.latitude, lng: form.longitude } as LatLng;
        center.value = [form.latitude, form.longitude];
    }
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
    <Head :title="pageTitle" />

    <AppLayout>
        <form class="grid gap-6 md:grid-cols-2" @submit.prevent="save">
            <div>
                <Card>
                    <template #title>
                        <Heading title="Site Information" description="Please fill the required fields." />
                    </template>

                    <template #content>
                        <div class="flex flex-col gap-4">
                            <InputProject :form="form" :projects="props.projects" />
                            <InputSiteReplacement :form="form" :dismantle-sites="props.dismantle_sites" />
                        </div>
                    </template>
                </Card>
            </div>

            <Card>
                <template #title>
                    <Heading title="Site Location" description="Please fill the required fields." />
                </template>

                <template #content>
                    <div class="flex flex-col gap-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <Label for="regency">Regency</Label>

                                <Select
                                    v-model="form.regency_id"
                                    label-id="regency"
                                    option-value="uuid"
                                    filter
                                    option-label="name"
                                    :options="props.regencies"
                                    placeholder="Please Select a Regency"
                                />

                                <InputError :message="form.errors.regency_id" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <InputSiteId :form="form" />

                            <div class="flex flex-col gap-2">
                                <Label for="site_name">Site Name</Label>
                                <InputText v-model="form.site_name" placeholder="Enter Site Name" />
                                <InputError :message="form.errors.site_name" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <Label for="latitude">Latitude</Label>
                                <InputNumber
                                    v-model="form.latitude"
                                    placeholder="Enter Latitude"
                                    :minFractionDigits="2"
                                    :max-fraction-digits="14"
                                    @value-change="onChangeLatLng"
                                />
                                <InputError :message="form.errors.latitude" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="longitude">Longitude</Label>
                                <InputNumber
                                    v-model="form.longitude"
                                    placeholder="Enter longitude"
                                    :minFractionDigits="2"
                                    :max-fraction-digits="14"
                                    @value-change="onChangeLatLng"
                                />
                                <InputError :message="form.errors.longitude" />
                            </div>
                        </div>

                        <SiteMap :form="form" :site="props.site" v-model:center="center" v-model:marker-position="markerPosition" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-3 md:col-span-2">
                <Button label="Back" severity="secondary" @click="router.visit(route('site.index'))" />
                <Button label="Submit" type="submit" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </form>
    </AppLayout>
</template>
