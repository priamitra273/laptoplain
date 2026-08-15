<script setup lang="ts">
import { computed, ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';
import { usePage } from '@inertiajs/vue3';
import { SharedData, SidebarMenuItem } from '@/types';

interface MenuItem {
  label: string;
  icon: string;
  to?: string; // 'to' is optional since not all items may have it (e.g., categories without links) and if provide it must be a route name in laravel
  items?: MenuItem[]; // sub-items are optional as well
}

// const menu = ref<MenuItem[]>([
//     {
//         label: 'Favorites',
//         icon: 'Star',
//         items: [
//             {
//                 label: 'Dashboard',
//                 icon: 'LayoutDashboard',
//                 to: 'dashboard'
//             }
//         ]
//     },
//     {
//         label: 'Master Data',
//         icon: 'FolderCog',
//         items: [
//             {
//                 label: 'Site',
//                 icon: 'MapPinned',
//                 to: 'site.index'
//             },
//             {
//                 label: 'Dinas',
//                 icon: 'BriefcaseBusiness',
//                 to: 'department.index'
//             },
//         ]
//     },
//     {
//         label: 'Settings',
//         icon: 'Settings',
//         items: [
//             {
//                 label: 'User',
//                 icon: 'UserRoundCog',
//                 to: 'user.index'
//             },
//             {
//                 label: 'Menu',
//                 icon: 'Compass',
//                 to: 'menu.index'
//             },
//             {
//                 label: 'Team',
//                 icon: 'Network',
//                 to: 'team.index'
//             },
//             {
//                 label: 'Role',
//                 icon: 'Shapes',
//                 to: 'role.index'
//             },
//             {
//                 label: 'Permission',
//                 icon: 'ShieldCheck',
//                 to: 'permission.index'
//             },
//         ]
//     },
// ]);

const page = usePage<SharedData>()

const menu = computed<SidebarMenuItem[]>(() => page.props.auth.menu)
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in menu" :key="item">
            <AppMenuItem :item="item" root :index="i" />

            <li class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
