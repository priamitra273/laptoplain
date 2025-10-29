<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import { RoleList } from '@/types';
import RoleListTable from './partials/RoleListTable.vue';

interface Props {
    roles?: RoleList[]
}

const props = withDefaults(defineProps<Props>(), {
    roles: () => []
})

</script>

<template>
    <Head title="Menu" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Role" description="Manage user's roles" />

            <RoleListTable :roles="roles" /> 
        </div>
    </AppLayout>
</template>