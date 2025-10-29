<script setup>
import { useLayout } from '@/composables/useLayouts';
import { computed, onBeforeUnmount, onMounted } from 'vue';

// import AppBreadCrumb from './AppBreadcrumb.vue'; 
import AppConfig from './AppConfig.vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

const { layoutConfig, layoutState, watchSidebarActive, unbindOutsideClickListener } = useLayout();

onMounted(() => {
    watchSidebarActive();
});

onBeforeUnmount(() => {
    unbindOutsideClickListener();
});

const containerClass = computed(() => {
    return [
        'layout-container',
        'layout-topbar-' + layoutConfig.topbarTheme,
        'layout-menu-' + layoutConfig.menuTheme,
        'layout-menu-profile-' + layoutConfig.menuProfilePosition,
        {
            'layout-overlay': layoutConfig.menuMode === 'overlay',
            'layout-static': layoutConfig.menuMode === 'static',
            'layout-slim': layoutConfig.menuMode === 'slim',
            'layout-slim-plus': layoutConfig.menuMode === 'slim-plus',
            'layout-horizontal': layoutConfig.menuMode === 'horizontal',
            'layout-reveal': layoutConfig.menuMode === 'reveal',
            'layout-drawer': layoutConfig.menuMode === 'drawer',
            'layout-sidebar-dark': layoutConfig.colorScheme === 'dark',
            'layout-static-inactive': layoutState.staticMenuDesktopInactive && layoutConfig.menuMode === 'static',
            'layout-overlay-active': layoutState.overlayMenuActive,
            'layout-mobile-active': layoutState.staticMenuMobileActive,
            'layout-menu-profile-active': layoutState.rightMenuActive,
            'layout-sidebar-active': layoutState.sidebarActive,
            'layout-sidebar-anchored': layoutState.anchored
        }
    ];
});
</script>

<template>
    <div :class="containerClass">
        <AppTopbar />
        <AppSidebar />

        <div class="layout-content-wrapper">
            <!-- <AppBreadCrumb class="content-breadcrumb"></AppBreadCrumb> -->
            <div class="layout-content">
                <slot></slot>
            </div>
        </div>

        <AppConfig />
        <Toast></Toast>
        <div class="layout-mask"></div>
    </div>
</template>
