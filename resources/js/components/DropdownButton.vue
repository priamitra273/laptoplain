<script setup lang="ts">
import { MenuItem } from 'primevue/menuitem';
import Menu, { MenuMethods } from 'primevue/menu';
import { ref } from 'vue';

interface Props {
    items: MenuItem[];
    id?: string;
    severity?: string;
    icon?: string;
    menuKey?: string | number;
    data?: Object
}

interface DropdownButtonItem extends MenuItem {
    data?: Object | undefined;
    menuKey?: string | number | undefined;
}

const props = withDefaults(defineProps<Props>(), {
    id: 'overlay_menu',
    severity: 'secondary',
    icon: 'pi pi-ellipsis-h',
})

const menu = ref<MenuMethods | null>(null);
const menuItems = ref<DropdownButtonItem[]>([]);

for (const item of props.items) {
    menuItems.value.push({
        ...item,
        data: props.data,
        menuKey: props.menuKey
    });
}

const toggle = (event: Event) => {
    menu.value?.toggle(event);
};
</script>

<template>
    <Button type="button" :icon="icon" @click="toggle" :severity="severity" />
    <Menu ref="menu" :id="id" :model="menuItems" :popup="true" />
</template>