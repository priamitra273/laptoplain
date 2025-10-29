<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { AnalyticServer, Hardware, HardwareComponent, HardwareStatus } from '@/types';
import { Head } from '@inertiajs/vue3';
import AnalyticServerTable from './AnalyticServerTable.vue';

interface Props {
    servers?: AnalyticServer[];
    hardware?: Hardware[];
    components: HardwareComponent[];
    hardware_status: HardwareStatus[];
}

const props = withDefaults(defineProps<Props>(), {
    servers: () => [],
    hardware: () => [],
});
</script>

<template>
    <Head title="Analytic Server" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Analytic Server" description="Manage master data analytic server" />

            <!-- Pass both servers AND hardware to the table component -->
            <AnalyticServerTable :servers="props.servers" :hardware="props.hardware" :hardware-status="props.hardware_status" :components />
        </div>
    </AppLayout>
</template>
