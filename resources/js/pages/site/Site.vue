<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import SiteTable from './SiteTable.vue';
import { Regency } from './type';

interface Props {
    regencies: Regency[];
}

defineProps<Props>()

</script>

<template>
    <Head title="Site" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Site" description="Manage master data site" />

            <SiteTable :regencies="regencies" />
        </div>
    </AppLayout>
</template>