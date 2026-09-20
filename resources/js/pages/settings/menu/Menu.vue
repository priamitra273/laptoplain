<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import type { Menu } from '@/types';
import { Head } from '@inertiajs/vue3';
import MenuForm from './MenuForm.vue';
import MenuTable from './MenuTable.vue';

interface AvailableRoute {
    uri: string;
    name: string;
}

interface Props {
    menu?: Menu[];
    parent_menu?: Menu[];
    available_routes?: AvailableRoute[];
}

const props = withDefaults(defineProps<Props>(), {
    menu: () => [],
    parent_menu: () => [],
    available_routes: () => [],
});

const overlay = useOverlay();

const menuForm = overlay.create(MenuForm);

const addMenu = () => {
    menuForm.open({ parentMenu: props.parent_menu, availableRoutes: props.available_routes });
};

const editMenu = (menu: Menu) => {
    menuForm.open({ value: menu, parentMenu: props.parent_menu, availableRoutes: props.available_routes });
};
</script>

<template>
    <Head title="Menu" />

    <AppLayout title="Menu">
        <Heading title="Menu" description="Manage sidebar navigation menu">
            <UButton v-if="can('menu.create')" size="sm" @click="addMenu">Add Menu</UButton>
        </Heading>

        <MenuTable :data="menu" @edit="editMenu" />
    </AppLayout>
</template>
