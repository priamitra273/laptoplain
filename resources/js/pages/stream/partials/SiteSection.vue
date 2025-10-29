<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { LatLng, PointTuple } from 'leaflet';

import 'leaflet/dist/leaflet.css';

interface Props {
    site_id: number;
    site_name: string;
    latitude: number;
    longitude: number;
    project_name: string;
    regency_name: string;
}

const props = defineProps<Props>()

const map = ref()
const siteMarker = ref();
const popup = ref()

const center = ref<PointTuple>([props.latitude, props.longitude]);
const marker = ref<LatLng>()
const zoom = ref(15);

onMounted(() => {
    marker.value = new LatLng(props.latitude, props.longitude)

    setTimeout(() => {
        siteMarker.value.leafletObject.openPopup()
    }, 200);
})
</script>

<template>
    <Card class="overflow-hidden" :pt="{
        body: {
            class: '!p-0'
        }
    }">
        <template #content>
            <div class="h-96 w-full">
                <l-map ref="map" id="map" v-model:zoom="zoom" :center="center" :use-global-leaflet="false"
                    :options="{ attributionControl: false }">
                    <l-tile-layer url="https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}"
                        :subdomains="['mt0', 'mt1', 'mt2', 'mt3']" layer-type="base" name="OpenStreetMap">
                    </l-tile-layer>

                    <LMarker ref="siteMarker" :lat-lng="center">
                        <LPopup ref="popup" :options="{permanent: true, interactive: true, closeButton: false, autoClose: false, maxWidth: 300, minWidth: 300}">
                            <div class="">        
                                <div class="grid gap-1 mt-3">
                                    <div class="grid grid-cols-4 gap-x-6 gap-y-3">
                                        <span>Site ID</span>
                                        <span class="font-bold">{{ props.site_id }}</span>
                                    </div>
        
                                    <div class="grid grid-cols-4 gap-6">
                                        <span>Site Name</span>
                                        <span class="font-bold col-span-3">{{ props.site_name }}</span>
                                    </div>
        
                                    <div class="grid grid-cols-4 gap-6">
                                        <span>Coordinate</span>
                                        <span class="font-bold col-span-3">{{ props.latitude }}, {{ props.longitude }}</span>
                                    </div>
                                </div>

                                <div class="mt-3 flex gap-1">
                                    <Tag :value="props.project_name" severity="secondary" />
                                    <Tag :value="props.regency_name" severity="secondary" />
                                </div>
                            </div>
                        </LPopup>
                    </LMarker>
                </l-map>
            </div>
        </template>
    </Card>
</template>