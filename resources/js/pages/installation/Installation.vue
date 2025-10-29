<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Regency } from './type';
import InstallationTable from './InstallationTable.vue';

interface Props {
    regencies: Regency[];
}

defineProps<Props>()

</script>

<template>
    <Head title="Installation" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Installation" description="Manage site installation progress" />

            <InstallationTable :regencies="regencies" />
        </div>
    </AppLayout>
</template>
