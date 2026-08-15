<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { UserList } from '@/types';
import { Head } from '@inertiajs/vue3';
import UserTable from './partials/UserTable.vue';

interface Props {
    users: UserList[];
}

const props = withDefaults(defineProps<Props>(), {
    users: () => []
})

</script>

<template>
    <Head title="Menu" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="User" description="Manage all users data" />

            <UserTable :users="users" />
        </div>
    </AppLayout>
</template>