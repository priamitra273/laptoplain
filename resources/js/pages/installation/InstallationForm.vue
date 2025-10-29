<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue'
import { Head } from '@inertiajs/vue3';
import { PreconfigCamera, SiteStatus } from '@/pages/site/type';
import SiteSection from './partials/SiteSection.vue';
import { Installation } from './type';
import PreconfigSection from './partials/PreconfigSection.vue';
import { computed } from 'vue';
import InputProgress from './partials/InputProgress.vue';
import TimelineSection from './partials/TimelineSection.vue';

interface Props {
    statuses: SiteStatus[];
    installation: Installation;
}

const props = defineProps<Props>();

const cctv = computed<PreconfigCamera[]>(() => props.installation.cctv ?? []);

</script>

<template>
    <Head title="Update Installation Progress" />

    <AppLayout>
        <div class="grid grid-cols-5 gap-6">
            <div class="col-span-5 md:col-span-2 flex flex-col gap-6">
                <SiteSection :value="props.installation" />
                <PreconfigSection :cctv="cctv"  />
            </div>

            <div class="col-span-5 md:col-span-3">
                <!-- <InputProgress :statuses="props.statuses" /> -->
                 <TimelineSection :value="props.installation" :statuses="props.statuses" />
            </div>
        </div>
    </AppLayout>
</template>
