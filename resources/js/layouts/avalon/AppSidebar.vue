<script setup>
import { useLayout } from '@/composables/useLayouts';
import AppMenu from './AppMenu.vue';
import AppMenuProfile from './AppMenuProfile.vue';

const { layoutState, layoutConfig, onSidebarToggle, onAnchorToggle } = useLayout();

let timeout = null;

function onMouseEnter() {
    if (!layoutState.anchored) {
        if (timeout) {
            clearTimeout(timeout);
            timeout = null;
        }
        onSidebarToggle(true);
    }
}

function onMouseLeave() {
    if (!layoutState.anchored) {
        if (!timeout) {
            timeout = setTimeout(() => onSidebarToggle(false), 300);
        }
    }
}
</script>

<template>
    <div class="layout-sidebar" @mouseenter="onMouseEnter" @mouseleave="onMouseLeave">
        <div class="layout-sidebar-top">
            <a href="/">
                <img src="/storage/logo.png" alt="Logo" class="layout-sidebar-logo" />
                <img src="/storage/t-logo.png" alt="Logo" class="layout-sidebar-logo-slim" />
            </a>
            <button class="layout-sidebar-anchor" type="button" @click="onAnchorToggle"></button>
        </div>
        <AppMenuProfile v-if="layoutConfig.menuProfilePosition === 'start'"></AppMenuProfile>
        <div class="layout-menu-container">
            <AppMenu />
        </div>
        <!-- <AppMenuProfile v-if="layoutConfig.menuProfilePosition === 'end'"></AppMenuProfile> -->
    </div>
</template>
