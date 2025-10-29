<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import Swal from 'sweetalert2';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { PreconfigCamera, SiteForm, SiteFormProps } from './type';
import { LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';

import AdvanceFileUpload from '@/components/AdvanceFileUpload.vue';
import { LatLng, PointTuple } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { InputNumberInputEvent } from 'primevue/inputnumber';
import { SelectChangeEvent } from 'primevue/select';
import Icon from '@/components/Icon.vue';
import SiteTimeline from './partials/SiteTimeline.vue';

const props = withDefaults(defineProps<SiteFormProps>(), {
    pageTitle: 'Add Site',
    regencies: () => [],
    devices: () => []
});

const map = ref();
const markerPosition = ref<LatLng>();

const center = ref<PointTuple>([
    props.site?.latitude ?? -6.196272919783133, 
    props.site?.longitude ?? 106.82881328696864
]);

const siteIdInput = ref();
const tempSiteId = ref<string>('');

const form: InertiaForm<SiteForm> = useForm({
    _method: 'POST',
    site_category: props.site?.replacement_to ? 'Replacement' : 'New',
    site_id: props.site?.site_id ?? null,
    site_name: props.site?.site_name ?? null,
    latitude: props.site?.latitude ?? null,
    longitude: props.site?.longitude ?? null,
    site_status_id: null,
    regency_id: props.site?.regency_uuid ?? null,
    project_id: props.site?.project_uuid ?? null,
    replacement_to: props.site?.replacement_to ?? null,
    remark: '',
    attachments: [],
    cctv: [
        { cctv_name: null, device_id: null, mac_address: null, ip_dhcp: null },
        { cctv_name: null, device_id: null, mac_address: null, ip_dhcp: null },
    ]
});

const siteCategories = ref<string[]>(['New', 'Replacement']);
const zoom = ref(12);

const statusSeverities: Record<string, string> = {
    OPEN: 'primary',
    PROGRESS: 'warn',
    RELOCATION: 'warn',
    DISMANTLE: 'danger',
    COMPLETE: 'success'
}

const statusName = computed(() => {
    return props.status.find((item) => item.uuid === form.site_status_id)?.name ?? ''
})

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

const onMapClick = (event: any) => {
    markerPosition.value = event.latlng;
    center.value = [event.latlng.lat, event.latlng.lng];

    form.latitude = event.latlng.lat;
    form.longitude = event.latlng.lng;
};

const onChangeLatLng = () => {
    if (form.latitude && form.longitude) {
        markerPosition.value = { lat: form.latitude, lng: form.longitude } as LatLng;
        center.value = [form.latitude, form.longitude];
    }
};

const onDragEndMarker = (event: any) => {
    const marker = event.target;
    const latlng = marker.getLatLng();

    markerPosition.value = latlng;
    center.value = [latlng.lat, latlng.lng];

    form.latitude = latlng.lat;
    form.longitude = latlng.lng;
};

const onInputSiteId = (event: InputNumberInputEvent) => {
    const input = (event.originalEvent.target as HTMLInputElement);

    if (input.value.length > 6) {
        input.value = tempSiteId.value
    }
    else {
        tempSiteId.value = input.value
    }
}

const setCctvName = () => {
    if (form.site_id && form.site_name) {
        for (const [index, cctv] of form.cctv.entries()) {
            cctv.cctv_name = cctv.cctv_name
                ? cctv.cctv_name
                : form.site_id + '-' + form.site_name
                    .replaceAll(/[^\w\s-]/gi, '')
                    .replaceAll(' ', '-')
                    .toUpperCase() + '_CCTV-' + (index + 1).toString().padStart(2, '0')
        }
    }
}

const getDeviceOptions = (includeId: string) => {
    const selectedDevice = form.cctv.filter((item) => item.device_id !== includeId).map((item) => item.device_id)

    return props.devices.filter((item) => !selectedDevice.includes(item.uuid))
}

const onSelectMacAddress = (event: SelectChangeEvent, formIndex: number) => {
    const uuid = event.value;

    form.cctv[formIndex].ip_dhcp = props.devices.find((item) => item.uuid === uuid)?.ip_dhcp

    delete form.errors[`cctv.${formIndex}.device_id`];
}

const addCctv = () => {
    form.cctv.push({ cctv_name: null, mac_address: null, ip_dhcp: null, device_id: null })

    setCctvName();
}

const removeCctv = (formIndex: number) => {
    form.cctv = form.cctv.filter((item, index) => index !== formIndex)
}

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        { debounce: 500, maxWait: 1000 },
    );
}

