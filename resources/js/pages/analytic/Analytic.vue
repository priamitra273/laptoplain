<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import AnalyticTable from './AnalyticTable.vue';

</script>

<template>
    <Head title="Config CCTV Analytic" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Config CCTV Analytic" description="Manage configuration CCTV analytics" />

            <AnalyticTable />
        </div>
    </AppLayout>
</template>