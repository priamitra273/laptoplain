<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import type { RoleList } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import RoleTable from './RoleTable.vue';

interface Props {
    roles?: RoleList[];
}

withDefaults(defineProps<Props>(), {
    roles: () => [],
});
</script>

<template>

    <Head title="Role" />

    <AppLayout title="Role">
        <Heading title="Role" description="Manage user's roles">
            <Link v-if="can('role.create')" :href="route('role.create')">
                <UButton size="sm">Add Role</UButton>
            </Link>
        </Heading>

        <RoleTable :data="roles" />
    </AppLayout>
</template>
