<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import StreamTable from './StreamTable.vue';

</script>

<template>
    <Head title="Stream CCTV" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Config Streaming" description="Manage streaming CCTV" />

            <StreamTable />
        </div>
    </AppLayout>
</template>