onMounted(() => {
    nextTick(() => { });

    if (props.site?.cctv) {
        form.cctv = props.site.cctv;
    }

    if (props.site?.latitude && props.site.longitude) {
        markerPosition.value = new LatLng(props.site.latitude, props.site.longitude)
        
        setTimeout(() => zoom.value = 15, 100)
    }
});
</script>

<template>

    <Head :title="pageTitle" />

    <AppLayout>
        <form class="grid md:grid-cols-2 gap-6" @submit.prevent="save">
            <Card>
                <template #content>
                    <Heading title="Site Information" description="Please fill the required fields." />

                    <div class="flex flex-col gap-4">
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="project_id">Project ID</Label>
                                <Select v-model="form.project_id" label-id="project_id" option-value="uuid"
                                    option-label="name" :options="props.projects" placeholder="Please Select a Project">
                                </Select>
                                <InputError :message="form.errors.project_id" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label>Category</Label>
                                <SelectButton v-model="form.site_category" :options="siteCategories"
                                    :allow-empty="false" />
                                <InputError :message="form.errors.site_category" />
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2" v-if="form.site_category === 'Replacement'">
                                <Label for="replacement_to">Site ID Replacement</Label>
                                <Select v-model="form.replacement_to" label-id="replacement_to" option-value="uuid"
                                    :options="props.dismantle_sites" placeholder="Please Select a Site">
                                    <template #option="{ option }">
                                        {{ option.site_id + '-' + option.site_name }}
                                    </template>
                                </Select>
                                <InputError :message="form.errors.replacement_to" />
                            </div>

                            <div class="flex flex-col gap-2" v-if="form.site_category === 'Replacement'">
                                <Label for="replacement_to">Site Name Replacement</Label>
                                <InputText placeholder="Site Name Replacement" disabled />
                            </div>
                        </div>

                        <Divider class="md:col-span-3" type="dashed" />

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="regency">Regency</Label>
                                <Select v-model="form.regency_id" label-id="regency" option-value="uuid" filter
                                    option-label="name" :options="props.regencies"
                                    placeholder="Please Select a Regency">
                                </Select>
                                <InputError :message="form.errors.regency" />
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="site_id">Site ID</Label>
                                <InputNumber ref="siteIdInput" v-model="form.site_id" input-id="site_id"
                                    placeholder="Enter Site ID" inputmode="numeric" :use-grouping="false" :max="999999"
                                    @input="onInputSiteId" @value-change="setCctvName" />
                                <InputError :message="form.errors.site_id" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="site_name">Site Name</Label>
                                <InputText v-model="form.site_name" placeholder="Enter Site Name"
                                    @change="setCctvName" />
                                <InputError :message="form.errors.site_name" />
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="latitude">Latitude</Label>
                                <InputNumber v-model="form.latitude" placeholder="Enter Latitude" :minFractionDigits="2"
                                    :max-fraction-digits="14" @value-change="onChangeLatLng" />
                                <InputError :message="form.errors.latitude" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="longitude">Longitude</Label>
                                <InputNumber v-model="form.longitude" placeholder="Enter longitude"
                                    :minFractionDigits="2" :max-fraction-digits="14" @value-change="onChangeLatLng" />
                                <InputError :message="form.errors.longitude" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 md:col-span-3">
                            <div class="h-72 w-full">
                                <l-map ref="map" id="map" v-model:zoom="zoom" :center="center"
                                    :use-global-leaflet="false" @click="onMapClick">
                                    <l-tile-layer url="https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}"
                                        :subdomains="['mt0', 'mt1', 'mt2', 'mt3']" layer-type="base"
                                        name="OpenStreetMap">
                                    </l-tile-layer>

                                    <LMarker v-if="markerPosition" :lat-lng="markerPosition" :draggable="true"
                                        @drag-end="onDragEndMarker">
                                    </LMarker>
                                </l-map>
                            </div>

                            <span class="text-sm italic text-surface-600 dark:text-surface-300">
                                * Click on map to get latitude and longitude
                            </span>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #content>
                    <!-- <Heading title="Work Progress" description="Please fill the required fields." /> -->

                    <Tabs value="0">
                        <TabList>
                            <Tab value="0" as="div" class="flex items-center gap-2">
                                <Icon name="FileInput" class="size-5"/>
                                <span>New Progress</span>
                            </Tab>
                            <Tab value="1" as="div" class="flex items-center gap-2">
                                <Icon name="GitPullRequestDraft" class="size-5"/>
                                <span>Timeline</span>
                            </Tab>
                        </TabList>

                        <TabPanels>
                            <TabPanel value="0">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <Label>Status</Label>
                                        <Select v-model="form.site_status_id" :options="props.status" option-value="uuid"
                                            option-label="name" placeholder="Select a Status">
                                            <template #value="{ value, placeholder }">
                                                <Tag v-if="value" :severity="statusSeverities[statusName]" :value="statusName" />
                                                <span v-else>{{ placeholder }}</span>
                                            </template>
            
                                            <template #option="{ option }">
                                                <Tag :severity="statusSeverities[option.name]" :value="option.name" />
                                            </template>
                                        </Select>
                                    </div>
            
                                    <div class="col-span-2 flex flex-col gap-2">
                                        <Label for="longitude">Remark</Label>
                                        <Editor v-model="form.remark" editorStyle="height: 100px">
                                            <template v-slot:toolbar>
                                                <span class="ql-formats">
                                                    <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                                                    <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                                                    <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                                                </span>
            
                                                <span class="ql-formats">
                                                    <button class="ql-list" value="ordered" v-tooltip.bottom="'Ordered'"
                                                        type="button"></button>
                                                    <button class="ql-list" value="bullet" v-tooltip.bottom="'Bullet'"
                                                        type="button"></button>
                                                </span>
                                            </template>
                                        </Editor>
                                        <InputError :message="form.errors.remark" />
                                    </div>
            
                                    <div class="col-span-2 flex flex-col gap-2">
                                        <Label for="longitude">Attachments</Label>
                                        <AdvanceFileUpload v-model="form.attachments" multiple />
                                    </div>
                                </div>
                            </TabPanel>

                            <TabPanel value="1">
                                <SiteTimeline :value="props.histories" />
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </template>
            </Card>

            <Card class="md:col-span-2" :class="{'border border-red-500': !!form.errors.cctv}">
                <template #content>
                    <div class="flex justify-between">
                        <Heading title="Preconfig CCTV" description="Please fill the required fields." />
                        <span>
                            <Button label="Add CCTV" icon="pi pi-fw pi-plus" text @click="addCctv" />
                        </span>
                    </div>

                    <DataTable :value="form.cctv" striped-rows>
                        <Column field="cctv_name" header="CCTV Name">
                            <template #body="{ index }">
                                <InputText v-model="form.cctv[index].cctv_name" :id="`cctv_name_${index}`" fluid
                                    placeholder="Enter CCTV Name" />
                                <InputError :message="form.errors[`cctv.${index}.cctv_name`]" class="mt-1" />
                            </template>
                        </Column>

                        <Column field="mac_address" header="Mac Address">
                            <template #body="{ index, data }">
                                <Select v-model="form.cctv[index].device_id" :options="getDeviceOptions(data.device_id)"
                                    fluid filter :virtualScrollerOptions="{ itemSize: 38 }" option-value="uuid"
                                    option-label="mac_address" placeholder="Select Mac Address"
                                    @change="onSelectMacAddress($event, index)" />
                                <InputError :message="form.errors[`cctv.${index}.device_id`]" class="mt-1" />
                            </template>
                        </Column>

                        <Column field="ip_dhcp" header="IP DHCP"></Column>

                        <Column>
                            <template #body="{ index }">
                                <Button icon="pi pi-fw pi-trash" severity="danger" text v-tooltip.bottom="'Delete'"
                                    @click="removeCctv(index)" />
                            </template>
                        </Column>
                    </DataTable>

                    <InputError :message="form.errors.cctv" class="mt-6" />
                </template>
            </Card>

            <div class="md:col-span-2 flex justify-end gap-3">
                <Button label="Back" severity="secondary" @click="router.visit(route('site.index'))" />
                <Button label="Submit" type="submit" :loading="form.processing" :disabled="form.processing"
                    @click="save" />
            </div>
        </form>
    </AppLayout>
</template>
