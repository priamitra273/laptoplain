<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { DeviceExtended } from './type';
import DeviceTable from './DeviceTable.vue';

interface Props {
    devices?: DeviceExtended[]
}

const props = withDefaults(defineProps<Props>(), {
    devices: () => []
})

</script>

<template>
    <Head title="Device" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Device" description="Manage devices" />

            <DeviceTable :devices="props.devices"/>
        </div>
    </AppLayout>
</template>