<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Hardware, HardwareBrand, HardwareComponent, HardwareModel } from '@/types';
import { Head } from '@inertiajs/vue3';
import HardwareTable from './HardwareTable.vue';

interface Props {
    hardware?: Hardware[];
    components?: HardwareComponent[];
    brands: HardwareBrand[];
    models?: HardwareModel[];
}

const props = withDefaults(defineProps<Props>(), {
    hardware: () => [],
});
</script>

<template>
    <Head title="Analytic Server" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Hardware" description="Manage master data hardware" />

            <HardwareTable :hardware="props.hardware" :components="props.components" :brands="props.brands" :models="props.models" />
        </div>
    </AppLayout>
</template>
