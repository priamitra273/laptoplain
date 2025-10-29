<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { Department } from '@/types';
import DepartmentTable from './DepartmentTable.vue';

interface Props {
    departments?: Department[]
}

const props = withDefaults(defineProps<Props>(), {
    departments: () => []
})

</script>

<template>
    <Head title="Dinas" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Department" description="Manage master data department" />

            <DepartmentTable :departments="departments" />
        </div>
    </AppLayout>
</template>