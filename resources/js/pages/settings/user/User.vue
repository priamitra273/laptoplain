<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import type { UserList } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import UserTable from './UserTable.vue';

interface Props {
    users?: UserList[];
}

withDefaults(defineProps<Props>(), {
    users: () => [],
});
</script>

<template>
    <Head title="User" />

    <AppLayout title="User">
        <Heading title="User" description="Manage all user data">
            <Link v-if="can('user.create')" :href="route('user.create')">
                <UButton size="sm">Add User</UButton>
            </Link>
        </Heading>

        <UserTable :data="users" />
    </AppLayout>
</template>
