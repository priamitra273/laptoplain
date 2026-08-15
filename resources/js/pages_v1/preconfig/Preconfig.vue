<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Regency } from './type';
import PreconfigTable from './PreconfigTable.vue';

interface Props {
    regencies: Regency[];
}

defineProps<Props>()

</script>

<template>
    <Head title="Preconfig" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Preconfig" description="Manage site preconfig network" />

            <PreconfigTable :regencies="regencies" />
        </div>
    </AppLayout>
</template>