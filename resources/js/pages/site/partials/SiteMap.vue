<script setup lang="ts">
import { Site } from '@/types';
import { LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import { LatLng, PointTuple } from 'leaflet';
import { computed, ref } from 'vue';
import { SiteFormNew } from '../type';
import { InertiaForm } from '@inertiajs/vue3';
import { useResizeObserver } from '@vueuse/core';

interface Props {
    site?: Site,
    markerPosition?: LatLng|null,
    center: PointTuple,
    form: InertiaForm<SiteFormNew>
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (event: 'update:markerPosition', value: LatLng): void;
    (event: 'update:center', value: PointTuple|null): void;
}>()

const map = ref();
const zoom = ref(12);

const markerPosition = computed({
    get: () => props.markerPosition,
    set: (value: LatLng) => emits('update:markerPosition', value)
});

const center = computed({
    get: () => props.center,
    set: (value: PointTuple) => emits('update:center', value)
});

const onMapClick = (event: any) => {
    markerPosition.value = event.latlng;
    center.value = [event.latlng.lat, event.latlng.lng];

    props.form.latitude = event.latlng.lat;
    props.form.longitude = event.latlng.lng;
};

const onDragEndMarker = (event: any) => {
    const marker = event.target;
    const latlng = marker.getLatLng();

    markerPosition.value = latlng;
    center.value = [latlng.lat, latlng.lng];

    props.form.latitude = latlng.lat;
    props.form.longitude = latlng.lng;
};

if (props.site?.latitude && props.site.longitude) {
    markerPosition.value = new LatLng(props.site.latitude, props.site.longitude)
    zoom.value = 15;
}

useResizeObserver(map, () => {
    map.value?.leafletObject?.invalidateSize()
})
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="h-72 w-full">
            <l-map ref="map" id="map" v-model:zoom="zoom" :center="center" :use-global-leaflet="false"
                @click="onMapClick">
                <l-tile-layer url="https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}"
                    :subdomains="['mt0', 'mt1', 'mt2', 'mt3']" layer-type="base" name="OpenStreetMap">
                </l-tile-layer>

                <LMarker v-if="markerPosition" :lat-lng="markerPosition" :draggable="true" @drag-end="onDragEndMarker">
                </LMarker>
            </l-map>
        </div>

        <span class="text-sm italic text-surface-600 dark:text-surface-300">
            * Click on map to get latitude and longitude
        </span>
    </div>
</template>