<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ComingSoon from '../project-lazy/ComingSoon.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="Project detail">
        <Head title="Project detail" />

        <ComingSoon label="Project detail" />
    </AppLayout>
</template>
